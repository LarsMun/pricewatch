<?php

namespace App\Service;

use App\Entity\Collection;
use App\Entity\ProductWatch;
use App\Entity\User;
use App\Repository\CollectionRepository;
use App\Repository\ProductWatchRepository;
use App\Repository\UserRepository;

class DiscoverService
{
    public function __construct(
        private readonly CollectionRepository $collectionRepository,
        private readonly ProductWatchRepository $productWatchRepository,
        private readonly UserRepository $userRepository,
    ) {
    }

    /**
     * Get public collections for the discover page.
     *
     * @return array{collections: array, totalCount: int, page: int, totalPages: int}
     */
    public function getDiscoverCollections(string $sort = 'recent', int $page = 1, int $limit = 12): array
    {
        $qb = $this->collectionRepository->createQueryBuilder('c')
            ->join('c.user', 'u')
            ->where('c.isPublic = true')
            ->andWhere('u.isPublic = true')
            ->andWhere('u.username IS NOT NULL');

        // Count total
        $countQb = clone $qb;
        $totalCount = (int) $countQb->select('COUNT(c.id)')->getQuery()->getSingleScalarResult();

        // Apply sorting
        switch ($sort) {
            case 'popular':
                // Sort by product count
                $qb->leftJoin('c.productWatches', 'pw')
                    ->groupBy('c.id')
                    ->orderBy('COUNT(pw.id)', 'DESC')
                    ->addOrderBy('c.createdAt', 'DESC');
                break;
            case 'recent':
            default:
                $qb->orderBy('c.createdAt', 'DESC');
                break;
        }

        $qb->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        $collections = $qb->getQuery()->getResult();

        return [
            'collections' => array_map(fn(Collection $c) => $this->formatCollection($c), $collections),
            'totalCount' => $totalCount,
            'page' => $page,
            'totalPages' => (int) ceil($totalCount / $limit),
        ];
    }

    /**
     * Get public users for the discover page.
     *
     * @return array{users: array, totalCount: int, page: int, totalPages: int}
     */
    public function getDiscoverUsers(string $sort = 'recent', int $page = 1, int $limit = 12): array
    {
        $qb = $this->userRepository->createQueryBuilder('u')
            ->where('u.isPublic = true')
            ->andWhere('u.username IS NOT NULL');

        // Count total
        $countQb = clone $qb;
        $totalCount = (int) $countQb->select('COUNT(u.id)')->getQuery()->getSingleScalarResult();

        // Apply sorting
        switch ($sort) {
            case 'popular':
                $qb->orderBy('u.followerCount', 'DESC')
                    ->addOrderBy('u.createdAt', 'DESC');
                break;
            case 'recent':
            default:
                $qb->orderBy('u.createdAt', 'DESC');
                break;
        }

        $qb->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        $users = $qb->getQuery()->getResult();

        return [
            'users' => array_map(fn(User $u) => $this->formatUser($u), $users),
            'totalCount' => $totalCount,
            'page' => $page,
            'totalPages' => (int) ceil($totalCount / $limit),
        ];
    }

    /**
     * Get homepage data with trending content.
     */
    public function getHomepageData(): array
    {
        return [
            'trendingProducts' => $this->getTrendingProducts(8),
            'recentCollections' => $this->getRecentCollections(6),
            'activeUsers' => $this->getActiveUsers(8),
            'stats' => $this->getStats(),
        ];
    }

