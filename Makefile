.PHONY: check build push deploy ship ship-fe

REGISTRY := ghcr.io/larsmun/pricewatch
SHA := $(shell git rev-parse --short HEAD)
VPS_HOST := root@149.210.215.153
VPS_DIR := /opt/shopq

# Run linter + typecheck in Docker frontend container
check:
	@echo "==> Running frontend checks..."
	docker compose exec pricewatch-frontend-dev sh -c "cd /app && npm run lint && npm run typecheck" 2>/dev/null || \
		(cd frontend && npm run lint && npm run typecheck)
	@echo "==> Checks passed!"

# Build Docker images locally for linux/amd64
build:
	@echo "==> Building API image ($(SHA))..."
	docker buildx build --platform linux/amd64 \
		-f docker/php/Dockerfile.prod \
		-t $(REGISTRY)/api:$(SHA) \
		-t $(REGISTRY)/api:latest \
		--load .
	@echo "==> Building scheduler image ($(SHA))..."
	docker buildx build --platform linux/amd64 \
		-f docker/php/Dockerfile.scheduler \
		-t $(REGISTRY)/scheduler:$(SHA) \
		-t $(REGISTRY)/scheduler:latest \
		--load .
	@echo "==> Building frontend image ($(SHA))..."
	docker buildx build --platform linux/amd64 \
		-f docker/nginx/Dockerfile.prod \
		--build-arg VITE_API_URL=https://api.shopq.app \
		-t $(REGISTRY)/frontend:$(SHA) \
		-t $(REGISTRY)/frontend:latest \
		--load .
	@echo "==> All images built!"

# Build only frontend image
build-fe:
	@echo "==> Building frontend image ($(SHA))..."
	docker buildx build --platform linux/amd64 \
		-f docker/nginx/Dockerfile.prod \
		--build-arg VITE_API_URL=https://api.shopq.app \
		-t $(REGISTRY)/frontend:$(SHA) \
		-t $(REGISTRY)/frontend:latest \
		--load .

# Build only API image (without Chromium)
build-api:
	@echo "==> Building API image ($(SHA))..."
	docker buildx build --platform linux/amd64 \
		-f docker/php/Dockerfile.prod \
		-t $(REGISTRY)/api:$(SHA) \
		-t $(REGISTRY)/api:latest \
		--load .

# Push images to GHCR
push:
	@echo "==> Pushing images to GHCR..."
	docker push $(REGISTRY)/api:$(SHA)
	docker push $(REGISTRY)/api:latest
	docker push $(REGISTRY)/scheduler:$(SHA)
	docker push $(REGISTRY)/scheduler:latest
	docker push $(REGISTRY)/frontend:$(SHA)
	docker push $(REGISTRY)/frontend:latest
	@echo "==> All images pushed!"

push-fe:
	@echo "==> Pushing frontend image to GHCR..."
	docker push $(REGISTRY)/frontend:$(SHA)
	docker push $(REGISTRY)/frontend:latest

push-api:
	@echo "==> Pushing API + scheduler images to GHCR..."
	docker push $(REGISTRY)/api:$(SHA)
	docker push $(REGISTRY)/api:latest
	docker push $(REGISTRY)/scheduler:$(SHA)
	docker push $(REGISTRY)/scheduler:latest

# Deploy on VPS via SSH (pulls images + migrates + restarts)
deploy:
	@echo "==> Deploying to production..."
	ssh $(VPS_HOST) 'cd $(VPS_DIR) && git pull origin main && bash deploy.sh'
	@echo "==> Deploy complete!"

# Full pipeline: check -> build -> push -> deploy
ship: check build push deploy

# Frontend-only pipeline
ship-fe: check build-fe push-fe deploy

# API-only pipeline
ship-api: build-api push-api deploy
