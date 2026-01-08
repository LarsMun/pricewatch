# WordPress CMS Integratie

## Overzicht

WordPress is geïntegreerd als CMS voor de marketing/landing pages van ShopQ. De React app draait onder `/app/` terwijl WordPress de rest van de website beheert.

## URL Structuur

| URL | Service | Beschrijving |
|-----|---------|--------------|
| `shopq.app/` | WordPress | Landing page, marketing |
| `shopq.app/wp-admin/` | WordPress | Admin dashboard |
| `shopq.app/blog/` | WordPress | Blog artikelen |
| `shopq.app/app/` | React | ShopQ applicatie |
| `shopq.app/app/login` | React | Login pagina |
| `shopq.app/app/dashboard` | React | User dashboard |
| `api.shopq.app/` | Symfony | REST API |

## Architectuur

```
                        Internet
                           │
                           ▼
┌─────────────────────────────────────────────────────────────┐
│                        Traefik                               │
│                   (Reverse Proxy + SSL)                      │
└──────────┬────────────────┬────────────────┬────────────────┘
           │                │                │
           │ Priority 20    │ Priority 10    │ Host: api.*
           │ /app/*         │ /* (default)   │
           ▼                ▼                ▼
    ┌──────────────┐ ┌──────────────┐ ┌──────────────┐
    │ React App    │ │  WordPress   │ │  Symfony API │
    │ (nginx)      │ │  (Apache)    │ │  (Apache)    │
    └──────────────┘ └──────┬───────┘ └──────────────┘
                           │
                           ▼
                    ┌──────────────┐
                    │ WordPress DB │
                    │  (MariaDB)   │
                    └──────────────┘
```

## Traefik Routing

De routing werkt op basis van priority:

1. **Frontend (priority 20)**: `Host('shopq.app') && PathPrefix('/app')` - Alleen `/app/*` routes
2. **WordPress (priority 10)**: `Host('shopq.app')` - Alle andere routes (fallback)
3. **API**: `Host('api.shopq.app')` - Aparte subdomain

## Docker Services

### WordPress Container

```yaml
wordpress:
  image: wordpress:6.7-php8.3-apache
  environment:
    WORDPRESS_DB_HOST: wordpress-db
    WORDPRESS_DB_USER: ${WP_DB_USER:-wordpress}
    WORDPRESS_DB_PASSWORD: ${WP_DB_PASSWORD}
    WORDPRESS_DB_NAME: ${WP_DB_NAME:-wordpress}
    WORDPRESS_CONFIG_EXTRA: |
      define('WP_HOME', 'https://shopq.app');
      define('WP_SITEURL', 'https://shopq.app');
      define('FORCE_SSL_ADMIN', true);
      if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
          $_SERVER['HTTPS'] = 'on';
      }
  volumes:
    - wordpress-data:/var/www/html
  labels:
    - "traefik.http.routers.wordpress.rule=Host(`shopq.app`) || Host(`www.shopq.app`)"
    - "traefik.http.routers.wordpress.priority=10"
```

### WordPress Database

```yaml
wordpress-db:
  image: mariadb:11.2
  environment:
    MARIADB_ROOT_PASSWORD: ${WP_DB_ROOT_PASSWORD}
    MARIADB_DATABASE: ${WP_DB_NAME:-wordpress}
    MARIADB_USER: ${WP_DB_USER:-wordpress}
    MARIADB_PASSWORD: ${WP_DB_PASSWORD}
  volumes:
    - wordpress-db-data:/var/lib/mysql
```

## Environment Variables

Voeg toe aan `.env.prod`:

```bash
# WordPress Database
WP_DB_ROOT_PASSWORD=<secure_random_password>
WP_DB_NAME=wordpress
WP_DB_USER=wordpress
WP_DB_PASSWORD=<secure_random_password>
```

Genereer veilige passwords met:
```bash
openssl rand -base64 32 | tr -d '/+=' | head -c 32
```

## SSL Achter Reverse Proxy

WordPress draait achter Traefik die SSL termineert. Zonder extra configuratie ontstaat een redirect loop omdat WordPress denkt dat het op HTTP draait.

De fix in `wp-config.php`:

```php
define('WP_HOME', 'https://shopq.app');
define('WP_SITEURL', 'https://shopq.app');
define('FORCE_SSL_ADMIN', true);

// Detecteer HTTPS via Traefik's X-Forwarded-Proto header
if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
    $_SERVER['HTTPS'] = 'on';
}
```

Dit wordt automatisch toegevoegd via `WORDPRESS_CONFIG_EXTRA` bij nieuwe installaties. Voor bestaande installaties moet het handmatig worden toegevoegd.

## Installatie

### Nieuwe Installatie

1. Voeg WordPress env vars toe aan `.env.prod`
2. Start containers:
   ```bash
   docker compose -f docker-compose.prod.yml --env-file .env.prod up -d wordpress wordpress-db
   ```
3. Ga naar `https://shopq.app/wp-admin/install.php`
4. Voltooi de setup wizard

### WP-CLI Gebruiken

WordPress CLI is niet standaard geïnstalleerd. Download het eerst:

