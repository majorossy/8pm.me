#!/bin/bash
################################################################################
# 7-Day Deployment Monitoring Script
# Archive.org Import Rearchitecture
#
# Purpose: Daily health checks for post-deployment monitoring
# Usage: Run daily for 7 days after deployment
################################################################################

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
LOG_DIR="$PROJECT_ROOT/var/log/deployment_monitoring"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
DAY_NUM=${1:-1}  # Default to Day 1 if not specified

mkdir -p "$LOG_DIR"

cd "$PROJECT_ROOT"

echo "═══════════════════════════════════════════════════════════"
echo "  Archive.org Import System - Day $DAY_NUM Monitoring"
echo "  $(date)"
echo "═══════════════════════════════════════════════════════════"
echo ""

# --- Error Logs ---
echo "📋 Error Logs (last 24h)"
echo "─────────────────────────────────────────────────────────"
if [ -f "var/log/exception.log" ]; then
    ERROR_COUNT=$(grep -c "error\|exception" var/log/exception.log 2>/dev/null || echo "0")
    echo "Total errors in log: $ERROR_COUNT"

    # Recent errors (last 50 lines)
    RECENT_ERRORS=$(tail -100 var/log/exception.log | grep -i "archivedotorg" | wc -l)
    echo "Archive.org related errors (last 100 lines): $RECENT_ERRORS"

    if [ "$RECENT_ERRORS" -gt 10 ]; then
        echo "⚠️  WARNING: High error count!"
        echo ""
        echo "Recent errors:"
        tail -50 var/log/exception.log | grep -i "archivedotorg" | tail -10
    else
        echo "✅ Error count: Normal"
    fi
else
    echo "✅ No exception log found (clean system)"
fi
echo ""

# --- Import Success Rate ---
echo "📊 Import Success Rate"
echo "─────────────────────────────────────────────────────────"
TOTAL_IMPORTS=$(bin/mysql -N -e "SELECT COUNT(*) FROM archivedotorg_import_run;" 2>/dev/null || echo "0")
COMPLETED=$(bin/mysql -N -e "SELECT COUNT(*) FROM archivedotorg_import_run WHERE status='completed';" 2>/dev/null || echo "0")
FAILED=$(bin/mysql -N -e "SELECT COUNT(*) FROM archivedotorg_import_run WHERE status='failed';" 2>/dev/null || echo "0")
RUNNING=$(bin/mysql -N -e "SELECT COUNT(*) FROM archivedotorg_import_run WHERE status='running';" 2>/dev/null || echo "0")

echo "Total imports: $TOTAL_IMPORTS"
echo "  Completed:   $COMPLETED"
echo "  Failed:      $FAILED"
echo "  Running:     $RUNNING"

if [ "$TOTAL_IMPORTS" -gt 0 ]; then
    SUCCESS_RATE=$(bin/mysql -N -e "SELECT ROUND(100.0 * SUM(CASE WHEN status='completed' THEN 1 ELSE 0 END) / COUNT(*), 2) FROM archivedotorg_import_run;")
    echo "Success rate: ${SUCCESS_RATE}%"

    if (( $(echo "$SUCCESS_RATE < 95" | bc -l) )); then
        echo "⚠️  WARNING: Success rate below 95% target"
    else
        echo "✅ Success rate: Healthy"
    fi
else
    echo "ℹ️  No imports recorded yet"
fi
echo ""

# --- Match Rate ---
echo "🎯 Match Rate"
echo "─────────────────────────────────────────────────────────"
ARTIST_COUNT=$(bin/mysql -N -e "SELECT COUNT(*) FROM archivedotorg_artist_status;" 2>/dev/null || echo "0")

if [ "$ARTIST_COUNT" -gt 0 ]; then
    AVG_MATCH_RATE=$(bin/mysql -N -e "SELECT ROUND(AVG(match_rate), 2) FROM archivedotorg_artist_status WHERE match_rate IS NOT NULL;" 2>/dev/null || echo "0")
    echo "Artists tracked: $ARTIST_COUNT"
    echo "Average match rate: ${AVG_MATCH_RATE}%"

    if (( $(echo "$AVG_MATCH_RATE < 97" | bc -l) )); then
        echo "⚠️  WARNING: Match rate below 97% target"
        echo ""
        echo "Artists with low match rates:"
        bin/mysql -e "SELECT a.artist_name, s.match_rate, s.tracks_unmatched
FROM archivedotorg_artist_status s
JOIN archivedotorg_artist a ON a.artist_id = s.artist_id
WHERE s.match_rate < 95
ORDER BY s.match_rate ASC
LIMIT 5;"
    else
        echo "✅ Match rate: Excellent"
    fi
else
    echo "ℹ️  No artist status data yet"
fi
echo ""

# --- Unmatched Tracks ---
echo "🔍 Unmatched Tracks"
echo "─────────────────────────────────────────────────────────"
UNMATCHED=$(bin/mysql -N -e "SELECT COUNT(*) FROM archivedotorg_unmatched_track WHERE resolved=0;" 2>/dev/null || echo "0")
echo "Total unmatched tracks: $UNMATCHED"

