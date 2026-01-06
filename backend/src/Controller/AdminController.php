<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Repository\ProductWatchRepository;
use App\Repository\PriceCheckRepository;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route("/api/admin")]
class AdminController extends AbstractController
{
    #[Route("/stats", name: "admin_stats", methods: ["GET"])]
    public function stats(
        UserRepository $userRepo,
        ProductWatchRepository $watchRepo,
        EntityManagerInterface $em
    ): JsonResponse {
        $conn = $em->getConnection();

        // User stats
        $totalUsers = (int) $conn->fetchOne("SELECT COUNT(*) FROM user");
        $verifiedUsers = (int) $conn->fetchOne("SELECT COUNT(*) FROM user WHERE is_verified = 1");
        $usersLast7Days = (int) $conn->fetchOne(
            "SELECT COUNT(*) FROM user WHERE created_at >= :date",
            ["date" => (new \DateTimeImmutable("-7 days"))->format("Y-m-d H:i:s")]
        );

        // Watch stats
        $totalWatches = (int) $conn->fetchOne("SELECT COUNT(*) FROM product_watch");
        $activeWatches = (int) $conn->fetchOne("SELECT COUNT(*) FROM product_watch WHERE is_active = 1");
        $pausedWatches = $totalWatches - $activeWatches;

        // Price check stats (last 24h)
        $checksLast24h = (int) $conn->fetchOne(
            "SELECT COUNT(*) FROM price_check WHERE checked_at >= :date",
            ["date" => (new \DateTimeImmutable("-24 hours"))->format("Y-m-d H:i:s")]
        );
        $successfulChecks = (int) $conn->fetchOne(
            "SELECT COUNT(*) FROM price_check WHERE checked_at >= :date AND was_successful = 1",
            ["date" => (new \DateTimeImmutable("-24 hours"))->format("Y-m-d H:i:s")]
        );
        $failedChecks = $checksLast24h - $successfulChecks;

        // Top domains
        $topDomains = $conn->fetchAllAssociative(
            "SELECT domain, COUNT(*) as count FROM product_watch GROUP BY domain ORDER BY count DESC LIMIT 10"
        );

        // Notifications last 7 days
        $notificationsLast7Days = (int) $conn->fetchOne(
            "SELECT COUNT(*) FROM notification WHERE sent_at >= :date",
            ["date" => (new \DateTimeImmutable("-7 days"))->format("Y-m-d H:i:s")]
        );

        return $this->json([
            "users" => [
                "total" => $totalUsers,
                "verified" => $verifiedUsers,
                "unverified" => $totalUsers - $verifiedUsers,
                "newLast7Days" => $usersLast7Days,
            ],
            "watches" => [
                "total" => $totalWatches,
                "active" => $activeWatches,
                "paused" => $pausedWatches,
            ],
            "priceChecks" => [
                "last24h" => $checksLast24h,
                "successful" => $successfulChecks,
                "failed" => $failedChecks,
                "successRate" => $checksLast24h > 0 ? round($successfulChecks / $checksLast24h * 100, 1) : 0,
            ],
            "notifications" => [
                "last7Days" => $notificationsLast7Days,
            ],
            "topDomains" => $topDomains,
        ]);
    }

