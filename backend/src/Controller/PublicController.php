<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Service\DiscoverService;
use App\Service\EmailSubscriberService;
use App\Service\PublicFeedService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/public')]
class PublicController extends AbstractController
{
    public function __construct(
        private readonly PublicFeedService $feedService,
        private readonly EmailSubscriberService $subscriberService,
        private readonly CategoryRepository $categoryRepository,
        private readonly DiscoverService $discoverService,
        private readonly RateLimiterFactory $subscribeEndpointLimiter,
    ) {
    }

    #[Route('/feed', name: 'api_public_feed', methods: ['GET'])]
    public function feed(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(48, max(1, (int) $request->query->get('limit', 24)));
        $domain = $request->query->get('domain');
        $categorySlug = $request->query->get('category');
        $sort = $request->query->get('sort', 'popular');

        // Validate sort option
        if (!array_key_exists($sort, PublicFeedService::SORT_OPTIONS)) {
            $sort = 'popular';
        }

        $result = $this->feedService->getFeed($page, $limit, $domain, $categorySlug, $sort);

        $response = $this->json($result);
        $response->headers->set('Cache-Control', 'public, max-age=30');
        return $response;
    }

    #[Route('/categories', name: 'api_public_categories', methods: ['GET'])]
    public function categories(): JsonResponse
    {
        $categories = $this->categoryRepository->getCategoryTreeWithCounts();

        $response = $this->json(['categories' => $categories]);
        $response->headers->set('Cache-Control', 'public, max-age=300');
        return $response;
    }

    #[Route('/homepage', name: 'api_public_homepage', methods: ['GET'])]
    public function homepage(): JsonResponse
    {
        $data = $this->discoverService->getHomepageData();

        $response = $this->json($data);
        $response->headers->set('Cache-Control', 'public, max-age=60');
        return $response;
    }

    #[Route('/feed/recent-changes', name: 'api_public_recent_changes', methods: ['GET'])]
    public function recentChanges(Request $request): JsonResponse
    {
        $limit = min(24, max(1, (int) $request->query->get('limit', 12)));

        $result = $this->feedService->getRecentPriceChanges($limit);

        return $this->json($result);
    }

    #[Route('/feed/domains', name: 'api_public_domains', methods: ['GET'])]
    public function domains(): JsonResponse
    {
        $domains = $this->feedService->getPopularDomains();

        $response = $this->json(['domains' => $domains]);
        $response->headers->set('Cache-Control', 'public, max-age=300');
        return $response;
    }

    #[Route('/products/{id}', name: 'api_public_product', methods: ['GET'])]
    public function product(int $id): JsonResponse
    {
        $product = $this->feedService->getProduct($id);

        if ($product === null) {
            return $this->json(['error' => 'Product niet gevonden'], Response::HTTP_NOT_FOUND);
        }

        $response = $this->json(['product' => $product]);
        $response->headers->set('Cache-Control', 'public, max-age=60');
        return $response;
    }

    #[Route('/users/{username}', name: 'api_public_user', methods: ['GET'])]
    public function userProfile(string $username): JsonResponse
    {
        $profile = $this->feedService->getUserProfile($username);

        if ($profile === null) {
            return $this->json(['error' => 'Gebruiker niet gevonden'], Response::HTTP_NOT_FOUND);
        }

        $response = $this->json(['user' => $profile]);
        $response->headers->set('Cache-Control', 'public, max-age=60');
        return $response;
    }

    #[Route('/users/{username}/collections/{slug}', name: 'api_public_user_collection', methods: ['GET'])]
    public function userCollection(string $username, string $slug): JsonResponse
    {
        $collection = $this->feedService->getUserCollection($username, $slug);

        if ($collection === null) {
            return $this->json(['error' => 'Collectie niet gevonden'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($collection);
    }

    #[Route('/subscribe', name: 'api_public_subscribe', methods: ['POST'])]
    public function subscribe(Request $request, ValidatorInterface $validator): JsonResponse
    {
        // Rate limiting
        $limiter = $this->subscribeEndpointLimiter->create($request->getClientIp());
        if (!$limiter->consume()->isAccepted()) {
            return $this->json([
                'error' => 'Te veel verzoeken. Probeer het later opnieuw.',
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['error' => 'Ongeldige JSON'], Response::HTTP_BAD_REQUEST);
        }

        $email = $data['email'] ?? null;
        $productId = $data['productId'] ?? null;

        // Validate email
        $emailConstraint = new Assert\Email();
        $errors = $validator->validate($email, $emailConstraint);
        if (count($errors) > 0 || !$email) {
            return $this->json(['error' => 'Ongeldig e-mailadres'], Response::HTTP_BAD_REQUEST);
        }

        if (!$productId || !is_numeric($productId)) {
            return $this->json(['error' => 'Product ID is verplicht'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $this->subscriberService->subscribe($email, (int) $productId);

            return $this->json([
                'message' => 'Controleer je inbox om je prijsalert te bevestigen.',
            ], Response::HTTP_CREATED);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/verify-subscription', name: 'api_public_verify_subscription', methods: ['POST'])]
    public function verifySubscription(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $token = $data['token'] ?? null;

        if (!$token) {
            return $this->json(['error' => 'Token is verplicht'], Response::HTTP_BAD_REQUEST);
        }

        $subscriber = $this->subscriberService->verify($token);

        if ($subscriber === null) {
            return $this->json(['error' => 'Ongeldige of verlopen token'], Response::HTTP_BAD_REQUEST);
        }

        return $this->json([
            'message' => 'Je prijsalert is bevestigd!',
            'productName' => $subscriber->getProductWatch()->getProductName(),
        ]);
    }

    #[Route('/unsubscribe', name: 'api_public_unsubscribe', methods: ['POST'])]
    public function unsubscribe(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $token = $data['token'] ?? null;

        if (!$token) {
            return $this->json(['error' => 'Token is verplicht'], Response::HTTP_BAD_REQUEST);
        }

        $success = $this->subscriberService->unsubscribe($token);

        if (!$success) {
            return $this->json(['error' => 'Ongeldige token'], Response::HTTP_BAD_REQUEST);
        }

        return $this->json(['message' => 'Je bent uitgeschreven van de prijsalert.']);
    }
}
