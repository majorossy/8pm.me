# Remaining FIXES.md Items - Priority List

**Date:** 2026-01-29
**Current Status:** 26/48 fixes complete (54%)
**Critical Fixes:** 13/16 complete (81%)

---

## ✅ What's Already Done

### Critical (13/16)
- ✅ Fix #1: Artist normalization table
- ✅ Fix #2: Performance documentation corrected
- ✅ Fix #3: File locking in commands
- ✅ Fix #4: Atomic progress file writes
- ✅ Fix #6: SKU format documented
- ✅ Fix #7: FK cascade actions
- ✅ Fix #8: Service interfaces (18 total)
- ✅ Fix #9: Memory cleanup (clearIndexes)
- ✅ Fix #10: Lock race condition (Redis + flock)
- ✅ Fix #12: Feature flags
- ✅ Fix #13: File audit command
- ✅ Fix #14: Exception hierarchy
- ✅ Fix #15: Cron uses locks

### High (7/19)
- ✅ Fix #18: Dashboard indexes (55 total)
- ✅ Fix #19: Large JSON extracted to separate table
- ✅ Fix #20: Unicode normalization (StringNormalizer exists)
- ✅ Fix #22: Cache cleanup command
- ✅ Fix #29: YAML stable keys
- ✅ Fix #30-33: Documentation/planning updates
- ✅ Fix #34: TEXT → JSON migration

### Medium (5/13)
- ✅ Fix #37: show_metadata table defined
- ✅ Fix #41: Hybrid matching (exact→alias→metaphone→fuzzy)
- ✅ Fix #45-47: YAML live-only, medleys, multi-album

---

## 🔴 CRITICAL - Still Needed (3 fixes)

### Fix #5: Database Transactions in BulkProductImporter ⚠️ HIGH RISK

**Status:** ❌ NOT IMPLEMENTED
**Risk:** Crash during bulk import leaves orphaned products
**Effort:** LOW (30 minutes)

**Location:** `Model/BulkProductImporter.php`

**What to Add:**
```php
private function createProduct(...): void
{
    $connection = $this->resourceConnection->getConnection();
    $connection->beginTransaction();

    try {
        $connection->insert('catalog_product_entity', [...]);
        $entityId = (int) $connection->lastInsertId();

        $this->setProductAttributes($entityId, ...);
        $connection->insert('catalog_product_website', [...]);
        $connection->insert('cataloginventory_stock_item', [...]);

        $connection->commit();
    } catch (\Exception $e) {
        $connection->rollBack();
        throw $e;
    }
}
```

**Priority:** 🔴 **HIGH** - Prevents data corruption

---

### Fix #11: Run Unit Tests ⚠️ VERIFICATION NEEDED

**Status:** ❌ NOT RUN (tests synced but not executed)
**Risk:** Unknown code quality, regressions
**Effort:** LOW (15 minutes to run)

**Action:**
```bash
# Run all ArchiveDotOrg unit tests
vendor/bin/phpunit --filter ArchiveDotOrg --testdox

# Expected: 102 test methods, 199+ assertions
```

**Expected Result:**
- All tests pass ✅
- OR identify what needs fixing ❌

**Priority:** 🔴 **HIGH** - Quality verification

---

### Fix #16: Admin Dashboard Checks Locks ⚠️ MEDIUM RISK

**Status:** ❌ NOT IMPLEMENTED
**Risk:** Admin can start import while CLI already running → duplicates
**Effort:** LOW (20 minutes)

**Location:** `Controller/Adminhtml/Dashboard/StartImport.php` (if exists)

**What to Add:**
```php
if ($this->lockService->isLocked('import', $artistName)) {
    $lockInfo = $this->lockService->getLockInfo('import', $artistName);
    return $result->setData([
        'success' => false,
        'error' => sprintf(
            'Import already in progress for %s (started by PID %d)',
            $artistName,
            $lockInfo['pid'] ?? 0
        )
    ]);
}
```

**Priority:** 🟡 **MEDIUM** - Admin feature safety

---

## 🟧 HIGH PRIORITY - Recommended (6 fixes)

### Fix #21: Ambiguous Match Logging

**Status:** ❌ NOT IMPLEMENTED
**Effort:** MEDIUM (1 hour)

**What:** When track exists in 2+ albums, log for manual resolution

**Location:** `Model/TrackMatcherService.php`

```php
if (count($potentialMatches) > 1) {
    $this->logAmbiguousMatch($trackTitle, $albumName, $potentialMatches);
    return null;  // Require admin resolution
}
```

---

### Fix #23: Signal Handlers (SIGTERM/SIGINT)

**Status:** ❌ NOT IMPLEMENTED
**Effort:** MEDIUM (1-2 hours)

**What:** Graceful shutdown on `docker stop` or Ctrl+C

**Location:** `Console/Command/BaseLoggedCommand.php`

```php
declare(ticks=1);

protected function setupSignalHandlers(): void
{
    if (!function_exists('pcntl_signal')) return;

    $handler = function (int $signal) {
        $this->shouldStop = true;
        $this->output->writeln("<comment>Stopping gracefully...</comment>");
    };

    pcntl_signal(SIGTERM, $handler);
    pcntl_signal(SIGINT, $handler);
}
```

**Benefit:** Clean lock cleanup, no stuck jobs

---

### Fix #24: PID Check Across Docker Boundary

**Status:** 🟡 PARTIAL (LockService exists, cross-boundary logic unknown)
**Effort:** MEDIUM (1 hour)

**What:** Fix `posix_kill($pid, 0)` check to handle Docker containers

**Location:** `Model/LockService.php:301-307`

