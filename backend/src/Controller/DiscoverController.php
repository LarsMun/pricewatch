<?php

namespace App\Controller;

use App\Service\DiscoverService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/discover')]
class DiscoverController extends AbstractController
{
    public function __construct(
        private readonly DiscoverService $discoverService,
    ) {
    }

    #[Route('/collections', name: 'api_discover_collections', methods: ['GET'])]
    public function collections(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(48, max(1, (int) $request->query->get('limit', 12)));
        $sort = $request->query->get('sort', 'recent');

        if (!in_array($sort, ['recent', 'popular'])) {
            $sort = 'recent';
        }

        $result = $this->discoverService->getDiscoverCollections($sort, $page, $limit);

        $response = $this->json($result);
        $response->headers->set('Cache-Control', 'public, max-age=60');
        return $response;
    }

    #[Route('/users', name: 'api_discover_users', methods: ['GET'])]
    public function users(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(48, max(1, (int) $request->query->get('limit', 12)));
        $sort = $request->query->get('sort', 'recent');

        if (!in_array($sort, ['recent', 'popular'])) {
            $sort = 'recent';
        }

        $result = $this->discoverService->getDiscoverUsers($sort, $page, $limit);

        $response = $this->json($result);
        $response->headers->set('Cache-Control', 'public, max-age=60');
        return $response;
    }
}
