# Get Her Running - Health Fix Agent Plan

**Status:** Planned (not yet implemented)
**Created:** 2026-02-01

## Goal

Create a `bin/health-fix` script that diagnoses and auto-fixes common issues using `bin/check-status`.

**Trigger phrases for Claude:** "get her running", "health check", "fix issues"

---

## What It Does

```
1. DIAGNOSE
   bin/check-status --no-gql  (fast initial scan)
                    ↓
2. PARSE OUTPUT
   Extract: IDX_STALE, POPULATE, GQL_BROKEN, MATCH warnings
                    ↓
3. FIX (in priority order)
   ⚠️ IDX_STALE  → auto-fix with bin/fix-index
   🔴 POPULATE   → ask user first (y/n/select)
   🔴 GQL_BROKEN → alert user (needs investigation)
   ⚠️ MATCH      → info only (suggest YAML updates)
                    ↓
4. VERIFY
   bin/check-status (with GQL to confirm frontend works)
```

---

## Issue Handling

| Status | Behavior | Command |
|--------|----------|---------|
| ⚠️ IDX_STALE | **Auto-fix** (fast, safe) | `bin/fix-index` |
| 🔴 POPULATE | **Ask first** (y/n/select) | `bin/magento archive:populate "Artist"` |
| 🔴 GQL_BROKEN | **Alert user** | Needs investigation |
| ⚠️ MATCH | **Info only** | Suggest `bin/magento archive:show-unmatched` |

---

## Files to Create

| File | Purpose |
|------|---------|
| `bin/health-fix` | Bash script for auto-diagnosis and repair |
| `.claude/skills/health.md` | Skill file so Claude knows about health checks |
| Update `bin/rs` | Add `health-fix` to control center menu |

---

## bin/health-fix Script

```bash
#!/bin/bash
# Health check and auto-fix
# - IDX_STALE: auto-fix (fast, safe)
# - POPULATE: ask first (slower, user choice)

cd "$(dirname "$0")/.."

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
BOLD='\033[1m'
NC='\033[0m'

echo ""
echo -e "${BOLD}🔍 Running health check...${NC}"
echo ""

# Run check-status and capture output
output=$(bin/check-status --no-gql 2>&1)
echo "$output"

# ============================================
# 1. IDX_STALE - Auto-fix (always safe)
# ============================================
stale_count=$(echo "$output" | grep -c "IDX_STALE" || echo 0)

if [ "$stale_count" -gt 0 ]; then
    echo ""
    echo -e "${YELLOW}⚠️  Found $stale_count artists with stale indexes${NC}"
    echo -e "${CYAN}🔧 Auto-fixing with bin/fix-index...${NC}"
    bin/fix-index
    echo -e "${GREEN}✅ Index fix complete${NC}"
fi

# ============================================
# 2. POPULATE - Ask first
# ============================================
# Extract artist names that need populate
populate_artists=()
while IFS= read -r line; do
    # Extract artist name (first 28 chars, trimmed)
    artist=$(echo "$line" | awk '{print $1}' | head -c 28 | xargs)
    [ -n "$artist" ] && populate_artists+=("$artist")
done < <(echo "$output" | grep "🔴 POPULATE")

populate_count=${#populate_artists[@]}

if [ "$populate_count" -gt 0 ]; then
    echo ""
    echo -e "${RED}🔴 Found $populate_count artists needing populate:${NC}"
    for i in "${!populate_artists[@]}"; do
        echo "   $((i+1)). ${populate_artists[$i]}"
    done
    echo ""
    read -p "Run populate? [y=all / n=skip / s=select]: " choice

    case "$choice" in
        y|Y)
            for artist in "${populate_artists[@]}"; do
                echo ""
                echo -e "${CYAN}📥 Populating: $artist${NC}"
                bin/magento archive:populate "$artist"
            done
            ;;
        s|S)
            echo "Enter numbers to populate (e.g., 1 3 4), or 'q' to skip:"
            read -p "> " selections
            for num in $selections; do
                idx=$((num - 1))
                if [ "$idx" -ge 0 ] && [ "$idx" -lt "$populate_count" ]; then
                    artist="${populate_artists[$idx]}"
                    echo ""
                    echo -e "${CYAN}📥 Populating: $artist${NC}"
                    bin/magento archive:populate "$artist"
                fi
            done
            ;;
        *)
            echo -e "${YELLOW}Skipping populate${NC}"
            ;;
    esac
fi

# ============================================
# 3. Final verification
# ============================================
if [ "$stale_count" -gt 0 ] || [ "$populate_count" -gt 0 ]; then
    echo ""
    echo -e "${BOLD}✅ Re-checking status...${NC}"
    echo ""
    bin/check-status
fi

echo ""
echo -e "${GREEN}${BOLD}Done!${NC}"
```

---

## .claude/skills/health.md

```markdown
# Health Check Skill

**Trigger:** "get her running", "health check", "fix issues"

## What to do

Run `bin/health-fix` - it handles everything:

1. Runs `bin/check-status --no-gql` (fast scan)
2. Auto-fixes IDX_STALE issues with `bin/fix-index`
3. Prompts user for POPULATE issues (y/n/select)
4. Re-verifies when done

## If issues remain after health-fix

- **GQL_BROKEN**: Check if Magento is running, investigate index tables
- **MATCH warnings**: Run `bin/magento archive:show-unmatched` to see which tracks need YAML definitions

## Quick commands

| Command | What it does |
|---------|--------------|
| `bin/health-fix` | Full diagnostic + auto-repair |
| `bin/check-status` | Just check, no fixes |
| `bin/check-status --no-gql` | Fast check (skip GraphQL) |
| `bin/fix-index` | Fix stale indexes only |
```

---

## bin/rs Menu Addition

Add to MONITORING & LOGS section:
```bash
"health-fix:Auto-diagnose and fix archive issues"
```

---

## Verification

```bash
bin/health-fix                    # Should detect and fix issues
bin/check-status                  # Verify all green
bin/rs health-fix                 # Test via control center
```

---

## Future Enhancements

1. **GQL_BROKEN auto-investigation** - Check Magento logs, suggest fixes
2. **MATCH reporting** - Generate report of unmatched tracks with YAML suggestions
3. **Dry-run mode** - Show what would be fixed without doing it
4. **Scheduled health checks** - Cron job integration
