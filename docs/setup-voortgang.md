# PrijsWacht - Setup Voortgang

> Laatst bijgewerkt: 2026-01-01

## Huidige Status: Fase 1 & 2 Compleet

Authenticatie, CRUD API en scraping core zijn volledig werkend.

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

### Fase 1: Authenticatie (Compleet)

#### Backend API Endpoints
| Endpoint | Method | Auth | Beschrijving |
|----------|--------|------|--------------|
| `/api/register` | POST | - | Registreer nieuwe gebruiker |
| `/api/login` | POST | - | Login, retourneert JWT token |
| `/api/me` | GET | JWT | Huidige gebruiker info |

#### Frontend Auth
- [x] `AuthContext` - Token opslag (localStorage), user state
- [x] `ProtectedRoute` - Redirect naar /login als niet ingelogd
- [x] `LoginPage` - Werkende login met error handling
- [x] `RegisterPage` - Werkende registratie met validatie
- [x] `DashboardPage` - Toont user email + uitlogknop

### Fase 2: Scraping Core (Compleet)

#### ProductWatch CRUD API
| Endpoint | Method | Auth | Beschrijving |
|----------|--------|------|--------------|
| `/api/watches` | GET | JWT | Lijst van user's watches |
| `/api/watches` | POST | JWT | Nieuwe watch aanmaken |
| `/api/watches/{id}` | GET | JWT | Watch detail + prijshistorie |
| `/api/watches/{id}` | PATCH | JWT | Update watch |
| `/api/watches/{id}` | DELETE | JWT | Verwijder watch |

#### Scraper Services
- [x] `ScrapeEngineInterface` - Contract voor scrape engines
- [x] `HttpEngine` - Fetch pages via Symfony HttpClient
- [x] `PriceExtractor` - CSS selector → prijs parsing
- [x] `PriceCheckService` - Business logic, debounce, failure tracking

#### CLI Commands
```bash
# Test scraper met URL + selector
docker exec pricewatch-php php bin/console app:test-scrape "https://example.com" ".price"

# Check alle due watches
docker exec pricewatch-php php bin/console app:check-prices

# Check specifieke watch
docker exec pricewatch-php php bin/console app:check-prices --watch=1
```

---

## Wat Nog Moet Gebeuren

### Fase 2b: Rate Limiting (optioneel)
- [ ] Per-domain throttling
- [ ] Configurable delays tussen requests

### Fase 3: Notificaties
- [ ] `NotificationService`
- [ ] Email templates (price_decrease, price_increase, site_broken)
- [ ] Debounce logic (voorkomt spam bij flapping prices)

### Fase 4: Frontend Uitbreiding
- [ ] Watch list view met echte data
- [ ] Watch detail + prijshistorie grafiek
- [ ] Add watch formulier

### Fase 5: Bookmarklet
- [ ] Bookmarklet JavaScript code
- [ ] Selector generatie logic
- [ ] `/api/watches/validate` endpoint
- [ ] Confirmation flow in React

### Fase 6: Browser Engine (voor SPA sites)
- [ ] `BrowserEngine` met Playwright/Puppeteer
- [ ] Fallback voor sites die JavaScript vereisen (bol.com, Amazon)

---

## Project Structuur

```
pricewatch/
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
│       │   └── ProductWatchController.php
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
│       │   └── PriceExtractor.php
│       └── Service/
│           └── PriceCheckService.php
├── frontend/
│   ├── package.json
│   ├── vite.config.ts
│   └── src/
│       ├── main.tsx
│       ├── App.tsx
│       ├── api/
│       │   └── client.ts
│       ├── components/
│       │   └── ProtectedRoute.tsx
│       ├── contexts/
│       │   └── AuthContext.tsx
│       ├── pages/
│       │   ├── HomePage.tsx
│       │   ├── LoginPage.tsx
│       │   ├── RegisterPage.tsx
│       │   └── DashboardPage.tsx
│       └── types/
│           └── index.ts
└── docs/
    ├── prijsmonitor-specificatie-v3.md
    ├── prijswacht-entities.md
    └── setup-voortgang.md
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

### HTTP Engine
De huidige `HttpEngine` werkt alleen met server-side rendered HTML. Sites die prijzen via JavaScript laden (SPA's) worden niet ondersteund:
- bol.com - Prijzen via React
- Amazon - Prijzen via JavaScript
- Veel moderne webshops

**Oplossing**: Later een `BrowserEngine` toevoegen met headless browser (Playwright).

### Price Parsing
De `PriceExtractor` ondersteunt:
- `€ 19,99` → `19.99`
- `19.99` → `19.99`
- `1.299,00` → `1299.00`
- `1,299.00` → `1299.00`

Niet ondersteund:
- Prijzen met tekst erbij ("vanaf €19,99")
- Meerdere prijzen in één element

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

**URL**: https://github.com/LarsMun/pricewatch

### Recente Commits
- `c180a03` - Add scraping core (Phase 2)
- `4269ea8` - Add authentication API and frontend integration
- `ebc2131` - Add .vite/ to gitignore
- `3bb87d4` - Initial commit: Pricewatch application
