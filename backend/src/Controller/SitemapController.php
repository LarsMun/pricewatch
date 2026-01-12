<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\ProductWatchRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SitemapController extends AbstractController
{
    public function __construct(
        private readonly ProductWatchRepository $productWatchRepository,
        private readonly CategoryRepository $categoryRepository,
    ) {
    }

    #[Route('/sitemap.xml', name: 'sitemap', methods: ['GET'])]
    public function sitemap(): Response
    {
        $baseUrl = 'https://shopq.nl';

        $urls = [];

        // Static pages
        $urls[] = [
            'loc' => $baseUrl,
            'changefreq' => 'daily',
            'priority' => '1.0',
        ];

        // Categories
        $categories = $this->categoryRepository->findAll();
        foreach ($categories as $category) {
            $urls[] = [
                'loc' => $baseUrl . '/?category=' . $category->getSlug(),
                'changefreq' => 'daily',
                'priority' => '0.8',
            ];
        }

        // Public products (limit to most recent 1000)
        $products = $this->productWatchRepository->createQueryBuilder('pw')
            ->join('pw.user', 'u')
            ->where('pw.isPublic = true')
            ->andWhere('pw.isActive = true')
            ->andWhere('u.isPublic = true')
            ->orderBy('pw.createdAt', 'DESC')
            ->setMaxResults(1000)
            ->getQuery()
            ->getResult();

        foreach ($products as $product) {
            $urls[] = [
                'loc' => $baseUrl . '/product/' . $product->getId(),
                'lastmod' => $product->getCreatedAt()->format('Y-m-d'),
                'changefreq' => 'daily',
                'priority' => '0.6',
            ];
        }

        // Generate XML
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($urls as $url) {
            $xml .= '  <url>' . "\n";
            $xml .= '    <loc>' . htmlspecialchars($url['loc']) . '</loc>' . "\n";
            if (isset($url['lastmod'])) {
                $xml .= '    <lastmod>' . $url['lastmod'] . '</lastmod>' . "\n";
            }
            if (isset($url['changefreq'])) {
                $xml .= '    <changefreq>' . $url['changefreq'] . '</changefreq>' . "\n";
            }
            if (isset($url['priority'])) {
                $xml .= '    <priority>' . $url['priority'] . '</priority>' . "\n";
            }
            $xml .= '  </url>' . "\n";
        }

        $xml .= '</urlset>';

        return new Response($xml, 200, [
            'Content-Type' => 'application/xml',
        ]);
    }

}
