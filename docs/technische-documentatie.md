# PrijsWacht - Technische Documentatie

**Versie:** 1.0
**Datum:** Januari 2026
**Status:** Fase 1 & 2 compleet

---

## Overzicht

PrijsWacht is een Nederlandse prijsmonitor webapplicatie waarmee gebruikers productprijzen kunnen volgen op diverse webshops. De applicatie detecteert prijswijzigingen en stuurt e-mailnotificaties bij prijsdalingen, -stijgingen of wanneer websites onbereikbaar worden.

### Technologie Stack

| Component | Technologie | Versie |
|-----------|-------------|--------|
| Backend | Symfony (PHP) | 7.2 / PHP 8.3+ |
| Frontend | React + TypeScript | 18 |
| Styling | Tailwind CSS | 3.x |
| Database | MariaDB | 11.2 |
| Auth | JWT (LexikJWTAuthenticationBundle) | 3.0 |
| Email Testing | Mailhog | latest |
| Containers | Docker Compose | - |

---

## Projectstructuur

```
pricewatch/
├── backend/                    # Symfony PHP applicatie
│   ├── src/
│   │   ├── Command/           # CLI commands (price checking)
│   │   ├── Controller/        # API endpoints
│   │   ├── Entity/            # Doctrine ORM entities
│   │   ├── Enum/              # PHP enums (CheckMethod, NotificationType)
│   │   ├── Repository/        # Database access layer
│   │   ├── Scraper/           # Web scraping engines
│   │   └── Service/           # Business logic
│   ├── config/                # Symfony configuratie
│   ├── migrations/            # Database migrations
│   └── templates/             # Twig email templates
│
├── frontend/                   # React/TypeScript applicatie
│   ├── src/
│   │   ├── api/              # API client
│   │   ├── components/       # React componenten
│   │   ├── contexts/         # Auth context
│   │   ├── hooks/            # React Query hooks
│   │   ├── pages/            # Pagina componenten
│   │   └── types/            # TypeScript interfaces
│   └── ...
│
├── docker/                     # Docker configuraties
│   ├── php/                   # PHP/Apache container
│   └── nginx/                 # Frontend container
│
└── docker-compose.yml          # Services orchestratie
```

---

## Backend Architectuur

### Entities (Database Models)

#### User
Gebruikersaccounts met JWT authenticatie.

```php
// Fields
id: int (PK)
email: string (unique, 180 chars)
password: string (bcrypt hash)
roles: array
isVerified: bool
createdAt: DateTimeImmutable

// Relaties
productWatches: OneToMany → ProductWatch
```

#### ProductWatch
Gevolgde producten met monitoring configuratie.

```php
// Identificatie
id: int (PK)
user: User (FK)
url: string (2048 chars)
domain: string (255 chars, auto-extracted)

// Product info
productName: ?string (500 chars)
imageUrl: ?string (2048 chars)
currency: string (3 chars, default 'EUR')

// Prijzen
currentPrice: ?decimal(10,2)
previousPrice: ?decimal(10,2)
originalPrice: ?decimal(10,2)
lastSeenRawText: ?string

// Configuratie
priceSelector: string (CSS selector of 'jsonld:path')
productSelector: ?string
checkMethod: enum('http', 'browser')

// Status
isActive: bool (default true)
consecutiveFailures: int (default 0)
nextCheckAt: DateTimeImmutable
lastCheckedAt: ?DateTimeImmutable
lastSuccessfulCheckAt: ?DateTimeImmutable

// Metadata
createdAt: DateTimeImmutable
```

**Belangrijke methoden:**
- `updatePrice(newPrice)` - Update met debounce (voorkomt flapping)
- `scheduleNextCheck()` - Plant volgende check (12u + random 0-60min)
- `hasReachedFailureThreshold()` - True bij 5+ opeenvolgende failures

#### PriceCheck
Historische prijscontroles.

```php
id: int (PK)
productWatch: ProductWatch (FK)
price: ?decimal(10,2)
rawText: ?string
wasSuccessful: bool
httpStatus: ?int
durationMs: ?int
errorMessage: ?string
checkedAt: DateTimeImmutable
```

