# XMF Library — Implementation & Testing Guide

Based on Code Review Report Rev. 4 — February 2026

---

## Scope

This document provides step-by-step instructions for implementing and testing the remaining fixes identified in the XMF Code Review. Each task includes: the exact file, the current code, the corrected replacement, a test strategy, and the commands to verify. Tasks are ordered by severity.

**Completed phases are summarized briefly. Open tasks contain full implementation instructions.**

---

## Overview

| Phase | Tasks | Focus | Status |
|-------|-------|-------|--------|
| 0 | 0.1–0.2 | Prerequisites & Environment | Done |
| 1 | 1.1–1.3 | Test Infrastructure | All completed |
| 2 | 2.1–2.2 | Critical Correctness Bugs | All completed |
| 3 | 3.1–3.2 | High Severity Bugs | 3.1 completed; 3.2 open |
| 4 | 4.1–4.5 | Medium Severity Improvements | 4.2, 4.3, 4.4 (partial) completed; 4.1, 4.5 open |
| 5 | 5.1 | Final Validation | Open |

---

## Phase 0: Prerequisites

### Task 0.1 — Environment Setup

**Required tools:**

- PHP >= 8.2 with extensions: mbstring, xml, bcmath, json, openssl, dom
- Composer 2.x
- PHPUnit (via Composer)
- PHPStan (via Composer)

**Commands:**

```bash
composer install
vendor/bin/phpunit --version
vendor/bin/phpstan --version
```

### Task 0.2 — Verify Current Baseline

```bash
composer test    # expect: all tests pass
composer analyse # expect: only baseline errors
```

---

## Phase 1: Test Infrastructure — ALL COMPLETED

- **Task 1.1** — All test lifecycle methods (`setUp()`, `tearDown()`, etc.) have `:void` return types.
- **Task 1.2** — `FileStorageTest` namespace corrected to `Xmf\Test\Key`.
- **Task 1.3** — `generateMonotonic()` and `resetMonotonicState()` implemented in `src/Ulid.php`. All Ulid tests pass.

---

## Phase 2: Critical Correctness Bugs — ALL COMPLETED

- **Task 2.1** — `Request::setVar()` now uses `$_ENV[$name]` / `$_SERVER[$name]` (not literal `'name'`).
- **Task 2.2** — `FilterInput` hex entity decoding now uses `html_entity_decode()`.

---

## Phase 3: High Severity Bugs

### Completed

- **Task 3.1** — `Metagen` `preg_replace_callback()` result is assigned to `$text`. `IPAddress::normalize()` checks `inet_pton()` for `false`. `Admin.php` config methods use `htmlspecialchars()`.

### Task 3.2 — Tables.php MySQL-Specific SQL `[High]`

**File:** `src/Database/Tables.php`

**Problem:** Hardcoded MySQL syntax throughout: backtick identifier quoting, `INFORMATION_SCHEMA` queries, `ENGINE=InnoDB DEFAULT CHARSET=utf8`, MySQL-specific `ALTER TABLE` syntax. This couples the class to MySQL and breaks database abstraction.

**Related sub-issues:**
- `strcasecmp($a, $b) == 0` should be `=== 0` (PHP 8 null handling)
- Unclosed database result sets in `getTable()` error paths

**Approach:** This is a significant refactor. Consider introducing a database dialect abstraction or documenting the MySQL-only limitation explicitly.

**Verify:**

```bash
composer test
composer analyse
```

---

## Phase 4: Medium Severity Improvements

### Completed

- **Task 4.2** — `Session.php` `unserialize()` uses `['allowed_classes' => false]`.
- **Task 4.3** — `Language.php` uses `realpath()` for path validation.
- **Task 4.4** (partial) — `Yaml.php` enforces 2 MB file size limit. Broad `\Exception` catch remains open.

### Task 4.1 — Harden FileStorage PHP Code Generation `[Medium]`

**File:** `src/Key/FileStorage.php` (line ~91)

**Problem:** String interpolation used to generate PHP code. While not exploitable (data is always hex from `hash()`), `var_export()` is the correct defensive pattern.

**Before:**

```php
$fileContents = "<?php\n//...\n" . "return '{$data}';\n";
```

**After:**

```php
$fileContents = "<?php\n//...\nreturn " . var_export($data, true) . ";\n";
```

**Verify:**

```bash
vendor/bin/phpunit tests/unit/Key/FileStorageTest.php
```

---

### Task 4.5 — Fix JWT Error Handling `[Medium]`

**File:** `src/Jwt/JsonWebToken.php` (line ~87)

**Problem:** Uses `trigger_error()` instead of throwing an exception. Callers have no structured way to handle JWT failures.

**Before:**

```php
trigger_error('JWT decode failed', E_USER_WARNING);
return false;
```

**After:**

```php
throw new \RuntimeException('JWT decode failed: ' . $e->getMessage());
```

**Verify:**

```php
// Test with invalid JWT
try {
    JsonWebToken::decode('invalid.jwt.token', $key);
    assert(false, 'Should have thrown RuntimeException');
} catch (\RuntimeException $e) {
    assert(strpos($e->getMessage(), 'JWT decode failed') !== false);
}
```

---

### Additional Open Medium-Severity Items

- **FilterInput loose comparisons** (`==` vs `===`) at lines 115, 341, 520
- **FilterInput inefficient loops** — convert `in_array()` to `array_flip()` hash lookups
- **Yaml.php** — replace broad `\Exception` catch with specific `ParseException`

---

## Phase 5: Final Validation

### Task 5.1 — Full Suite Verification

Run the complete validation suite:

```bash
# 1. Full CI checks (lint + analyse + test)
composer ci

# 2. Or run individually:
composer test     # All tests pass (0 failures, 0 errors)
composer analyse  # No new errors beyond baseline
composer lint     # PSR-12 compliance
```

---

## Appendix A: File-to-Task Map

| File | Task(s) |
|------|---------|
| `src/Database/Tables.php` | 3.2 |
| `src/Key/FileStorage.php` | 4.1 |
| `src/Jwt/JsonWebToken.php` | 4.5 |
| `src/FilterInput.php` | Open (loose comparisons, inefficient loops) |
| `src/Yaml.php` | Open (broad exception catch) |

---

## Appendix B: Test Commands Quick Reference

| Command | Purpose |
|---------|---------|
| `composer install` | Install all dependencies |
| `composer ci` | Run all CI checks (lint + analyse + test) |
| `composer test` | Run full test suite |
| `composer lint` | Check code style (PSR-12) |
| `composer fix` | Auto-fix code style |
| `composer analyse` | Run PHPStan static analysis |
| `composer baseline` | Regenerate PHPStan baseline |
| `vendor/bin/phpunit --filter TestName` | Run specific test |
| `vendor/bin/phpunit tests/unit/File.php` | Run specific file |

---