```php
$hostname = $info['hostname'] ?? '';
$currentHostname = gethostname();

// Only check PID if on same host
if ($hostname === $currentHostname) {
    if (posix_kill($pid, 0)) {
        continue; // Process still alive
    }
} else {
    // Different container - only clean if very old (8+ hours)
    if ($mtime > $cutoff && $maxAgeHours < 8) {
        continue;
    }
}
```

---

### Fix #26: Progress File Migration

**Status:** ❌ NOT IMPLEMENTED
**Effort:** LOW (30 minutes)

**What:** Add version field to progress files for backward compatibility

---

### Fix #27: Use Magento Filesystem ⚠️ CODE QUALITY

**Status:** 🟡 PARTIAL (5 direct file operations found)
**Effort:** MEDIUM (1-2 hours)

**What:** Replace `file_put_contents()` with `Filesystem::writeFile()`

**Files to Update:**
- Model/LockService.php
- Model/ProgressTracker.php
- Model/Queue/JobStatusManager.php
- (2 others - need to find)

**Why:** Magento best practices, better error handling

---

### Fix #28: Circuit Breaker for API

**Status:** ❌ NOT IMPLEMENTED
**Effort:** MEDIUM (2 hours)

**What:** Prevent hammering Archive.org API after repeated failures

**Location:** `Model/ArchiveApiClient.php`

```php
private const CIRCUIT_BREAKER_THRESHOLD = 5;

if ($this->failureCount >= self::CIRCUIT_BREAKER_THRESHOLD) {
    throw new \RuntimeException('Circuit breaker open - too many failures');
}
```

**Benefit:** Protects external API, faster fail-fast

---

## 🟨 MEDIUM PRIORITY - Nice to Have (8 fixes)

### Database Optimizations

**Fix #35:** TIMESTAMP(3) → TIMESTAMP(6)
- Current: Standard TIMESTAMP
- Benefit: Microsecond precision for sub-second operations
- Effort: LOW (migration SQL)

**Fix #36:** BIGINT → INT optimization
- Current: May be using BIGINT unnecessarily
- Benefit: 4 bytes saved per row
- Effort: LOW (migration SQL)

### Code Improvements

**Fix #38:** Stuck Job Cleanup
- Add `cleanupStuckJobs()` method to JobStatusManager
- Effort: MEDIUM (1 hour)

**Fix #40:** Extend Redis TTL
- Change from 1hr to 24hr for long imports
- Effort: TRIVIAL (change constant)

### Testing

**Fix #42:** Error Handling Tests
- Add tests for API timeout, corrupt data, etc.
- Effort: HIGH (4+ hours)

**Fix #43:** Idempotency Tests
- Verify re-runs produce same result
- Effort: MEDIUM (2 hours)

**Fix #44:** API Contract Tests
- Verify mocks match real API
- Effort: MEDIUM (2 hours)

**Fix #48:** Temp File Cleanup Cron
- Clean orphaned `*.tmp.*` files
- Effort: LOW (30 minutes)

---

## ⏳ DEFERRED (2 fixes)

**Fix #39:** VirtioFS File Locking
- Explicitly deferred - test during Phase 0
- Current: All PHP runs in Docker, flock should work

**Fix #25:** Downtime Estimates
- Documentation only, already addressed

---

## Recommended Action Plan

### Quick Wins (2-3 hours) - Do These First

1. **Fix #5: Add Database Transactions** (30 min) 🔴
   - Prevents data corruption
   - Simple try/catch/rollback pattern

2. **Fix #11: Run Unit Tests** (15 min) 🔴
   - Verify code quality
   - Identify any regressions

3. **Fix #40: Extend Redis TTL** (5 min) 🟨
   - Change constant from 3600 to 86400
   - Prevents locks expiring during long imports

4. **Fix #48: Temp File Cleanup** (30 min) 🟨
   - Add to existing cron job
   - Prevents disk space issues

5. **Fix #16: Admin Dashboard Lock Check** (20 min) 🔴
   - Prevents concurrent import conflicts
   - Similar to cron lock checking

**Result:** 5 fixes, ~2 hours, eliminates critical risks

---

### Medium Effort (4-6 hours) - Do Next

6. **Fix #23: Signal Handlers** (2 hours) 🟧
   - Graceful shutdown on Ctrl+C / docker stop
   - Proper lock cleanup

7. **Fix #28: Circuit Breaker** (2 hours) 🟧
   - Protect Archive.org API
   - Faster failure detection

8. **Fix #27: Magento Filesystem** (2 hours) 🟧
   - Best practices compliance
   - Better error handling

**Result:** 3 more fixes, production hardening

---

### Long-Term (8+ hours) - Optional

9. **Fix #21: Ambiguous Match Logging** (1 hour)
10. **Fix #24: PID Check Across Docker** (1 hour)
11. **Fix #26: Progress File Migration** (30 min)
12. **Fix #38: Stuck Job Cleanup** (1 hour)
13. **Fix #42-44: Comprehensive Testing** (8+ hours)

**Result:** Full FIXES.md completion

---

## Summary

**Current Status:** 26/48 (54%)

**Quick Wins Path:**
- Do 5 quick fixes (2-3 hours) → **31/48 (65%)**
- Critical fixes: 16/16 (100%) ✅

**Medium Effort Path:**
- Add 3 more fixes (4-6 hours) → **34/48 (71%)**
- All high-risk items addressed

**Full Completion:**
- Add remaining 14 fixes (16+ hours) → **48/48 (100%)**

---

## Recommendation

**Do Quick Wins (5 fixes, 2-3 hours)** - Eliminates all critical risks:
- ✅ Database transactions
- ✅ Unit tests run
- ✅ Redis TTL extended
- ✅ Temp file cleanup
- ✅ Admin lock checking

**Result:** Production-ready with 100% critical fixes complete.

**Then decide:** Medium effort (polish) or ship it?