#### Notification
Verstuurde e-mailnotificaties.

```php
id: int (PK)
productWatch: ProductWatch (FK)
oldPrice: ?decimal(10,2)
newPrice: ?decimal(10,2)
type: enum('price_decrease', 'price_increase', 'site_broken')
sentAt: DateTimeImmutable
```

---

### API Endpoints

#### Authenticatie (`AuthController`)

| Method | Endpoint | Beschrijving |
|--------|----------|--------------|
| POST | `/api/login` | JWT login (username, password) |
| POST | `/api/register` | Account aanmaken (email, password) |
| GET | `/api/me` | Huidige gebruiker ophalen |

#### Watches (`ProductWatchController`)

| Method | Endpoint | Beschrijving |
|--------|----------|--------------|
| GET | `/api/watches` | Alle watches van gebruiker |
| POST | `/api/watches` | Nieuwe watch aanmaken |
| POST | `/api/watches/analyze` | URL analyseren voor auto-detectie |
| POST | `/api/watches/check-all` | Alle watches direct checken |
| GET | `/api/watches/{id}` | Watch details + prijshistorie |
| PATCH | `/api/watches/{id}` | Watch updaten (naam, selector, active) |
| DELETE | `/api/watches/{id}` | Watch verwijderen |

#### Bookmarklet (`BookmarkletController`)

| Method | Endpoint | Beschrijving |
|--------|----------|--------------|
| GET | `/api/bookmarklet.js` | Bookmarklet JavaScript |
| POST | `/api/watches/validate` | Selector valideren op URL |

---

### Services

#### PriceCheckService
Core prijsmonitoring logica.

```php
check(ProductWatch $watch): PriceCheck
```

**Workflow:**
1. Selecteer engine (HTTP of Browser)
2. Fetch URL
3. Extraheer prijs met selector
4. Bij succes: update prijs, extract afbeelding, stuur notificatie
5. Bij falen: increment failures, pauzeer na 5 failures
6. Plan volgende check

#### NotificationService
E-mail notificaties versturen.

```php
notifyPriceDecrease(watch, oldPrice, newPrice): Notification
notifyPriceIncrease(watch, oldPrice, newPrice): Notification
notifySiteBroken(watch): Notification
```

#### UrlAnalyzerService
Automatische URL analyse voor watch creatie.

```php
analyze(string $url): UrlAnalysisResult
```

**Detectie prioriteit:**
1. JSON-LD Product data (schema.org)
2. CSS selectors (`.price`, `[data-price]`, etc.)
3. Meta tags (og:image, og:title)

---

### Scraper Engines

#### HttpEngine
Standaard HTTP scraping.

- Symfony HttpClient
- 30 seconden timeout
- Chrome User-Agent
- Geschikt voor statische HTML

#### BrowserEngine
Headless Chrome scraping met JavaScript.

- Symfony Panther (Selenium WebDriver)
- Headless Chrome
- 2 seconden wachttijd voor JS rendering
- Geschikt voor SPA's en dynamische content

#### PriceExtractor
Prijsextractie uit HTML.

**Selector formaten:**
- CSS: `.price`, `#product-price`, `[data-price]`
- JSON-LD: `jsonld:offers.price`

**Prijs parsing:**
- Europees: `€ 19,99` → `19.99`
- Met duizendtallen: `1.299,00` → `1299.00`
- US formaat: `$1,299.00` → `1299.00`

#### ImageExtractor
Product afbeelding extractie.

**Prioriteit:**
1. JSON-LD `image` veld
2. `og:image` meta tag
3. `twitter:image` meta tag

---

### CLI Commands

```bash
# Prijzen checken (voor cron)
php bin/console app:check-prices --limit=100

# Specifieke watch checken
php bin/console app:check-prices --watch=123
```

---

## Frontend Architectuur

### Pages

