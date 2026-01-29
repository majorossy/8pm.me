#!/bin/bash
################################################################################
# Phase 5: Cleanup Old Code
# Archive.org Import Rearchitecture
#
# Purpose: Remove deprecated code after 30-day grace period
# Duration: ~5 minutes
# Downtime: NO
#
# IMPORTANT: Only run this 30+ days after Phase 4 deployment
################################################################################

set -e  # Exit on error

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="$PROJECT_ROOT/backups/cleanup"

cd "$PROJECT_ROOT"

echo "═══════════════════════════════════════════════════════════"
echo "  Phase 5: Cleanup Old Code"
echo "  Started: $(date)"
echo "═══════════════════════════════════════════════════════════"
echo ""

# Safety check
echo "⚠️  SAFETY CHECK ⚠️"
echo ""
echo "This script will DELETE old data patches and deprecated code."
echo "Only run this if:"
echo "  1. Phase 4 was deployed 30+ days ago"
echo "  2. System has been stable"
echo "  3. No rollback needed"
echo ""
read -p "Are you sure you want to continue? (yes/no): " CONFIRM

if [ "$CONFIRM" != "yes" ]; then
    echo ""
    echo "Aborted by user. No changes made."
    exit 0
fi
echo ""

# Step 1: Create backup
echo "[1/4] Creating backup before cleanup..."
mkdir -p "$BACKUP_DIR"

# List files to be deleted
OLD_PATCHES=(
    "src/app/code/ArchiveDotOrg/Core/Setup/Patch/Data/CreateCategoryStructure.php"
    "src/app/code/ArchiveDotOrg/Core/Setup/Patch/Data/AddAdditionalArtists.php"
    "src/app/code/ArchiveDotOrg/Core/Setup/Patch/Data/AddTracksGroup1.php"
    "src/app/code/ArchiveDotOrg/Core/Setup/Patch/Data/AddTracksGroup2.php"
    "src/app/code/ArchiveDotOrg/Core/Setup/Patch/Data/AddTracksGroup3.php"
)

# Backup files before deletion
for PATCH in "${OLD_PATCHES[@]}"; do
    if [ -f "$PATCH" ]; then
        BACKUP_PATH="$BACKUP_DIR/$(basename "$PATCH")"
        cp "$PATCH" "$BACKUP_PATH"
        echo "  Backed up: $(basename "$PATCH")"
    fi
done

echo "✓ Backups created in: $BACKUP_DIR"
echo ""

# Step 2: Delete old data patches
echo "[2/4] Deleting old data patches..."
DELETED_COUNT=0

for PATCH in "${OLD_PATCHES[@]}"; do
    if [ -f "$PATCH" ]; then
        rm "$PATCH"
        echo "  Deleted: $PATCH"
        ((DELETED_COUNT++))
    else
        echo "  Not found: $PATCH (already removed)"
    fi
done

echo "✓ Deleted $DELETED_COUNT files"
echo ""

# Step 3: Clean database patch_list table
echo "[3/4] Cleaning database patch_list table..."

# Delete patch records
bin/mysql -e "DELETE FROM patch_list WHERE patch_name LIKE '%CreateCategoryStructure%';"
bin/mysql -e "DELETE FROM patch_list WHERE patch_name LIKE '%AddAdditionalArtists%';"
bin/mysql -e "DELETE FROM patch_list WHERE patch_name LIKE '%AddTracksGroup%';"

REMAINING_PATCHES=$(bin/mysql -N -e "SELECT COUNT(*) FROM patch_list WHERE patch_name LIKE '%ArchiveDotOrg%';")
echo "  Remaining ArchiveDotOrg patches: $REMAINING_PATCHES"
echo "✓ Database cleaned"
echo ""

# Step 4: Create final backup
echo "[4/4] Creating final system backup..."
FINAL_BACKUP_DIR="$PROJECT_ROOT/backups/final_$TIMESTAMP"
mkdir -p "$FINAL_BACKUP_DIR"

# Backup database
echo "  Backing up database..."
mysqldump magento > "$FINAL_BACKUP_DIR/database.sql"

# Backup code
echo "  Backing up code..."
git archive -o "$FINAL_BACKUP_DIR/code.tar.gz" HEAD

# Backup metadata
echo "  Backing up metadata..."
if [ -d "var/archivedotorg/metadata" ]; then
    tar -czf "$FINAL_BACKUP_DIR/metadata.tar.gz" var/archivedotorg/metadata
fi

BACKUP_SIZE=$(du -sh "$FINAL_BACKUP_DIR" | cut -f1)
echo "✓ Final backup created ($BACKUP_SIZE)"
echo "  Location: $FINAL_BACKUP_DIR"
echo ""

# Summary
echo "═══════════════════════════════════════════════════════════"
echo "  Phase 5: COMPLETE ✓"
echo "  Completed: $(date)"
echo "═══════════════════════════════════════════════════════════"
echo ""
echo "Cleanup Summary:"
echo "  Files deleted: $DELETED_COUNT"
echo "  Backups: $BACKUP_DIR"
echo "  Final backup: $FINAL_BACKUP_DIR"
echo ""
echo "🎉 All deployment phases complete!"
echo ""
echo "Post-deployment tasks:"
echo "  1. Continue monitoring for 30+ days"
echo "  2. Collect user feedback"
echo "  3. Document lessons learned"
echo "  4. Plan version 2.0 features"
echo ""
