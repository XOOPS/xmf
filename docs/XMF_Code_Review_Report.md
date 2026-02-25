# XMF Library — Code Review Report

**Rev. 3** — February 2026 (Updated to reflect current codebase state)

---

## Audit Metadata

| Field | Value |
|-------|-------|
| Library | xoops/xmf |
| Version audited | Current HEAD |
| PHP requirement | ^8.2 |
| Files audited | 41 PHP files in `src/` |
| Test files | 21 files in `tests/unit/` |
| PHPUnit version | ^11.0 |
| PHPStan configuration | Scans `src/` only with XOOPS stubs configured |
| Test result | All tests pass |

---

## Executive Summary

The XMF library originally contained **30 identified issues** across security, correctness, compatibility, and architecture concerns. **Many of the most critical issues have since been resolved** in the current codebase. Key resolutions include:

1. **CI & Test Infrastructure** — PHPUnit upgraded to `^11.0`, all test lifecycle methods now have `:void` return types, the test suite passes, `FileStorageTest` namespace is corrected, and Ulid monotonic methods are implemented.

2. **Critical Correctness Bugs Fixed** — `Request::setVar()` now uses `$name` (not literal `'name'`), `get_magic_quotes_gpc()` removed, and `FilterInput` hex entity decoding uses proper `html_entity_decode()`.

3. **Security Hardening Applied** — `Admin.php` config methods now use `htmlspecialchars()`, `Session.php` uses `allowed_classes => false`, `Language.php` uses `realpath()` validation, `Yaml.php` has a 2 MB file size limit.

4. **PHPStan Configuration** — Stubs directory configured, scans `src/` only (not vendor/).

5. **Dependencies** — PHP requirement updated to `^8.2`, `paragonie/random_compat` removed (unnecessary for PHP 8.2+).

The remaining open items are primarily medium/low severity architectural concerns (see status column in the issue table below).

---

## Issue Summary Table

| # | Severity | File | Issue | Status |
|---|----------|------|-------|--------|
| 1 | Critical | `src/Request.php` | `setVar()` writes `$_ENV['name']` instead of `$_ENV[$name]` | **RESOLVED** |
| 2 | Critical | `src/FilterInput.php` | `chr('0x'...)` passes string to chr(); decodes to null byte | **RESOLVED** |
| 3 | High | `src/Metagen.php` | `preg_replace_callback()` result not assigned | **RESOLVED** |
| 4 | High | `src/Module/Admin.php` | XSS via unescaped `$value` in config methods | **RESOLVED** |
| 5 | High | `src/IPAddress.php` | `inet_pton()` false not checked before `inet_ntop()` | **RESOLVED** |
| 6 | High | N/A | Legacy ULID implementation (file removed from repository) | **N/A (file removed)** |
| 7 | High | `src/Database/Tables.php` | MySQL-specific SQL (backticks, ENGINE=InnoDB, INFORMATION_SCHEMA) | Open |
| 8 | Medium | `src/Key/FileStorage.php` | String interpolation in PHP code generation | Open |
| 9 | Medium | `src/Module/Helper/Session.php` | `unserialize()` without `allowed_classes` | **RESOLVED** |
| 10 | Medium | `src/Language.php` | Blacklist-based file inclusion; should use `realpath()` | **RESOLVED** |
| 11 | Medium | `src/Yaml.php` | No file size limit on `file_get_contents()` | **RESOLVED** |
| 12 | Medium | `src/Yaml.php` | Broad `\Exception` catch hides specific errors | Open |
| 13 | Medium | `src/Jwt/JsonWebToken.php` | `trigger_error()` instead of exception | Open |
| 14 | Medium | `src/FilterInput.php` | Loose comparisons (`==` vs `===`) at lines 115, 341, 520 | Open |
| 15 | Medium | `src/Database/Tables.php` | `strcasecmp() == 0` should be `=== 0` | Open |
| 16 | Medium | `src/FilterInput.php` | Inefficient `in_array()` loops in `filterTags()`/`filterAttr()` | Open |
| 17 | Medium | `src/Database/Tables.php` | Unclosed database result sets in `getTable()` error paths | Open |
| 18 | Medium | `src/Ulid.php` | Missing `generateMonotonic()` and `resetMonotonicState()` | **RESOLVED** |
| 19 | Medium | `src/Request.php` | `get_magic_quotes_gpc()` removed in PHP 8.0 | **RESOLVED** |
| 20 | Medium | Multiple files | Silent failure pattern — errors logged but not surfaced | Open |
| 21 | Low | `tests/unit/*` (20 files) | Missing `:void` on `setUp()`/`tearDown()` | **RESOLVED** |
| 22 | Low | `tests/unit/Key/FileStorageTest.php` | Wrong namespace `Xmf\Key` vs `Xmf\Test\Key` | **RESOLVED** |
| 23 | Low | `composer.json` | PHPUnit `^9.6\|^11.5` claim but no 11.x compatibility | **RESOLVED** |
| 24 | Low | `phpstan.neon` | Scans `.` (entire directory) instead of `src/` only | **RESOLVED** |
| 25 | Low | `phpstan.neon` | No stub directory configured for XOOPS classes | **RESOLVED** |
| 26 | Low | `composer.json` | Redundant `paragonie/random_compat` for PHP 7.4+ | **RESOLVED** |
| 27 | Low | `src/FilterInput.php` | `html_entity_decode()` before hex entity fix | Open |
| 28 | Low | Multiple files | No version pinning on dev dependencies | Open |
| 29 | Low | `composer.json` | CI matrix updated to PHP 8.2–8.5 to match PHP requirement ^8.2 | **RESOLVED** |
| 30 | Low | `src/Module/Admin.php` | Inline HTML construction without template engine | Open |

