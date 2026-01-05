# ShopQ - Setup Voortgang

> Laatst bijgewerkt: 2026-01-05

## Huidige Status: LIVE IN PRODUCTIE 🚀

ShopQ is volledig live en operationeel op productie-servers.

---

## Live Omgeving

| Component | URL | Status |
|-----------|-----|--------|
| Frontend | https://shopq.app | ✅ Live |
| API | https://api.shopq.app | ✅ Live |
| API Docs | https://api.shopq.app/api/doc | ✅ Live |
| Health Check | https://api.shopq.app/api/health | ✅ Live |

### VPS Specificaties
- **Provider**: Transip BladeVPS X1
- **IP**: 149.210.215.153
- **OS**: Ubuntu/Debian
- **RAM**: ~850 MB + 2GB swap
- **SSL**: Let's Encrypt (auto-renew via Traefik)

---

## Development Services (Lokaal)

| Service | Container | Poort | URL |
|---------|-----------|-------|-----|
| Backend (Symfony 7.4) | pricewatch-php | 8100 | http://localhost:8100 |
| Frontend (React 18) | pricewatch-frontend | 3100 | http://localhost:3100 |
| Database (MariaDB 11.2) | pricewatch-mariadb | 13307 | - |
| Mailhog (email testing) | pricewatch-mailhog | 11025 (SMTP) / 18025 (Web) | http://localhost:18025 |

---

## Wat Is Gedaan

### Fase 0: Docker Setup
- [x] `docker-compose.yml` met 4 services (mariadb, php, frontend, mailhog)
- [x] `docker/php/Dockerfile` - PHP 8.3 + Apache + extensions
- [x] `docker/php/php.ini` - PHP configuratie voor development
- [x] `docker/nginx/Dockerfile.frontend` - Node 20 Alpine voor Vite dev server
- [x] Netwerk: `pricewatch-network` voor inter-container communicatie
- [x] Volumes voor persistente data (mariadb, vendor, node_modules)
- [x] Apache AllowOverride + .htaccess voor Symfony routing

### Fase 1: Authenticatie (Compleet) ✅

#### Backend API Endpoints
| Endpoint | Method | Auth | Beschrijving |
|----------|--------|------|--------------|
| `/api/register` | POST | - | Registreer nieuwe gebruiker + stuurt verificatie email |
| `/api/login` | POST | - | Login, retourneert JWT token |
| `/api/me` | GET | JWT | Huidige gebruiker info |
| `/api/me` | DELETE | JWT | Account verwijderen (GDPR) |
| `/api/me/export` | GET | JWT | Data exporteren (GDPR) |
| `/api/verify-email` | POST | - | Verifieer email met token |
| `/api/resend-verification` | POST | JWT | Verstuur verificatie email opnieuw |
| `/api/forgot-password` | POST | - | Wachtwoord reset aanvragen |
| `/api/reset-password` | POST | - | Nieuw wachtwoord instellen met token |

#### Frontend Auth
- [x] `AuthContext` - Token opslag (localStorage), user state, multi-tab sync
- [x] `ProtectedRoute` - Redirect naar /login als niet ingelogd
- [x] `LoginPage` - Werkende login met error handling + "Wachtwoord vergeten?" link
- [x] `RegisterPage` - Werkende registratie met validatie + ToS checkbox + verificatie melding
- [x] `DashboardPage` - Watch overzicht met grid layout
- [x] `VerifyEmailPage` - Verificatie link handling
- [x] `VerificationBanner` - Waarschuwing voor niet-geverifieerde gebruikers
- [x] `ForgotPasswordPage` - Email input met success state
- [x] `ResetPasswordPage` - Nieuw wachtwoord instellen met token validatie

### Fase 2: Scraping Core (Compleet) ✅

#### ProductWatch CRUD API
| Endpoint | Method | Auth | Beschrijving |
|----------|--------|------|--------------|
| `/api/watches` | GET | JWT | Lijst van user's watches |
| `/api/watches` | POST | JWT | Nieuwe watch aanmaken |
| `/api/watches/{id}` | GET | JWT | Watch detail + prijshistorie |
| `/api/watches/{id}` | PATCH | JWT | Update watch |
| `/api/watches/{id}` | DELETE | JWT | Verwijder watch |
| `/api/watches/analyze` | POST | JWT | URL analyseren voor auto-detectie |
| `/api/watches/check-all` | POST | JWT | Alle watches direct checken |
| `/api/watches/validate` | POST | - | Selector valideren op URL |
| `/api/bookmarklet.js` | GET | - | Bookmarklet JavaScript |

