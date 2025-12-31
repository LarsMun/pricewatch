<?php

namespace App\Repository;

use App\Entity\Notification;
use App\Entity\ProductWatch;
use App\Enum\NotificationType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Notification>
 */
class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    public function save(Notification $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Notification $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find notifications for a watch.
     *
     * @return Notification[]
     */
    public function findByWatch(ProductWatch $watch, int $limit = 50): array
    {
        return $this->createQueryBuilder('n')
            ->where('n.productWatch = :watch')
            ->setParameter('watch', $watch)
            ->orderBy('n.sentAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find notifications by type.
     *
     * @return Notification[]
     */
    public function findByType(NotificationType $type, int $limit = 100): array
    {
        return $this->createQueryBuilder('n')
            ->where('n.type = :type')
            ->setParameter('type', $type)
            ->orderBy('n.sentAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
