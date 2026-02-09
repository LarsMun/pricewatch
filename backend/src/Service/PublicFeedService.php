<?php

namespace App\Service;

use App\Entity\ProductWatch;
use App\Entity\User;
use App\Repository\CategoryRepository;
use App\Repository\CollectionRepository;
use App\Repository\ProductWatchRepository;
use App\Repository\UserRepository;
use App\Repository\NotificationRepository;

class PublicFeedService
{
    public function __construct(
        private readonly ProductWatchRepository $productWatchRepository,
        private readonly UserRepository $userRepository,
        private readonly NotificationRepository $notificationRepository,
        private readonly CategoryRepository $categoryRepository,
        private readonly CollectionRepository $collectionRepository,
    ) {
    }

    /**
     * Available sort options for the feed.
     */
    public const SORT_OPTIONS = [
        'popular' => 'Populairste',
        'price_drop' => 'Grootste prijsdaling',
        'newest' => 'Nieuwste',
        'price_low' => 'Prijs laag-hoog',
        'price_high' => 'Prijs hoog-laag',
    ];

    /**
     * Get the public feed of products.
     *
     * @return array{products: array, totalCount: int, page: int, totalPages: int}
     */
    public function getFeed(
        int $page = 1,
        int $limit = 24,
        ?string $domain = null,
        ?string $categorySlug = null,
        string $sort = 'popular'
    ): array {
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

        // Category filter - includes children categories
        if ($categorySlug !== null) {
            $category = $this->categoryRepository->findBySlug($categorySlug);
            if ($category !== null) {
                $categoryIds = $category->getDescendantIds();
                $qb->andWhere('pw.category IN (:categoryIds)')
                    ->setParameter('categoryIds', $categoryIds);
            }
        }

        // Count total
        $countQb = clone $qb;
        $totalCount = (int) $countQb->select('COUNT(pw.id)')->getQuery()->getSingleScalarResult();

        // Apply sorting
        // Note: Using `+ 0` to force MySQL to convert string prices to numbers
        switch ($sort) {
            case 'price_drop':
                // Sort by percentage price drop (biggest drop first)
                // Only include products that have a previous price and current < previous
                $qb->andWhere('pw.previousPrice IS NOT NULL')
                    ->andWhere('(pw.currentPrice + 0) < (pw.previousPrice + 0)')
                    ->orderBy('((pw.previousPrice + 0) - (pw.currentPrice + 0)) / (pw.previousPrice + 0)', 'DESC');
                break;

            case 'newest':
                $qb->orderBy('pw.createdAt', 'DESC');
                break;

            case 'price_low':
                $qb->orderBy('pw.currentPrice + 0', 'ASC');
                break;

            case 'price_high':
                $qb->orderBy('pw.currentPrice + 0', 'DESC');
                break;

            case 'popular':
            default:
                $qb->orderBy('pw.subscriberCount', 'DESC')
                    ->addOrderBy('pw.createdAt', 'DESC');
                break;
        }

        $qb->setFirstResult(($page - 1) * $limit)
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

        // Get user's public collections
        $collections = $this->collectionRepository->createQueryBuilder('c')
            ->where('c.user = :user')
            ->andWhere('c.isPublic = true')
            ->setParameter('user', $user)
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();

        return [
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'memberSince' => $user->getCreatedAt()->format('c'),
            'productCount' => count($watches),
            'followerCount' => $user->getFollowerCount(),
            'followingCount' => $user->getFollowingCount(),
            'products' => array_map(fn(ProductWatch $w) => $this->formatProduct($w), $watches),
            'collections' => array_map(fn($c) => [
                'name' => $c->getName(),
                'slug' => $c->getSlug(),
                'description' => $c->getDescription(),
                'productCount' => $c->getProductWatches()->filter(fn($pw) => $pw->isPublic() && $pw->isActive())->count(),
            ], $collections),
        ];
    }

    /**
     * Get a user's public collection by slug.
     */
    public function getUserCollection(string $username, string $collectionSlug): ?array
    {
        $user = $this->userRepository->findOneBy(['username' => $username]);

        if ($user === null || !$user->isPublic()) {
            return null;
        }

        // Find collection by matching slug
        $collections = $this->collectionRepository->findBy(['user' => $user, 'isPublic' => true]);
        $collection = null;
        foreach ($collections as $c) {
            if ($c->getSlug() === $collectionSlug) {
                $collection = $c;
                break;
            }
        }

        if ($collection === null) {
            return null;
        }

        // Get public watches in this collection
        $watches = $collection->getProductWatches()->filter(
            fn(ProductWatch $pw) => $pw->isPublic() && $pw->isActive()
        )->toArray();

        return [
            'username' => $user->getUsername(),
            'collection' => [
                'name' => $collection->getName(),
                'slug' => $collection->getSlug(),
                'description' => $collection->getDescription(),
            ],
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

        // Include category info
        $category = $watch->getCategory();
        if ($category !== null) {
            $data['category'] = [
                'id' => $category->getId(),
                'name' => $category->getName(),
                'slug' => $category->getSlug(),
                'icon' => $category->getIcon(),
            ];
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
