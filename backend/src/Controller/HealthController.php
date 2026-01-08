<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HealthController extends AbstractController
{
    #[Route('/api/health', name: 'api_health', methods: ['GET'])]
    public function health(EntityManagerInterface $em): JsonResponse
    {
        try {
            // Check database connection
            $em->getConnection()->executeQuery('SELECT 1');
            $dbStatus = 'ok';
        } catch (\Exception $e) {
            $dbStatus = 'error: ' . $e->getMessage();
        }

        $status = $dbStatus === 'ok' ? Response::HTTP_OK : Response::HTTP_SERVICE_UNAVAILABLE;

        return $this->json([
            'status' => $dbStatus === 'ok' ? 'healthy' : 'unhealthy',
            'database' => $dbStatus,
            'timestamp' => (new \DateTimeImmutable())->format('c'),
        ], $status);
    }

    #[Route('/robots.txt', name: 'robots_txt', methods: ['GET'])]
    public function robotsTxt(): Response
    {
        // Disallow all crawlers from the API
        return new Response(
            "User-agent: *\nDisallow: /",
            Response::HTTP_OK,
            ['Content-Type' => 'text/plain']
        );
    }
}