| Route | Component | Beschrijving |
|-------|-----------|--------------|
| `/` | HomePage | Landing page |
| `/login` | LoginPage | Inloggen |
| `/register` | RegisterPage | Registreren |
| `/dashboard` | DashboardPage | Overzicht watches |
| `/add-watch` | AddWatchPage | Watch toevoegen |
| `/watch/:id` | WatchDetailPage | Watch details |
| `/bookmarklet` | BookmarkletPage | Bookmarklet instructies |

### Components

#### WatchList
Grid van watch cards met:
- Product afbeelding
- Naam en domein
- Status badge (Actief/Gepauzeerd/Fout)
- Huidige prijs met verandering
- Percentage wijziging
- Pauze/Wis knoppen

#### AddWatchModal
Wizard voor nieuwe watch:

**Stap 1:** URL invoeren → Analyseer
**Stap 2:** Bevestig gedetecteerde data → Toevoegen

Features:
- Auto-detectie JSON-LD
- Selector keuze knoppen
- Preview met afbeelding en prijs
- Bewerkbare velden

### Hooks (React Query)

```typescript
// Queries
useWatches()           // Alle watches
useWatch(id)           // Enkele watch + historie
useAnalyzeUrl()        // URL analyseren

// Mutations
useCreateWatch()       // Watch aanmaken
useDeleteWatch()       // Watch verwijderen
useToggleWatch()       // Pauze/hervat
useCheckAllWatches()   // Alles checken
```

### Auth Context

```typescript
const { user, token, login, register, logout, isLoading } = useAuth()
```

- JWT token in localStorage (`pricewatch_token`)
- Auto-validatie bij mount
- Protected routes via ProtectedRoute component

---

## Docker Setup

### Services

| Service | Poort | Beschrijving |
|---------|-------|--------------|
| pricewatch-php | 8100 | Backend API |
| pricewatch-frontend | 3100 | React dev server |
| pricewatch-mariadb | 13307 | Database |
| pricewatch-mailhog | 18025 | Email UI |

### Opstarten

```bash
docker compose up -d
```

### Toegang

- **Frontend:** http://localhost:3100
- **Backend API:** http://localhost:8100/api
- **Mailhog UI:** http://localhost:18025

---

## Database Schema

```sql
-- Gebruikers
CREATE TABLE user (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(180) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    roles JSON,
    is_verified BOOLEAN DEFAULT FALSE,
    created_at DATETIME NOT NULL
);

-- Product watches
CREATE TABLE product_watch (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    url VARCHAR(2048) NOT NULL,
    domain VARCHAR(255) NOT NULL,
    product_name VARCHAR(500),
    price_selector VARCHAR(500) NOT NULL,
    image_url VARCHAR(2048),
    currency VARCHAR(3) DEFAULT 'EUR',
    current_price DECIMAL(10,2),
    previous_price DECIMAL(10,2),
    original_price DECIMAL(10,2),
    check_method ENUM('http', 'browser') DEFAULT 'http',
    consecutive_failures INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    next_check_at DATETIME NOT NULL,
    last_checked_at DATETIME,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE,
    INDEX idx_next_check (next_check_at),
    INDEX idx_user_active (user_id, is_active)
);

-- Prijshistorie
CREATE TABLE price_check (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_watch_id INT NOT NULL,
    price DECIMAL(10,2),
    raw_text VARCHAR(500),
    was_successful BOOLEAN NOT NULL,
    http_status INT,
    duration_ms INT,
    error_message VARCHAR(1000),
    checked_at DATETIME NOT NULL,
    FOREIGN KEY (product_watch_id) REFERENCES product_watch(id) ON DELETE CASCADE,
    INDEX idx_watch_checked (product_watch_id, checked_at)
);

-- Notificaties
CREATE TABLE notification (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_watch_id INT NOT NULL,
    old_price DECIMAL(10,2),
    new_price DECIMAL(10,2),
    type ENUM('price_decrease', 'price_increase', 'site_broken'),
    sent_at DATETIME NOT NULL,
    FOREIGN KEY (product_watch_id) REFERENCES product_watch(id) ON DELETE CASCADE
);
```

---

## Features Overzicht

### Geïmplementeerd