#### Scraper Services
- [x] `ScrapeEngineInterface` - Contract voor scrape engines
- [x] `HttpEngine` - Fetch pages via Symfony HttpClient
- [x] `BrowserEngine` - Headless Chrome via Symfony Panther
- [x] `PriceExtractor` - CSS selector + JSON-LD → prijs parsing
- [x] `ImageExtractor` - Product afbeelding extractie
- [x] `PriceCheckService` - Business logic, debounce, failure tracking
- [x] `UrlAnalyzerService` - Auto-detectie van product info
- [x] `NotificationService` - Email notificaties versturen
- [x] `RobotsTxtChecker` - robots.txt compliance
- [x] `DomainRateLimiter` - Per-domein rate limiting

#### CLI Commands
```bash
# Test scraper met URL + selector
docker exec shopq-php php bin/console app:test-scrape "https://example.com" ".price"

# Check alle due watches
docker exec shopq-php php bin/console app:check-prices

# Check specifieke watch
docker exec shopq-php php bin/console app:check-prices --watch=1
```

---

## Voltooide Fases

### Fase 2b: Rate Limiting & Compliance ✅
- [x] Per-domain throttling (10 req/uur via DomainRateLimiter)
- [x] robots.txt compliance checking (RobotsTxtChecker)
- [x] Crawl-delay respect

### Fase 3: Notificaties ✅
- [x] `NotificationService`
- [x] Email templates (price_decrease, price_increase, site_broken)
- [x] Debounce logic (voorkomt spam bij flapping prices)
- [x] Mailhog integratie voor development

### Fase 4: Frontend Uitbreiding ✅
- [x] Watch list view met echte data (WatchList component)
- [x] Watch detail + prijshistorie (WatchDetailPage)
- [x] Add watch wizard (AddWatchModal)
- [x] Privacy Policy, Terms of Service, Contact pagina's
- [x] Footer met juridische links

### Fase 5: Bookmarklet ✅
- [x] Bookmarklet JavaScript code (BookmarkletController)
- [x] Selector generatie logic
- [x] `/api/watches/validate` endpoint
- [x] BookmarkletPage met installatie-instructies

### Fase 6: Browser Engine ✅
- [x] `BrowserEngine` met Symfony Panther (headless Chrome)
- [x] Auto-fallback voor JavaScript-rendered sites

### Fase 7: Email Verificatie ✅
- [x] `EmailVerificationService` - Token generatie, verificatie, email verzending
- [x] `verification.html.twig` - Email template voor verificatie
- [x] User entity uitgebreid met `verificationToken` en `verificationExpiresAt`
- [x] Verificatie endpoints (`/api/verify-email`, `/api/resend-verification`)
- [x] Onverifieerde gebruikers kunnen geen watches aanmaken
- [x] Frontend `VerifyEmailPage` voor verificatie link handling
- [x] Frontend `VerificationBanner` met resend functionaliteit
- [x] Multi-tab authenticatie synchronisatie
- [x] React Query cache clearing bij logout

### Fase 8: Wachtwoord Reset ✅
- [x] `PasswordResetService` - Token generatie, validatie, password reset
- [x] `password_reset.html.twig` - Email template met reset button
- [x] User entity uitgebreid met `passwordResetToken` en `passwordResetExpiresAt`
- [x] Reset endpoints (`/api/forgot-password`, `/api/reset-password`)
- [x] 1 uur token expiry (korter dan verificatie voor security)
- [x] Security: altijd success response (voorkomt email enumeration)
- [x] Frontend `ForgotPasswordPage` - email input met success state
- [x] Frontend `ResetPasswordPage` - nieuw wachtwoord instellen met token
- [x] "Wachtwoord vergeten?" link op LoginPage

