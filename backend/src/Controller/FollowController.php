<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserFollow;
use App\Repository\UserFollowRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class FollowController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserFollowRepository $userFollowRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/api/users/{id}/follow', name: 'api_user_follow', methods: ['POST'])]
    public function follow(int $id): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        if ($currentUser->getId() === $id) {
            return $this->json(['error' => 'Je kunt jezelf niet volgen'], Response::HTTP_BAD_REQUEST);
        }

        $targetUser = $this->userRepository->find($id);
        if ($targetUser === null) {
            return $this->json(['error' => 'Gebruiker niet gevonden'], Response::HTTP_NOT_FOUND);
        }

        if (!$targetUser->isPublic()) {
            return $this->json(['error' => 'Deze gebruiker heeft een privé profiel'], Response::HTTP_FORBIDDEN);
        }

        $existingFollow = $this->userFollowRepository->findFollow($currentUser, $targetUser);
        if ($existingFollow !== null) {
            return $this->json([
                'message' => 'Je volgt deze gebruiker al',
                'followerCount' => $targetUser->getFollowerCount(),
            ]);
        }

        $follow = new UserFollow();
        $follow->setFollower($currentUser);
        $follow->setFollowing($targetUser);

        $targetUser->incrementFollowerCount();
        $currentUser->incrementFollowingCount();

        $this->entityManager->persist($follow);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Je volgt nu ' . ($targetUser->getUsername() ?? 'deze gebruiker'),
            'followerCount' => $targetUser->getFollowerCount(),
        ], Response::HTTP_CREATED);
    }

    #[Route('/api/users/{id}/follow', name: 'api_user_unfollow', methods: ['DELETE'])]
    public function unfollow(int $id): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        if ($currentUser->getId() === $id) {
            return $this->json(['error' => 'Je kunt jezelf niet ontvolgen'], Response::HTTP_BAD_REQUEST);
        }

        $targetUser = $this->userRepository->find($id);
        if ($targetUser === null) {
            return $this->json(['error' => 'Gebruiker niet gevonden'], Response::HTTP_NOT_FOUND);
        }

        $follow = $this->userFollowRepository->findFollow($currentUser, $targetUser);
        if ($follow === null) {
            return $this->json([
                'message' => 'Je volgt deze gebruiker niet',
                'followerCount' => $targetUser->getFollowerCount(),
            ]);
        }

        $targetUser->decrementFollowerCount();
        $currentUser->decrementFollowingCount();

        $this->entityManager->remove($follow);
        $this->entityManager->flush();

        return $this->json([
            'message' => 'Je volgt ' . ($targetUser->getUsername() ?? 'deze gebruiker') . ' niet meer',
            'followerCount' => $targetUser->getFollowerCount(),
        ]);
    }

    #[Route('/api/users/{id}/followers', name: 'api_user_followers', methods: ['GET'])]
    public function followers(int $id, Request $request): JsonResponse
    {
        $targetUser = $this->userRepository->find($id);
        if ($targetUser === null || !$targetUser->isPublic()) {
            return $this->json(['error' => 'Gebruiker niet gevonden'], Response::HTTP_NOT_FOUND);
        }

        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(50, max(1, (int) $request->query->get('limit', 20)));
        $offset = ($page - 1) * $limit;

        $follows = $this->userFollowRepository->findFollowers($targetUser, $limit, $offset);
        $totalCount = $this->userFollowRepository->countFollowers($targetUser);

        $followers = [];
        foreach ($follows as $follow) {
            $follower = $follow->getFollower();
            $followers[] = $this->serializeUserSummary($follower);
        }

        return $this->json([
            'followers' => $followers,
            'totalCount' => $totalCount,
            'page' => $page,
            'totalPages' => (int) ceil($totalCount / $limit),
        ]);
    }

    #[Route('/api/users/{id}/following', name: 'api_user_following', methods: ['GET'])]
    public function following(int $id, Request $request): JsonResponse
    {
        $targetUser = $this->userRepository->find($id);
        if ($targetUser === null || !$targetUser->isPublic()) {
            return $this->json(['error' => 'Gebruiker niet gevonden'], Response::HTTP_NOT_FOUND);
        }

        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(50, max(1, (int) $request->query->get('limit', 20)));
        $offset = ($page - 1) * $limit;

        $follows = $this->userFollowRepository->findFollowing($targetUser, $limit, $offset);
        $totalCount = $this->userFollowRepository->countFollowing($targetUser);

        $followingUsers = [];
        foreach ($follows as $follow) {
            $user = $follow->getFollowing();
            $followingUsers[] = $this->serializeUserSummary($user);
        }

        return $this->json([
            'following' => $followingUsers,
            'totalCount' => $totalCount,
            'page' => $page,
            'totalPages' => (int) ceil($totalCount / $limit),
        ]);
    }

    #[Route('/api/me/following/ids', name: 'api_me_following_ids', methods: ['GET'])]
    public function myFollowingIds(): JsonResponse
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        $ids = $this->userFollowRepository->getFollowingIds($currentUser);

        return $this->json([
            'followingIds' => $ids,
        ]);
    }

    private function serializeUserSummary(User $user): array
    {
        $productCount = $user->getProductWatches()->filter(
            fn($pw) => $pw->isPublic() && $pw->isActive()
        )->count();

        return [
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'followerCount' => $user->getFollowerCount(),
            'productCount' => $productCount,
            'memberSince' => $user->getCreatedAt()->format('c'),
        ];
    }
}
