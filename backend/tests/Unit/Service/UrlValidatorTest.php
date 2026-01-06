<?php

namespace App\Tests\Unit\Service;

use App\Service\UrlValidator;
use PHPUnit\Framework\TestCase;

class UrlValidatorTest extends TestCase
{
    private UrlValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new UrlValidator();
    }

    public function testValidHttpUrl(): void
    {
        $this->validator->validate('http://example.com/page');
        $this->addToAssertionCount(1); // No exception = pass
    }

    public function testValidHttpsUrl(): void
    {
        $this->validator->validate('https://example.com/page');
        $this->addToAssertionCount(1);
    }

    public function testBlocksLocalhost(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Localhost URLs zijn niet toegestaan');
        $this->validator->validate('http://localhost/admin');
    }

    public function testBlocks127001(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Localhost URLs zijn niet toegestaan');
        $this->validator->validate('http://127.0.0.1/admin');
    }

    public function testBlocksPrivateIp10x(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Private IP adressen zijn niet toegestaan');
        $this->validator->validate('http://10.0.0.1/internal');
    }

    public function testBlocksPrivateIp172x(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Private IP adressen zijn niet toegestaan');
        $this->validator->validate('http://172.16.0.1/internal');
    }

    public function testBlocksPrivateIp192168x(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Private IP adressen zijn niet toegestaan');
        $this->validator->validate('http://192.168.1.1/internal');
    }

    public function testBlocksFileScheme(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Alleen HTTP en HTTPS URLs zijn toegestaan');
        $this->validator->validate('file:///etc/passwd');
    }

    public function testBlocksFtpScheme(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Alleen HTTP en HTTPS URLs zijn toegestaan');
        $this->validator->validate('ftp://ftp.example.com/file');
    }

    public function testBlocksZeroZeroZeroZero(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Localhost URLs zijn niet toegestaan');
        $this->validator->validate('http://0.0.0.0/');
    }

    public function testBlocksIpv6Localhost(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Localhost URLs zijn niet toegestaan');
        $this->validator->validate('http://[::1]/');
    }

    public function testBlocksUrlWithoutHost(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Ongeldige URL');
        $this->validator->validate('http:///path/only');
    }

    public function testAllowsPublicIpAddress(): void
    {
        // 8.8.8.8 is Google's public DNS
        $this->validator->validate('http://8.8.8.8/');
        $this->addToAssertionCount(1);
    }

    public function testAllowsValidDomain(): void
    {
        $this->validator->validate('https://www.bol.com/nl/product/123');
        $this->addToAssertionCount(1);
    }

    public function testAllowsSubdomain(): void
    {
        // Use a real domain that will resolve
        $this->validator->validate('https://www.google.com/');
        $this->addToAssertionCount(1);
    }

    public function testBlocksPrivateIp169254x(): void
    {
        // Link-local address
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Private IP adressen zijn niet toegestaan');
        $this->validator->validate('http://169.254.169.254/metadata');
    }

    public function testValidateAndResolveReturnsResolvedUrl(): void
    {
        // Test that validateAndResolve returns both resolved URL and original host
        $result = $this->validator->validateAndResolve('https://www.google.com/');

        $this->assertArrayHasKey('url', $result);
        $this->assertArrayHasKey('originalHost', $result);
        $this->assertEquals('www.google.com', $result['originalHost']);
    }
}
