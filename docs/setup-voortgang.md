# PrijsWacht - Setup Voortgang

> Laatst bijgewerkt: 2024-12-31

## Huidige Status: Basis Infrastructuur Compleet

De basis Docker-infrastructuur en project skeletons zijn opgezet en werkend.

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

### Docker Setup
- [x] `docker-compose.yml` met 4 services (mariadb, php, frontend, mailhog)
- [x] `docker/php/Dockerfile` - PHP 8.3 + Apache + extensions
- [x] `docker/php/php.ini` - PHP configuratie voor development
- [x] `docker/nginx/Dockerfile.frontend` - Node 20 Alpine voor Vite dev server
- [x] Netwerk: `pricewatch-network` voor inter-container communicatie
- [x] Volumes voor persistente data (mariadb, vendor, node_modules)

### Backend (Symfony)
- [x] Symfony 7.4 project skeleton
- [x] Composer dependencies geïnstalleerd
- [x] JWT authenticatie geconfigureerd (keys gegenereerd)
- [x] Database connectie naar MariaDB
- [x] Eerste migratie uitgevoerd

#### Entities (volgens specificatie)
- [x] `src/Entity/User.php` - Gebruikersaccount met Symfony Security
- [x] `src/Entity/ProductWatch.php` - Gemonitorde productpagina
- [x] `src/Entity/PriceCheck.php` - Historische prijscheck
- [x] `src/Entity/Notification.php` - Verstuurde notificatie

#### Enums
- [x] `src/Enum/CheckMethod.php` - HTTP of BROWSER
- [x] `src/Enum/NotificationType.php` - PRICE_DECREASE, PRICE_INCREASE, SITE_BROKEN

#### Repositories
- [x] `src/Repository/UserRepository.php`
- [x] `src/Repository/ProductWatchRepository.php` - Met `findDueForCheck()`, `findByUser()`, rate limiting queries
- [x] `src/Repository/PriceCheckRepository.php` - Met history queries
- [x] `src/Repository/NotificationRepository.php`

#### Configuratie
- [x] `config/packages/doctrine.yaml` - MariaDB configuratie
- [x] `config/packages/security.yaml` - JWT firewall, user provider
- [x] `config/packages/lexik_jwt_authentication.yaml` - JWT settings
- [x] `config/packages/nelmio_cors.yaml` - CORS voor API
- [x] `config/packages/messenger.yaml` - Async queue setup
- [x] `config/packages/mailer.yaml` - Mailhog configuratie

### Frontend (React)
- [x] Vite + React 18 + TypeScript project
- [x] TailwindCSS geconfigureerd
- [x] React Router v7 voor navigatie
- [x] TanStack Query voor server state management
- [x] API client basis (`src/api/client.ts`)
- [x] TypeScript types (`src/types/index.ts`)

#### Pagina's (basis)
- [x] `src/pages/HomePage.tsx` - Landing page
- [x] `src/pages/LoginPage.tsx` - Login formulier (nog niet functioneel)
- [x] `src/pages/RegisterPage.tsx` - Registratie formulier (nog niet functioneel)
- [x] `src/pages/DashboardPage.tsx` - Dashboard placeholder

---

## Wat Nog Moet Gebeuren

### Fase 1: Foundation (bijna klaar)
- [ ] API endpoints voor authenticatie (`/api/register`, `/api/login`)
- [ ] Frontend login/register koppelen aan API
- [ ] Protected routes in React

### Fase 2: Scraping Core
- [ ] `ScrapeEngineInterface` + `HttpEngine` implementatie
- [ ] `PriceExtractor` service (selector → price)
- [ ] Worker command: process watches waar `next_check_at <= now()`
- [ ] Rate limiting per domain

### Fase 3: Notificaties
- [ ] `NotificationService`
- [ ] Email templates (price_decrease, price_increase, site_broken)
- [ ] Debounce logic implementatie

### Fase 4: Frontend
- [ ] Watch list view met data
- [ ] Watch detail + prijshistorie grafiek
- [ ] Add watch flow

### Fase 5: Bookmarklet
- [ ] Bookmarklet JavaScript code
- [ ] Selector generatie logic
- [ ] `/api/watches/validate` endpoint
- [ ] Confirmation flow in React

---

## Handige Commando's

### Docker
```bash
# Start alle services
docker compose up -d

# Stop alle services
docker compose down

# Bekijk logs (alle services)
docker compose logs -f

# Bekijk logs (specifieke service)
docker compose logs -f pricewatch-php

# Herstart een service
docker compose restart pricewatch-php

# Rebuild na Dockerfile wijziging
docker compose build --no-cache pricewatch-php
docker compose up -d
```