### Fase 9: Pre-Launch Security & Quality Fixes ✅
- [x] SSRF Protection via `UrlValidator` service
  - Blokkeert localhost, 127.0.0.1, private IP ranges (10.x, 172.16-31.x, 192.168.x)
  - Blokkeert non-HTTP schemes (file://, ftp://, etc.)
  - DNS rebinding bescherming (resolved IPs ook gecheckt)
- [x] Rate limiting op `/api/watches/validate` endpoint (10/minuut per IP)
- [x] User-level rate limiting op `/api/watches/check-all` (1x per 15 minuten)
- [x] `lastErrorMessage` veld op ProductWatch voor debugging in UI
- [x] Automatische engine fallback (HTTP → Browser bij 403/429)
  - Retry met headless browser als HTTP geblokkeerd wordt
  - Watch wordt automatisch omgezet naar browser engine bij succes

### Fase 10: Production Readiness ✅

#### CI/CD & Testing
- [x] GitHub Actions workflow (`.github/workflows/ci.yml`)
  - Backend: PHPUnit tests met PHP 8.3
  - Frontend: npm build + lint met Node 20
  - Automatisch bij push/PR naar main
- [x] 127 unit & integration tests (backend)
  - UrlValidatorTest, PriceExtractorTest, ProductWatchTest
  - UserTest, RobotsTxtCheckerTest, AuthControllerTest
  - Mocks voor externe services

#### Docker Production Configuration
- [x] Multi-stage Dockerfiles voor productie
  - `docker/php/Dockerfile.prod` - OPcache, Chromium, optimized
  - `docker/nginx/Dockerfile.prod` - gzip, caching, SPA routing
- [x] `docker-compose.prod.yml` met Traefik labels
  - Automatische SSL via Let's Encrypt
  - Health checks op alle services
- [x] Scheduler service voor automatische prijschecks (elke 5 min)

#### Deployment
- [x] `deploy.sh` - One-command deployment script
- [x] `docs/deployment.md` - Uitgebreide deployment handleiding
- [x] `.env.prod.example` - Productie environment template

#### Error Tracking (Sentry)
- [x] Backend: `sentry-symfony` bundle integratie
- [x] Frontend: `@sentry/react` met ErrorBoundary
- [x] PII filtering (geen IP-adressen)
- [x] Configureerbaar via `SENTRY_DSN` env vars

#### Frontend Optimizations
- [x] Code splitting (vendor-react, vendor-query, vendor-sentry)
- [x] Terser minification (console/debugger removal)
- [x] Source maps voor debugging
- [x] Chunk size warnings

#### PWA Support
- [x] `vite-plugin-pwa` integratie
- [x] Web app manifest (naam, iconen, theme)
- [x] Workbox service worker
- [x] API response caching (NetworkFirst)
- [x] Auto-update registratie

#### API Documentation
- [x] NelmioApiDocBundle (OpenAPI/Swagger)
- [x] Beschikbaar op `/api/doc`
- [x] JWT Bearer auth geconfigureerd

#### Webhook Notifications
- [x] `WebhookService` voor Discord en Slack
- [x] Discord: Rich embeds met kleuren en thumbnails
- [x] Slack: Block Kit met buttons
- [x] User webhook configuratie via `/api/me/settings`
- [x] `SettingsPage` frontend voor webhook URLs
- [x] Database migratie voor webhook velden

#### Admin Dashboard
- [x] `AdminController` met statistieken endpoints
  - `GET /api/admin/stats` - Gebruikers, watches, checks stats
  - `GET /api/admin/users` - Gepagineerde gebruikerslijst
  - `GET /api/admin/users/{id}` - Gebruiker detail + watches
  - `PATCH /api/admin/users/{id}/role` - Admin rol toekennen/intrekken
  - `GET /api/admin/recent-checks` - Recente prijschecks
- [x] `ROLE_ADMIN` access control in security.yaml
- [x] `AdminPage` frontend met 3 tabs:
  - Overzicht: Stats cards, success rate, top domeinen
  - Gebruikers: Tabel met verificatie status, watch count, admin toggle
  - Recente Checks: Live feed van prijschecks
- [x] Admin link in dashboard header (alleen voor admins)

### Fase 11: Productie Deployment ✅

#### VPS Setup (Transip BladeVPS X1)
- [x] Docker & Docker Compose geïnstalleerd
- [x] 2GB swap toegevoegd (voor lage RAM VPS)
- [x] SSH key-based authentication

#### Traefik Reverse Proxy
- [x] Traefik v3.x als reverse proxy
- [x] Automatische SSL via Let's Encrypt (ACME HTTP challenge)
- [x] HTTP → HTTPS redirect
- [x] www → non-www redirect

#### Container Deployment
- [x] Frontend (nginx) op shopq.app
- [x] API (PHP/Apache) op api.shopq.app
- [x] MariaDB 11.2 database
- [x] Scheduler service (prijscheck elke 5 min)
- [x] Health checks op alle services

#### DNS Configuratie
- [x] A-records voor shopq.app en api.shopq.app
- [x] Geen AAAA records (IPv6 veroorzaakt Let's Encrypt issues)

#### Fixes tijdens deployment
- [x] Symfony .env file creatie in Dockerfile (PathException fix)
- [x] Doctrine cache pools configuratie
- [x] Environment variables doorgeven aan containers
- [x] IPv4-only healthchecks (Alpine wget IPv6 issue)

---

## Wat Nog Moet Gebeuren

### Toekomstige Uitbreidingen
- [ ] Push notificaties (web push)
- [ ] Prijsdrempel alerts ("mail me als < €100")
- [ ] Meerdere valuta's ondersteuning
- [ ] Browser extensie
- [ ] Prijsgrafiek visualisatie

---

## Project Structuur

```
shopq/
├── .github/
│   └── workflows/
│       └── ci.yml                  # GitHub Actions CI/CD
├── docker-compose.yml              # Development
├── docker-compose.prod.yml         # Production
├── deploy.sh                       # Deployment script
├── .env.prod.example               # Production env template
├── docker/
│   ├── php/
│   │   ├── Dockerfile              # Development
│   │   ├── Dockerfile.prod         # Production (multi-stage)
│   │   └── php.ini
│   └── nginx/
│       ├── Dockerfile.frontend     # Development
│       ├── Dockerfile.prod         # Production
│       └── nginx.prod.conf
├── backend/
│   ├── bin/console
│   ├── composer.json
│   ├── config/
│   │   ├── packages/
│   │   │   ├── doctrine.yaml
│   │   │   ├── security.yaml       # ROLE_ADMIN config
│   │   │   ├── lexik_jwt_authentication.yaml
│   │   │   ├── rate_limiter.yaml
│   │   │   ├── sentry.yaml         # Error tracking
│   │   │   └── nelmio_api_doc.yaml # API docs
│   │   └── jwt/
│   ├── migrations/
│   ├── tests/                      # PHPUnit tests
│   │   ├── Unit/
│   │   └── Integration/
│   └── src/
│       ├── Command/
│       │   ├── CheckPricesCommand.php
│       │   └── TestScrapeCommand.php
│       ├── Controller/
│       │   ├── AuthController.php
│       │   ├── ProductWatchController.php
│       │   ├── BookmarkletController.php
│       │   ├── AdminController.php   # Admin dashboard API
│       │   └── HealthController.php  # Health check endpoint
│       ├── Entity/
│       │   ├── User.php              # +webhook fields
│       │   ├── ProductWatch.php
│       │   ├── PriceCheck.php
│       │   └── Notification.php
│       ├── Enum/
│       ├── Repository/
│       ├── Scraper/
│       │   ├── ScrapeEngineInterface.php
│       │   ├── HttpEngine.php
│       │   ├── BrowserEngine.php
│       │   ├── PriceExtractor.php
│       │   └── ImageExtractor.php
│       └── Service/
│           ├── PriceCheckService.php
│           ├── NotificationService.php
│           ├── WebhookService.php    # Discord/Slack webhooks
│           ├── EmailVerificationService.php
│           ├── PasswordResetService.php
│           ├── UrlAnalyzerService.php
│           ├── UrlValidator.php
│           ├── RobotsTxtChecker.php
│           └── DomainRateLimiter.php
├── frontend/
│   ├── package.json
│   ├── vite.config.ts              # PWA + code splitting
│   └── src/
│       ├── main.tsx                # Sentry integration
│       ├── App.tsx
│       ├── api/
│       │   └── client.ts
│       ├── components/
│       │   ├── ProtectedRoute.tsx
│       │   ├── WatchList.tsx
│       │   ├── AddWatchModal.tsx
│       │   ├── VerificationBanner.tsx
│       │   └── Footer.tsx
│       ├── contexts/
│       │   └── AuthContext.tsx
│       ├── hooks/
│       │   └── useWatches.ts
│       ├── pages/
│       │   ├── HomePage.tsx
│       │   ├── LoginPage.tsx
│       │   ├── RegisterPage.tsx
│       │   ├── DashboardPage.tsx
│       │   ├── AddWatchPage.tsx
│       │   ├── WatchDetailPage.tsx
│       │   ├── BookmarkletPage.tsx
│       │   ├── VerifyEmailPage.tsx
│       │   ├── ForgotPasswordPage.tsx
│       │   ├── ResetPasswordPage.tsx
│       │   ├── SettingsPage.tsx      # Webhook configuratie
│       │   ├── AdminPage.tsx         # Admin dashboard
│       │   ├── PrivacyPage.tsx
│       │   ├── TermsPage.tsx
│       │   └── ContactPage.tsx
│       └── types/
│           └── index.ts
└── docs/
    ├── technische-documentatie.md
    ├── deployment.md               # Deployment handleiding
    ├── prijsmonitor-specificatie-v3.md
    ├── prijswacht-entities.md
    ├── setup-voortgang.md
    └── codeManifest.md
```

---

## Handige Commando's

### Docker
```bash
# Start alle services
docker compose up -d

# Stop alle services
docker compose down

# Bekijk logs
docker compose logs -f pricewatch-php

# Rebuild na Dockerfile wijziging
docker compose build --no-cache pricewatch-php && docker compose up -d
```

### Backend (Symfony)
```bash
# Toegang tot PHP container
docker exec -it pricewatch-php bash

# Cache clearen
docker exec pricewatch-php php bin/console cache:clear

# Migraties uitvoeren
docker exec pricewatch-php php bin/console doctrine:migrations:migrate

# Routes bekijken
docker exec pricewatch-php php bin/console debug:router
```

### Scraper Testing
```bash
# Test een URL met selector
docker exec pricewatch-php php bin/console app:test-scrape \
  "https://example.com/product" ".price-class"

# Check alle due watches
docker exec pricewatch-php php bin/console app:check-prices

# Check specifieke watch (voor debugging)
docker exec pricewatch-php php bin/console app:check-prices --watch=1
```

### API Testing
```bash
# Login en token krijgen
curl -X POST http://localhost:8100/api/login \
  -H "Content-Type: application/json" \
  -d '{"username":"test@example.com","password":"testpassword123"}'

# Watches ophalen (gebruik token van login)
curl http://localhost:8100/api/watches \
  -H "Authorization: Bearer <TOKEN>"

# Watch aanmaken
curl -X POST http://localhost:8100/api/watches \
  -H "Authorization: Bearer <TOKEN>" \
  -H "Content-Type: application/json" \
  -d '{"url":"https://example.com","priceSelector":".price","productName":"Test"}'
```

### Database
```bash
# MySQL CLI toegang
docker exec -it pricewatch-mariadb mariadb -u pricewatch -ppricewatch pricewatch

# Database dump
docker exec pricewatch-mariadb mariadb-dump -u pricewatch -ppricewatch pricewatch > backup.sql
```

---

## Bekende Beperkingen

### Dual Engine Support ✅
De applicatie heeft nu twee scrape engines:
- **HttpEngine** - Voor statische HTML sites (snel, efficiënt)
- **BrowserEngine** - Voor JavaScript-rendered sites (Symfony Panther + headless Chrome)

Gebruikers kunnen per watch kiezen welke engine te gebruiken.

### Price Parsing
De `PriceExtractor` ondersteunt:
- `€ 19,99` → `19.99`
- `19.99` → `19.99`
- `1.299,00` → `1299.00`
- `1,299.00` → `1299.00`
- JSON-LD Product schema data

Niet ondersteund:
- Prijzen met tekst erbij ("vanaf €19,99")
- Meerdere prijzen in één element

### Rate Limiting
Per-domein rate limiting is actief:
- Maximum 10 requests per domein per uur
- robots.txt wordt gerespecteerd

---

## Environment Variables

### Backend (.env)
```env
APP_ENV=dev
APP_SECRET=changeme_in_production
DATABASE_URL=mysql://pricewatch:pricewatch@pricewatch-mariadb:3306/pricewatch
MAILER_DSN=smtp://pricewatch-mailhog:1025
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=pricewatch
```

### Frontend
De API URL wordt geconfigureerd via Vite proxy in `vite.config.ts`.

---

## Git Repository

**URL**: https://github.com/LarsMun/shopq

### Recente Commits
- `564ed9f` - Add email verification and password reset features
- `837a1bf` - Add legal compliance, GDPR features, and scraping safeguards
- `45d9661` - Add URL analyzer wizard, bookmarklet, and image support
- `7333320` - Update documentation with Phase 1 & 2 progress
- `c180a03` - Add scraping core (Phase 2)
