# PrijsWacht Deployment Guide

## Prerequisites

- Docker & Docker Compose installed
- Domain configured (prijswacht.nl, api.prijswacht.nl)
- Traefik reverse proxy running (or modify docker-compose.prod.yml for your setup)
- SSL certificates (handled by Traefik with Let's Encrypt)

## Quick Start

1. **Clone the repository:**
   ```bash
   git clone https://github.com/LarsMun/pricewatch.git
   cd pricewatch
   ```

2. **Configure environment:**
   ```bash
   cp .env.prod.example .env.prod
   nano .env.prod  # Edit with your values
   ```

3. **Generate JWT keys:**
   ```bash
   mkdir -p backend/config/jwt
   openssl genrsa -out backend/config/jwt/private.pem 4096
   openssl rsa -in backend/config/jwt/private.pem -pubout -out backend/config/jwt/public.pem
   ```

4. **Deploy:**
   ```bash
   ./deploy.sh
   ```

## Environment Variables

| Variable | Description | Example |
|----------|-------------|---------|
| `DB_ROOT_PASSWORD` | MariaDB root password | `strong_random_password` |
| `DB_PASSWORD` | Application database password | `strong_random_password` |
| `APP_SECRET` | Symfony secret (32+ chars) | `generate_with_openssl_rand` |
| `JWT_PASSPHRASE` | JWT key passphrase | `your_passphrase` |
| `MAILER_DSN` | Email configuration | `smtp://user:pass@host:587` |
| `FRONTEND_URL` | Frontend URL | `https://prijswacht.nl` |
| `VITE_API_URL` | API URL for frontend | `https://api.prijswacht.nl` |

## Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                        Traefik                               │
│                    (Reverse Proxy)                           │
└─────────────────┬────────────────────────┬──────────────────┘
                  │                        │
                  ▼                        ▼
         ┌───────────────┐        ┌───────────────┐
         │   Frontend    │        │     API       │
         │   (nginx)     │        │   (PHP/Apache)│
         │ prijswacht.nl │        │api.prijswacht │
         └───────────────┘        └───────┬───────┘
                                          │
                                          ▼
                                  ┌───────────────┐
                                  │   MariaDB     │
                                  │  (Database)   │
                                  └───────────────┘
                                          ▲
                                          │
                                  ┌───────────────┐
                                  │  Scheduler    │
                                  │ (Price Checks)│
                                  └───────────────┘
```

## Services

| Service | Port | Description |
|---------|------|-------------|
| `frontend` | 80 (internal) | React SPA served by nginx |
| `api` | 80 (internal) | Symfony API |
| `db` | 3306 (internal) | MariaDB database |
| `scheduler` | - | Cron job for price checks |

## Traefik Setup

If you don't have Traefik running, create the external network first:

```bash
docker network create web
```

Example Traefik configuration in `traefik.yml`:
```yaml
entryPoints:
  web:
    address: ":80"
    http:
      redirections:
        entryPoint:
          to: websecure
          scheme: https
  websecure:
    address: ":443"

certificatesResolvers:
  letsencrypt:
    acme:
      email: your@email.com
      storage: /letsencrypt/acme.json
      httpChallenge:
        entryPoint: web
```

## Manual Deployment Steps

If not using the deploy script:

```bash
# Build images
docker compose -f docker-compose.prod.yml build

# Start database first
docker compose -f docker-compose.prod.yml up -d db

# Wait for database to be ready
sleep 10

# Run migrations
docker compose -f docker-compose.prod.yml run --rm api php bin/console doctrine:migrations:migrate --no-interaction

# Start all services
docker compose -f docker-compose.prod.yml up -d
```

## Monitoring

### Health Checks

- **API:** `https://api.prijswacht.nl/api/health`
- **Frontend:** `https://prijswacht.nl/health`

### Logs

```bash
# All logs
docker compose -f docker-compose.prod.yml logs -f

# Specific service
docker compose -f docker-compose.prod.yml logs -f api
docker compose -f docker-compose.prod.yml logs -f scheduler
```

### Database Backup

```bash
# Create backup
docker compose -f docker-compose.prod.yml exec db mysqldump -u$DB_USER -p$DB_PASSWORD $DB_DATABASE > backup_$(date +%Y%m%d).sql

# Restore backup
docker compose -f docker-compose.prod.yml exec -T db mysql -u$DB_USER -p$DB_PASSWORD $DB_DATABASE < backup.sql
```

## Scaling

The scheduler runs every 5 minutes and processes 50 watches per run. Adjust in `docker-compose.prod.yml`:

```yaml
scheduler:
  command: >
    sh -c "while true; do
      php bin/console app:check-prices --limit=100;  # Increase limit
      sleep 180;  # Reduce interval to 3 minutes
    done"
```

## Troubleshooting

### API returns 500 errors
```bash
# Check API logs
docker compose -f docker-compose.prod.yml logs api

# Clear cache
docker compose -f docker-compose.prod.yml exec api php bin/console cache:clear --env=prod
```

### Database connection issues
```bash
# Check database is running
docker compose -f docker-compose.prod.yml ps db

# Test connection
docker compose -f docker-compose.prod.yml exec db mysql -u$DB_USER -p$DB_PASSWORD -e "SELECT 1"
```

### Price checks not running
```bash
# Check scheduler logs
docker compose -f docker-compose.prod.yml logs scheduler

# Run manually
docker compose -f docker-compose.prod.yml exec api php bin/console app:check-prices --limit=5 -v
```
