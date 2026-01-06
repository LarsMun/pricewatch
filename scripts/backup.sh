#!/bin/bash
#
# ShopQ Database Backup Script
# Creates compressed database backups and optionally uploads to remote storage.
#
# Usage:
#   ./scripts/backup.sh [--upload]
#
# Environment variables (set in .env.prod):
#   DB_USER, DB_PASSWORD, DB_DATABASE
#   BACKUP_RETENTION_DAYS (default: 7)
#   BACKUP_REMOTE (optional, rclone remote name for cloud upload)
#

set -euo pipefail

# Configuration
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
BACKUP_DIR="${PROJECT_DIR}/backups"
RETENTION_DAYS="${BACKUP_RETENTION_DAYS:-7}"
COMPOSE_FILE="${PROJECT_DIR}/docker-compose.prod.yml"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

log_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

log_warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if running from project directory
if [ ! -f "${COMPOSE_FILE}" ]; then
    log_error "docker-compose.prod.yml not found. Run this script from the project root."
    exit 1
fi

# Load environment variables
if [ -f "${PROJECT_DIR}/.env.prod" ]; then
    export $(grep -v '^#' "${PROJECT_DIR}/.env.prod" | xargs)
else
    log_error ".env.prod not found. Cannot determine database credentials."
    exit 1
fi

# Verify required variables
if [ -z "${DB_PASSWORD:-}" ] || [ -z "${DB_DATABASE:-}" ]; then
    log_error "DB_PASSWORD and DB_DATABASE must be set in .env.prod"
    exit 1
fi

DB_USER="${DB_USER:-pricewatch}"
DB_DATABASE="${DB_DATABASE:-pricewatch}"

# Create backup directory if it doesn't exist
mkdir -p "${BACKUP_DIR}"

# Generate backup filename with timestamp
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="${BACKUP_DIR}/shopq_${TIMESTAMP}.sql.gz"

log_info "Starting database backup..."
log_info "Database: ${DB_DATABASE}"
log_info "Backup file: ${BACKUP_FILE}"

# Create backup using docker exec
if docker compose -f "${COMPOSE_FILE}" exec -T db \
    mariadb-dump \
    -u"${DB_USER}" \
    -p"${DB_PASSWORD}" \
    --single-transaction \
    --routines \
    --triggers \
    "${DB_DATABASE}" | gzip > "${BACKUP_FILE}"; then

    BACKUP_SIZE=$(du -h "${BACKUP_FILE}" | cut -f1)
    log_info "Backup created successfully (${BACKUP_SIZE})"
else
    log_error "Backup failed!"
    rm -f "${BACKUP_FILE}"
    exit 1
fi

# Verify backup is not empty
if [ ! -s "${BACKUP_FILE}" ]; then
    log_error "Backup file is empty!"
    rm -f "${BACKUP_FILE}"
    exit 1
fi

# Upload to remote storage if --upload flag is passed and BACKUP_REMOTE is set
if [ "${1:-}" = "--upload" ] && [ -n "${BACKUP_REMOTE:-}" ]; then
    log_info "Uploading to remote storage: ${BACKUP_REMOTE}"

    if command -v rclone &> /dev/null; then
        if rclone copy "${BACKUP_FILE}" "${BACKUP_REMOTE}:shopq-backups/"; then
            log_info "Upload successful"
        else
            log_warn "Upload failed, but local backup was created"
        fi
    else
        log_warn "rclone not installed, skipping remote upload"
    fi
fi

# Clean up old backups
log_info "Cleaning up backups older than ${RETENTION_DAYS} days..."
DELETED_COUNT=$(find "${BACKUP_DIR}" -name "shopq_*.sql.gz" -type f -mtime +${RETENTION_DAYS} -delete -print | wc -l)

if [ "${DELETED_COUNT}" -gt 0 ]; then
    log_info "Deleted ${DELETED_COUNT} old backup(s)"
fi

# List current backups
log_info "Current backups:"
ls -lh "${BACKUP_DIR}"/shopq_*.sql.gz 2>/dev/null || echo "  (none)"

log_info "Backup complete!"
