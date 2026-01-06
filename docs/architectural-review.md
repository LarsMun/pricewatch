# ShopQ - Architectural Review Report

**Review Date:** January 6, 2026
**Reviewer Role:** Lead Developer / System Architect
**Document Version:** 1.0
**Project Status:** Production (Live at shopq.app)

---

## Table of Contents

1. [Executive Summary](#1-executive-summary)
2. [Scope of Review](#2-scope-of-review)
3. [Technology Stack Assessment](#3-technology-stack-assessment)
4. [Security Analysis](#4-security-analysis)
5. [Architectural Analysis](#5-architectural-analysis)
6. [Code Quality Assessment](#6-code-quality-assessment)
7. [Performance Analysis](#7-performance-analysis)
8. [Testing Assessment](#8-testing-assessment)
9. [Infrastructure Review](#9-infrastructure-review)
10. [Database Design Review](#10-database-design-review)
11. [Frontend Architecture Review](#11-frontend-architecture-review)
12. [API Design Review](#12-api-design-review)
13. [Operational Readiness](#13-operational-readiness)
14. [Risk Assessment](#14-risk-assessment)
15. [Recommendations](#15-recommendations)
16. [Implementation Roadmap](#16-implementation-roadmap)
17. [Appendix](#17-appendix)

---

## 1. Executive Summary

### 1.1 Overview

ShopQ is a Dutch price monitoring web application that allows users to track product prices across various webshops. The application detects price changes and sends email/webhook notifications when prices decrease, increase, or when websites become unreachable.

### 1.2 Key Findings

| Category | Rating | Critical Issues | High Issues | Medium Issues |
|----------|--------|-----------------|-------------|---------------|
| Security | ⚠️ Needs Work | 3 | 2 | 4 |
| Architecture | ✅ Acceptable | 0 | 4 | 3 |
| Code Quality | ✅ Acceptable | 0 | 2 | 6 |
| Performance | ⚠️ Needs Work | 0 | 3 | 4 |
| Testing | ⚠️ Needs Work | 0 | 2 | 3 |
| Infrastructure | ⚠️ Needs Work | 1 | 3 | 2 |

### 1.3 Overall Assessment

**Current State:** MVP-grade application suitable for beta testing with limited users.

**Production Readiness:** The application is live but requires immediate attention to critical security issues before handling significant user traffic or sensitive data.

### 1.4 Immediate Actions Required

1. Fix timing attack vulnerability in token comparison
2. Implement rate limiting on authentication endpoints
3. Address SSRF DNS rebinding vulnerability
4. Implement asynchronous processing for scraping operations

---

## 2. Scope of Review

### 2.1 Components Reviewed

| Component | Files Reviewed | Coverage |
|-----------|----------------|----------|
| Backend (Symfony) | 35 PHP files | Full |
| Frontend (React) | 28 TSX files | Full |
| Configuration | 16 YAML files | Full |
| Infrastructure | 8 Docker files | Full |
| Documentation | 6 MD files | Full |

### 2.2 Review Methodology

- Static code analysis
- Architecture pattern assessment
- Security vulnerability scanning
- Performance bottleneck identification
- Best practices comparison against OWASP, Symfony, and React guidelines

### 2.3 Out of Scope

- Penetration testing
- Load testing with production traffic
- Third-party dependency vulnerability scanning (recommend using `composer audit` and `npm audit`)

---

## 3. Technology Stack Assessment

### 3.1 Backend Stack

| Technology | Version | Assessment | Notes |
|------------|---------|------------|-------|
| PHP | 8.3+ | ✅ Excellent | Latest stable, good performance |
| Symfony | 7.2 | ✅ Excellent | Current LTS, well-supported |
| Doctrine ORM | 3.x | ✅ Good | Standard choice |
| MariaDB | 11.2 | ✅ Good | MySQL-compatible, performant |
| JWT Auth | LexikJWT 3.0 | ✅ Good | Industry standard |

**Strengths:**
- Modern PHP version with strong typing support
- Well-established framework with excellent documentation
- Standard authentication pattern

**Concerns:**
- No Redis/Memcached for caching layer
- No message queue for async processing (Messenger configured but underutilized)

### 3.2 Frontend Stack

| Technology | Version | Assessment | Notes |
|------------|---------|------------|-------|
| React | 18.3.1 | ✅ Excellent | Latest stable |
| TypeScript | 5.6.2 | ✅ Excellent | Strong typing |
| Vite | 6.0.1 | ✅ Excellent | Fast build tool |
| TanStack Query | 5.60.0 | ✅ Excellent | Excellent data fetching |
| Tailwind CSS | 3.4.15 | ✅ Good | Utility-first, productive |

**Strengths:**
- Modern tooling with excellent developer experience
- TypeScript provides compile-time safety
- React Query handles caching and synchronization well

**Concerns:**
- No state management beyond React Query and Context
- No form library (manual validation)

### 3.3 Infrastructure Stack

| Technology | Purpose | Assessment |
|------------|---------|------------|
| Docker | Containerization | ✅ Good |
| Docker Compose | Orchestration | ⚠️ Acceptable for small scale |
| Traefik | Reverse Proxy | ✅ Good |
| Let's Encrypt | SSL | ✅ Good |

**Concerns:**
- No Kubernetes for scaling
- No container orchestration beyond Compose
- Single-node deployment

---

## 4. Security Analysis

### 4.1 Critical Vulnerabilities

#### 4.1.1 Timing Attack on Token Comparison

**Severity:** 🔴 Critical
**Location:** `backend/src/Entity/User.php:199-205, 235-243`
**CVSS Score:** 5.3 (Medium)

**Current Code:**
```php
public function isVerificationTokenValid(string $token): bool
{
    if ($this->verificationToken !== $token) {
        return false;
    }
    // ...
}
```

**Vulnerability:** String comparison with `!==` is vulnerable to timing attacks. An attacker can determine token characters by measuring response time differences. With enough requests, tokens can be reconstructed.

**Attack Scenario:**
1. Attacker sends requests with tokens starting with 'a', 'b', 'c'...
2. Correct first character takes slightly longer to compare
3. Repeat for each position until full token is discovered

**Remediation:**
```php
public function isVerificationTokenValid(string $token): bool
{
    if ($this->verificationToken === null) {
        return false;
    }

    if (!hash_equals($this->verificationToken, $token)) {
        return false;
    }

    return $this->verificationExpiresAt !== null
        && $this->verificationExpiresAt > new \DateTimeImmutable();
}
```

**Affected Methods:**
- `User::isVerificationTokenValid()`
- `User::isPasswordResetTokenValid()`

---

#### 4.1.2 SSRF DNS Rebinding Vulnerability

**Severity:** 🔴 Critical
**Location:** `backend/src/Service/UrlValidator.php:52-57`
**CVSS Score:** 7.5 (High)

**Current Code:**
```php
$ip = gethostbyname($host);
if ($ip !== $host) {
    if ($this->isPrivateIp($ip)) {
        throw new \InvalidArgumentException(...);
    }
}
// Later: HTTP client makes request to original $host
```

**Vulnerability:** Time-of-check-to-time-of-use (TOCTOU) race condition. DNS resolution at validation time may differ from resolution at request time.

**Attack Scenario:**
1. Attacker controls DNS for `evil.attacker.com`
2. First DNS query returns `8.8.8.8` (public IP, passes validation)
3. Attacker quickly changes DNS to return `127.0.0.1`
4. HTTP client resolves again, hits localhost
5. Attacker can probe internal services

**Remediation:**
```php
public function validateAndResolve(string $url): string
{
    // ... scheme and format validation ...

    $host = parse_url($url, PHP_URL_HOST);
    $ip = gethostbyname($host);

    if ($this->isPrivateIp($ip)) {
        throw new \InvalidArgumentException('Private IP not allowed');
    }

    // Return URL with IP instead of hostname
    return str_replace($host, $ip, $url);
}
```

Alternative: Use HTTP client with `CURLOPT_RESOLVE` to pin IP address.

---

#### 4.1.3 Missing Rate Limiting on Authentication

**Severity:** 🔴 Critical
**Location:** `backend/config/packages/security.yaml`
**CVSS Score:** 7.5 (High)

**Current State:** No rate limiting on:
- `/api/login` - Brute force attacks possible
- `/api/register` - Account enumeration, resource exhaustion
- `/api/forgot-password` - Email bombing, enumeration

**Impact:**
- Credential stuffing attacks
- Password brute forcing
- Email spam via password reset
- Resource exhaustion via mass registration

**Remediation:**

```yaml
# config/packages/rate_limiter.yaml
framework:
    rate_limiter:
        login_attempt:
            policy: sliding_window
            limit: 5
            interval: '15 minutes'

        registration:
            policy: fixed_window
            limit: 3
            interval: '1 hour'

        password_reset:
            policy: sliding_window
            limit: 3
            interval: '1 hour'
```

```php
// AuthController.php
#[Route('/login', name: 'api_login', methods: ['POST'])]
public function login(
    Request $request,
    RateLimiterFactory $loginLimiter
): JsonResponse {
    $limiter = $loginLimiter->create($request->getClientIp());

    if (!$limiter->consume()->isAccepted()) {
        return $this->json([
            'error' => 'Too many login attempts. Try again later.'
        ], Response::HTTP_TOO_MANY_REQUESTS);
    }
    // ...
}
```

---

### 4.2 High Severity Issues

#### 4.2.1 JWT Token Persistence Without Expiry Handling

**Severity:** 🟠 High
**Location:** `frontend/src/contexts/AuthContext.tsx:23`

**Issue:** JWT tokens stored in localStorage persist indefinitely on client side. No refresh token mechanism exists.

**Risks:**
- Stolen tokens provide permanent access
- No way to invalidate compromised tokens
- XSS attacks can extract tokens

**Remediation:**
1. Implement refresh token rotation
2. Store tokens in httpOnly cookies
3. Validate token expiry client-side
4. Implement token blacklisting for logout

---

#### 4.2.2 Insufficient Password Requirements

**Severity:** 🟠 High
**Location:** `backend/src/Controller/AuthController.php:55-57`

**Current:** Only length >= 8 characters required.

**Missing:**
- Complexity requirements (uppercase, numbers, symbols)
- Common password blacklist
- Breach database checking (haveibeenpwned)

**Remediation:**
```php
#[Assert\PasswordStrength(minScore: PasswordStrength::STRENGTH_MEDIUM)]
#[Assert\NotCompromisedPassword]
private ?string $plainPassword = null;
```

---

### 4.3 Medium Severity Issues

| Issue | Location | Description |
|-------|----------|-------------|
| No CSRF Protection | API endpoints | Stateless JWT mitigates, but cookie-based sessions would be vulnerable |
| Webhook URL SSRF | AuthController.php | Discord/Slack URLs validated by format but redirects not checked |
| Error Message Information Leakage | Various controllers | Some errors reveal implementation details |
| No Security Headers | Response | Missing CSP, X-Frame-Options in API responses |

### 4.4 Security Recommendations Summary

| Priority | Issue | Effort | Impact |
|----------|-------|--------|--------|
| 🔴 P0 | Timing attack fix | 1 hour | Critical |
| 🔴 P0 | Auth rate limiting | 4 hours | Critical |
| 🔴 P0 | SSRF DNS rebinding | 8 hours | Critical |
| 🟠 P1 | JWT refresh tokens | 16 hours | High |
| 🟠 P1 | Password strength | 2 hours | High |
| 🟡 P2 | Security headers | 2 hours | Medium |
| 🟡 P2 | Error sanitization | 4 hours | Medium |

---

## 5. Architectural Analysis

### 5.1 Overall Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                         Client                               │
│  ┌─────────────────────────────────────────────────────┐    │
│  │              React SPA (PWA)                         │    │
│  │  ┌───────────┐ ┌───────────┐ ┌───────────────────┐  │    │
│  │  │   Pages   │ │Components │ │   React Query     │  │    │
│  │  └─────┬─────┘ └─────┬─────┘ └─────────┬─────────┘  │    │
│  │        │             │                 │            │    │
│  │        └─────────────┴────────┬────────┘            │    │
│  │                               │                     │    │
│  │                        ┌──────┴──────┐              │    │
│  │                        │ API Client  │              │    │
│  │                        └──────┬──────┘              │    │
│  └───────────────────────────────┼─────────────────────┘    │
└──────────────────────────────────┼──────────────────────────┘
                                   │ HTTPS
┌──────────────────────────────────┼──────────────────────────┐
│                          Traefik │ (Reverse Proxy)          │
└──────────────────────────────────┼──────────────────────────┘
                                   │
┌──────────────────────────────────┼──────────────────────────┐
│                         Backend  │                          │
│  ┌───────────────────────────────┴───────────────────────┐  │
│  │                  Symfony 7.2 API                       │  │
│  │  ┌────────────┐  ┌────────────┐  ┌────────────────┐   │  │
│  │  │ Controllers│  │  Services  │  │  Repositories  │   │  │
│  │  └──────┬─────┘  └──────┬─────┘  └───────┬────────┘   │  │
│  │         │               │                │            │  │
│  │  ┌──────┴───────────────┴────────────────┴────────┐   │  │
│  │  │              Doctrine ORM                       │   │  │
│  │  └────────────────────────┬───────────────────────┘   │  │
│  └───────────────────────────┼───────────────────────────┘  │
└──────────────────────────────┼──────────────────────────────┘
                               │
┌──────────────────────────────┼──────────────────────────────┐
│                         Database                            │
│                    ┌─────────┴─────────┐                    │
│                    │    MariaDB 11.2   │                    │
│                    └───────────────────┘                    │
└─────────────────────────────────────────────────────────────┘
```

### 5.2 Architectural Patterns

| Pattern | Implementation | Assessment |
|---------|---------------|------------|
| Layered Architecture | Controllers → Services → Repositories | ✅ Good |
| Repository Pattern | Doctrine Repositories | ✅ Good |
| Dependency Injection | Symfony Container | ✅ Good |
| Strategy Pattern | Scraper Engines | ✅ Good |
| Observer Pattern | Not implemented | ⚠️ Missing for events |

### 5.3 High Severity Architectural Issues

#### 5.3.1 Synchronous Scraping Blocks API Requests

**Location:** `backend/src/Controller/ProductWatchController.php:192-196`

**Current Flow:**
```
User clicks "Add Watch"
    → API receives request
    → Creates watch in DB
    → Runs price check SYNCHRONOUSLY (up to 30 seconds)
    → Returns response
```

**Problems:**
1. User waits up to 30 seconds for response
2. PHP worker blocked, reducing throughput
3. HTTP timeout may occur before completion
4. Poor user experience

**Target Flow:**
```
User clicks "Add Watch"
    → API receives request
    → Creates watch in DB
    → Queues async price check
    → Returns response immediately
    → Background worker processes check
    → Frontend polls for result OR receives WebSocket update
```

**Remediation:**
```php
// Create message class
class CheckPriceMessage
{
    public function __construct(public readonly int $watchId) {}
}

// Dispatch instead of direct call
$this->messageBus->dispatch(new CheckPriceMessage($watch->getId()));

return $this->json([
    'message' => 'Watch created, initial check queued',
    'watch' => $this->serializeWatch($watch),
], Response::HTTP_CREATED);
```

---

#### 5.3.2 Blocking Sleep in Scraper Service

**Location:** `backend/src/Service/PriceCheckService.php:77-78`

**Current Code:**
```php
if ($crawlDelay !== null && $crawlDelay > 0) {
    usleep((int) ($crawlDelay * 1000000));
}
```

**Problem:** If robots.txt specifies `Crawl-delay: 10`, the PHP process sleeps for 10 seconds. During batch operations:
- 100 watches × 10 second delay = 1000 seconds blocked
- Worker pool exhaustion
- Memory held during sleep

**Remediation:**
```php
if ($crawlDelay !== null && $crawlDelay > 0) {
    $nextAllowedCheck = new \DateTimeImmutable("+{$crawlDelay} seconds");
    $watch->setNextCheckAt($nextAllowedCheck);

    return $this->createSkippedCheck(
        $watch,
        "Respecting crawl-delay, rescheduled to {$nextAllowedCheck->format('c')}"
    );
}
```

---

#### 5.3.3 Missing Database Transactions

**Location:** `backend/src/Service/PriceCheckService.php:120-121`

**Current Code:**
```php
$this->entityManager->persist($priceCheck);
$this->entityManager->flush();
```

**Problem:** Multiple entities modified without transaction boundary:
1. `$watch` modified (price, failures, timestamps)
2. `$priceCheck` created
3. Potentially `$notification` created

If flush fails partway, database becomes inconsistent.

**Remediation:**
```php
$this->entityManager->wrapInTransaction(function() use ($watch, $priceCheck) {
    $this->entityManager->persist($priceCheck);
    // watch changes flushed automatically
});
```

---

#### 5.3.4 Check-All Endpoint Timeout Risk

**Location:** `backend/src/Controller/ProductWatchController.php:110-126`

**Current Implementation:**
```php
foreach ($watches as $watch) {
    $check = $this->priceCheckService->check($watch);
    // Each check can take up to 30 seconds
}
```

**Problem:**
- User with 20 watches × 5 seconds each = 100 second request
- PHP max_execution_time typically 30-60 seconds
- HTTP proxy timeout typically 60 seconds
- Result: Request fails, no feedback to user

**Remediation:**
```php
#[Route('/check-all', name: 'api_watches_check_all', methods: ['POST'])]
public function checkAll(): JsonResponse
{
    $user = $this->getUser();
    $watches = $this->watchRepository->findActiveByUser($user);

    $batchId = Uuid::v4()->toString();

    foreach ($watches as $watch) {
        $this->messageBus->dispatch(
            new CheckPriceMessage($watch->getId(), $batchId)
        );
    }

    return $this->json([
        'batchId' => $batchId,
        'queued' => count($watches),
        'message' => 'Checks queued. Poll /api/batches/{batchId} for status.'
    ]);
}
```

---

### 5.4 Medium Severity Architectural Issues

#### 5.4.1 N+1 Query Problem in Data Export

**Location:** `backend/src/Controller/AuthController.php:171-215`

```php
foreach ($user->getProductWatches() as $watch) {      // Query 1
    foreach ($watch->getPriceChecks() as $check) {    // Query N
        foreach ($watch->getNotifications() as $n) {   // Query N
```

**Impact:** User with 10 watches, 100 checks each = 21+ queries.

**Remediation:**
```php
// ProductWatchRepository.php
public function findByUserWithHistory(User $user): array
{
    return $this->createQueryBuilder('w')
        ->addSelect('pc', 'n')
        ->leftJoin('w.priceChecks', 'pc')
        ->leftJoin('w.notifications', 'n')
        ->where('w.user = :user')
        ->setParameter('user', $user)
        ->getQuery()
        ->getResult();
}
```

---

#### 5.4.2 No Event-Driven Architecture

**Current:** Direct service calls for side effects.

```php
// When price changes
$this->notificationService->notifyPriceDecrease($watch, $old, $new);
```

**Problem:**
- Tight coupling between price checking and notifications
- Hard to add new side effects (analytics, webhooks, logging)
- No retry mechanism for failed notifications

**Target Architecture:**
```php
// Dispatch event
$this->eventDispatcher->dispatch(new PriceChangedEvent($watch, $old, $new));

// Separate listeners handle side effects
class SendEmailNotificationListener { /* ... */ }
class SendWebhookNotificationListener { /* ... */ }
class LogPriceChangeListener { /* ... */ }
```

---

#### 5.4.3 Missing Circuit Breaker Pattern

**Current:** Every scrape attempt hits external site directly.

**Problem:**
- If site is down, every check fails and waits for timeout
- No backoff for consistently failing domains
- Wastes resources on known-bad requests

**Remediation:**
```php
class DomainCircuitBreaker
{
    public function isOpen(string $domain): bool
    {
        $failures = $this->cache->get("circuit_{$domain}_failures", 0);
        $lastFailure = $this->cache->get("circuit_{$domain}_last");

        if ($failures >= 5 && $lastFailure > time() - 300) {
            return true; // Circuit open, skip requests
        }

        return false;
    }
}
```

---

### 5.5 Architectural Recommendations

| Priority | Improvement | Effort | Impact |
|----------|-------------|--------|--------|
| 🟠 P1 | Async scraping via queue | 16 hours | High |
| 🟠 P1 | Database transactions | 4 hours | High |
| 🟠 P1 | Batch check queuing | 8 hours | High |
| 🟡 P2 | Event-driven notifications | 16 hours | Medium |
| 🟡 P2 | Fix N+1 queries | 4 hours | Medium |
| 🟡 P2 | Circuit breaker | 8 hours | Medium |
| 🟢 P3 | Remove blocking sleep | 2 hours | Medium |

---

## 6. Code Quality Assessment

### 6.1 Positive Observations

1. **Consistent coding style** - PSR-12 followed throughout
2. **Type hints** - PHP 8 features used appropriately
3. **Separation of concerns** - Clear controller/service/repository split
4. **Meaningful names** - Classes and methods are self-documenting
5. **Enum usage** - CheckMethod, NotificationType properly typed

### 6.2 Code Quality Issues

#### 6.2.1 Magic Numbers

**Severity:** 🟡 Medium

**Examples:**
```php
// ProductWatch.php:299
public function hasReachedFailureThreshold(): bool
{
    return $this->consecutiveFailures >= 5;  // Magic number
}

// ProductWatch.php:329
$baseInterval = 12 * 60 * 60;  // Magic number
$jitter = random_int(0, 60 * 60);  // Magic number

// ProductWatchController.php:216
50  // Magic number for history limit
```

**Remediation:**
```php
class ProductWatch
{
    private const FAILURE_THRESHOLD = 5;
    private const CHECK_INTERVAL_HOURS = 12;
    private const JITTER_MAX_MINUTES = 60;

    public function hasReachedFailureThreshold(): bool
    {
        return $this->consecutiveFailures >= self::FAILURE_THRESHOLD;
    }
}
```

---

#### 6.2.2 Validation Logic in Controllers

**Severity:** 🟡 Medium
**Location:** `backend/src/Controller/AuthController.php:55-57`

**Current:**
```php
if (strlen($password) < 8) {
    return $this->json(['error' => '...'], Response::HTTP_BAD_REQUEST);
}
```

**Problem:**
- Validation duplicated across endpoints
- No single source of truth for password rules
- Easy to miss when adding new endpoints

**Remediation:**
```php
// Create DTO with constraints
class RegisterRequest
{
    #[Assert\NotBlank]
    #[Assert\Email]
    public string $email;

    #[Assert\NotBlank]
    #[Assert\Length(min: 8, minMessage: 'Wachtwoord moet minimaal 8 karakters zijn')]
    public string $password;
}

// Controller uses DTO
public function register(
    #[MapRequestPayload] RegisterRequest $request
): JsonResponse {
    // Validation already done by framework
}
```

---

#### 6.2.3 Missing Request/Response DTOs

**Severity:** 🟡 Medium

**Current Pattern:**
```php
$data = json_decode($request->getContent(), true);
$url = $data['url'] ?? null;
$priceSelector = $data['priceSelector'] ?? null;

if (!$url || !$priceSelector) {
    return $this->json(['error' => '...'], Response::HTTP_BAD_REQUEST);
}
```

**Problems:**
- No type safety
- Manual null checking
- Duplicated validation code
- No IDE autocompletion

**Remediation:**
```php
// Request DTO
readonly class CreateWatchRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Url]
        public string $url,

        #[Assert\NotBlank]
        public string $priceSelector,

        public ?string $productName = null,
        public ?string $currency = 'EUR',
    ) {}
}

// Response DTO
readonly class WatchResponse
{
    public function __construct(
        public int $id,
        public string $url,
        public string $domain,
        public ?string $currentPrice,
        // ...
    ) {}

    public static function fromEntity(ProductWatch $watch): self
    {
        return new self(
            id: $watch->getId(),
            url: $watch->getUrl(),
            // ...
        );
    }
}
```

---

#### 6.2.4 Inconsistent Error Response Format

**Severity:** 🟡 Medium

**Current Formats:**
```json
// Format 1
{"error": "Invalid JSON"}

// Format 2
{"errors": {"email": "Invalid email"}}

// Format 3
{"message": "Watch created"}
```

**Remediation - RFC 7807 Problem Details:**
```php
// Custom exception handler
class ApiProblem
{
    public function __construct(
        public string $type,
        public string $title,
        public int $status,
        public ?string $detail = null,
        public ?array $violations = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'type' => $this->type,
            'title' => $this->title,
            'status' => $this->status,
            'detail' => $this->detail,
            'violations' => $this->violations,
        ]);
    }
}

// Usage
return $this->json(
    new ApiProblem(
        type: '/errors/validation',
        title: 'Validation Error',
        status: 400,
        detail: 'The request contains invalid data',
        violations: $errors
    )->toArray(),
    400
);
```

---

#### 6.2.5 Unused Entity Fields

**Severity:** 🟢 Low
**Location:** `backend/src/Entity/ProductWatch.php:67-71`

```php
#[ORM\Column(type: Types::JSON, nullable: true)]
private ?array $parseRuleJson = null;

#[ORM\Column(type: Types::TEXT, nullable: true)]
private ?string $selectorContextHtml = null;
```

**Problem:** Dead code increases cognitive load and migration complexity.

**Remediation:** Create migration to remove unused columns, delete entity properties.

---

#### 6.2.6 Long Methods

**Severity:** 🟢 Low
**Location:** `backend/src/Controller/AuthController.php:164-236` (exportData method)

**Problem:** 70+ line method handling multiple responsibilities.

**Remediation:** Extract to dedicated service:
```php
// ExportService.php
class UserExportService
{
    public function exportUserData(User $user): array
    {
        return [
            'exportedAt' => (new \DateTimeImmutable())->format('c'),
            'user' => $this->serializeUser($user),
            'watches' => $this->serializeWatches($user->getProductWatches()),
        ];
    }
}
```

---

### 6.3 Code Quality Metrics

| Metric | Value | Target | Status |
|--------|-------|--------|--------|
| Average Method Length | ~25 lines | <20 lines | ⚠️ |
| Max Cyclomatic Complexity | ~8 | <10 | ✅ |
| Class Coupling | Medium | Low | ⚠️ |
| Code Duplication | Low | Minimal | ✅ |
| Type Coverage | ~90% | 100% | ✅ |

---

## 7. Performance Analysis

### 7.1 Identified Bottlenecks

#### 7.1.1 Browser Engine Memory Consumption

**Severity:** 🟠 High

**Issue:** Symfony Panther keeps headless Chrome processes. Without explicit cleanup:
```php
$client = $this->panther->createClient();
$client->request('GET', $url);
// Client not explicitly closed
```

**Impact:**
- Memory grows with each request
- Eventually OOM kills worker
- Chrome zombie processes accumulate

**Remediation:**
```php
try {
    $client = $this->panther->createClient();
    return $client->request('GET', $url);
} finally {
    $client->quit();  // Always cleanup
}
```

---

#### 7.1.2 No robots.txt Caching

**Severity:** 🟠 High

**Current Behavior:** Unclear if robots.txt responses are cached.

**Ideal Behavior:**
```php
public function isAllowed(string $url): bool
{
    $domain = parse_url($url, PHP_URL_HOST);

    $rules = $this->cache->get(
        "robots_{$domain}",
        fn() => $this->fetchAndParse($domain),
        3600  // Cache for 1 hour
    );

    return $this->checkRules($rules, $url);
}
```

---

#### 7.1.3 Unbounded Price History

**Severity:** 🟡 Medium

**Current:** All price checks stored forever.

**Impact:**
- 2 checks/day × 365 days × 1000 watches = 730,000 rows/year
- Query performance degrades
- Storage costs increase

**Remediation:**
```php
// Scheduled command to prune old data
public function pruneOldChecks(int $daysToKeep = 90): int
{
    $threshold = new \DateTimeImmutable("-{$daysToKeep} days");

    return $this->createQueryBuilder('pc')
        ->delete()
        ->where('pc.checkedAt < :threshold')
        ->setParameter('threshold', $threshold)
        ->getQuery()
        ->execute();
}
```

---

#### 7.1.4 Missing Database Indexes

**Severity:** 🟡 Medium

**Missing Indexes:**
```php
// PriceCheck entity - frequently queried by productWatch + date
#[ORM\Index(columns: ['product_watch_id', 'checked_at'], name: 'idx_check_history')]

// Notification entity - queried for recent notifications
#[ORM\Index(columns: ['sent_at'], name: 'idx_notification_date')]

// User entity - token lookups
#[ORM\Index(columns: ['verification_token'], name: 'idx_verification_token')]
#[ORM\Index(columns: ['password_reset_token'], name: 'idx_reset_token')]
```

---

### 7.2 Performance Recommendations

| Priority | Improvement | Expected Impact |
|----------|-------------|-----------------|
| 🟠 P1 | Browser cleanup | Prevent OOM crashes |
| 🟠 P1 | robots.txt caching | 50% reduction in external requests |
| 🟡 P2 | Add missing indexes | 10x faster queries |
| 🟡 P2 | Price history pruning | Stable storage growth |
| 🟢 P3 | Query result caching | Reduced DB load |

---

## 8. Testing Assessment

### 8.1 Current Test Coverage

| Category | Tests | Coverage | Assessment |
|----------|-------|----------|------------|
| Unit Tests | 110 | ~70% services | ✅ Good |
| Integration Tests | 17 | Controllers | ✅ Good |
| E2E Tests | 0 | None | 🔴 Missing |
| Performance Tests | 0 | None | 🔴 Missing |

### 8.2 Test Gaps

#### 8.2.1 Missing End-to-End Tests

**Impact:** No verification that full user flows work correctly.

**Needed Tests:**
```typescript
// Cypress/Playwright tests
describe('User Registration Flow', () => {
  it('should register, verify email, and create first watch', () => {
    cy.visit('/register')
    cy.get('[name=email]').type('test@example.com')
    cy.get('[name=password]').type('securepassword123')
    cy.get('[type=submit]').click()

    // Check email (via Mailhog API)
    cy.verifyEmailLink()

    // Create watch
    cy.visit('/dashboard')
    cy.get('[data-testid=add-watch]').click()
    // ...
  })
})
```

---

#### 8.2.2 Missing Command Tests

**Location:** `backend/src/Command/CheckPricesCommand.php`

**Needed:**
```php
class CheckPricesCommandTest extends KernelTestCase
{
    public function testCommandProcessesDueWatches(): void
    {
        // Arrange: Create watch with past nextCheckAt
        // Act: Run command
        // Assert: Watch was checked
    }

    public function testCommandRespectsLimit(): void
    {
        // Arrange: Create 10 due watches
        // Act: Run command with --limit=5
        // Assert: Only 5 processed
    }

    public function testCommandHandlesFailuresGracefully(): void
    {
        // Arrange: Create watch with unreachable URL
        // Act: Run command
        // Assert: No exception, failure recorded
    }
}
```

---

#### 8.2.3 Missing Concurrency Tests

**Issue:** No tests for race conditions:
- Two requests updating same watch
- Simultaneous check-all from same user
- Token regeneration during validation

---

### 8.3 Testing Recommendations

| Priority | Improvement | Effort |
|----------|-------------|--------|
| 🟠 P1 | Add E2E test suite | 24 hours |
| 🟠 P1 | Command tests | 8 hours |
| 🟡 P2 | Concurrency tests | 8 hours |
| 🟡 P2 | Performance benchmarks | 8 hours |
| 🟢 P3 | Mutation testing | 4 hours |

---

## 9. Infrastructure Review

### 9.1 Current Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    VPS (Transip X1)                         │
│                    850MB RAM + 2GB Swap                     │
│  ┌───────────────────────────────────────────────────────┐  │
│  │                     Traefik                            │  │
│  │              (Reverse Proxy + SSL)                     │  │
│  └──────────────────┬────────────────────────────────────┘  │
│                     │                                       │
│  ┌──────────────────┼────────────────────────────────────┐  │
│  │                  │    Docker Network                   │  │
│  │   ┌──────────────┴──────────────┐                     │  │
│  │   │                             │                     │  │
│  │ ┌─┴─────────┐  ┌───────────┐  ┌─┴─────────┐          │  │
│  │ │ Frontend  │  │    API    │  │ Scheduler │          │  │
│  │ │  (nginx)  │  │(PHP/Apache│  │  (cron)   │          │  │
│  │ └───────────┘  └─────┬─────┘  └───────────┘          │  │
│  │                      │                                │  │
│  │               ┌──────┴──────┐                         │  │
│  │               │   MariaDB   │                         │  │
│  │               │             │                         │  │
│  │               └─────────────┘                         │  │
│  └───────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
```

### 9.2 Infrastructure Issues

#### 9.2.1 Single Point of Failure

**Severity:** 🔴 Critical

**Issue:** Every component is single-instance:
- Single database (no replica)
- Single API server
- Single scheduler
- No failover

**Impact:** Any component failure = complete outage.

**Remediation:**

Short-term:
- Database replication (MariaDB replica)
- Health check monitoring (UptimeRobot, Healthchecks.io)

Long-term:
- Move to managed Kubernetes (DigitalOcean, Linode)
- Database on managed service (PlanetScale, AWS RDS)
- Multiple API replicas behind load balancer

---

#### 9.2.2 No Automated Backups

**Severity:** 🟠 High

**Issue:** No documented backup strategy.

**Required Backups:**
1. Database (daily full, hourly incremental)
2. JWT keys (encrypted, off-site)
3. Environment configuration

**Remediation:**
```bash
#!/bin/bash
# /opt/backups/backup.sh

# Database backup
docker exec shopq-db mariadb-dump -u root -p$DB_PASSWORD shopq | \
  gzip > /backups/db_$(date +%Y%m%d_%H%M%S).sql.gz

# Upload to S3/B2
rclone copy /backups remote:shopq-backups --max-age 1h

# Cleanup old backups (keep 7 days)
find /backups -name "*.gz" -mtime +7 -delete
```

---

#### 9.2.3 No Resource Limits

**Severity:** 🟠 High

**Issue:** Containers have no resource constraints in docker-compose.

**Risk:**
- Browser engine can consume all RAM
- Runaway process can crash entire server
- No isolation between services

**Remediation:**
```yaml
# docker-compose.prod.yml
services:
  api:
    deploy:
      resources:
        limits:
          cpus: '1.0'
          memory: 512M
        reservations:
          memory: 256M

  scheduler:
    deploy:
      resources:
        limits:
          cpus: '0.5'
          memory: 256M
```

---

#### 9.2.4 Secrets in Environment Variables

**Severity:** 🟡 Medium

**Issue:** Sensitive data in plain environment variables:
- Database password
- JWT passphrase
- Sentry DSN

**Better Approach:**
```yaml
# docker-compose.prod.yml
services:
  api:
    secrets:
      - db_password
      - jwt_passphrase

secrets:
  db_password:
    file: ./secrets/db_password.txt
  jwt_passphrase:
    file: ./secrets/jwt_passphrase.txt
```

---

### 9.3 Infrastructure Recommendations

| Priority | Improvement | Effort | Cost Impact |
|----------|-------------|--------|-------------|
| 🔴 P0 | Automated backups | 4 hours | ~$5/month |
| 🟠 P1 | Resource limits | 2 hours | None |
| 🟠 P1 | Monitoring/alerting | 4 hours | Free-$10/month |
| 🟡 P2 | Database replica | 8 hours | ~$20/month |
| 🟡 P2 | Secret management | 8 hours | None |
| 🟢 P3 | Multi-node deployment | 40 hours | ~$50/month |

---

## 10. Database Design Review

### 10.1 Schema Assessment

```
┌──────────────┐     ┌─────────────────┐     ┌──────────────┐
│     user     │     │  product_watch  │     │ price_check  │
├──────────────┤     ├─────────────────┤     ├──────────────┤
│ id (PK)      │────<│ user_id (FK)    │────<│ watch_id(FK) │
│ email        │     │ id (PK)         │     │ id (PK)      │
│ password     │     │ url             │     │ price        │
│ roles        │     │ domain          │     │ raw_text     │
│ is_verified  │     │ product_name    │     │ was_success  │
│ verif_token  │     │ price_selector  │     │ http_status  │
│ verif_exp    │     │ current_price   │     │ duration_ms  │
│ reset_token  │     │ previous_price  │     │ error_msg    │
│ reset_exp    │     │ original_price  │     │ checked_at   │
│ discord_url  │     │ check_method    │     └──────────────┘
│ slack_url    │     │ is_active       │
│ created_at   │     │ failures        │     ┌──────────────┐
└──────────────┘     │ next_check_at   │────<│ notification │
                     │ last_checked_at │     ├──────────────┤
                     │ created_at      │     │ id (PK)      │
                     └─────────────────┘     │ watch_id(FK) │
                                             │ old_price    │
                                             │ new_price    │
                                             │ type         │
                                             │ sent_at      │
                                             └──────────────┘
```

### 10.2 Positive Aspects

1. **Proper foreign keys** with CASCADE delete
2. **Appropriate indexes** on frequently queried columns
3. **Immutable timestamps** using DateTimeImmutable
4. **Normalized structure** - no data duplication

### 10.3 Issues

#### 10.3.1 Missing Composite Indexes

```sql
-- Current: Single-column index
INDEX idx_next_check (next_check_at)

-- Better: Composite for common query
INDEX idx_active_next_check (is_active, next_check_at)

-- Query this optimizes:
SELECT * FROM product_watch
WHERE is_active = 1 AND next_check_at <= NOW()
```

---

#### 10.3.2 No Soft Deletes

**Issue:** Hard deletes make audit trails impossible.

**Recommendation:** Add `deleted_at` column for soft deletes on user and watch tables.

---

#### 10.3.3 Price as String

**Issue:** Prices stored as `DECIMAL(10,2)` but typed as `?string` in PHP.

```php
#[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
private ?string $currentPrice = null;
```

**Problem:** Doctrine DECIMAL maps to string, but this leads to comparison issues:
```php
if ($newPrice === $oldPrice)  // String comparison: "10.00" !== "10"
```

**Recommendation:** Either:
1. Use float with proper epsilon comparison
2. Store as integer cents
3. Use a Money value object

---

### 10.4 Schema Recommendations

| Priority | Change | Migration Required |
|----------|--------|-------------------|
| 🟡 P2 | Add composite indexes | Yes |
| 🟡 P2 | Add soft deletes | Yes |
| 🟢 P3 | Price value object | Yes |
| 🟢 P3 | Remove unused columns | Yes |

---

## 11. Frontend Architecture Review

### 11.1 Component Structure

```
src/
├── api/
│   └── client.ts           # HTTP client
├── components/
│   ├── AddWatchModal.tsx   # Watch creation wizard
│   ├── Footer.tsx          # Global footer
│   ├── ProtectedRoute.tsx  # Auth guard
│   ├── VerificationBanner.tsx
│   └── WatchList.tsx       # Watch grid
├── contexts/
│   └── AuthContext.tsx     # Authentication state
├── hooks/
│   └── useWatches.ts       # React Query hooks
├── pages/
│   └── [14 page components]
└── types/
    └── index.ts            # TypeScript interfaces
```

### 11.2 Positive Aspects

1. **React Query** for server state - excellent choice
2. **TypeScript** throughout - type safety
3. **Protected routes** pattern - clean auth flow
4. **Context for global state** - appropriate for auth

### 11.3 Issues

#### 11.3.1 No Form Library

**Current:**
```typescript
const [email, setEmail] = useState('')
const [password, setPassword] = useState('')
const [error, setError] = useState('')

const handleSubmit = async (e: FormEvent) => {
  e.preventDefault()
  if (!email || !password) {
    setError('All fields required')
    return
  }
  // ...
}
```

**Problems:**
- Manual validation
- No field-level errors
- No dirty/touched tracking
- Boilerplate in every form

**Recommendation:** Use React Hook Form:
```typescript
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'

const schema = z.object({
  email: z.string().email(),
  password: z.string().min(8),
})

function LoginForm() {
  const { register, handleSubmit, formState: { errors } } = useForm({
    resolver: zodResolver(schema)
  })

  return (
    <form onSubmit={handleSubmit(onSubmit)}>
      <input {...register('email')} />
      {errors.email && <span>{errors.email.message}</span>}
    </form>
  )
}
```

---

#### 11.3.2 Token Stored in localStorage

**Severity:** 🟠 High

**Issue:** XSS attack can steal auth token.

```typescript
localStorage.setItem(TOKEN_KEY, response.token)
```

**Remediation Options:**
1. **HttpOnly cookies** (recommended) - requires backend changes
2. **In-memory only** - lost on refresh, needs refresh token
3. **Token rotation** - shorter lifespan reduces window

---

#### 11.3.3 No Error Boundary

**Issue:** Unhandled errors crash entire app.

```typescript
// main.tsx - Add error boundary
import * as Sentry from '@sentry/react'

const SentryErrorBoundary = Sentry.withErrorBoundary(App, {
  fallback: <ErrorFallback />,
  showDialog: true,
})
```

---

#### 11.3.4 No Loading States for Navigation

**Issue:** Page transitions have no feedback.

**Recommendation:** Use React Router's loading states or add global loading indicator.

---

### 11.4 Frontend Recommendations

| Priority | Improvement | Effort |
|----------|-------------|--------|
| 🟠 P1 | Form library | 8 hours |
| 🟠 P1 | Secure token storage | 16 hours |
| 🟡 P2 | Error boundaries | 4 hours |
| 🟡 P2 | Loading states | 4 hours |
| 🟢 P3 | Component documentation | 8 hours |

---

## 12. API Design Review

### 12.1 RESTful Compliance

| Aspect | Status | Notes |
|--------|--------|-------|
| Resource naming | ✅ Good | `/api/watches`, `/api/watches/{id}` |
| HTTP methods | ✅ Good | GET, POST, PATCH, DELETE used correctly |
| Status codes | ⚠️ Partial | Some errors return 400 when 422 appropriate |
| HATEOAS | ❌ Missing | No hypermedia links in responses |
| Versioning | ❌ Missing | No API version prefix |

### 12.2 Issues

#### 12.2.1 No API Versioning

**Current:** `/api/watches`

**Recommended:** `/api/v1/watches`

**Rationale:** Breaking changes to existing clients become impossible without versioning.

---

#### 12.2.2 Inconsistent Response Structure

**Current responses vary:**
```json
// Success
{"message": "Watch created", "watch": {...}}

// List
{"watches": [...], "total": 10}

// Error
{"error": "Invalid JSON"}
```

**Recommended standard:**
```json
// Success (single)
{
  "data": {...},
  "meta": {"timestamp": "..."}
}

// Success (list)
{
  "data": [...],
  "meta": {"total": 10, "page": 1, "pageSize": 20}
}

// Error
{
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Invalid input",
    "details": [...]
  }
}
```

---

#### 12.2.3 No Pagination on List Endpoints

**Current:** `/api/watches` returns all watches.

**Problem:** User with 100+ watches gets massive response.

**Recommendation:**
```
GET /api/watches?page=1&pageSize=20&sort=-createdAt
```

Response:
```json
{
  "data": [...],
  "meta": {
    "page": 1,
    "pageSize": 20,
    "total": 150,
    "totalPages": 8
  }
}
```

---

### 12.3 API Recommendations

| Priority | Improvement | Effort |
|----------|-------------|--------|
| 🟠 P1 | Add API versioning | 4 hours |
| 🟠 P1 | Standardize responses | 8 hours |
| 🟡 P2 | Add pagination | 8 hours |
| 🟢 P3 | Add HATEOAS links | 8 hours |

---

## 13. Operational Readiness

### 13.1 Monitoring & Observability

| Component | Status | Tool |
|-----------|--------|------|
| Error tracking | ✅ | Sentry |
| Application logs | ⚠️ Partial | Docker logs (not aggregated) |
| Metrics | ❌ Missing | None |
| Uptime monitoring | ❌ Missing | None |
| Distributed tracing | ❌ Missing | None |

### 13.2 Required Operational Capabilities

#### 13.2.1 Centralized Logging

**Current:** Logs in individual containers, lost on restart.

**Recommendation:**
- ELK Stack (self-hosted)
- Loki + Grafana
- CloudWatch/Datadog (managed)

---

#### 13.2.2 Application Metrics

**Missing Metrics:**
- Request rate
- Response time percentiles
- Error rate
- Database query time
- Scraper success rate
- Queue depth

**Recommendation:** Prometheus + Grafana

---

#### 13.2.3 Alerting

**Required Alerts:**
- Service down (5xx errors spike)
- Database connection failures
- Memory threshold exceeded
- Scraper failure rate > 50%
- SSL certificate expiry warning

---

### 13.3 Documentation Status

| Document | Status | Notes |
|----------|--------|-------|
| Technical docs | ✅ | Comprehensive |
| API docs | ✅ | Swagger/OpenAPI |
| Deployment guide | ✅ | Exists |
| Runbook | ❌ Missing | No operational procedures |
| Architecture decision records | ❌ Missing | No ADRs |

### 13.4 Operational Recommendations

| Priority | Improvement | Effort |
|----------|-------------|--------|
| 🟠 P1 | Uptime monitoring | 1 hour |
| 🟠 P1 | Create runbook | 8 hours |
| 🟡 P2 | Centralized logging | 16 hours |
| 🟡 P2 | Application metrics | 16 hours |
| 🟢 P3 | Distributed tracing | 24 hours |

---

## 14. Risk Assessment

### 14.1 Risk Matrix

| Risk | Likelihood | Impact | Score | Mitigation |
|------|------------|--------|-------|------------|
| Security breach via timing attack | Medium | High | 🔴 High | Implement hash_equals |
| Service outage (single point of failure) | Medium | High | 🔴 High | Add redundancy |
| Data loss (no backups) | Low | Critical | 🔴 High | Implement backups |
| Performance degradation under load | High | Medium | 🟠 Medium | Async processing |
| Brute force attack | High | Medium | 🟠 Medium | Rate limiting |
| Third-party site blocks scraper | High | Low | 🟡 Low | Already has fallback |

### 14.2 Business Risks

| Risk | Description | Mitigation |
|------|-------------|------------|
| Legal/GDPR | User data handling | Already implements export/delete |
| Robots.txt violations | Sites blocking bot | Already checks robots.txt |
| Resource exhaustion | Unlimited watches | Implement per-user limits |

---

## 15. Recommendations

### 15.1 Summary by Priority

#### 🔴 Critical (Do Immediately)

1. **Fix timing attack vulnerability**
   - File: `User.php`
   - Change: Use `hash_equals()` for token comparison
   - Effort: 1 hour

2. **Add authentication rate limiting**
   - Files: `rate_limiter.yaml`, `AuthController.php`
   - Change: Limit login/register/reset attempts
   - Effort: 4 hours

3. **Fix SSRF DNS rebinding**
   - File: `UrlValidator.php`
   - Change: Pin resolved IP or resolve at request time
   - Effort: 8 hours

4. **Implement automated backups**
   - New: Backup script + cron
   - Effort: 4 hours

#### 🟠 High Priority (Do This Week)

5. **Async initial price check**
   - Files: `ProductWatchController.php`, new Message class
   - Change: Queue check instead of blocking
   - Effort: 16 hours

6. **Add database transactions**
   - File: `PriceCheckService.php`
   - Change: Wrap related changes in transaction
   - Effort: 4 hours

7. **Queue check-all endpoint**
   - File: `ProductWatchController.php`
   - Change: Dispatch messages instead of synchronous loop
   - Effort: 8 hours

8. **Add resource limits to containers**
   - File: `docker-compose.prod.yml`
   - Change: Add memory/CPU limits
   - Effort: 2 hours

#### 🟡 Medium Priority (Do This Month)

9. **Implement API versioning** - 4 hours
10. **Standardize error responses** - 8 hours
11. **Add form library to frontend** - 8 hours
12. **Create operational runbook** - 8 hours
13. **Add E2E test suite** - 24 hours
14. **Fix N+1 query in export** - 4 hours
15. **Add missing database indexes** - 2 hours

#### 🟢 Low Priority (Backlog)

16. Remove unused entity fields
17. Extract request/response DTOs
18. Add centralized logging
19. Implement soft deletes
20. Add HATEOAS to API responses

---

## 16. Implementation Roadmap

### 16.1 Phase 1: Security Hardening (Week 1)

```
Day 1-2: Critical Security Fixes
├── Fix timing attack (1 hour)
├── Add auth rate limiting (4 hours)
└── Fix SSRF DNS rebinding (8 hours)

Day 3-4: Infrastructure Security
├── Implement automated backups (4 hours)
├── Add resource limits (2 hours)
└── Add uptime monitoring (1 hour)

Day 5: Testing & Verification
├── Security testing
└── Documentation update
```

### 16.2 Phase 2: Performance & Reliability (Week 2-3)

```
Week 2:
├── Async initial price check (16 hours)
├── Queue check-all endpoint (8 hours)
└── Database transactions (4 hours)

Week 3:
├── Fix N+1 queries (4 hours)
├── Add database indexes (2 hours)
├── robots.txt caching (4 hours)
└── Browser engine cleanup (4 hours)
```

### 16.3 Phase 3: Code Quality & API (Week 4)

```
├── API versioning (4 hours)
├── Standardize error responses (8 hours)
├── Request/Response DTOs (8 hours)
└── Remove dead code (4 hours)
```

### 16.4 Phase 4: Testing & Operations (Week 5-6)

```
Week 5:
├── E2E test suite (24 hours)
├── Command tests (8 hours)
└── Performance benchmarks (8 hours)

Week 6:
├── Operational runbook (8 hours)
├── Centralized logging (16 hours)
└── Application metrics (16 hours)
```

---

## 17. Appendix

### 17.1 Files Reviewed

```
Backend (35 files):
- src/Command/CheckPricesCommand.php
- src/Command/TestScrapeCommand.php
- src/Controller/AdminController.php
- src/Controller/AuthController.php
- src/Controller/BookmarkletController.php
- src/Controller/HealthController.php
- src/Controller/ProductWatchController.php
- src/Entity/Notification.php
- src/Entity/PriceCheck.php
- src/Entity/ProductWatch.php
- src/Entity/User.php
- src/Enum/CheckMethod.php
- src/Enum/NotificationType.php
- src/Repository/NotificationRepository.php
- src/Repository/PriceCheckRepository.php
- src/Repository/ProductWatchRepository.php
- src/Repository/UserRepository.php
- src/Scraper/BrowserEngine.php
- src/Scraper/HttpEngine.php
- src/Scraper/ImageExtractor.php
- src/Scraper/PriceExtractor.php
- src/Scraper/ScrapeEngineInterface.php
- src/Service/DomainRateLimiter.php
- src/Service/EmailVerificationService.php
- src/Service/NotificationService.php
- src/Service/PasswordResetService.php
- src/Service/PriceCheckService.php
- src/Service/RobotsTxtChecker.php
- src/Service/UrlAnalyzerService.php
- src/Service/UrlValidator.php
- src/Service/WebhookService.php
- config/packages/security.yaml
- config/packages/rate_limiter.yaml
- config/packages/doctrine.yaml
- config/services.yaml

Frontend (28 files):
- src/api/client.ts
- src/components/*.tsx (6 files)
- src/contexts/AuthContext.tsx
- src/hooks/useWatches.ts
- src/pages/*.tsx (14 files)
- src/types/index.ts
- vite.config.ts

Infrastructure (8 files):
- docker-compose.yml
- docker-compose.prod.yml
- docker/php/Dockerfile
- docker/php/Dockerfile.prod
- docker/nginx/Dockerfile.frontend
- docker/nginx/Dockerfile.prod
- docker/nginx/nginx.prod.conf
- deploy.sh
```

### 17.2 Security References

- OWASP Top 10: https://owasp.org/Top10/
- OWASP SSRF Prevention: https://cheatsheetseries.owasp.org/cheatsheets/Server_Side_Request_Forgery_Prevention_Cheat_Sheet.html
- Symfony Security: https://symfony.com/doc/current/security.html
- JWT Best Practices: https://datatracker.ietf.org/doc/html/rfc8725

### 17.3 Glossary

| Term | Definition |
|------|------------|
| SSRF | Server-Side Request Forgery - attack where server makes requests to unintended locations |
| TOCTOU | Time-of-check-to-time-of-use - race condition between validation and use |
| JWT | JSON Web Token - compact, URL-safe means of representing claims |
| N+1 Query | Database anti-pattern where N additional queries are made for N items |
| Circuit Breaker | Pattern that prevents cascading failures by short-circuiting failing operations |

---

**Document prepared by:** Architectural Review
**Review date:** January 6, 2026
**Next review:** After Phase 1 implementation
