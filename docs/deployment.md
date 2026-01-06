# ShopQ Deployment Guide

## Live Omgeving

| Omgeving | URL | Status |
|----------|-----|--------|
| Frontend | https://shopq.app | Live |
| API | https://api.shopq.app | Live |
| API Docs | https://api.shopq.app/api/doc | Live |
| Health Check | https://api.shopq.app/api/health | Live |

## VPS Specificaties

| Component | Details |
|-----------|---------|
| Provider | Transip BladeVPS X1 |
| IP | 149.210.215.153 |
| OS | Ubuntu/Debian |
| RAM | ~850 MB + 2GB swap |
| SSH | `ssh shopq` (via ~/.ssh/config) |

## Architectuur

```
                        Internet
                           │
                           ▼
┌─────────────────────────────────────────────────────────────┐
│                     Transip VPS                              │
│                  149.210.215.153                             │
│                                                              │
│  ┌─────────────────────────────────────────────────────┐    │
│  │                    Traefik                           │    │
│  │              (Reverse Proxy + SSL)                   │    │
│  │         Port 80/443 → Let's Encrypt                 │    │
│  └─────────────────┬────────────────────┬──────────────┘    │
│                    │                    │                    │
│                    ▼                    ▼                    │
│           ┌───────────────┐    ┌───────────────┐            │
│           │   Frontend    │    │     API       │            │
│           │    (nginx)    │    │ (PHP/Apache)  │            │
│           │  shopq.app    │    │api.shopq.app  │            │
│           └───────────────┘    └───────┬───────┘            │
│                                        │                     │
│                                        ▼                     │
│                                ┌───────────────┐            │
│                                │   MariaDB     │            │
│                                │   11.2        │            │
│                                └───────────────┘            │
│                                        ▲                     │
│                                        │                     │
│                                ┌───────────────┐            │
│                                │  Scheduler    │            │
│                                │ (elke 5 min)  │            │
│                                └───────────────┘            │
│                                                              │
│  Network: web (external) + shopq_internal                   │
└─────────────────────────────────────────────────────────────┘
```

## Prerequisites

- Docker & Docker Compose v2+
- Traefik v3.x als reverse proxy
- DNS A-records naar VPS IP (geen AAAA records!)
- SSH toegang tot VPS

## CI/CD Pipeline

Docker images worden automatisch gebouwd door GitHub Actions en gepusht naar GitHub Container Registry (GHCR). Dit maakt deployments veel sneller omdat de VPS alleen images hoeft te pullen in plaats van te builden.

| Image | Registry URL | Triggers |
|-------|--------------|----------|
| API | `ghcr.io/larsmun/pricewatch/api:latest` | Push naar main |
| Frontend | `ghcr.io/larsmun/pricewatch/frontend:latest` | Push naar main |

### Workflow

```
┌─────────────────────────────────────────────────────────────────┐
│                      GitHub Actions                              │
│                                                                  │
│   Push to main                                                   │
│        │                                                         │
│        ▼                                                         │
│   ┌─────────────┐    ┌─────────────┐                           │
│   │ Run Tests   │    │ Lint Code   │                           │
│   │ (PHPUnit)   │    │ (ESLint)    │                           │
│   └──────┬──────┘    └──────┬──────┘                           │
│          │                  │                                    │
│          ▼                  ▼                                    │
│   ┌─────────────────────────────────────────┐                   │
│   │         Build Docker Images              │                   │
│   │  - API (PHP/Apache/Chromium)            │                   │
│   │  - Frontend (Node build + nginx)        │                   │
│   └──────────────────┬──────────────────────┘                   │
│                      │                                           │
│                      ▼                                           │
│   ┌─────────────────────────────────────────┐                   │
│   │         Push to GHCR                     │                   │
│   │  ghcr.io/larsmun/pricewatch/api:latest  │                   │
│   │  ghcr.io/larsmun/pricewatch/frontend    │                   │
│   └─────────────────────────────────────────┘                   │
└─────────────────────────────────────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────────┐
│                      VPS Deployment                              │
│                                                                  │
│   ./deploy.sh                                                    │
│        │                                                         │
│        ▼                                                         │
│   docker compose pull  ──►  ~30 seconden (alleen download)      │
│        │                                                         │
│        ▼                                                         │
│   docker compose up -d                                           │
└─────────────────────────────────────────────────────────────────┘
```