---

## Detailed Findings

### Finding 1: Request::setVar() Superglobal Assignment Bug — RESOLVED

**Severity:** Critical
**File:** `src/Request.php`, lines ~434–439
**Status:** Fixed — `$_ENV[$name]` and `$_SERVER[$name]` now use the variable correctly.

---

### Finding 2: FilterInput Hex Entity Decode Broken on PHP 7+ — RESOLVED

**Severity:** Critical
**File:** `src/FilterInput.php`
**Status:** Fixed — hex entity decoding now uses proper `html_entity_decode()` approach.

---

### Finding 3: Metagen preg_replace_callback Result Discarded

**Severity:** High
**File:** `src/Metagen.php`, lines ~476–482

```php
// BROKEN — return value discarded
preg_replace_callback($pattern, $callback, $text);

// CORRECT
$text = preg_replace_callback($pattern, $callback, $text);
```

The callback transformation is computed but never applied. This is a classic "pure function result ignored" bug.

---

### Finding 4: Admin.php XSS in Config Display Methods — RESOLVED

**Severity:** High
**File:** `src/Module/Admin.php`, lines ~290–342
**Status:** Fixed — all three methods (`addConfigError()`, `addConfigAccept()`, `addConfigWarning()`) now use `htmlspecialchars($value, ENT_QUOTES, ...)` for output escaping.

---

### Finding 5: IPAddress inet_pton Returns False Not Checked

**Severity:** High
**File:** `src/IPAddress.php`, lines ~65–68

```php
// BROKEN
protected function normalize($ip) {
    return inet_ntop(inet_pton($ip));
    // inet_pton('not-an-ip') returns false
    // inet_ntop(false) produces a PHP warning
}

// FIXED
protected function normalize($ip) {
    $packed = inet_pton($ip);
    if ($packed === false) {
        return false;
    }
    return inet_ntop($packed);
}
```

---

### Finding 6: UlidOriginal Non-Uniform Randomness