if [ "$UNMATCHED" -gt 100 ]; then
    echo "⚠️  WARNING: High unmatched count (target: <100)"
    echo ""
    echo "Top unmatched tracks needing attention:"
    bin/mysql -e "SELECT track_name, occurrences, suggested_match
FROM archivedotorg_unmatched_track
WHERE resolved=0
ORDER BY occurrences DESC
LIMIT 10;"
elif [ "$UNMATCHED" -gt 0 ]; then
    echo "⚠️  Unmatched tracks present (review recommended)"
else
    echo "✅ No unmatched tracks"
fi
echo ""

# --- Dashboard Performance ---
echo "⚡ Dashboard Performance"
echo "─────────────────────────────────────────────────────────"
echo "Testing query performance..."

# Artist status query
START=$(date +%s%N)
bin/mysql -e "SELECT * FROM archivedotorg_artist_status LIMIT 1;" > /dev/null
END=$(date +%s%N)
DURATION=$(( (END - START) / 1000000 ))
echo "  Artist status query: ${DURATION}ms"

# Import run query
START=$(date +%s%N)
bin/mysql -e "SELECT * FROM archivedotorg_import_run ORDER BY started_at DESC LIMIT 20;" > /dev/null
END=$(date +%s%N)
DURATION=$(( (END - START) / 1000000 ))
echo "  Import history query: ${DURATION}ms"

echo ""
if [ "$DURATION" -gt 100 ]; then
    echo "⚠️  WARNING: Queries slower than 100ms target"
else
    echo "✅ Query performance: Excellent"
fi
echo ""

# --- Disk Usage ---
echo "💾 Disk Usage"
echo "─────────────────────────────────────────────────────────"
if [ -d "var/archivedotorg/metadata" ]; then
    METADATA_SIZE=$(du -sh var/archivedotorg/metadata 2>/dev/null | cut -f1)
    echo "Metadata folder: $METADATA_SIZE"
else
    echo "Metadata folder: Not found"
fi

if [ -d "var/archivedotorg/progress" ]; then
    PROGRESS_SIZE=$(du -sh var/archivedotorg/progress 2>/dev/null | cut -f1)
    PROGRESS_FILES=$(find var/archivedotorg/progress -name "*.json" | wc -l)
    echo "Progress files: $PROGRESS_FILES ($PROGRESS_SIZE)"
else
    echo "Progress folder: Not found"
fi

LOG_SIZE=$(du -sh var/log 2>/dev/null | cut -f1)
echo "Log folder: $LOG_SIZE"
echo ""

# --- Memory & CPU (if available) ---
echo "🖥️  System Resources"
echo "─────────────────────────────────────────────────────────"

# Check for running imports
IMPORT_PROCESSES=$(ps aux | grep "archivedotorg:" | grep -v grep | wc -l)
echo "Active import processes: $IMPORT_PROCESSES"

if [ "$IMPORT_PROCESSES" -gt 0 ]; then
    echo ""
    echo "Running imports:"
    ps aux | grep "archivedotorg:" | grep -v grep | awk '{print "  PID " $2 ": " $11 " " $12 " " $13}'
fi
echo ""

# --- Summary ---
echo "═══════════════════════════════════════════════════════════"
echo "  Day $DAY_NUM Monitoring: Complete"
echo "═══════════════════════════════════════════════════════════"
echo ""

# Day-specific checks
case $DAY_NUM in
    1)
        echo "Day 1 Focus: Check error logs every 30 minutes"
        ;;
    2)
        echo "Day 2 Focus: Monitor dashboard performance"
        ;;
    3)
        echo "Day 3 Focus: Verify match rates are stable"
        ;;
    4)
        echo "Day 4 Focus: Check for memory leaks"
        ;;
    5)
        echo "Day 5 Focus: Verify cron jobs executing"
        ;;
    6)
        echo "Day 6 Focus: Monitor disk space growth"
        ;;
    7)
        echo "Day 7 Focus: Full system health check"
        echo ""
        echo "🎉 7-day monitoring period complete!"
        echo "   Review all monitoring logs and prepare final report."
        ;;
esac

echo ""
echo "Log saved to: $LOG_DIR/monitoring_day${DAY_NUM}_${TIMESTAMP}.txt"

# Save to log file
{
    echo "=== Day $DAY_NUM Monitoring - $(date) ==="
    echo ""
    echo "Imports: $TOTAL_IMPORTS total, $COMPLETED completed, $FAILED failed"
    echo "Match rate: ${AVG_MATCH_RATE}%"
    echo "Unmatched: $UNMATCHED tracks"
    echo "Disk: Metadata $METADATA_SIZE"
    echo ""
} >> "$LOG_DIR/monitoring_day${DAY_NUM}_${TIMESTAMP}.txt"
