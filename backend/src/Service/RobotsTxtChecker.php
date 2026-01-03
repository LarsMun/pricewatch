<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class RobotsTxtChecker
{
    private array $robotsCache = [];

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
    ) {
    }

    public function checkAndLog(string $url, string $userAgent = 'ShopQBot/1.0'): bool
    {
        $parsedUrl = parse_url($url);
        if (!$parsedUrl || !isset($parsedUrl['host'])) {
            return false;
        }

        $domain = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
        $path = $parsedUrl['path'] ?? '/';

        $rules = $this->getRobotsTxtRules($domain);
        $isAllowed = $this->isPathAllowed($path, $rules, $userAgent);

        $this->logger->info('Robots.txt check', [
            'domain' => $domain,
            'path' => $path,
            'user_agent' => $userAgent,
            'allowed' => $isAllowed,
            'has_robots_txt' => !empty($rules),
        ]);

        return $isAllowed;
    }

    public function getCrawlDelay(string $url, string $userAgent = 'ShopQBot/1.0'): ?float
    {
        $parsedUrl = parse_url($url);
        if (!$parsedUrl || !isset($parsedUrl['host'])) {
            return null;
        }

        $domain = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
        $rules = $this->getRobotsTxtRules($domain);

        $userAgentLower = strtolower($userAgent);
        foreach ($rules as $agent => $agentRules) {
            if ($this->matchesUserAgent($userAgentLower, $agent)) {
                if (isset($agentRules['crawl-delay'])) {
                    return (float) $agentRules['crawl-delay'];
                }
            }
        }

        if (isset($rules['*']['crawl-delay'])) {
            return (float) $rules['*']['crawl-delay'];
        }

        return null;
    }

    private function getRobotsTxtRules(string $domain): array
    {
        if (isset($this->robotsCache[$domain])) {
            return $this->robotsCache[$domain];
        }

        $robotsUrl = $domain . '/robots.txt';

        try {
            $response = $this->httpClient->request('GET', $robotsUrl, [
                'timeout' => 5,
                'headers' => [
                    'User-Agent' => 'ShopQBot/1.0 (+https://shopq.nl/bot)',
                ],
            ]);

            if ($response->getStatusCode() !== 200) {
                $this->robotsCache[$domain] = [];
                return [];
            }

            $content = $response->getContent();
            $rules = $this->parseRobotsTxt($content);

            $this->logger->debug('Fetched robots.txt', [
                'domain' => $domain,
                'rules_count' => count($rules),
            ]);

            $this->robotsCache[$domain] = $rules;
            return $rules;

        } catch (\Exception $e) {
            $this->logger->warning('Failed to fetch robots.txt', [
                'domain' => $domain,
                'error' => $e->getMessage(),
            ]);

            $this->robotsCache[$domain] = [];
            return [];
        }
    }

    private function parseRobotsTxt(string $content): array
    {
        $rules = [];
        $currentAgent = null;

        $lines = explode("\n", $content);

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $commentPos = strpos($line, '#');
            if ($commentPos !== false) {
                $line = trim(substr($line, 0, $commentPos));
            }

            $colonPos = strpos($line, ':');
            if ($colonPos === false) {
                continue;
            }

            $directive = strtolower(trim(substr($line, 0, $colonPos)));
            $value = trim(substr($line, $colonPos + 1));

            if ($directive === 'user-agent') {
                $currentAgent = strtolower($value);
                if (!isset($rules[$currentAgent])) {
                    $rules[$currentAgent] = [
                        'allow' => [],
                        'disallow' => [],
                    ];
                }
            } elseif ($currentAgent !== null) {
                if ($directive === 'allow') {
                    $rules[$currentAgent]['allow'][] = $value;
                } elseif ($directive === 'disallow') {
                    $rules[$currentAgent]['disallow'][] = $value;
                } elseif ($directive === 'crawl-delay') {
                    $rules[$currentAgent]['crawl-delay'] = $value;
                }
            }
        }

        return $rules;
    }

    private function isPathAllowed(string $path, array $rules, string $userAgent): bool
    {
        if (empty($rules)) {
            return true;
        }

        $userAgentLower = strtolower($userAgent);
        $applicableRules = null;

        foreach ($rules as $agent => $agentRules) {
            if ($this->matchesUserAgent($userAgentLower, $agent)) {
                $applicableRules = $agentRules;
                break;
            }
        }

        if ($applicableRules === null && isset($rules['*'])) {
            $applicableRules = $rules['*'];
        }

        if ($applicableRules === null) {
            return true;
        }

        $allowMatch = $this->findBestMatch($path, $applicableRules['allow'] ?? []);
        $disallowMatch = $this->findBestMatch($path, $applicableRules['disallow'] ?? []);

        if ($allowMatch === null && $disallowMatch === null) {
            return true;
        }

        if ($allowMatch !== null && $disallowMatch === null) {
            return true;
        }

        if ($allowMatch === null && $disallowMatch !== null) {
            return strlen($disallowMatch) > 0;
        }

        return strlen($allowMatch) >= strlen($disallowMatch);
    }

    private function matchesUserAgent(string $userAgent, string $pattern): bool
    {
        if ($pattern === '*') {
            return true;
        }

        return str_contains($userAgent, $pattern);
    }

    private function findBestMatch(string $path, array $patterns): ?string
    {
        $bestMatch = null;

        foreach ($patterns as $pattern) {
            if ($this->pathMatchesPattern($path, $pattern)) {
                if ($bestMatch === null || strlen($pattern) > strlen($bestMatch)) {
                    $bestMatch = $pattern;
                }
            }
        }

        return $bestMatch;
    }

    private function pathMatchesPattern(string $path, string $pattern): bool
    {
        if ($pattern === '' || $pattern === '/') {
            return true;
        }

        $regex = preg_quote($pattern, '/');
        $regex = str_replace('\*', '.*', $regex);
        $regex = str_replace('\$', '$', $regex);

        if (!str_ends_with($pattern, '$') && !str_ends_with($pattern, '*')) {
            $regex .= '.*';
        }

        return (bool) preg_match('/^' . $regex . '/', $path);
    }
}
