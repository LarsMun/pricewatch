<?php

namespace App\Repository;

use App\Entity\EmailSubscriber;
use App\Entity\ProductWatch;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EmailSubscriber>
 */
class EmailSubscriberRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EmailSubscriber::class);
    }

    public function save(EmailSubscriber $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(EmailSubscriber $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByVerificationToken(string $token): ?EmailSubscriber
    {
        return $this->findOneBy(['verificationToken' => $token]);
    }

    public function findByUnsubscribeToken(string $token): ?EmailSubscriber
    {
        return $this->findOneBy(['unsubscribeToken' => $token]);
    }

    public function findByEmailAndWatch(string $email, ProductWatch $watch): ?EmailSubscriber
    {
        return $this->findOneBy([
            'email' => $email,
            'productWatch' => $watch,
        ]);
    }

    /**
     * Get all verified subscribers for a product watch.
     *
     * @return EmailSubscriber[]
     */
    public function findVerifiedByWatch(ProductWatch $watch): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.productWatch = :watch')
            ->andWhere('s.isVerified = true')
            ->setParameter('watch', $watch)
            ->getQuery()
            ->getResult();
    }

    /**
     * Count verified subscribers for a product watch.
     */
    public function countVerifiedByWatch(ProductWatch $watch): int
    {
        return (int) $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.productWatch = :watch')
            ->andWhere('s.isVerified = true')
            ->setParameter('watch', $watch)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
