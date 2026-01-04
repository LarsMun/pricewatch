<?php

namespace App\Tests\Integration\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthControllerTest extends WebTestCase
{
    protected function clearUsers(): void
    {
        // Get container from static kernel (created by createClient)
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $em->createQuery('DELETE FROM App\Entity\User u')->execute();
    }

    // === Registration Tests ===

    public function testRegisterSuccess(): void
    {
        $client = static::createClient();
        $this->clearUsers();

        $client->request('POST', '/api/register', [], [], 
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'test@example.com', 'password' => 'password123'])
        );

        $this->assertResponseStatusCodeSame(201);
        
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('user', $data);
        $this->assertSame('test@example.com', $data['user']['email']);
    }

    public function testRegisterDuplicateEmail(): void
    {
        $client = static::createClient();
        $this->clearUsers();

        // Create first user
        $client->request('POST', '/api/register', [], [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'duplicate@example.com', 'password' => 'password123'])
        );
        $this->assertResponseStatusCodeSame(201);

        // Try to create user with same email
        $client->request('POST', '/api/register', [], [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'duplicate@example.com', 'password' => 'password123'])
        );

        $this->assertResponseStatusCodeSame(400);
    }

    public function testRegisterInvalidEmail(): void
    {
        $client = static::createClient();
        $this->clearUsers();

        $client->request('POST', '/api/register', [], [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'not-an-email', 'password' => 'password123'])
        );

        $this->assertResponseStatusCodeSame(400);
    }

    public function testRegisterShortPassword(): void
    {
        $client = static::createClient();
        $this->clearUsers();

        $client->request('POST', '/api/register', [], [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'test@example.com', 'password' => 'short'])
        );

        $this->assertResponseStatusCodeSame(400);
        
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertStringContainsString('8 karakters', $data['error']);
    }

    public function testRegisterMissingFields(): void
    {
        $client = static::createClient();
        $this->clearUsers();

        $client->request('POST', '/api/register', [], [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'test@example.com'])
        );

        $this->assertResponseStatusCodeSame(400);
        
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $data);
    }

    // === Login Tests ===

    public function testLoginSuccess(): void
    {
        $client = static::createClient();
        $this->clearUsers();

        // First register a user
        $client->request('POST', '/api/register', [], [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'login@example.com', 'password' => 'password123'])
        );
        $this->assertResponseStatusCodeSame(201);

        // Now login
        $client->request('POST', '/api/login', [], [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['username' => 'login@example.com', 'password' => 'password123'])
        );

        $this->assertResponseIsSuccessful();
        
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('token', $data);
    }

    public function testLoginWrongPassword(): void
    {
        $client = static::createClient();
        $this->clearUsers();

        // First register a user
        $client->request('POST', '/api/register', [], [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'wrongpw@example.com', 'password' => 'password123'])
        );

        // Try login with wrong password
        $client->request('POST', '/api/login', [], [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['username' => 'wrongpw@example.com', 'password' => 'wrongpassword'])
        );

        $this->assertResponseStatusCodeSame(401);
    }

    public function testLoginNonExistentUser(): void
    {
        $client = static::createClient();
        $this->clearUsers();

        $client->request('POST', '/api/login', [], [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['username' => 'nonexistent@example.com', 'password' => 'password123'])
        );

        $this->assertResponseStatusCodeSame(401);
    }

    // === Me Endpoint Tests ===

    public function testGetMeAuthenticated(): void
    {
        $client = static::createClient();
        $this->clearUsers();

        // Register and login
        $client->request('POST', '/api/register', [], [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'me@example.com', 'password' => 'password123'])
        );

        $client->request('POST', '/api/login', [], [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['username' => 'me@example.com', 'password' => 'password123'])
        );

        $loginData = json_decode($client->getResponse()->getContent(), true);
        $token = $loginData['token'] ?? null;
        $this->assertNotNull($token, 'Login should return a token');

        // Access /me endpoint with token
        $client->request('GET', '/api/me', [], [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]
        );

        $this->assertResponseIsSuccessful();
        
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('me@example.com', $data['email']);
        $this->assertFalse($data['isVerified']);
    }

    public function testGetMeUnauthenticated(): void
    {
        $client = static::createClient();
        $this->clearUsers();

        $client->request('GET', '/api/me');

        $this->assertResponseStatusCodeSame(401);
    }

    // === Email Verification Tests ===

    public function testVerifyEmailInvalidToken(): void
    {
        $client = static::createClient();
        $this->clearUsers();

        $client->request('POST', '/api/verify-email', [], [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['token' => 'invalid-token-12345'])
        );

        $this->assertResponseStatusCodeSame(400);
        
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertStringContainsString('Ongeldige', $data['error']);
    }

    public function testVerifyEmailMissingToken(): void
    {
        $client = static::createClient();
        $this->clearUsers();

        $client->request('POST', '/api/verify-email', [], [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([])
        );

        $this->assertResponseStatusCodeSame(400);
    }

    // === Password Reset Tests ===

    public function testForgotPasswordReturnsSuccessEvenForNonExistentUser(): void
    {
        $client = static::createClient();
        $this->clearUsers();

        // Request password reset for non-existent email
        $client->request('POST', '/api/forgot-password', [], [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'nonexistent@example.com'])
        );

        // Should still return 200 to prevent email enumeration
        $this->assertResponseIsSuccessful();
        
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('message', $data);
    }

    public function testForgotPasswordExistingUser(): void
    {
        $client = static::createClient();
        $this->clearUsers();

        // Register a user first
        $client->request('POST', '/api/register', [], [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'forgot@example.com', 'password' => 'password123'])
        );

        // Request password reset
        $client->request('POST', '/api/forgot-password', [], [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'forgot@example.com'])
        );

        $this->assertResponseIsSuccessful();
    }

    public function testResetPasswordInvalidToken(): void
    {
        $client = static::createClient();
        $this->clearUsers();

        $client->request('POST', '/api/reset-password', [], [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['token' => 'invalid-token-xyz', 'password' => 'newpassword123'])
        );

        $this->assertResponseStatusCodeSame(400);
        
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertStringContainsString('Ongeldige', $data['error']);
    }

    public function testResetPasswordMissingFields(): void
    {
        $client = static::createClient();
        $this->clearUsers();

        $client->request('POST', '/api/reset-password', [], [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['token' => 'some-token'])
        );

        $this->assertResponseStatusCodeSame(400);
    }

    public function testResetPasswordShortPassword(): void
    {
        $client = static::createClient();
        $this->clearUsers();

        $client->request('POST', '/api/reset-password', [], [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['token' => 'some-token', 'password' => 'short'])
        );

        $this->assertResponseStatusCodeSame(400);
        
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertStringContainsString('8 karakters', $data['error']);
    }
}