## Quick Start (Nieuwe Deployment)

### 1. VPS Voorbereiden

```bash
# SSH naar VPS
ssh shopq

# Docker installeren (indien nodig)
curl -fsSL https://get.docker.com | sh
sudo usermod -aG docker $USER

# Swap toevoegen (voor lage RAM VPS)
sudo fallocate -l 2G /swapfile
sudo chmod 600 /swapfile
sudo mkswap /swapfile
sudo swapon /swapfile
echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab
```

### 2. Traefik Setup

```bash
# Traefik directory
sudo mkdir -p /opt/traefik
sudo touch /opt/traefik/acme.json
sudo chmod 600 /opt/traefik/acme.json

# Traefik config
cat > /opt/traefik/traefik.yml << 'EOF'
api:
  dashboard: true
  insecure: true

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

providers:
  docker:
    endpoint: "unix:///var/run/docker.sock"
    exposedByDefault: false
    network: web

certificatesResolvers:
  letsencrypt:
    acme:
      email: lars@shopq.app
      storage: /acme.json
      httpChallenge:
        entryPoint: web
EOF

# Docker network voor Traefik
docker network create web

# Start Traefik
docker run -d \
  --name traefik \
  --network web \
  -p 80:80 -p 443:443 -p 8080:8080 \
  -v /opt/traefik/traefik.yml:/etc/traefik/traefik.yml \
  -v /opt/traefik/acme.json:/acme.json \
  -v /var/run/docker.sock:/var/run/docker.sock \
  traefik:latest
```

### 3. ShopQ Deployen

```bash
# Clone repository
cd /opt
git clone https://github.com/LarsMun/pricewatch.git shopq
cd shopq

# Environment configureren
cp .env.prod.example .env.prod
nano .env.prod  # Vul je waarden in

# JWT keys genereren
mkdir -p backend/config/jwt
openssl genrsa -out backend/config/jwt/private.pem 4096
openssl rsa -in backend/config/jwt/private.pem -pubout -out backend/config/jwt/public.pem

# Pull pre-built images en starten
docker compose -f docker-compose.prod.yml --env-file .env.prod pull
docker compose -f docker-compose.prod.yml --env-file .env.prod up -d

# Database migraties
docker compose -f docker-compose.prod.yml --env-file .env.prod \
  exec api php bin/console doctrine:migrations:migrate --no-interaction
```

## Environment Variables

| Variable | Beschrijving | Voorbeeld |
|----------|--------------|-----------|
| `DB_ROOT_PASSWORD` | MariaDB root password | `strong_random_password` |
| `DB_DATABASE` | Database naam | `shopq` |
| `DB_USER` | Database gebruiker | `shopq` |
| `DB_PASSWORD` | Database password | `strong_random_password` |
| `APP_SECRET` | Symfony secret (32+ chars) | `openssl rand -hex 32` |
| `JWT_SECRET_KEY` | Pad naar private key | `%kernel.project_dir%/config/jwt/private.pem` |
| `JWT_PUBLIC_KEY` | Pad naar public key | `%kernel.project_dir%/config/jwt/public.pem` |
| `JWT_PASSPHRASE` | JWT passphrase | (leeg of je passphrase) |
| `MAILER_DSN` | Email configuratie | `resend+api://API_KEY@default` |
| `FRONTEND_URL` | Frontend URL | `https://shopq.app` |
| `VITE_API_URL` | API URL voor frontend | `https://api.shopq.app` |
| `CORS_ALLOW_ORIGIN` | CORS regex | `^https://shopq\.app$` |
| `SENTRY_DSN` | Sentry error tracking | (optioneel) |

## Services

| Service | Container | Intern Port | Beschrijving |
|---------|-----------|-------------|--------------|
| `frontend` | shopq-frontend-1 | 80 | React SPA via nginx |
| `api` | shopq-api-1 | 80 | Symfony API via Apache |
| `db` | shopq-db-1 | 3306 | MariaDB 11.2 |
| `scheduler` | shopq-scheduler-1 | - | Prijscheck cron (5 min) |

