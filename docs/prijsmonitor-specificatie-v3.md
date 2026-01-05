# PrijsWacht - Prijsmonitor voor Nederlandse Webshops

## Projectoverzicht

PrijsWacht is een webapplicatie waarmee gebruikers productpagina's van willekeurige webshops kunnen monitoren op prijswijzigingen. In tegenstelling tot bestaande oplossingen die óf te generiek zijn (Visualping - "er is iets veranderd") óf te specifiek (CamelCamelCamel - alleen Amazon), richt PrijsWacht zich op het slim monitoren van prijzen op Nederlandse webshops.

### Kernprobleem

Gebruikers willen weten wanneer een product in prijs daalt (of stijgt) zonder dagelijks handmatig de website te checken. Bestaande tools falen vaak op:

- JavaScript-rendered content (React/Next.js/Vue shops)
- Variabele HTML-structuren per webshop
- Nederlandse webshops die niet door internationale tools ondersteund worden

### Oplossing

Een gebruiksvriendelijke tool waarbij de gebruiker:

1. Een URL invoert
2. Via bookmarklet/extensie de prijs selecteert op de echte pagina
3. Automatisch notificaties ontvangt bij prijswijzigingen

---

## Gebruikersflow

```
┌─────────────────────────────────────────────────────────────────────┐
│                         GEBRUIKERSFLOW                              │
└─────────────────────────────────────────────────────────────────────┘

1. SETUP (eenmalig)
   Gebruiker installeert bookmarklet of browser-extensie
   ↓
2. SELECTIE (op externe site)
   Gebruiker opent productpagina in eigen browser
   Gebruiker activeert bookmarklet/extensie
   Gebruiker klikt op prijselement
   Bookmarklet stuurt URL + selector + context naar backend
   ↓
3. VALIDATIE
   Backend scraped pagina ter verificatie
   Prijs wordt geëxtraheerd en getoond ter bevestiging
   ↓
4. OPSLAAN
   ProductWatch wordt aangemaakt
   Initiële prijs + raw_text worden opgeslagen
   next_check_at wordt gezet
   ↓
5. MONITORING (automatisch)
   Worker pakt watches waar next_check_at <= now()
   Bij prijswijziging (2x bevestigd) → notificatie
   Bij 5 opeenvolgende fouten → "site gewijzigd" notificatie + pause
   next_check_at wordt opnieuw gezet met jitter
```

---

## Technische Stack

### Backend

| Component | Technologie | Toelichting |
|-----------|-------------|-------------|
| Framework | Symfony 7.2+ | PHP 8.3+, volledige toolkit |
| Database | MariaDB 11.2 | Betrouwbaar, MySQL-compatibel |
| Scraping | Dual Engine | HttpEngine + BrowserEngine (zie onder) |
| Mail | Symfony Mailer | Notificaties |
| Error Tracking | Sentry | Production monitoring |

#### Scrape Engine Abstractie (Geïmplementeerd)

```php
interface ScrapeEngineInterface
{
    public function fetch(string $url): ScrapeResult;
}

class HttpEngine implements ScrapeEngineInterface
{
    // Symfony HttpClient - snel, lightweight
    // Voor statische HTML sites
}

class BrowserEngine implements ScrapeEngineInterface
{
    // Symfony Panther + headless Chrome
    // Voor JavaScript-rendered sites
    // Automatische fallback bij 403/429 responses
}
```

**Status:** Beide engines geïmplementeerd met automatische fallback.

### Frontend

| Component | Technologie | Toelichting |
|-----------|-------------|-------------|
| Framework | React | Bestaande expertise |
| API | REST (JSON) | Symfony als API backend |
| Selector | Bookmarklet | Draait op externe site, stuurt data naar backend |

### Infrastructuur

| Component | Technologie |
|-----------|-------------|
| Containerization | Docker |
| Environment | OTAP |
| OS | Ubuntu |

---

## Datamodel

### Entity Relationship Diagram

