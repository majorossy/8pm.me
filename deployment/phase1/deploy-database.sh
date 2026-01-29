#!/bin/bash
################################################################################
# Phase 1: Database Migration Deployment
# Archive.org Import Rearchitecture
#
# Purpose: Deploy database schema changes with minimal downtime
# Duration: ~5 minutes
# Downtime: YES (maintenance mode)
################################################################################

set -e  # Exit on error

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
BACKUP_DIR="$PROJECT_ROOT/backups/db"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

cd "$PROJECT_ROOT"

echo "═══════════════════════════════════════════════════════════"
echo "  Phase 1: Database Migration"
echo "  Started: $(date)"
echo "═══════════════════════════════════════════════════════════"
echo ""

# Step 1: Create backup directory
echo "[1/6] Creating backup directory..."
mkdir -p "$BACKUP_DIR"
echo "✓ Backup directory: $BACKUP_DIR"
echo ""

# Step 2: Backup database
echo "[2/6] Backing up production database..."
echo "  Timestamp: $TIMESTAMP"
bin/mysql -e "SELECT NOW() as backup_started;"

bin/mysqldump --add-routines --no-tablespaces > "$BACKUP_DIR/backup_$TIMESTAMP.sql"

BACKUP_SIZE=$(du -h "$BACKUP_DIR/backup_$TIMESTAMP.sql" | cut -f1)
echo "✓ Backup created: backup_$TIMESTAMP.sql ($BACKUP_SIZE)"
echo ""

# Step 3: Enable maintenance mode
echo "[3/6] Enabling maintenance mode..."
bin/magento maintenance:enable
echo "✓ Maintenance mode: ENABLED"
echo ""

# Step 4: Run database migrations
echo "[4/6] Running database migrations..."
echo "  This may take 2-3 minutes..."
START_TIME=$(date +%s)

bin/magento setup:upgrade 2>&1 | tee "$BACKUP_DIR/migration_log_$TIMESTAMP.txt"

END_TIME=$(date +%s)
DURATION=$((END_TIME - START_TIME))
echo "✓ Migrations completed in ${DURATION}s"
echo ""

# Step 5: Verify tables
echo "[5/6] Verifying database tables..."
EXPECTED_TABLES=9
ACTUAL_TABLES=$(bin/mysql -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='magento' AND table_name LIKE 'archivedotorg_%';")

echo "  Expected tables: $EXPECTED_TABLES"
echo "  Actual tables:   $ACTUAL_TABLES"

if [ "$ACTUAL_TABLES" -eq "$EXPECTED_TABLES" ]; then
    echo "✓ All tables verified"
else
    echo "✗ ERROR: Table count mismatch!"
    echo ""
    echo "Rolling back..."
    bin/mysql magento < "$BACKUP_DIR/backup_$TIMESTAMP.sql"
    bin/magento maintenance:disable
    exit 1
fi

# Verify indexes
echo ""
echo "  Verifying indexes..."
INDEXES=$(bin/mysql -N -e "SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema='magento' AND table_name='catalog_product_entity' AND index_name='idx_created_at';")

if [ "$INDEXES" -gt 0 ]; then
    echo "✓ Critical indexes present"
else
    echo "⚠ WARNING: Some indexes may be missing"
fi
echo ""

# Step 6: Disable maintenance mode
echo "[6/6] Disabling maintenance mode..."
bin/magento maintenance:disable
echo "✓ Maintenance mode: DISABLED"
echo ""

# Summary
echo "═══════════════════════════════════════════════════════════"
echo "  Phase 1: COMPLETE ✓"
echo "  Completed: $(date)"
echo "  Total Duration: ${DURATION}s"
echo "═══════════════════════════════════════════════════════════"
echo ""
echo "Next step: Run Phase 2 (Code Deployment)"
echo "  Script: deployment/phase2/deploy-code.sh"
echo ""
echo "Backup location: $BACKUP_DIR/backup_$TIMESTAMP.sql"
echo ""
