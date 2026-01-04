<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\EmailVerificationService;
use App\Service\PasswordResetService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api')]
class AuthController extends AbstractController
{
    #[Route('/login', name: 'api_login', methods: ['POST'])]
    public function login(): JsonResponse
    {
        // This method is handled by the json_login authenticator in security.yaml
        // It should never be reached - if it is, authentication failed
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['error' => 'Invalid credentials'], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json(['message' => 'Login successful']);
    }

    #[Route('/register', name: 'api_register', methods: ['POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        EmailVerificationService $verificationService
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        if (!$email || !$password) {
            return $this->json(['error' => 'Email en wachtwoord zijn verplicht'], Response::HTTP_BAD_REQUEST);
        }

        if (strlen($password) < 8) {
            return $this->json(['error' => 'Wachtwoord moet minimaal 8 karakters zijn'], Response::HTTP_BAD_REQUEST);
        }

        $user = new User();
        $user->setEmail($email);
        $user->setPassword($passwordHasher->hashPassword($user, $password));

        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        $entityManager->persist($user);
        $entityManager->flush();

        $verificationService->sendVerificationEmail($user);

        return $this->json([
            'message' => 'Account aangemaakt. Check je e-mail om je account te bevestigen.',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
            ]
        ], Response::HTTP_CREATED);
    }

    #[Route('/me', name: 'api_me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'isVerified' => $user->isVerified(),
            'createdAt' => $user->getCreatedAt()->format('c'),
        ]);
    }

    #[Route('/me', name: 'api_delete_account', methods: ['DELETE'])]
    public function deleteAccount(EntityManagerInterface $entityManager): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $entityManager->remove($user);
        $entityManager->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/me/export', name: 'api_export_data', methods: ['GET'])]
    public function exportData(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $watches = [];
        foreach ($user->getProductWatches() as $watch) {
            $priceChecks = [];
            foreach ($watch->getPriceChecks() as $check) {
                $priceChecks[] = [
                    'id' => $check->getId(),
                    'price' => $check->getPrice(),
                    'rawText' => $check->getRawText(),
                    'wasSuccessful' => $check->isWasSuccessful(),
                    'httpStatus' => $check->getHttpStatus(),
                    'durationMs' => $check->getDurationMs(),
                    'errorMessage' => $check->getErrorMessage(),
                    'checkedAt' => $check->getCheckedAt()->format('c'),
                ];
            }

            $notifications = [];
            foreach ($watch->getNotifications() as $notification) {
                $notifications[] = [
                    'id' => $notification->getId(),
                    'type' => $notification->getType()->value,
                    'oldPrice' => $notification->getOldPrice(),
                    'newPrice' => $notification->getNewPrice(),
                    'sentAt' => $notification->getSentAt()->format('c'),
                ];
            }

            $watches[] = [
                'id' => $watch->getId(),
                'url' => $watch->getUrl(),
                'domain' => $watch->getDomain(),
                'productName' => $watch->getProductName(),
                'priceSelector' => $watch->getPriceSelector(),
                'imageUrl' => $watch->getImageUrl(),
                'currency' => $watch->getCurrency(),
                'currentPrice' => $watch->getCurrentPrice(),
                'previousPrice' => $watch->getPreviousPrice(),
                'originalPrice' => $watch->getOriginalPrice(),
                'checkMethod' => $watch->getCheckMethod()->value,
                'isActive' => $watch->isActive(),
                'consecutiveFailures' => $watch->getConsecutiveFailures(),
                'createdAt' => $watch->getCreatedAt()->format('c'),
                'lastCheckedAt' => $watch->getLastCheckedAt()?->format('c'),
                'priceChecks' => $priceChecks,
                'notifications' => $notifications,
            ];
        }

        $export = [
            'exportedAt' => (new \DateTimeImmutable())->format('c'),
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'isVerified' => $user->isVerified(),
                'createdAt' => $user->getCreatedAt()->format('c'),
            ],
            'watches' => $watches,
        ];

        $response = new JsonResponse($export);
        $response->headers->set(
            'Content-Disposition',
            'attachment; filename="shopq-export-' . date('Y-m-d') . '.json"'
        );

        return $response;
    }

    #[Route('/verify-email', name: 'api_verify_email', methods: ['POST'])]
    public function verifyEmail(
        Request $request,
        EmailVerificationService $verificationService
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $token = $data['token'] ?? null;

        if (!$token) {
            return $this->json(['error' => 'Token is verplicht'], Response::HTTP_BAD_REQUEST);
        }

        $user = $verificationService->verifyToken($token);

        if (!$user) {
            return $this->json([
                'error' => 'Ongeldige of verlopen verificatielink'
            ], Response::HTTP_BAD_REQUEST);
        }

        return $this->json(['message' => 'E-mailadres succesvol geverifieerd']);
    }

    #[Route('/resend-verification', name: 'api_resend_verification', methods: ['POST'])]
    public function resendVerification(
        EmailVerificationService $verificationService
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        if ($user->isVerified()) {
            return $this->json([
                'error' => 'E-mailadres is al geverifieerd'
            ], Response::HTTP_BAD_REQUEST);
        }

        $verificationService->resendVerificationEmail($user);

        return $this->json(['message' => 'Verificatie e-mail opnieuw verzonden']);
    }

    #[Route('/forgot-password', name: 'api_forgot_password', methods: ['POST'])]
    public function forgotPassword(
        Request $request,
        PasswordResetService $resetService
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;

        if (!$email) {
            return $this->json(['error' => 'E-mailadres is verplicht'], Response::HTTP_BAD_REQUEST);
        }

        // Always return success to prevent email enumeration
        $resetService->sendResetEmail($email);

        return $this->json([
            'message' => 'Als dit e-mailadres bij ons bekend is, ontvang je een e-mail met instructies.'
        ]);
    }

    #[Route('/reset-password', name: 'api_reset_password', methods: ['POST'])]
    public function resetPassword(
        Request $request,
        PasswordResetService $resetService
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $token = $data['token'] ?? null;
        $password = $data['password'] ?? null;

        if (!$token || !$password) {
            return $this->json(['error' => 'Token en wachtwoord zijn verplicht'], Response::HTTP_BAD_REQUEST);
        }

        if (strlen($password) < 8) {
            return $this->json(['error' => 'Wachtwoord moet minimaal 8 karakters zijn'], Response::HTTP_BAD_REQUEST);
        }

        $success = $resetService->resetPassword($token, $password);

        if (!$success) {
            return $this->json([
                'error' => 'Ongeldige of verlopen reset link'
            ], Response::HTTP_BAD_REQUEST);
        }

        return $this->json(['message' => 'Wachtwoord succesvol gewijzigd']);
    }
}
