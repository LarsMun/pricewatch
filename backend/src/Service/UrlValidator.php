<?php

namespace App\Service;

/**
 * Validates URLs for SSRF protection.
 * Prevents requests to private IPs, localhost, and non-HTTP schemes.
 */
class UrlValidator
{
    private const BLOCKED_HOSTS = [
        'localhost',
        '127.0.0.1',
        '0.0.0.0',
        '::1',
        '[::1]', // IPv6 localhost with brackets (as returned by parse_url)
    ];

    /**
     * Validates that a URL is safe to scrape (no private IPs, only http/https)
     *
     * @throws \InvalidArgumentException if URL is not allowed
     */
    public function validate(string $url): void
    {
        // 1. Check scheme (only http/https)
        $scheme = parse_url($url, PHP_URL_SCHEME);
        if (!in_array($scheme, ['http', 'https'], true)) {
            throw new \InvalidArgumentException('Alleen HTTP en HTTPS URLs zijn toegestaan');
        }

        // 2. Get host
        $host = parse_url($url, PHP_URL_HOST);
        if ($host === null || $host === false) {
            throw new \InvalidArgumentException('Ongeldige URL: geen host gevonden');
        }

        // 3. Check blocked hostnames
        if (in_array(strtolower($host), self::BLOCKED_HOSTS, true)) {
            throw new \InvalidArgumentException('Deze host is niet toegestaan');
        }

        // 4. Check if host is already an IP address
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            if ($this->isPrivateIp($host)) {
                throw new \InvalidArgumentException('Private IP-adressen zijn niet toegestaan');
            }
            return; // Valid public IP, no need to resolve
        }

        // 5. Resolve hostname and check for private IPs
        $ip = gethostbyname($host);
        if ($ip !== $host) { // gethostbyname returns original string if resolution fails
            if ($this->isPrivateIp($ip)) {
                throw new \InvalidArgumentException('Private IP-adressen zijn niet toegestaan');
            }
        }
    }

    /**
     * Check if IP is private, reserved, or loopback
     */
    private function isPrivateIp(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) === false;
    }
}
