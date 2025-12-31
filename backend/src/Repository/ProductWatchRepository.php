<?php

namespace App\Repository;

use App\Entity\ProductWatch;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProductWatch>
 */
class ProductWatchRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductWatch::class);
    }

    public function save(ProductWatch $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ProductWatch $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find watches that are due for checking.
     *
     * @return ProductWatch[]
     */
    public function findDueForCheck(int $limit = 100): array
    {
        return $this->createQueryBuilder('pw')
            ->where('pw.nextCheckAt <= :now')
            ->andWhere('pw.isActive = true')
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('pw.nextCheckAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find active watches by user.
     *
     * @return ProductWatch[]
     */
    public function findActiveByUser(User $user): array
    {
        return $this->createQueryBuilder('pw')
            ->where('pw.user = :user')
            ->andWhere('pw.isActive = true')
            ->setParameter('user', $user)
            ->orderBy('pw.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all watches by user.
     *
     * @return ProductWatch[]
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('pw')
            ->where('pw.user = :user')
            ->setParameter('user', $user)
            ->orderBy('pw.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Count watches for a specific domain that were checked recently.
     * Used for rate limiting.
     */
    public function countRecentChecksByDomain(string $domain, \DateTimeImmutable $since): int
    {
        return (int) $this->createQueryBuilder('pw')
            ->select('COUNT(pw.id)')
            ->where('pw.domain = :domain')
            ->andWhere('pw.lastCheckedAt >= :since')
            ->setParameter('domain', $domain)
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Count total watches for a user.
     */
    public function countByUser(User $user): int
    {
        return (int) $this->createQueryBuilder('pw')
            ->select('COUNT(pw.id)')
            ->where('pw.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
