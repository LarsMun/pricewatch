# ShopQ - Setup Voortgang

> Laatst bijgewerkt: 2026-01-04

## Huidige Status: MVP+ Compleet (Fase 1-8)

Alle kernfunctionaliteit is geïmplementeerd: authenticatie met email verificatie en wachtwoord reset, CRUD API, scraping, notificaties, bookmarklet, en compliance features.

---

## Draaiende Services

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

---

## Wat Nog Moet Gebeuren

### Toekomstige Uitbreidingen
- [ ] Unit & integration tests
- [ ] API documentatie (Swagger/OpenAPI)
- [ ] Productie deployment configuratie
- [ ] Cron job setup documentatie

---

## Project Structuur

```
shopq/
├── docker-compose.yml
├── docker/
│   ├── php/
│   │   ├── Dockerfile
│   │   └── php.ini
│   └── nginx/
│       └── Dockerfile.frontend
├── backend/
│   ├── bin/console
│   ├── composer.json
│   ├── config/
│   │   ├── packages/
│   │   │   ├── doctrine.yaml
│   │   │   ├── security.yaml
│   │   │   ├── lexik_jwt_authentication.yaml
│   │   │   ├── rate_limiter.yaml
│   │   │   └── ...
│   │   └── jwt/
│   │       ├── private.pem
│   │       └── public.pem
│   ├── migrations/
│   ├── public/
│   │   ├── index.php
│   │   └── .htaccess
│   └── src/
│       ├── Command/
│       │   ├── CheckPricesCommand.php
│       │   └── TestScrapeCommand.php
│       ├── Controller/
│       │   ├── AuthController.php
│       │   ├── ProductWatchController.php
│       │   └── BookmarkletController.php
│       ├── Entity/
│       │   ├── User.php
│       │   ├── ProductWatch.php
│       │   ├── PriceCheck.php
│       │   └── Notification.php
│       ├── Enum/
│       │   ├── CheckMethod.php
│       │   └── NotificationType.php
│       ├── Repository/
│       │   ├── UserRepository.php
│       │   ├── ProductWatchRepository.php
│       │   ├── PriceCheckRepository.php
│       │   └── NotificationRepository.php
│       ├── Scraper/
│       │   ├── ScrapeEngineInterface.php
│       │   ├── HttpEngine.php
│       │   ├── BrowserEngine.php
│       │   ├── PriceExtractor.php
│       │   └── ImageExtractor.php
│       └── Service/
│           ├── PriceCheckService.php
│           ├── NotificationService.php
│           ├── EmailVerificationService.php
│           ├── PasswordResetService.php
│           ├── UrlAnalyzerService.php
│           ├── RobotsTxtChecker.php
│           └── DomainRateLimiter.php
├── frontend/
│   ├── package.json
│   ├── vite.config.ts
│   └── src/
│       ├── main.tsx
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
│       │   ├── PrivacyPage.tsx
│       │   ├── TermsPage.tsx
│       │   └── ContactPage.tsx
│       └── types/
│           └── index.ts
└── docs/
    ├── technische-documentatie.md
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
