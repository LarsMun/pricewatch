<?php

namespace App\Repository;

use App\Entity\Collection;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Collection>
 */
class CollectionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Collection::class);
    }

    public function save(Collection $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Collection $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return Collection[]
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.user = :user')
            ->setParameter('user', $user)
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find collections with watch count for tabs display
     * @return array<array{id: int, name: string, watchCount: int}>
     */
    public function findByUserWithWatchCount(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->select('c.id', 'c.name', 'c.description', 'COUNT(pw.id) as watchCount')
            ->leftJoin('c.productWatches', 'pw')
            ->andWhere('c.user = :user')
            ->setParameter('user', $user)
            ->groupBy('c.id')
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
