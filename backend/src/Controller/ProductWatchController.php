<?php

namespace App\Controller;

use App\Entity\ProductWatch;
use App\Entity\User;
use App\Repository\ProductWatchRepository;
use App\Repository\PriceCheckRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/watches')]
class ProductWatchController extends AbstractController
{
    public function __construct(
        private ProductWatchRepository $watchRepository,
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator,
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

        /** @var User $user */
        $user = $this->getUser();

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

        return $this->json([
            'message' => 'Watch aangemaakt',
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
            'watch' => $this->serializeWatch($watch),
            'priceHistory' => array_map(fn($pc) => [
                'id' => $pc->getId(),
                'price' => $pc->getPrice(),
                'rawText' => $pc->getRawText(),
                'wasSuccessful' => $pc->wasSuccessful(),
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
            'isActive' => $watch->isActive(),
            'nextCheckAt' => $watch->getNextCheckAt()?->format('c'),
            'lastCheckedAt' => $watch->getLastCheckedAt()?->format('c'),
            'lastSuccessfulCheckAt' => $watch->getLastSuccessfulCheckAt()?->format('c'),
            'createdAt' => $watch->getCreatedAt()->format('c'),
        ];
    }
}
