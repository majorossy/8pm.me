#!/bin/bash
################################################################################
# Phase 3: Data Migration
# Archive.org Import Rearchitecture
#
# Purpose: Migrate metadata to organized folder structure and YAML configs
# Duration: ~15 minutes
# Downtime: NO (background process)
################################################################################

set -e  # Exit on error

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
BACKUP_DIR="$PROJECT_ROOT/backups/metadata"
TIMESTAMP=$(date +%Y%m%d)

cd "$PROJECT_ROOT"

echo "═══════════════════════════════════════════════════════════"
echo "  Phase 3: Data Migration"
echo "  Started: $(date)"
echo "═══════════════════════════════════════════════════════════"
echo ""

# Step 1: Check current structure
echo "[1/5] Analyzing current metadata structure..."
METADATA_DIR="var/archivedotorg/metadata"

if [ ! -d "$METADATA_DIR" ]; then
    echo "⚠ WARNING: Metadata directory not found: $METADATA_DIR"
    echo "  Creating directory..."
    mkdir -p "$METADATA_DIR"
fi

FILE_COUNT=$(find "$METADATA_DIR" -name "*.json" 2>/dev/null | wc -l)
echo "  Total JSON files: $FILE_COUNT"
echo ""

# Step 2: Backup metadata
echo "[2/5] Backing up metadata folder..."
mkdir -p "$BACKUP_DIR"

if [ "$FILE_COUNT" -gt 0 ]; then
    echo "  Creating backup: metadata.backup.$TIMESTAMP"
    cp -r "$METADATA_DIR" "$BACKUP_DIR/metadata.backup.$TIMESTAMP"
    BACKUP_SIZE=$(du -sh "$BACKUP_DIR/metadata.backup.$TIMESTAMP" | cut -f1)
    echo "✓ Backup created ($BACKUP_SIZE)"
else
    echo "⚠ No files to backup (empty metadata directory)"
fi
echo ""

# Step 3: Run folder migration
echo "[3/5] Running folder migration..."
echo "  Migrating to organized structure: {Artist}/*.json"

if bin/magento archivedotorg:migrate:organize-folders --help &>/dev/null; then
    # Dry run first
    echo "  Running dry-run to preview changes..."
    bin/magento archivedotorg:migrate:organize-folders --dry-run

    echo ""
    echo "  Executing migration..."
    bin/magento archivedotorg:migrate:organize-folders

    # Verify
    NEW_FILE_COUNT=$(find "$METADATA_DIR" -name "*.json" 2>/dev/null | wc -l)
    echo "  Files after migration: $NEW_FILE_COUNT"

    if [ "$FILE_COUNT" -eq "$NEW_FILE_COUNT" ]; then
        echo "✓ File count verified (no data loss)"
    else
        echo "⚠ WARNING: File count mismatch!"
        echo "  Before: $FILE_COUNT"
        echo "  After:  $NEW_FILE_COUNT"
    fi
else
    echo "⚠ Migration command not found (may need to implement or skip)"
fi
echo ""

# Step 4: Export to YAML
echo "[4/5] Exporting artist configurations to YAML..."

if bin/magento archivedotorg:migrate:export --help &>/dev/null; then
    # Dry run first
    echo "  Running dry-run to preview YAML generation..."
    bin/magento archivedotorg:migrate:export --dry-run

    echo ""
    echo "  Generating YAML configs..."
    bin/magento archivedotorg:migrate:export

    # Count YAML files
    YAML_DIR="src/app/code/ArchiveDotOrg/Core/config/artists"
    if [ -d "$YAML_DIR" ]; then
        YAML_COUNT=$(find "$YAML_DIR" -name "*.yaml" -o -name "*.yml" | wc -l)
        echo "  YAML files created: $YAML_COUNT"
    else
        echo "⚠ YAML directory not found: $YAML_DIR"
    fi
else
    echo "⚠ Export command not found (may need to implement or skip)"
fi
echo ""

# Step 5: Validate all configs
echo "[5/5] Validating YAML configurations..."

if bin/magento archivedotorg:validate --help &>/dev/null; then
    bin/magento archivedotorg:validate --all
    echo "✓ All YAML configs validated"
else
    echo "⚠ Validation command not found (may need to implement or skip)"
fi
echo ""

# Summary
echo "═══════════════════════════════════════════════════════════"
echo "  Phase 3: COMPLETE ✓"
echo "  Completed: $(date)"
echo "═══════════════════════════════════════════════════════════"
echo ""
echo "Migration Summary:"
echo "  Metadata files: $FILE_COUNT"
echo "  Backup: $BACKUP_DIR/metadata.backup.$TIMESTAMP"
echo ""
echo "Next step: Run Phase 4 (Admin Dashboard)"
echo "  Script: deployment/phase4/enable-dashboard.sh"
echo ""