```bash
docker exec shopq-wordpress-1 bash -c "curl -sO https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar"

# Gebruiker aanmaken
docker exec shopq-wordpress-1 php wp-cli.phar user create username email@example.com \
  --role=administrator --user_pass='password' --allow-root --path=/var/www/html

# Plugin installeren
docker exec shopq-wordpress-1 php wp-cli.phar plugin install plugin-name --activate \
  --allow-root --path=/var/www/html
```

## Beheer

### WordPress Admin

- URL: `https://shopq.app/wp-admin/`
- Gebruiker: `lars`
- Email: `lars@munne.me`

### Database Toegang

```bash
# Via docker exec
docker exec -it shopq-wordpress-db-1 mariadb -uwordpress -p wordpress

# Gebruikers bekijken
docker exec shopq-wordpress-db-1 mariadb -uwordpress -p<password> wordpress \
  -e "SELECT user_login, user_email FROM wp_users"
```

### Backup

```bash
# WordPress database backup
docker exec shopq-wordpress-db-1 mariadb-dump -uwordpress -p<password> wordpress \
  > wordpress_backup_$(date +%Y%m%d).sql

# WordPress bestanden backup
docker cp shopq-wordpress-1:/var/www/html ./wordpress_files_backup

# Restore database
docker exec -i shopq-wordpress-db-1 mariadb -uwordpress -p<password> wordpress \
  < wordpress_backup.sql
```

### Logs

```bash
# WordPress/Apache logs
docker compose -f docker-compose.prod.yml logs -f wordpress

# Database logs
docker compose -f docker-compose.prod.yml logs -f wordpress-db
```

## React App Aanpassingen

De React app is aangepast om onder `/app/` te draaien:

### Vite Config (`frontend/vite.config.ts`)

```typescript
export default defineConfig({
  base: '/app/',
  plugins: [
    VitePWA({
      manifest: {
        start_url: '/app/',
        scope: '/app/',
        // ...
      }
    })
  ]
})
```

### React Router (`frontend/src/main.tsx`)

```tsx
<BrowserRouter basename="/app">
  <App />
</BrowserRouter>
```

Alle interne `<Link to="/...">` routes werken automatisch correct door de `basename` instelling.

## Troubleshooting

### Redirect Loop op wp-login.php

**Oorzaak**: WordPress detecteert geen HTTPS achter Traefik.

**Oplossing**: Voeg SSL detectie toe aan wp-config.php:

```bash
docker exec shopq-wordpress-1 bash -c 'cat >> /var/www/html/wp-config.php << "EOF"

// SSL behind reverse proxy fix
define("FORCE_SSL_ADMIN", true);
if (isset($_SERVER["HTTP_X_FORWARDED_PROTO"]) && $_SERVER["HTTP_X_FORWARDED_PROTO"] === "https") {
    $_SERVER["HTTPS"] = "on";
}
EOF'
```

### React App Flash voor WordPress Pages

**Oorzaak**: PWA service worker van oude installatie cached alle routes.

**Oplossing voor gebruikers**:
1. Open DevTools (F12)
2. Application → Service Workers → Unregister
3. Application → Storage → Clear site data

De nieuwe service worker scope is `/app/` dus dit probleem treedt niet op voor nieuwe bezoekers.

### WordPress Container Start Niet

**Controleer**:
```bash
# Container logs
docker compose -f docker-compose.prod.yml logs wordpress

# Database health
docker compose -f docker-compose.prod.yml ps wordpress-db

# Environment variables
grep WP_ .env.prod
```

### wp-config.php Handmatig Repareren

Als wp-config.php corrupt is:

```bash
# Backup huidige config
docker exec shopq-wordpress-1 cp /var/www/html/wp-config.php /var/www/html/wp-config.php.bak

# Nieuwe config uploaden
scp wp-config-fix.php user@server:/tmp/
docker cp /tmp/wp-config-fix.php shopq-wordpress-1:/var/www/html/wp-config.php
```

### Database Reset

**Let op**: Dit verwijdert alle WordPress content!

```bash
# Stop WordPress
docker compose -f docker-compose.prod.yml stop wordpress

# Verwijder database volume
docker volume rm shopq_wordpress-db-data

# Start opnieuw (database wordt opnieuw aangemaakt)
docker compose -f docker-compose.prod.yml up -d wordpress-db wordpress
```

## WordPress in Menu Linken naar App

In WordPress admin:
1. Ga naar Appearance → Menus
2. Voeg Custom Link toe:
   - URL: `/app/`
   - Link Text: "Start App" of "Login"
3. Sla menu op

## Aanbevolen Plugins

- **Yoast SEO** - SEO optimalisatie
- **WP Super Cache** - Caching (let op: test met Traefik)
- **Wordfence** - Security
- **UpdraftPlus** - Backups

## Resource Limieten

De WordPress containers hebben resource limieten in docker-compose.prod.yml:

| Container | CPU | Memory |
|-----------|-----|--------|
| wordpress | 1.0 | 512MB |
| wordpress-db | 0.5 | 256MB |

Pas aan indien nodig voor betere performance.