    private function getTrendingProducts(int $limit): array
    {
        $watches = $this->productWatchRepository->createQueryBuilder('pw')
            ->join('pw.user', 'u')
            ->where('pw.isPublic = true')
            ->andWhere('pw.isActive = true')
            ->andWhere('u.isPublic = true')
            ->andWhere('pw.currentPrice IS NOT NULL')
            ->orderBy('pw.subscriberCount', 'DESC')
            ->addOrderBy('pw.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return array_map(fn(ProductWatch $pw) => $this->formatProduct($pw), $watches);
    }

    private function getRecentCollections(int $limit): array
    {
        $collections = $this->collectionRepository->createQueryBuilder('c')
            ->join('c.user', 'u')
            ->where('c.isPublic = true')
            ->andWhere('u.isPublic = true')
            ->andWhere('u.username IS NOT NULL')
            ->orderBy('c.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return array_map(fn(Collection $c) => $this->formatCollection($c), $collections);
    }

    private function getActiveUsers(int $limit): array
    {
        // Active users = users with most public products
        $result = $this->userRepository->createQueryBuilder('u')
            ->select('u, COUNT(pw.id) as productCount')
            ->leftJoin('u.productWatches', 'pw', 'WITH', 'pw.isPublic = true AND pw.isActive = true')
            ->where('u.isPublic = true')
            ->andWhere('u.username IS NOT NULL')
            ->groupBy('u.id')
            ->having('COUNT(pw.id) > 0')
            ->orderBy('COUNT(pw.id)', 'DESC')
            ->addOrderBy('u.followerCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return array_map(function ($row) {
            $user = $row[0];
            return $this->formatUser($user, (int) $row['productCount']);
        }, $result);
    }

    private function getStats(): array
    {
        $totalProducts = (int) $this->productWatchRepository->createQueryBuilder('pw')
            ->select('COUNT(pw.id)')
            ->join('pw.user', 'u')
            ->where('pw.isPublic = true')
            ->andWhere('pw.isActive = true')
            ->andWhere('u.isPublic = true')
            ->getQuery()
            ->getSingleScalarResult();

        $totalUsers = (int) $this->userRepository->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.isPublic = true')
            ->andWhere('u.username IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'totalProducts' => $totalProducts,
            'totalUsers' => $totalUsers,
        ];
    }

    private function formatCollection(Collection $collection): array
    {
        $user = $collection->getUser();
        $productWatches = $collection->getProductWatches();

        // Get first product image as thumbnail
        $thumbnailUrl = null;
        foreach ($productWatches as $pw) {
            if ($pw->isPublic() && $pw->isActive() && $pw->getImageUrl()) {
                $thumbnailUrl = $pw->getImageUrl();
                break;
            }
        }

        // Count public watches
        $productCount = $productWatches->filter(
            fn(ProductWatch $pw) => $pw->isPublic() && $pw->isActive()
        )->count();

        return [
            'id' => $collection->getId(),
            'name' => $collection->getName(),
            'description' => $collection->getDescription(),
            'slug' => $collection->getSlug(),
            'productCount' => $productCount,
            'thumbnailUrl' => $thumbnailUrl,
            'createdAt' => $collection->getCreatedAt()->format('c'),
            'user' => [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
            ],
        ];
    }

    private function formatUser(User $user, ?int $productCount = null): array
    {
        if ($productCount === null) {
            $productCount = $user->getProductWatches()->filter(
                fn(ProductWatch $pw) => $pw->isPublic() && $pw->isActive()
            )->count();
        }

        return [
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'followerCount' => $user->getFollowerCount(),
            'productCount' => $productCount,
            'memberSince' => $user->getCreatedAt()->format('c'),
        ];
    }

    private function formatProduct(ProductWatch $watch): array
    {
        $user = $watch->getUser();

        return [
            'id' => $watch->getId(),
            'productName' => $watch->getProductName() ?: $watch->getDomain(),
            'url' => $watch->getUrl(),
            'domain' => $watch->getDomain(),
            'imageUrl' => $watch->getImageUrl(),
            'currentPrice' => $watch->getCurrentPrice(),
            'previousPrice' => $watch->getPreviousPrice(),
            'currency' => $watch->getCurrency(),
            'subscriberCount' => $watch->getSubscriberCount(),
            'createdAt' => $watch->getCreatedAt()->format('c'),
            'username' => $user->getUsername(),
        ];
    }
}