```
┌─────────────────┐       ┌─────────────────────────┐
│      User       │       │      ProductWatch       │
├─────────────────┤       ├─────────────────────────┤
│ id (PK)         │──────<│ id (PK)                 │
│ email (UK)      │       │ user_id (FK)            │
│ password        │       │ url                     │
│ is_verified     │       │ domain                  │
│ created_at      │       │ product_name            │
└─────────────────┘       │ price_selector          │
                          │ product_selector        │
                          │ currency                │
                          │ current_price           │
                          │ previous_price          │
                          │ original_price          │
                          │ last_seen_raw_text      │
                          │ parse_rule_json         │
                          │ check_method            │
                          │ consecutive_failures    │
                          │ next_check_at           │
                          │ last_checked_at         │
                          │ last_successful_check_at│
                          │ is_active               │
                          │ created_at              │
                          └───────────┬─────────────┘
                                      │
                     ┌────────────────┴────────────────┐
                     │                                 │
                     ▼                                 ▼
          ┌──────────────────┐              ┌─────────────────┐
          │    PriceCheck    │              │  Notification   │
          ├──────────────────┤              ├─────────────────┤
          │ id (PK)          │              │ id (PK)         │
          │ product_watch_id │              │ product_watch_id│
          │ price            │              │ old_price       │
          │ raw_text         │              │ new_price       │
          │ was_successful   │              │ type            │
          │ http_status      │              │ sent_at         │
          │ duration_ms      │              └─────────────────┘
          │ error_message    │
          │ checked_at       │
          └──────────────────┘
```

### Entiteiten in Detail

#### User

Standaard gebruikersentiteit voor authenticatie.

| Veld | Type | Constraints | Toelichting |
|------|------|-------------|-------------|
| `id` | integer | PK, auto-increment | |
| `email` | string(180) | unique, not null | Login identifier |
| `password` | string(255) | not null | Hashed password |
| `is_verified` | boolean | default: false | Email verificatie |
| `created_at` | datetime | not null | Registratiedatum |

#### ProductWatch

De kern van de applicatie: een "zoekopdracht" die een gebruiker heeft ingesteld.

| Veld | Type | Constraints | Toelichting |
|------|------|-------------|-------------|
| `id` | integer | PK, auto-increment | |
| `user_id` | integer | FK → User, not null | Eigenaar |
| `url` | string(2048) | not null | Volledige product-URL |
| `domain` | string(255) | not null, indexed | Geëxtraheerd uit URL, voor rate limiting |
| `product_name` | string(500) | nullable | Door gebruiker geselecteerde naam |
| `price_selector` | string(500) | not null | CSS selector voor prijselement |
| `product_selector` | string(500) | nullable | CSS selector voor productnaam |
| `currency` | char(3) | default: 'EUR' | ISO 4217 currency code |
| `current_price` | decimal(10,2) | nullable | Laatst bekende prijs |
| `previous_price` | decimal(10,2) | nullable | Prijs vóór huidige (voor debounce) |
| `original_price` | decimal(10,2) | nullable | Prijs bij aanmaken watch |
| `last_seen_raw_text` | string(500) | nullable | Ruwe tekst van laatste succesvolle extract |
| `parse_rule_json` | json | nullable | Parsing config (decimal separator, regex, etc.) |
| `selector_context_html` | text | nullable | ~300 chars HTML rondom geselecteerd element (debug + toekomstige anchoring) |
| `check_method` | string(20) | default: 'http' | Enum: 'http', 'browser' |
| `consecutive_failures` | integer | default: 0 | Teller voor opeenvolgende fouten |
| `next_check_at` | datetime | not null, indexed | Volgende geplande check |
| `last_checked_at` | datetime | nullable | Laatste poging |
| `last_successful_check_at` | datetime | nullable | Laatste succesvolle check |
| `is_active` | boolean | default: true | Actief of gepauzeerd |
| `created_at` | datetime | not null | Aanmaakdatum |

**parse_rule_json voorbeeld:**
```json
{
  "decimal_separator": ",",
  "thousand_separator": ".",
  "strip_chars": ["€", " ", "\n"],
  "regex": "([0-9.,]+)",
  "attribute": null
}
```

#### PriceCheck

Historische log van alle prijschecks. Gebruikt voor prijshistorie-grafiek en debugging.

| Veld | Type | Constraints | Toelichting |
|------|------|-------------|-------------|
| `id` | integer | PK, auto-increment | |
| `product_watch_id` | integer | FK → ProductWatch, not null | |
| `price` | decimal(10,2) | nullable | null bij failure |
| `raw_text` | string(500) | nullable | Ruwe geëxtraheerde tekst |
| `was_successful` | boolean | not null | Check geslaagd? |
| `http_status` | integer | nullable | HTTP response code |
| `duration_ms` | integer | nullable | Duur van scrape in milliseconden |
| `error_message` | string(1000) | nullable | Foutmelding bij failure |
| `checked_at` | datetime | not null | Tijdstip van check |

**Retentie:** 90 dagen. Oudere data kan later geaggregeerd worden (daily min/max).

#### Notification

Log van alle verstuurde notificaties.