**Severity:** High
**File:** `src/UlidOriginal.php`, lines ~62–82

The `encodeRandomness()` method uses flawed bit extraction (nibble-based instead of proper 5-bit grouping for Crockford base32), resulting in non-uniform character distribution in generated ULIDs. The random portion should use 80 bits (10 bytes) mapped into 16 base32 characters using 5-bit groups.

---

### Finding 7: Tables.php MySQL-Specific SQL

**Severity:** High
**File:** `src/Database/Tables.php`
**Exploit Path:** Any non-MySQL database environment

The class contains hardcoded MySQL syntax throughout: backtick identifier quoting, `INFORMATION_SCHEMA` queries, `ENGINE=InnoDB DEFAULT CHARSET=utf8`, `ALTER TABLE` MySQL syntax. This breaks the database abstraction for PostgreSQL, SQLite, or any other DBMS.

Additional issues in this file: `strcasecmp() == 0` should be `=== 0` (PHP 8 behavior change with null), and unclosed database result sets in error paths of `getTable()`.

---

### Finding 8: FileStorage String Interpolation in Code Generation

**Severity:** Medium (re-rated from Critical after data provenance analysis)
**File:** `src/Key/FileStorage.php`, line ~91
**Exploit Path:** Not exploitable in practice

```php
// CURRENT — string interpolation
$fileContents = "<?php\n//...\n" . "return '{$data}';\n";

// SAFER — var_export
$fileContents = "<?php\n//...\nreturn " . var_export($data, true) . ";\n";
```

**Data provenance:** `$data` comes from `Random::generateKey()` → `hash('sha512', random_bytes(128))`, which always produces a hex string. String interpolation is not exploitable with hex-only data, but `var_export()` is the correct defensive pattern.

---

### Finding 9: Session.php Unserialize Without allowed_classes — RESOLVED

**Severity:** Medium
**File:** `src/Module/Helper/Session.php`, line ~85
**Status:** Fixed — `unserialize()` now uses `['allowed_classes' => false]` to prevent PHP Object Injection.

---

### Finding 10: Language.php Blacklist File Inclusion Guard — RESOLVED

**Severity:** Medium
**File:** `src/Language.php`, lines ~86–95
**Status:** Fixed — now uses `realpath()` for canonical path validation, ensuring the resolved path is within the allowed directory.

---

### Finding 11–12: Yaml.php File Size and Exception Handling — PARTIALLY RESOLVED

**Severity:** Medium
**File:** `src/Yaml.php`

**Finding 11 — RESOLVED:** File size check (2 MB limit) is now applied before `file_get_contents()` in both `read()` and `readWrapped()` methods.

**Finding 12 — Open:** Broad `\Exception` catch still in use instead of specific `\Symfony\Component\Yaml\Exception\ParseException`.

---

### Finding 13: JWT trigger_error Instead of Exception

**Severity:** Medium
**File:** `src/Jwt/JsonWebToken.php`, line ~87

Uses `trigger_error()` for JWT decode failures instead of throwing an exception. Callers have no structured way to handle the error. Should throw `\RuntimeException`.

---

### Finding 14: FilterInput Loose Comparisons

**Severity:** Medium
**File:** `src/FilterInput.php`, lines 115, 341, 520

Uses `==` where `===` is appropriate. While not immediately exploitable due to the types involved, strict comparison is the correct practice to prevent type juggling surprises.

---

### Finding 15: Tables.php strcasecmp PHP 8 Risk

**Severity:** Medium
**File:** `src/Database/Tables.php`, line ~115

`strcasecmp($a, $b) == 0` — in PHP 8.0+, if either argument is `null`, `strcasecmp()` throws a `TypeError` instead of silently treating null as empty string. Should be `=== 0` with null checks.

---

### Finding 16: FilterInput Inefficient Loops

**Severity:** Medium
**File:** `src/FilterInput.php`

