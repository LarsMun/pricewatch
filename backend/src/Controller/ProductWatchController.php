<?php

namespace App\Controller;

use App\Entity\ProductWatch;
use App\Entity\User;
use App\Message\CheckPriceMessage;
use App\Repository\ProductWatchRepository;
use App\Repository\PriceCheckRepository;
use App\Service\CategoryService;
use App\Service\PriceCheckService;
use App\Service\UrlAnalyzerService;
use App\Service\UrlValidator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/watches')]
class ProductWatchController extends AbstractController
{
    public function __construct(
        private ProductWatchRepository $watchRepository,
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator,
        private PriceCheckService $priceCheckService,
        private UrlAnalyzerService $urlAnalyzer,
        private UrlValidator $urlValidator,
        private CategoryService $categoryService,
        private RateLimiterFactory $checkAllUserLimiter,
        private MessageBusInterface $messageBus,
    ) {}

    #[Route('', name: 'api_watches_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $watches = $this->watchRepository->findByUser($user);

        return $this->json([
            'watches' => array_map(fn($w) => $this->serializeWatch($w), $watches),
            'total' => count($watches),
        ]);
    }

    #[Route('/analyze', name: 'api_watches_analyze', methods: ['POST'])]
    public function analyze(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $url = $data['url'] ?? null;

        if (!$url) {
            return $this->json(['error' => 'URL is verplicht'], Response::HTTP_BAD_REQUEST);
        }

        // Validate URL format
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return $this->json(['error' => 'Ongeldige URL'], Response::HTTP_BAD_REQUEST);
        }

        // SSRF protection
        try {
            $this->urlValidator->validate($url);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        $result = $this->urlAnalyzer->analyze($url);

        if (!$result->success) {
            return $this->json([
                'success' => false,
                'error' => $result->error,
            ], Response::HTTP_BAD_REQUEST);
        }

        return $this->json([
            'success' => true,
            'url' => $result->url,
            'domain' => $result->domain,
            'productName' => $result->productName,
            'price' => $result->price,
            'currency' => $result->currency,
            'imageUrl' => $result->imageUrl,
            'priceSelector' => $result->priceSelector,
            'detectionMethod' => $result->detectionMethod,
            'availableSelectors' => $result->availableSelectors,
            'jsonLdCategory' => $result->jsonLdCategory,
        ]);
    }

    #[Route('/check-all', name: 'api_watches_check_all', methods: ['POST'])]
    public function checkAll(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        // Rate limit per user (1x per 15 minuten)
        $limiter = $this->checkAllUserLimiter->create($user->getUserIdentifier());
        if (!$limiter->consume()->isAccepted()) {
            return $this->json([
                'error' => 'Je kunt alle watches maximaal eens per 15 minuten checken.'
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        $watches = $this->watchRepository->findBy(['user' => $user, 'isActive' => true]);

        // Queue all checks asynchronously
        foreach ($watches as $watch) {
            $this->messageBus->dispatch(new CheckPriceMessage($watch->getId()));
        }

        return $this->json([
            'message' => 'Prijschecks worden verwerkt',
            'queued' => count($watches),
        ]);
    }

    #[Route('', name: 'api_watches_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        $url = $data['url'] ?? null;
        $priceSelector = $data['priceSelector'] ?? null;

        if (!$url || !$priceSelector) {
            return $this->json(['error' => 'url en priceSelector zijn verplicht'], Response::HTTP_BAD_REQUEST);
        }

        // SSRF protection
        try {
            $this->urlValidator->validate($url);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        /** @var User $user */
        $user = $this->getUser();

        if (!$user->isVerified()) {
            return $this->json([
                'error' => 'Verifieer eerst je e-mailadres voordat je watches kunt aanmaken'
            ], Response::HTTP_FORBIDDEN);
        }

        $watch = new ProductWatch();
        $watch->setUser($user);
        $watch->setUrl($url);
        $watch->setPriceSelector($priceSelector);

        if (isset($data['productName'])) {
            $watch->setProductName($data['productName']);
        }
        if (isset($data['productSelector'])) {
            $watch->setProductSelector($data['productSelector']);
        }
        if (isset($data['currency'])) {
            $watch->setCurrency($data['currency']);
        }
        if (isset($data['imageUrl'])) {
            $watch->setImageUrl($data['imageUrl']);
        }

        // Auto-categorize the product
        $jsonLdCategory = $data['jsonLdCategory'] ?? null;
        $category = $this->categoryService->determineCategory(
            $url,
            $watch->getDomain(),
            $watch->getProductName(),
            $jsonLdCategory
        );
        if ($category !== null) {
            $watch->setCategory($category);
        }

        $errors = $this->validator->validate($watch);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->persist($watch);
        $this->entityManager->flush();

        // Queue initial price check asynchronously (non-blocking)
        $this->messageBus->dispatch(new CheckPriceMessage($watch->getId()));

        return $this->json([
            'message' => 'Watch aangemaakt, eerste prijscheck wordt verwerkt',
            'watch' => $this->serializeWatch($watch),
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_watches_show', methods: ['GET'])]
    public function show(int $id, PriceCheckRepository $priceCheckRepository): JsonResponse
    {
        $watch = $this->findUserWatch($id);
        if (!$watch) {
            return $this->json(['error' => 'Watch niet gevonden'], Response::HTTP_NOT_FOUND);
        }

        // Get recent price history
        $priceHistory = $priceCheckRepository->findBy(
            ['productWatch' => $watch],
            ['checkedAt' => 'DESC'],
            50
        );

        return $this->json([
            'watch' => array_merge($this->serializeWatch($watch), [
                'lastSeenRawText' => $watch->getLastSeenRawText(),
            ]),
            'priceHistory' => array_map(fn($pc) => [
                'id' => $pc->getId(),
                'price' => $pc->getPrice(),
                'rawText' => $pc->getRawText(),
                'wasSuccessful' => $pc->wasSuccessful(),
                'httpStatus' => $pc->getHttpStatus(),
                'durationMs' => $pc->getDurationMs(),
                'errorMessage' => $pc->getErrorMessage(),
                'checkedAt' => $pc->getCheckedAt()->format('c'),
            ], $priceHistory),
        ]);
    }

    #[Route('/{id}', name: 'api_watches_update', methods: ['PATCH'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $watch = $this->findUserWatch($id);
        if (!$watch) {
            return $this->json(['error' => 'Watch niet gevonden'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return $this->json(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        if (isset($data['productName'])) {
            $watch->setProductName($data['productName']);
        }
        if (isset($data['priceSelector'])) {
            $watch->setPriceSelector($data['priceSelector']);
        }
        if (isset($data['productSelector'])) {
            $watch->setProductSelector($data['productSelector']);
        }
        if (isset($data['isActive'])) {
            $watch->setIsActive((bool) $data['isActive']);
            if ($data['isActive']) {
                $watch->scheduleNextCheck();
            }
        }

        $errors = $this->validator->validate($watch);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->flush();

        return $this->json([
            'message' => 'Watch bijgewerkt',
            'watch' => $this->serializeWatch($watch),
        ]);
    }

    #[Route('/{id}', name: 'api_watches_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $watch = $this->findUserWatch($id);
        if (!$watch) {
            return $this->json(['error' => 'Watch niet gevonden'], Response::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($watch);
        $this->entityManager->flush();

        return $this->json(['message' => 'Watch verwijderd']);
    }

    private function findUserWatch(int $id): ?ProductWatch
    {
        /** @var User $user */
        $user = $this->getUser();
        $watch = $this->watchRepository->find($id);

        if (!$watch || $watch->getUser() !== $user) {
            return null;
        }

        return $watch;
    }

    private function serializeWatch(ProductWatch $watch): array
    {
        return [
            'id' => $watch->getId(),
            'url' => $watch->getUrl(),
            'domain' => $watch->getDomain(),
            'productName' => $watch->getProductName(),
            'priceSelector' => $watch->getPriceSelector(),
            'currency' => $watch->getCurrency(),
            'currentPrice' => $watch->getCurrentPrice(),
            'previousPrice' => $watch->getPreviousPrice(),
            'originalPrice' => $watch->getOriginalPrice(),
            'checkMethod' => $watch->getCheckMethod()->value,
            'consecutiveFailures' => $watch->getConsecutiveFailures(),
            'lastErrorMessage' => $watch->getLastErrorMessage(),
            'isActive' => $watch->isActive(),
            'nextCheckAt' => $watch->getNextCheckAt()?->format('c'),
            'lastCheckedAt' => $watch->getLastCheckedAt()?->format('c'),
            'lastSuccessfulCheckAt' => $watch->getLastSuccessfulCheckAt()?->format('c'),
            'imageUrl' => $watch->getImageUrl(),
            'createdAt' => $watch->getCreatedAt()->format('c'),
            'collectionIds' => $watch->getCollectionIds(),
        ];
    }
}