| Veld | Type | Constraints | Toelichting |
|------|------|-------------|-------------|
| `id` | integer | PK, auto-increment | |
| `product_watch_id` | integer | FK → ProductWatch, not null | |
| `old_price` | decimal(10,2) | nullable | Vorige prijs (null bij site_broken) |
| `new_price` | decimal(10,2) | nullable | Nieuwe prijs (null bij site_broken) |
| `type` | string(50) | not null | Enum: zie onder |
| `sent_at` | datetime | not null | Verzendtijdstip |

**Notification types (MVP):**

- `price_decrease` - Prijs is gedaald
- `price_increase` - Prijs is gestegen
- `site_broken` - 5 opeenvolgende failures

---

## Business Rules

### Scheduling

**Geen cron batches.** In plaats daarvan:

1. Elke watch heeft een `next_check_at` timestamp
2. Worker query: `WHERE next_check_at <= NOW() AND is_active = true ORDER BY next_check_at LIMIT 100`
3. Na check: `next_check_at = NOW() + 12 hours + random(0-60 minutes)`

De jitter (random 0-60 min) voorkomt dat alle watches van hetzelfde moment synchroon blijven.

### Rate Limiting per Domein

Om blokkades te voorkomen:

- Maximum 10 requests per domein per uur
- Worker groepeert op `domain` en respecteert limiet
- Bij limiet: watch wordt overgeslagen, `next_check_at` blijft staan

### Prijswijziging Detectie (met debounce)

```
Nieuwe prijs geëxtraheerd
     │
     ├─── Gelijk aan current_price ───→ Geen actie
     │
     └─── Anders dan current_price
               │
               ├─── Gelijk aan previous_price ───→ Flap detectie, geen actie
               │                                   (prijs flipt heen en weer)
               │
               └─── Anders dan previous_price ───→ Echte wijziging!
                                                   previous_price = current_price
                                                   current_price = nieuwe prijs
                                                   Stuur notificatie
```

### Failure Handling

```
Check uitgevoerd
     │
     ├─── Succes ───→ consecutive_failures = 0
     │                last_successful_check_at = now()
     │                Vergelijk prijs (zie boven)
     │
     └─── Failure ──→ consecutive_failures += 1
                      │
                      ├─── consecutive_failures < 5 ───→ Geen actie
                      │
                      └─── consecutive_failures = 5 ───→ Stuur "site_broken" notificatie
                                                         is_active = false
                                                         (user moet re-selecten)
```

### Reactivatie na site_broken

User kan via UI:
1. Watch opnieuw activeren
2. Wordt geforceerd door "re-select" flow (bookmarklet opnieuw gebruiken)
3. Nieuwe selector overschrijft oude
4. `consecutive_failures` reset naar 0

---

## API Endpoints

### Authenticatie

| Method | Endpoint | Beschrijving |
|--------|----------|--------------|
| POST | `/api/register` | Registratie |
| POST | `/api/login` | Login (JWT) |
| POST | `/api/verify-email` | Email verificatie |

### ProductWatch

| Method | Endpoint | Beschrijving |
|--------|----------|--------------|
| GET | `/api/watches` | Lijst van eigen watches |
| POST | `/api/watches` | Nieuwe watch aanmaken (vanuit bookmarklet) |
| GET | `/api/watches/{id}` | Detail + prijshistorie |
| PATCH | `/api/watches/{id}` | Wijzigen (is_active toggle, reselect) |
| DELETE | `/api/watches/{id}` | Verwijderen |

### Prijshistorie

| Method | Endpoint | Beschrijving |
|--------|----------|--------------|
| GET | `/api/watches/{id}/history` | Prijschecks voor grafiek |

### Bookmarklet Support

| Method | Endpoint | Beschrijving |
|--------|----------|--------------|
| POST | `/api/watches/validate` | Valideer URL + selector, return extracted price |
| GET | `/api/bookmarklet.js` | Bookmarklet JavaScript code |

---

## Bookmarklet Aanpak

### Waarom bookmarklet ipv in-app selector?

In-app selector is complex vanwege:
- X-Frame-Options / CSP blokkeren iframes
- Screenshot + coordinate mapping is lastig (scroll, responsive, zoom)
- Precomputen van "selecteerbare elementen" is onzinnig

### UX Framing (belangrijk!)

"Bookmarklet" is jargon. Voor niet-technische gebruikers:

| Niet zeggen | Wel zeggen |
|-------------|------------|
| "Installeer onze bookmarklet" | "Sleep de PrijsWacht-knop naar je bladwijzerbalk" |
| "Bookmarklet activeren" | "Klik op de PrijsWacht-knop" |
| "JavaScript bookmark" | "1-klik prijs selecteren" |