### Backend (Symfony)
```bash
# Toegang tot PHP container
docker exec -it pricewatch-php bash

# Symfony console commando's
docker exec pricewatch-php php bin/console <command>

# Cache clearen
docker exec pricewatch-php php bin/console cache:clear

# Nieuwe migratie maken
docker exec pricewatch-php php bin/console doctrine:migrations:diff

# Migraties uitvoeren
docker exec pricewatch-php php bin/console doctrine:migrations:migrate

# Entity maken (interactief)
docker exec -it pricewatch-php php bin/console make:entity

# Controller maken
docker exec -it pricewatch-php php bin/console make:controller
```

### Frontend
```bash
# Toegang tot frontend container
docker exec -it pricewatch-frontend sh

# NPM commando's
docker exec pricewatch-frontend npm install <package>
docker exec pricewatch-frontend npm run build
```

### Database
```bash
# MySQL CLI toegang
docker exec -it pricewatch-mariadb mariadb -u pricewatch -ppricewatch pricewatch

# Database dump
docker exec pricewatch-mariadb mariadb-dump -u pricewatch -ppricewatch pricewatch > backup.sql
```

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
│   │   ├── bundles.php
│   │   ├── packages/
│   │   │   ├── doctrine.yaml
│   │   │   ├── framework.yaml
│   │   │   ├── lexik_jwt_authentication.yaml
│   │   │   ├── mailer.yaml
│   │   │   ├── messenger.yaml
│   │   │   ├── monolog.yaml
│   │   │   ├── nelmio_cors.yaml
│   │   │   ├── security.yaml
│   │   │   └── validator.yaml
│   │   ├── routes.yaml
│   │   ├── routes/
│   │   │   └── framework.yaml
│   │   ├── services.yaml
│   │   └── jwt/
│   │       ├── private.pem
│   │       └── public.pem
│   ├── migrations/
│   │   └── Version20251230233213.php
│   ├── public/
│   │   └── index.php
│   ├── src/
│   │   ├── Controller/
│   │   ├── Entity/
│   │   │   ├── User.php
│   │   │   ├── ProductWatch.php
│   │   │   ├── PriceCheck.php
│   │   │   └── Notification.php
│   │   ├── Enum/
│   │   │   ├── CheckMethod.php
│   │   │   └── NotificationType.php
│   │   ├── Repository/
│   │   │   ├── UserRepository.php
│   │   │   ├── ProductWatchRepository.php
│   │   │   ├── PriceCheckRepository.php
│   │   │   └── NotificationRepository.php
│   │   ├── Service/
│   │   └── Kernel.php
│   └── .env
├── frontend/
│   ├── package.json
│   ├── vite.config.ts
│   ├── tsconfig.json
│   ├── tailwind.config.js
│   ├── postcss.config.js
│   ├── index.html
│   └── src/
│       ├── main.tsx
│       ├── App.tsx
│       ├── index.css
│       ├── vite-env.d.ts
│       ├── api/
│       │   └── client.ts
│       ├── components/
│       ├── hooks/
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
    ├── codeManifest.md
    └── setup-voortgang.md (dit bestand)
```

---

## Environment Variables

### Backend (.env)
```env
APP_ENV=dev
APP_SECRET=changeme_in_production
DATABASE_URL=mysql://pricewatch:pricewatch@pricewatch-mariadb:3306/pricewatch?serverVersion=mariadb-11.2.0
MAILER_DSN=smtp://pricewatch-mailhog:1025
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=pricewatch
MESSENGER_TRANSPORT_DSN=doctrine://default?auto_setup=0
CORS_ALLOW_ORIGIN='^https?://(localhost|127\.0\.0\.1)(:[0-9]+)?$'
```

### Frontend (via Vite)
```env
VITE_API_URL=http://localhost:8100
```

---

## Poorten Overzicht (vermijd conflicten)

Andere projecten op dit systeem gebruiken:
- 5432 (whendue-postgres)
- 8000 (whendue-php)
- 8081, 19000-19002 (whendue-frontend)
- 1025, 8025 (whendue-mailhog)
- 13000 (money-frontend)
- 13306 (money-mysql)
- 18787 (money-backend)

PriceWatch gebruikt:
- 8100 (backend)
- 3100 (frontend)
- 13307 (mariadb)
- 11025, 18025 (mailhog)

---

## Volgende Stappen

1. **Maak authenticatie API endpoints** - `AuthController` met register en login
2. **Koppel frontend aan API** - Login/register forms werkend maken
3. **Implementeer ProductWatch CRUD** - Basis API voor watches beheren
4. **Begin met scraping engine** - HttpEngine voor eenvoudige sites
