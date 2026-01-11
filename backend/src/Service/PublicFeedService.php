<?php

namespace App\Service;

use App\Entity\ProductWatch;
use App\Entity\User;
use App\Repository\ProductWatchRepository;
use App\Repository\UserRepository;
use App\Repository\NotificationRepository;

class PublicFeedService
{
    public function __construct(
        private readonly ProductWatchRepository $productWatchRepository,
        private readonly UserRepository $userRepository,
        private readonly NotificationRepository $notificationRepository,
    ) {
    }

    /**
     * Get the public feed of products.
     *
     * Scoring algorithm combines:
     * - Recency: newer products score higher
     * - Popularity: more subscribers = higher score
     * - Price activity: recent price changes boost score
     *
     * @return array{products: array, totalCount: int, page: int, totalPages: int}
     */
    public function getFeed(int $page = 1, int $limit = 24, ?string $domain = null): array
    {
        $qb = $this->productWatchRepository->createQueryBuilder('pw')
            ->join('pw.user', 'u')
            ->where('pw.isPublic = true')
            ->andWhere('pw.isActive = true')
            ->andWhere('u.isPublic = true')
            ->andWhere('pw.currentPrice IS NOT NULL');

        if ($domain !== null) {
            $qb->andWhere('pw.domain = :domain')
                ->setParameter('domain', $domain);
        }

        // Count total
        $countQb = clone $qb;
        $totalCount = (int) $countQb->select('COUNT(pw.id)')->getQuery()->getSingleScalarResult();

        // Calculate score and order by it
        // Score = recency_score + popularity_score + activity_score
        // We use a simplified version: ORDER BY recent activity, then popularity, then creation
        $qb->orderBy('pw.subscriberCount', 'DESC')
            ->addOrderBy('pw.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        $watches = $qb->getQuery()->getResult();

        return [
            'products' => array_map(fn(ProductWatch $w) => $this->formatProduct($w), $watches),
            'totalCount' => $totalCount,
            'page' => $page,
            'totalPages' => (int) ceil($totalCount / $limit),
        ];
    }

    /**
     * Get products with recent price changes.
     *
     * @return array{products: array, totalCount: int}
     */
    public function getRecentPriceChanges(int $limit = 12): array
    {
        $since = new \DateTimeImmutable('-7 days');

        $notifications = $this->notificationRepository->createQueryBuilder('n')
            ->join('n.productWatch', 'pw')
            ->join('pw.user', 'u')
            ->where('pw.isPublic = true')
            ->andWhere('pw.isActive = true')
            ->andWhere('u.isPublic = true')
            ->andWhere('n.sentAt >= :since')
            ->andWhere('n.type IN (:types)')
            ->setParameter('since', $since)
            ->setParameter('types', ['price_decrease', 'price_increase'])
            ->orderBy('n.sentAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        $products = [];
        $seenIds = [];
        foreach ($notifications as $notification) {
            $watch = $notification->getProductWatch();
            if (!in_array($watch->getId(), $seenIds)) {
                $seenIds[] = $watch->getId();
                $products[] = $this->formatProduct($watch, $notification);
            }
        }

        return [
            'products' => $products,
            'totalCount' => count($products),
        ];
    }

    /**
     * Get a single public product by ID.
     */
    public function getProduct(int $id): ?array
    {
        $watch = $this->productWatchRepository->find($id);

        if ($watch === null) {
            return null;
        }

        if (!$watch->isPublic() || !$watch->isActive()) {
            return null;
        }

        if (!$watch->getUser()->isPublic()) {
            return null;
        }

        return $this->formatProductDetail($watch);
    }

    /**
     * Get a user's public profile.
     */
    public function getUserProfile(string $username): ?array
    {
        $user = $this->userRepository->findOneBy(['username' => $username]);

        if ($user === null || !$user->isPublic()) {
            return null;
        }

        $watches = $this->productWatchRepository->createQueryBuilder('pw')
            ->where('pw.user = :user')
            ->andWhere('pw.isPublic = true')
            ->andWhere('pw.isActive = true')
            ->setParameter('user', $user)
            ->orderBy('pw.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return [
            'username' => $user->getUsername(),
            'memberSince' => $user->getCreatedAt()->format('c'),
            'productCount' => count($watches),
            'products' => array_map(fn(ProductWatch $w) => $this->formatProduct($w), $watches),
        ];
    }

    /**
     * Get list of unique domains from public watches.
     *
     * @return array<string, int>
     */
    public function getPopularDomains(int $limit = 20): array
    {
        $result = $this->productWatchRepository->createQueryBuilder('pw')
            ->select('pw.domain, COUNT(pw.id) as cnt')
            ->join('pw.user', 'u')
            ->where('pw.isPublic = true')
            ->andWhere('pw.isActive = true')
            ->andWhere('u.isPublic = true')
            ->groupBy('pw.domain')
            ->orderBy('cnt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        $domains = [];
        foreach ($result as $row) {
            $domains[$row['domain']] = (int) $row['cnt'];
        }

        return $domains;
    }

    private function formatProduct(ProductWatch $watch, $notification = null): array
    {
        $data = [
            'id' => $watch->getId(),
            'productName' => $watch->getProductName() ?: $watch->getDomain(),
            'url' => $watch->getUrl(),
            'domain' => $watch->getDomain(),
            'imageUrl' => $watch->getImageUrl(),
            'currentPrice' => $watch->getCurrentPrice(),
            'previousPrice' => $watch->getPreviousPrice(),
            'originalPrice' => $watch->getOriginalPrice(),
            'currency' => $watch->getCurrency(),
            'subscriberCount' => $watch->getSubscriberCount(),
            'createdAt' => $watch->getCreatedAt()->format('c'),
        ];

        if ($watch->getUser()->getUsername()) {
            $data['username'] = $watch->getUser()->getUsername();
        }

        if ($notification !== null) {
            $data['lastPriceChange'] = [
                'type' => $notification->getType()->value,
                'oldPrice' => $notification->getOldPrice(),
                'newPrice' => $notification->getNewPrice(),
                'changedAt' => $notification->getSentAt()->format('c'),
            ];
        }

        return $data;
    }

    private function formatProductDetail(ProductWatch $watch): array
    {
        $data = $this->formatProduct($watch);

        // Add price history (last 30 price checks)
        $priceHistory = [];
        $priceChecks = $watch->getPriceChecks()->slice(0, 30);
        foreach ($priceChecks as $check) {
            if ($check->wasSuccessful() && $check->getPrice() !== null) {
                $priceHistory[] = [
                    'price' => $check->getPrice(),
                    'checkedAt' => $check->getCheckedAt()->format('c'),
                ];
            }
        }
        $data['priceHistory'] = $priceHistory;

        // Add watcher count (owner counts as 1)
        $data['watcherCount'] = 1 + $watch->getSubscriberCount();

        return $data;
    }
}