- [x] Gebruikersregistratie en login (JWT)
- [x] Watch CRUD operaties
- [x] Automatische prijsmonitoring (12-uurs interval)
- [x] E-mail notificaties bij prijswijzigingen
- [x] Prijshistorie tracking
- [x] Pauze/hervat individuele watches
- [x] Automatische "site broken" detectie (5 failures)
- [x] Product afbeelding extractie
- [x] JSON-LD (schema.org) detectie
- [x] Dual scraping engines (HTTP + Headless Chrome)
- [x] Intelligente prijs parsing (EU & US formaten)
- [x] CSS selector auto-generatie
- [x] Bookmarklet tool
- [x] URL analyzer wizard
- [x] "Check all" functionaliteit
- [x] Responsieve UI (Tailwind)

### Toekomstige uitbreidingen

- [ ] Email verificatie
- [ ] Wachtwoord reset
- [ ] Notificatie voorkeuren (alleen dalingen)
- [ ] Prijsdrempel alerts
- [ ] Meerdere valuta's
- [ ] Browser extensie
- [ ] Prijsgrafiek visualisatie
- [ ] Export functionaliteit

---

## Configuratie

### Environment Variables

```bash
# Backend (.env)
APP_ENV=dev
APP_SECRET=your-secret-here
DATABASE_URL=mysql://user:pass@host:3306/db
MAILER_DSN=smtp://mailhog:1025

# Frontend (.env)
VITE_API_URL=http://localhost:8100
```

### Cron Setup (productie)

```bash
# Elke 15 minuten prijzen checken
*/15 * * * * cd /var/www/html && php bin/console app:check-prices --limit=50
```

---

## API Authenticatie

Alle beveiligde endpoints vereisen een JWT token in de Authorization header:

```
Authorization: Bearer <jwt-token>
```

**Publieke endpoints:**
- `POST /api/login`
- `POST /api/register`
- `GET /api/bookmarklet.js`
- `POST /api/watches/validate`

---

## Workflow Voorbeelden

### Watch toevoegen via Dashboard

1. Klik "Nieuwe watch"
2. Plak product URL
3. Klik "Analyseer URL"
4. Controleer gedetecteerde gegevens
5. Klik "Toevoegen"
6. Watch wordt aangemaakt met eerste prijscheck

### Watch toevoegen via Bookmarklet

1. Installeer bookmarklet (eenmalig)
2. Bezoek productpagina
3. Klik bookmarklet
4. Selecteer prijs element (of gebruik auto-detectie)
5. Bevestig in PrijsWacht
6. Watch actief

### Prijscheck cyclus

```
[Cron] → app:check-prices
    │
    ├─ Vind watches waar nextCheckAt <= nu
    │
    ├─ Per watch:
    │   ├─ Fetch URL (HTTP of Browser engine)
    │   ├─ Extract prijs
    │   │
    │   ├─ Succes?
    │   │   ├─ Update currentPrice
    │   │   ├─ Extract afbeelding
    │   │   ├─ Stuur notificatie (indien gewijzigd)
    │   │   └─ Plan volgende check (+12u)
    │   │
    │   └─ Falen?
    │       ├─ Increment failures
    │       ├─ Bij 5 failures: pauzeer + notificatie
    │       └─ Plan volgende check (+12u)
    │
    └─ Log resultaten
```

---

## Ontwikkeling

### Lokaal draaien

```bash
# Start containers
docker compose up -d

# Backend dependencies
docker exec pricewatch-php composer install

# Frontend dependencies
docker exec pricewatch-frontend npm install

# Database migrations
docker exec pricewatch-php php bin/console doctrine:migrations:migrate

# Frontend dev server draait automatisch op :3100
```

### Handige commando's

```bash
# PHP logs bekijken
docker logs -f pricewatch-php

# Database CLI
docker exec -it pricewatch-mariadb mysql -upricewatch -ppricewatch pricewatch

# Symfony cache clearen
docker exec pricewatch-php php bin/console cache:clear

# Frontend build
docker exec pricewatch-frontend npm run build
```

---

*Laatst bijgewerkt: Januari 2026*
