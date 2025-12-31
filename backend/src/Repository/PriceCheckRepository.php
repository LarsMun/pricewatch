<?php

namespace App\Repository;

use App\Entity\PriceCheck;
use App\Entity\ProductWatch;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PriceCheck>
 */
class PriceCheckRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PriceCheck::class);
    }

    public function save(PriceCheck $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(PriceCheck $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find price history for a watch.
     *
     * @return PriceCheck[]
     */
    public function findByWatch(ProductWatch $watch, int $limit = 100): array
    {
        return $this->createQueryBuilder('pc')
            ->where('pc.productWatch = :watch')
            ->setParameter('watch', $watch)
            ->orderBy('pc.checkedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find successful price checks for history graph.
     *
     * @return PriceCheck[]
     */
    public function findSuccessfulByWatch(ProductWatch $watch, int $limit = 100): array
    {
        return $this->createQueryBuilder('pc')
            ->where('pc.productWatch = :watch')
            ->andWhere('pc.wasSuccessful = true')
            ->setParameter('watch', $watch)
            ->orderBy('pc.checkedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Delete price checks older than a given date.
     * Used for retention cleanup (90 days).
     */
    public function deleteOlderThan(\DateTimeImmutable $date): int
    {
        return $this->createQueryBuilder('pc')
            ->delete()
            ->where('pc.checkedAt < :date')
            ->setParameter('date', $date)
            ->getQuery()
            ->execute();
    }
}