    #[Route("/users", name: "admin_users", methods: ["GET"])]
    public function users(
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse {
        $page = max(1, (int) $request->query->get("page", 1));
        $limit = min(50, max(1, (int) $request->query->get("limit", 20)));
        $offset = ($page - 1) * $limit;

        $conn = $em->getConnection();

        $total = (int) $conn->fetchOne("SELECT COUNT(*) FROM user");

        $users = $conn->fetchAllAssociative("
            SELECT 
                u.id,
                u.email,
                u.is_verified as isVerified,
                u.roles,
                u.created_at as createdAt,
                (SELECT COUNT(*) FROM product_watch pw WHERE pw.user_id = u.id) as watchCount
            FROM user u
            ORDER BY u.created_at DESC
            LIMIT :limit OFFSET :offset
        ", ["limit" => $limit, "offset" => $offset], ["limit" => \PDO::PARAM_INT, "offset" => \PDO::PARAM_INT]);

        // Convert roles from JSON string
        foreach ($users as &$user) {
            $user["roles"] = json_decode($user["roles"], true) ?: [];
            $user["isVerified"] = (bool) $user["isVerified"];
        }

        return $this->json([
            "users" => $users,
            "pagination" => [
                "page" => $page,
                "limit" => $limit,
                "total" => $total,
                "pages" => (int) ceil($total / $limit),
            ],
        ]);
    }

    #[Route("/users/{id}", name: "admin_user_detail", methods: ["GET"])]
    public function userDetail(
        int $id,
        UserRepository $userRepo,
        EntityManagerInterface $em
    ): JsonResponse {
        $user = $userRepo->find($id);

        if (!$user) {
            return $this->json(["error" => "User not found"], Response::HTTP_NOT_FOUND);
        }

        $conn = $em->getConnection();

        $watches = $conn->fetchAllAssociative("
            SELECT 
                pw.id,
                pw.url,
                pw.domain,
                pw.product_name as productName,
                pw.current_price as currentPrice,
                pw.is_active as isActive,
                pw.consecutive_failures as consecutiveFailures,
                pw.last_checked_at as lastCheckedAt,
                pw.created_at as createdAt
            FROM product_watch pw
            WHERE pw.user_id = :userId
            ORDER BY pw.created_at DESC
        ", ["userId" => $id]);

        foreach ($watches as &$watch) {
            $watch["isActive"] = (bool) $watch["isActive"];
        }

        return $this->json([
            "id" => $user->getId(),
            "email" => $user->getEmail(),
            "isVerified" => $user->isVerified(),
            "roles" => $user->getRoles(),
            "createdAt" => $user->getCreatedAt()->format("c"),
            "watches" => $watches,
        ]);
    }

    #[Route("/users/{id}/role", name: "admin_user_role", methods: ["PATCH"])]
    public function updateUserRole(
        int $id,
        Request $request,
        UserRepository $userRepo,
        EntityManagerInterface $em
    ): JsonResponse {
        $user = $userRepo->find($id);

        if (!$user) {
            return $this->json(["error" => "User not found"], Response::HTTP_NOT_FOUND);
        }

        /** @var User $currentUser */
        $currentUser = $this->getUser();

        $data = json_decode($request->getContent(), true);
        $action = $data["action"] ?? null;

        if ($action === "grant_admin") {
            $roles = $user->getRoles();
            if (!in_array("ROLE_ADMIN", $roles)) {
                $roles[] = "ROLE_ADMIN";
                $user->setRoles(array_values(array_unique($roles)));
            }
        } elseif ($action === "revoke_admin") {
            // Prevent self-demotion
            if ($user->getId() === $currentUser->getId()) {
                return $this->json([
                    "error" => "Je kunt je eigen admin rol niet verwijderen"
                ], Response::HTTP_FORBIDDEN);
            }

            // Prevent removing the last admin
            $adminCount = $userRepo->countByRole("ROLE_ADMIN");
            if ($adminCount <= 1) {
                return $this->json([
                    "error" => "Er moet minimaal één admin blijven"
                ], Response::HTTP_FORBIDDEN);
            }

            $roles = array_filter($user->getRoles(), fn($r) => $r !== "ROLE_ADMIN");
            $user->setRoles(array_values($roles));
        } else {
            return $this->json(["error" => "Invalid action"], Response::HTTP_BAD_REQUEST);
        }

        $em->flush();

        return $this->json([
            "message" => "Role updated",
            "roles" => $user->getRoles(),
        ]);
    }

    #[Route("/recent-checks", name: "admin_recent_checks", methods: ["GET"])]
    public function recentChecks(
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse {
        $limit = min(100, max(1, (int) $request->query->get("limit", 50)));

        $conn = $em->getConnection();

        $checks = $conn->fetchAllAssociative("
            SELECT 
                pc.id,
                pc.price,
                pc.was_successful as wasSuccessful,
                pc.http_status as httpStatus,
                pc.duration_ms as durationMs,
                pc.error_message as errorMessage,
                pc.checked_at as checkedAt,
                pw.domain,
                pw.product_name as productName,
                u.email as userEmail
            FROM price_check pc
            JOIN product_watch pw ON pc.product_watch_id = pw.id
            JOIN user u ON pw.user_id = u.id
            ORDER BY pc.checked_at DESC
            LIMIT :limit
        ", ["limit" => $limit], ["limit" => \PDO::PARAM_INT]);

        foreach ($checks as &$check) {
            $check["wasSuccessful"] = (bool) $check["wasSuccessful"];
        }

        return $this->json($checks);
    }
}
