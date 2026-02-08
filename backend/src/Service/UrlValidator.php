<?php

namespace App\Service;

/**
 * Validates URLs for SSRF protection.
 * Prevents requests to private IPs, localhost, and non-HTTP schemes.
 * Uses IP pinning to prevent DNS rebinding attacks.
 */
class UrlValidator
{
    private const BLOCKED_HOSTS = [
        'localhost',
        '127.0.0.1',
        '0.0.0.0',
        '::1',
        '[::1]',
    ];

    private const BLOCKED_HOSTS_IP = [
        '0.0.0.0',
        '::',
    ];

    /**
     * Validates URL and returns the URL with hostname replaced by resolved IP.
     * This prevents DNS rebinding attacks by pinning the IP at validation time.
     *
     * @throws \InvalidArgumentException if URL is invalid or resolves to private IP
     * @return array{url: string, originalHost: string} URL with IP and original hostname for Host header
     */
    public function validateAndResolve(string $url): array
    {
        // Basic URL validation
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('Ongeldige URL');
        }

        $parsedUrl = parse_url($url);
        $scheme = $parsedUrl['scheme'] ?? '';
        $host = $parsedUrl['host'] ?? '';

        // Only allow HTTP(S)
        if (!in_array(strtolower($scheme), ['http', 'https'], true)) {
            throw new \InvalidArgumentException('Alleen HTTP en HTTPS URLs zijn toegestaan');
        }

        if (empty($host)) {
            throw new \InvalidArgumentException('Ongeldige URL: geen host gevonden');
        }

        // Block known localhost variants
        if (in_array(strtolower($host), self::BLOCKED_HOSTS, true)) {
            throw new \InvalidArgumentException('Localhost URLs zijn niet toegestaan');
        }

        // Check if host is already an IP address
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            if ($this->isPrivateIp($host)) {
                throw new \InvalidArgumentException('Private IP adressen zijn niet toegestaan');
            }
            return ['url' => $url, 'originalHost' => $host];
        }

        // Resolve hostname to IP
        $ip = gethostbyname($host);

        // If gethostbyname fails, it returns the hostname
        if ($ip === $host) {
            throw new \InvalidArgumentException('Kon hostname niet resolven');
        }

        // Check if resolved IP is private
        if ($this->isPrivateIp($ip)) {
            throw new \InvalidArgumentException('Private IP adressen zijn niet toegestaan');
        }

        // Return URL with IP instead of hostname (prevents DNS rebinding)
        $resolvedUrl = str_replace("://{$host}", "://{$ip}", $url);

        return [
            'url' => $resolvedUrl,
            'originalHost' => $host,
        ];
    }

    /**
     * Original validate method for backwards compatibility.
     * Use validateAndResolve() for new code to prevent DNS rebinding.
     *
     * @throws \InvalidArgumentException if URL is not allowed
     */
    public function validate(string $url): void
    {
        $this->validateAndResolve($url);
    }

    /**
     * Check if IP is private, reserved, or loopback.
     * Uses PHP's built-in filter_var which handles both IPv4 and IPv6.
     */
    private function isPrivateIp(string $ip): bool
    {
        if (in_array($ip, self::BLOCKED_HOSTS_IP, true)) {
            return true;
        }

        // filter_var returns false if the IP is private, reserved, or invalid
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) === false;
    }
}