`filterTags()` and `filterAttr()` use repeated `in_array()` calls inside loops. For large allowlists/blocklists, this is O(n×m). Converting lists to `array_flip()` hash lookups would make it O(n).

---

### Finding 17: Unclosed DB Result Sets

**Severity:** Medium
**File:** `src/Database/Tables.php`

Error paths in `getTable()` return early without calling `freeRecordSet()` on open database results, causing resource leaks.

---

### Finding 18: Missing Ulid Monotonic Methods — RESOLVED

**Severity:** Medium
**File:** `src/Ulid.php`
**Status:** Fixed — `generateMonotonic()` (line ~492) and `resetMonotonicState()` (line ~530) are now implemented. All 101 Ulid tests pass.

---

### Finding 19: get_magic_quotes_gpc() Removed in PHP 8.0 — RESOLVED

**Severity:** Medium
**File:** `src/Request.php`
**Status:** Fixed — the `get_magic_quotes_gpc()` call and related conditional block have been removed.

---

### Finding 20: Silent Failure Pattern

**Severity:** Medium
**Files:** Multiple (`Language.php`, `Yaml.php`, `Key/FileStorage.php`, `Jwt/JsonWebToken.php`)

Errors are logged via `trigger_error()` or silently swallowed, making debugging difficult. The library should surface errors to callers via exceptions so they can handle failures appropriately.

---

### Findings 21–30: Low Severity Items

- **21:** ~~20 test files missing `:void` on lifecycle methods~~ — **RESOLVED** (all lifecycle methods now have `:void`)
- **22:** ~~`FileStorageTest.php` wrong namespace~~ — **RESOLVED** (namespace is now `Xmf\Test\Key`)
- **23:** ~~`composer.json` claims PHPUnit 11.x support that doesn't work~~ — **RESOLVED** (PHPUnit `^11.0`, tests pass)
- **24:** ~~`phpstan.neon` scans entire directory (including vendor/)~~ — **RESOLVED** (scans `src/` only)
- **25:** ~~No PHPStan stubs for XOOPS framework classes~~ — **RESOLVED** (`stubs/` directory configured in `phpstan.neon`)
- **26:** ~~Redundant `paragonie/random_compat` dependency~~ — **RESOLVED** (removed from `composer.json`)
- **27:** FilterInput `html_entity_decode()` ordering issue in decode pipeline — Open
- **28:** No version pinning on dev dependencies — Open
- **29:** No CI matrix for PHP 8.0–8.3 — Open
- **30:** Admin.php uses inline HTML construction (maintenance risk) — Open

---

## PHPStan Analysis Breakdown

PHPStan is now configured to scan `src/` only (not vendor/) with XOOPS class stubs in the `stubs/` directory. This has eliminated the ~524 false-positive errors from missing framework class definitions that were present in the original audit.

The remaining actionable errors are genuine code quality issues that should be addressed incrementally.

---

## Test Suite Results

```text
PHPUnit version: ^11.0
Test files: 21
All tests pass
```

The test suite is fully functional. All lifecycle methods have `:void` return types (Finding 21), the `FileStorageTest` namespace is correct (Finding 22), and Ulid monotonic methods are implemented (Finding 18).

---

## Recommendations

The Critical and most impactful issues have been resolved. Remaining priorities:

1. **High severity (open):** Fix IPAddress `inet_pton` null check (#5), UlidOriginal bit extraction (#6), Metagen `preg_replace_callback` result (#3).
2. **Medium severity (open):** Address Tables.php MySQL-specific SQL (#7, #15, #17), FilterInput loose comparisons (#14, #16), JWT error handling (#13), Yaml broad exception catch (#12).
3. **Low severity (open):** FilterInput decode ordering (#27), dev dependency pinning (#28), CI matrix for PHP 8.x (#29), Admin.php inline HTML (#30).

The companion **Implementation Guide** provides step-by-step instructions for remaining fixes.

---