**UI elementen:**
- Grote, duidelijke sleepbare knop
- Visuele instructie (gifje/video, max 15 sec)
- "Geavanceerd" sectie voor technische details
- Fallback: "Lukt het niet? Kopieer deze code..." (voor edge cases)

### Bookmarklet Flow

```javascript
// Simplified bookmarklet logic
javascript:(function(){
  // Inject selection UI
  // User clicks element
  // Generate CSS selector
  // Capture context HTML (~300 chars rondom element)
  // POST to PrijsWacht API:
  //   - url: window.location.href
  //   - selector: generated CSS selector
  //   - raw_text: element.textContent
  //   - context_html: surrounding HTML snippet
  // Redirect to PrijsWacht confirmation page
})();
```

### Context HTML Capture

Bij selectie wordt ~300 karakters HTML rondom het element opgeslagen:

```javascript
function captureContext(element) {
  const parent = element.parentElement?.parentElement || element.parentElement;
  const html = parent?.outerHTML || element.outerHTML;
  return html.substring(0, 300);
}
```

**Waarom:**
- Debug info bij parse failures
- Input voor toekomstige "anchoring" (element vinden op basis van omringende tekst)
- Fragment voor LLM-assisted selector repair
- Niet de hele DOM - dat is overkill en privacy-gevoelig

### Selector Generatie

Prioriteit voor robuuste selectors:
1. `id` attribuut (als uniek)
2. `data-*` attributen (vaak stabiel)
3. Unieke class combinatie
4. DOM path als fallback

Later (v2): "anchoring" - element vinden relatief aan bekende tekst (bijv. "€" of "Prijs:").

---

## Beslissingen

| Vraag | Beslissing | Status |
|-------|------------|--------|
| Limiet watches per user | **Ongelimiteerd** (MVP) | ✅ |
| Retentie PriceChecks | **90 dagen** | ✅ |
| Unsubscribe | **Globale unsubscribe link** in elke mail | ✅ |
| Na site_broken | **Automatisch pauzeren** (`is_active = false`) | ✅ |
| Check frequentie | **~12 uur interval + jitter** per watch | ✅ |
| Scheduler interval | **5 minuten**, 50 watches per run | ✅ |
| Scrape engines | **Dual engine** met auto-fallback | ✅ |
| Notificaties | **Email + Discord/Slack webhooks** | ✅ |

---

## Bouwvolgorde (Voltooid)

### Fase 1: Foundation ✅
- [x] Symfony project setup
- [x] Doctrine entities + migrations
- [x] User authenticatie (registration, login, JWT)
- [x] Basic CRUD voor ProductWatch

### Fase 2: Scraping Core ✅
- [x] ScrapeEngineInterface + HttpEngine implementatie
- [x] PriceExtractor service (selector → price)
- [x] Worker command: process watches waar `next_check_at <= now()`
- [x] PriceCheck logging
- [x] Rate limiting per domain

### Fase 3: Notificaties ✅
- [x] NotificationService
- [x] Email templates (price_decrease, price_increase, site_broken)
- [x] Debounce logic
- [x] Discord/Slack webhook integratie

### Fase 4: Frontend ✅
- [x] React project setup (Vite + TypeScript)
- [x] Login/register pages met email verificatie
- [x] Watch list view
- [x] Watch detail + prijshistorie

### Fase 5: Bookmarklet ✅
- [x] Bookmarklet JavaScript
- [x] Selector generatie logic
- [x] `/api/watches/validate` endpoint
- [x] URL analyzer wizard

### Fase 6: Browser Engine ✅
- [x] BrowserEngine (Symfony Panther + headless Chrome)
- [x] Auto-fallback bij 403/429 responses
- [x] Automatische engine switch bij succes

### Fase 7-10: Production Ready ✅
- [x] Email verificatie + password reset
- [x] SSRF bescherming (UrlValidator)
- [x] Unit & integration tests (127 tests)
- [x] CI/CD (GitHub Actions)
- [x] Docker production setup
- [x] Sentry error tracking
- [x] Admin dashboard
- [x] PWA support

---

## Toekomstige Uitbreidingen (backlog)

- Browser extensie (beter UX dan bookmarklet)
- Betaalde tier met meer watches
- Slack/Telegram notificaties
- Prijshistorie aggregatie (daily min/max na 90 dagen)
- "Anchoring" selectors (vind element relatief aan tekst)
- Publieke watchlist delen
- Price drop alerts voor specifiek bedrag ("mail me als < €100")

---

*Document versie: 1.0 (Production Ready)*
*Laatst bijgewerkt: 2026-01-05*
*Auteur: Lars*
