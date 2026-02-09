<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserFollow;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserFollow>
 */
class UserFollowRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserFollow::class);
    }

    public function save(UserFollow $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(UserFollow $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find followers of a user with pagination.
     *
     * @return UserFollow[]
     */
    public function findFollowers(User $user, int $limit = 20, int $offset = 0): array
    {
        return $this->createQueryBuilder('uf')
            ->join('uf.follower', 'f')
            ->where('uf.following = :user')
            ->andWhere('f.isPublic = true')
            ->setParameter('user', $user)
            ->orderBy('uf.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find users that a user is following with pagination.
     *
     * @return UserFollow[]
     */
    public function findFollowing(User $user, int $limit = 20, int $offset = 0): array
    {
        return $this->createQueryBuilder('uf')
            ->join('uf.following', 'f')
            ->where('uf.follower = :user')
            ->andWhere('f.isPublic = true')
            ->setParameter('user', $user)
            ->orderBy('uf.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Count followers of a user.
     */
    public function countFollowers(User $user): int
    {
        return (int) $this->createQueryBuilder('uf')
            ->select('COUNT(uf.id)')
            ->join('uf.follower', 'f')
            ->where('uf.following = :user')
            ->andWhere('f.isPublic = true')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Count users that a user is following.
     */
    public function countFollowing(User $user): int
    {
        return (int) $this->createQueryBuilder('uf')
            ->select('COUNT(uf.id)')
            ->join('uf.following', 'f')
            ->where('uf.follower = :user')
            ->andWhere('f.isPublic = true')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Check if a user is following another user.
     */
    public function isFollowing(User $follower, User $following): bool
    {
        $result = $this->createQueryBuilder('uf')
            ->select('COUNT(uf.id)')
            ->where('uf.follower = :follower')
            ->andWhere('uf.following = :following')
            ->setParameter('follower', $follower)
            ->setParameter('following', $following)
            ->getQuery()
            ->getSingleScalarResult();

        return $result > 0;
    }

    /**
     * Find the follow relationship between two users.
     */
    public function findFollow(User $follower, User $following): ?UserFollow
    {
        return $this->findOneBy([
            'follower' => $follower,
            'following' => $following,
        ]);
    }

    /**
     * Get array of user IDs that a user is following.
     *
     * @return int[]
     */
    public function getFollowingIds(User $user): array
    {
        $result = $this->createQueryBuilder('uf')
            ->select('IDENTITY(uf.following) as userId')
            ->where('uf.follower = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getScalarResult();

        return array_map(fn($row) => (int) $row['userId'], $result);
    }
}