## Dagelijks Beheer

### Updates Deployen

De snelste manier is het deployment script gebruiken:

```bash
ssh shopq
cd /opt/shopq
./deploy.sh
```

Of handmatig:

```bash
ssh shopq
cd /opt/shopq
git pull origin main

# Pull nieuwe images (gebouwd door GitHub Actions)
docker compose -f docker-compose.prod.yml --env-file .env.prod pull

# Herstart containers
docker compose -f docker-compose.prod.yml --env-file .env.prod up -d

# Migraties (indien nodig)
docker compose -f docker-compose.prod.yml --env-file .env.prod \
  exec api php bin/console doctrine:migrations:migrate --no-interaction
```

> **Tip**: Wacht ~2 minuten na een push naar main voordat je deployed, zodat GitHub Actions de images kan bouwen.

### Logs Bekijken

```bash
# Alle services
docker compose -f docker-compose.prod.yml logs -f

# Specifieke service
docker compose -f docker-compose.prod.yml logs -f api
docker compose -f docker-compose.prod.yml logs -f scheduler

# Traefik logs
docker logs -f traefik
```

### Cache Legen

```bash
docker compose -f docker-compose.prod.yml --env-file .env.prod \
  exec api php bin/console cache:clear --env=prod
```

### Database Backup

```bash
# Backup maken
docker compose -f docker-compose.prod.yml exec db \
  mysqldump -ushopq -p$DB_PASSWORD shopq > backup_$(date +%Y%m%d).sql

# Backup herstellen
docker compose -f docker-compose.prod.yml exec -T db \
  mysql -ushopq -p$DB_PASSWORD shopq < backup.sql
```

## Monitoring

### Health Checks

```bash
# API health
curl https://api.shopq.app/api/health

# Frontend
curl -I https://shopq.app

# Traefik dashboard (intern)
ssh shopq -L 8080:localhost:8080
# Open http://localhost:8080 in browser
```

### Container Status

```bash
ssh shopq
docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"
```

## Troubleshooting

### SSL Certificaat Problemen

Let's Encrypt heeft problemen met IPv6. Zorg dat:
- Alleen A-records zijn ingesteld (geen AAAA records)
- Traefik correct is geconfigureerd
- acme.json permissions: `chmod 600 /opt/traefik/acme.json`

```bash
# Check certificaten
cat /opt/traefik/acme.json | jq '.letsencrypt.Certificates[].domain'
```

### Container Unhealthy

```bash
# Check health details
docker inspect shopq-api-1 --format '{{json .State.Health}}' | jq

# Handmatig health testen
docker exec shopq-api-1 curl -s localhost/api/health
```

### Memory Issues

De VPS heeft beperkt geheugen. Bij OOM:

```bash
# Check swap
free -h

# Swap toevoegen/vergroten
sudo swapoff /swapfile
sudo fallocate -l 4G /swapfile
sudo mkswap /swapfile
sudo swapon /swapfile
```

### API 500 Errors

```bash
# Symfony logs
docker compose -f docker-compose.prod.yml logs api | grep -i error

# Cache problemen
docker compose -f docker-compose.prod.yml --env-file .env.prod \
  exec api php bin/console cache:clear --env=prod

# Permissies
docker compose -f docker-compose.prod.yml --env-file .env.prod \
  exec api chown -R www-data:www-data var/
```

### Database Connection Issues

```bash
# Check database container
docker compose -f docker-compose.prod.yml ps db

# Test connectie
docker compose -f docker-compose.prod.yml exec db \
  mysql -ushopq -p$DB_PASSWORD -e "SELECT 1"
```

## Scaling

De scheduler verwerkt standaard 50 watches per 5 minuten. Aanpassen in `docker-compose.prod.yml`:

```yaml
scheduler:
  command: >
    sh -c "while true; do
      php bin/console app:check-prices --limit=100;  # Meer watches
      sleep 180;  # Elke 3 minuten
    done"
```

## SSH Configuratie (Lokaal)

Voeg toe aan `~/.ssh/config`:

```
Host shopq
    HostName 149.210.215.153
    User larsmunne
    IdentityFile ~/.ssh/shopq-vps
```
