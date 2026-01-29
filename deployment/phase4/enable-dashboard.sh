#!/bin/bash
################################################################################
# Phase 4: Admin Dashboard Activation
# Archive.org Import Rearchitecture
#
# Purpose: Enable admin dashboard module
# Duration: ~5 minutes
# Downtime: NO
################################################################################

set -e  # Exit on error

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"

cd "$PROJECT_ROOT"

echo "═══════════════════════════════════════════════════════════"
echo "  Phase 4: Admin Dashboard Activation"
echo "  Started: $(date)"
echo "═══════════════════════════════════════════════════════════"
echo ""

# Step 1: Check module status
echo "[1/4] Checking Admin module status..."
MODULE_STATUS=$(bin/magento module:status ArchiveDotOrg_Admin 2>/dev/null || echo "not_found")

if echo "$MODULE_STATUS" | grep -q "ArchiveDotOrg_Admin"; then
    echo "  Module found: ArchiveDotOrg_Admin"

    if echo "$MODULE_STATUS" | grep -q "Enabled"; then
        echo "✓ Module already enabled"
    else
        echo "  Module status: Disabled"
        echo "  Enabling module..."
        bin/magento module:enable ArchiveDotOrg_Admin
        echo "✓ Module enabled"
    fi
else
    echo "⚠ WARNING: ArchiveDotOrg_Admin module not found"
    echo "  This may need to be created in Phase 5 work"
    echo "  Continuing with cache flush..."
fi
echo ""

# Step 2: Clear caches
echo "[2/4] Clearing admin caches..."
bin/magento cache:flush
echo "✓ Caches cleared"
echo ""

# Step 3: Verify dashboard accessibility
echo "[3/4] Verifying dashboard routes..."

# Check if admin routes are registered
ROUTES_XML="src/app/code/ArchiveDotOrg/Admin/etc/adminhtml/routes.xml"
if [ -f "$ROUTES_XML" ]; then
    echo "✓ Admin routes configuration found"
else
    echo "⚠ Admin routes not found: $ROUTES_XML"
fi

# Check if menu is registered
MENU_XML="src/app/code/ArchiveDotOrg/Admin/etc/adminhtml/menu.xml"
if [ -f "$MENU_XML" ]; then
    echo "✓ Admin menu configuration found"
else
    echo "⚠ Admin menu not found: $MENU_XML"
fi

# Check for controller
CONTROLLER="src/app/code/ArchiveDotOrg/Admin/Controller/Adminhtml/Dashboard/Index.php"
if [ -f "$CONTROLLER" ]; then
    echo "✓ Dashboard controller found"
else
    echo "⚠ Dashboard controller not found: $CONTROLLER"
fi
echo ""

# Step 4: Test dashboard queries
echo "[4/4] Testing dashboard database queries..."

# Test import_run table
IMPORT_RUNS=$(bin/mysql -N -e "SELECT COUNT(*) FROM archivedotorg_import_run;" 2>/dev/null || echo "0")
echo "  Import runs in database: $IMPORT_RUNS"

# Test artist_status table
ARTIST_STATUS=$(bin/mysql -N -e "SELECT COUNT(*) FROM archivedotorg_artist_status;" 2>/dev/null || echo "0")
echo "  Artist status records: $ARTIST_STATUS"

# Test unmatched_track table
UNMATCHED=$(bin/mysql -N -e "SELECT COUNT(*) FROM archivedotorg_unmatched_track WHERE resolved=0;" 2>/dev/null || echo "0")
echo "  Unmatched tracks: $UNMATCHED"

if [ "$IMPORT_RUNS" -gt 0 ] || [ "$ARTIST_STATUS" -gt 0 ]; then
    echo "✓ Dashboard data available"
else
    echo "⚠ No dashboard data yet (will populate with first import)"
fi
echo ""

# Summary
echo "═══════════════════════════════════════════════════════════"
echo "  Phase 4: COMPLETE ✓"
echo "  Completed: $(date)"
echo "═══════════════════════════════════════════════════════════"
echo ""
echo "Dashboard Access:"
echo "  URL: Admin > Content > Archive.org Import > Dashboard"
echo "  (If module exists and routes are configured)"
echo ""
echo "Next steps:"
echo "  1. Log into Magento Admin"
echo "  2. Navigate to Content menu"
echo "  3. Look for 'Archive.org Import' menu item"
echo "  4. Test all dashboard features"
echo ""
echo "Phase 5 (Cleanup) should run 30 days from now"
echo "  Script: deployment/phase5/cleanup-old-code.sh"
echo ""
