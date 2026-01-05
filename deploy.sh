#!/bin/bash
set -e

# PrijsWacht Deployment Script
# Usage: ./deploy.sh [production|staging]

ENVIRONMENT=${1:-production}
COMPOSE_FILE="docker-compose.prod.yml"
ENV_FILE=".env.prod"

echo "🚀 Deploying PrijsWacht to $ENVIRONMENT..."

# Check if .env.prod exists
if [ ! -f "$ENV_FILE" ]; then
    echo "❌ Error: $ENV_FILE not found!"
    echo "   Copy .env.prod.example to .env.prod and configure your settings."
    exit 1
fi

# Load environment variables
export $(grep -v '^#' $ENV_FILE | xargs)

# Pull latest code
echo "📥 Pulling latest code..."
git pull origin main

# Build images
echo "🔨 Building Docker images..."
docker compose -f $COMPOSE_FILE build --no-cache

# Stop current containers (except db to prevent data issues)
echo "⏹️  Stopping current containers..."
docker compose -f $COMPOSE_FILE stop api frontend scheduler

# Run database migrations
echo "📊 Running database migrations..."
docker compose -f $COMPOSE_FILE run --rm api php bin/console doctrine:migrations:migrate --no-interaction

# Clear and warm up cache
echo "🧹 Clearing cache..."
docker compose -f $COMPOSE_FILE run --rm api php bin/console cache:clear --env=prod
docker compose -f $COMPOSE_FILE run --rm api php bin/console cache:warmup --env=prod

# Start new containers
echo "▶️  Starting new containers..."
docker compose -f $COMPOSE_FILE up -d

# Health check
echo "🏥 Running health check..."
sleep 5
if curl -sf http://localhost/api/health > /dev/null; then
    echo "✅ API health check passed!"
else
    echo "⚠️  API health check failed. Check logs with: docker compose -f $COMPOSE_FILE logs api"
fi

# Clean up old images
echo "🧹 Cleaning up old Docker images..."
docker image prune -f

echo ""
echo "✅ Deployment complete!"
echo ""
echo "Useful commands:"
echo "  - View logs:    docker compose -f $COMPOSE_FILE logs -f"
echo "  - API logs:     docker compose -f $COMPOSE_FILE logs -f api"
echo "  - Stop all:     docker compose -f $COMPOSE_FILE down"
echo "  - DB shell:     docker compose -f $COMPOSE_FILE exec db mysql -u\$DB_USER -p\$DB_PASSWORD \$DB_DATABASE"
