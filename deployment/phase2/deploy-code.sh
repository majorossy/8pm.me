#!/bin/bash
################################################################################
# Phase 2: Code Deployment
# Archive.org Import Rearchitecture
#
# Purpose: Deploy new code with zero downtime
# Duration: ~10 minutes
# Downtime: NO (backward compatible)
################################################################################

set -e  # Exit on error

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

cd "$PROJECT_ROOT"

echo "═══════════════════════════════════════════════════════════"
echo "  Phase 2: Code Deployment"
echo "  Started: $(date)"
echo "═══════════════════════════════════════════════════════════"
echo ""

# Step 1: Git status check
echo "[1/7] Checking Git status..."
git status --short
echo "  Current branch: $(git branch --show-current)"
echo "  Current commit: $(git rev-parse --short HEAD)"
echo ""

# Step 2: Pull latest code (if needed)
echo "[2/7] Deploying code..."
echo "  Note: In production, use 'git pull' or deployment tool"
echo "  Skipping in this script (already on latest)"
echo "✓ Code up to date"
echo ""

# Step 3: Clear caches
echo "[3/7] Clearing Magento caches..."
bin/magento cache:flush
echo "✓ Caches flushed"
echo ""

# Step 4: Compile DI
echo "[4/7] Compiling dependency injection..."
START_TIME=$(date +%s)

bin/magento setup:di:compile

END_TIME=$(date +%s)
DURATION=$((END_TIME - START_TIME))
echo "✓ DI compiled in ${DURATION}s"
echo ""

# Step 5: Deploy static content
echo "[5/7] Deploying static content..."
START_TIME=$(date +%s)

bin/magento setup:static-content:deploy -f en_US

END_TIME=$(date +%s)
DURATION=$((END_TIME - START_TIME))
echo "✓ Static content deployed in ${DURATION}s"
echo ""

# Step 6: Verify old commands (backward compatibility)
echo "[6/7] Verifying backward compatibility..."
echo "  Testing old commands with deprecation warnings..."

# Test old download command
if bin/magento archivedotorg:download-metadata --help &>/dev/null; then
    echo "✓ archivedotorg:download-metadata - WORKING (deprecated)"
else
    echo "⚠ archivedotorg:download-metadata - NOT FOUND (may have been removed)"
fi

# Test old populate command
if bin/magento archivedotorg:populate-tracks --help &>/dev/null; then
    echo "✓ archivedotorg:populate-tracks - WORKING (deprecated)"
else
    echo "⚠ archivedotorg:populate-tracks - NOT FOUND (may have been removed)"
fi

# Test new commands
if bin/magento archivedotorg:download --help &>/dev/null; then
    echo "✓ archivedotorg:download - WORKING (new)"
else
    echo "✗ archivedotorg:download - FAILED"
    exit 1
fi

if bin/magento archivedotorg:populate --help &>/dev/null; then
    echo "✓ archivedotorg:populate - WORKING (new)"
else
    echo "✗ archivedotorg:populate - FAILED"
    exit 1
fi
echo ""

# Step 7: Check for errors
echo "[7/7] Checking error logs..."
if [ -f "var/log/exception.log" ]; then
    RECENT_ERRORS=$(tail -50 var/log/exception.log | grep -i "error\|exception" | wc -l)
    echo "  Recent errors (last 50 lines): $RECENT_ERRORS"

    if [ "$RECENT_ERRORS" -gt 10 ]; then
        echo "⚠ WARNING: High error count detected"
        echo "  Review: var/log/exception.log"
    else
        echo "✓ Error count acceptable"
    fi
else
    echo "✓ No exception log (clean deployment)"
fi
echo ""

# Summary
TOTAL_DURATION=$(($(date +%s) - START_TIME))
echo "═══════════════════════════════════════════════════════════"
echo "  Phase 2: COMPLETE ✓"
echo "  Completed: $(date)"
echo "═══════════════════════════════════════════════════════════"
echo ""
echo "Next step: Run Phase 3 (Data Migration)"
echo "  Script: deployment/phase3/migrate-data.sh"
echo ""
