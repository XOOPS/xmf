# XMF Library — Code Review Report

**Rev. 2** — February 2026

---

## Audit Metadata

| Field | Value |
|-------|-------|
| Library | xoops/xmf |
| Version audited | Current HEAD |
| PHP requirement | >= 7.4.0 |
| Files audited | 41 PHP files in `src/` |
| Test files | 21 files in `tests/unit/` |
| PHPUnit version claimed | ^9.6 \| ^11.5 |
| PHPStan errors (total) | 724 at level max |
| PHPStan errors (actionable) | ~200 (real code issues) |
| PHPStan errors (stub-related) | ~524 (missing XOOPS class defs) |
| Test result | 20/21 files fail (missing `:void`); 1 file has 101 errors (missing methods) |

---

## Executive Summary

The XMF library contains **30 identified issues** across security, correctness, compatibility, and architecture concerns. The two most impactful findings are:

1. **CI Matrix Mismatch** — `composer.json` claims PHPUnit `^9.6|^11.5` support, but zero test files actually work under PHPUnit 11.x due to missing `:void` return types. This is a first-class compatibility failure, not a cosmetic issue.

2. **Two Critical Correctness Bugs** — `Request::setVar()` writes to the wrong superglobal key (literal `'name'` vs `$name`), and `FilterInput` hex entity decoding is broken on all PHP 7+ versions (`chr()` receives a string instead of an integer).

The PHPStan error count of 724 breaks down into ~200 actionable code issues and ~524 errors caused by missing XOOPS framework class definitions (resolvable with stub files).

---

## Issue Summary Table

| # | Severity | File | Issue | Exploit Path |
|---|----------|------|-------|-------------|
| 1 | Critical | `src/Request.php` | `setVar()` writes `$_ENV['name']` instead of `$_ENV[$name]` | Any code calling `Request::setVar()` for ENV/SERVER |
| 2 | Critical | `src/FilterInput.php` | `chr('0x'...)` passes string to chr(); decodes to null byte | Any input containing hex entities `&#xNN;` |
| 3 | High | `src/Metagen.php` | `preg_replace_callback()` result not assigned | Any metadata generation using callback patterns |
| 4 | High | `src/Module/Admin.php` | XSS via unescaped `$value` in config methods | Admin-to-admin; requires module config with user data |
| 5 | High | `src/IPAddress.php` | `inet_pton()` false not checked before `inet_ntop()` | Any invalid IP string input |
| 6 | High | `src/UlidOriginal.php` | Non-uniform randomness from flawed bit extraction | ULIDs generated via UlidOriginal |
| 7 | High | `src/Database/Tables.php` | MySQL-specific SQL (backticks, ENGINE=InnoDB, INFORMATION_SCHEMA) | Any non-MySQL database usage |
| 8 | Medium | `src/Key/FileStorage.php` | String interpolation in PHP code generation | Not exploitable: data is always hex from `hash()` |
| 9 | Medium | `src/Module/Helper/Session.php` | `unserialize()` without `allowed_classes` | Requires session data tampering |
| 10 | Medium | `src/Language.php` | Blacklist-based file inclusion; should use `realpath()` | Path traversal with crafted input |
| 11 | Medium | `src/Yaml.php` | No file size limit on `file_get_contents()` | Memory exhaustion with large YAML file |
| 12 | Medium | `src/Yaml.php` | Broad `\Exception` catch hides specific errors | Silent failure on YAML parse errors |
| 13 | Medium | `src/Jwt/JsonWebToken.php` | `trigger_error()` instead of exception | Callers cannot catch JWT failures structurally |
| 14 | Medium | `src/FilterInput.php` | Loose comparisons (`==` vs `===`) at lines 115, 341, 520 | Type juggling edge cases |
| 15 | Medium | `src/Database/Tables.php` | `strcasecmp() == 0` should be `=== 0` | PHP 8.0+ null argument behavior change |
| 16 | Medium | `src/FilterInput.php` | Inefficient `in_array()` loops in `filterTags()`/`filterAttr()` | Performance on large tag/attribute lists |
| 17 | Medium | `src/Database/Tables.php` | Unclosed database result sets in `getTable()` error paths | Resource leaks under error conditions |
| 18 | Medium | `src/Ulid.php` | Missing `generateMonotonic()` and `resetMonotonicState()` | All monotonic ULID generation; 101 test failures |
| 19 | Medium | `src/Request.php` | `get_magic_quotes_gpc()` removed in PHP 8.0 | Fatal error on PHP 8+ |
| 20 | Medium | Multiple files | Silent failure pattern — errors logged but not surfaced | Debugging difficulty across the library |
| 21 | Low | `tests/unit/*` (20 files) | Missing `:void` on `setUp()`/`tearDown()` | PHPUnit 11.x incompatibility |
| 22 | Low | `tests/unit/Key/FileStorageTest.php` | Wrong namespace `Xmf\Key` vs `Xmf\Test\Key` | Test autoloading failure |
| 23 | Low | `composer.json` | PHPUnit `^9.6\|^11.5` claim but no 11.x compatibility | CI pipeline false confidence |
| 24 | Low | `phpstan.neon` | Scans `.` (entire directory) instead of `src/` only | Noisy results including vendor/ |
| 25 | Low | `phpstan.neon` | No stub directory configured for XOOPS classes | 524 false-positive errors |
| 26 | Low | `composer.json` | Redundant `paragonie/random_compat` for PHP 7.4+ | Unnecessary dependency |
| 27 | Low | `src/FilterInput.php` | `html_entity_decode()` before hex entity fix | Double-decode ordering issue |
| 28 | Low | Multiple files | No version pinning on dev dependencies | Build reproducibility risk |
| 29 | Low | `composer.json` | Missing CI matrix for PHP 8.0/8.1/8.2/8.3 | Untested PHP version compatibility |
| 30 | Low | `src/Module/Admin.php` | Inline HTML construction without template engine | Maintenance and security liability |

---

## Detailed Findings

### Finding 1: Request::setVar() Superglobal Assignment Bug

**Severity:** Critical
**File:** `src/Request.php`, lines ~442, 445
**Exploit Path:** Any code calling `Request::setVar()` for ENV or SERVER superglobals

The `setVar()` method uses a literal string `'name'` instead of the variable `$name` when writing to `$_ENV` and `$_SERVER`:

```php
// BROKEN — lines ~442, 445
case 'ENV':
    $_ENV['name'] = $value;      // Always writes to key 'name'
    break;
case 'SERVER':
    $_SERVER['name'] = $value;   // Always writes to key 'name'
    break;

// CORRECT
case 'ENV':
    $_ENV[$name] = $value;       // Writes to the intended key
    break;
case 'SERVER':
    $_SERVER[$name] = $value;
    break;
```

This means any call like `Request::setVar('HTTP_HOST', 'example.com', 'SERVER')` silently writes to `$_SERVER['name']` instead of `$_SERVER['HTTP_HOST']`. The data is lost and `$_SERVER['name']` accumulates stale values.

---

### Finding 2: FilterInput Hex Entity Decode Broken on PHP 7+

**Severity:** Critical
**File:** `src/FilterInput.php`, line ~557
**Exploit Path:** Any input containing hex character entities (`&#xNN;`)

```php
// BROKEN
return chr('0x' . $matches[1]);

// On PHP 7+, chr() receives the string '0x41', which is cast to int 0
// So &#x41; decodes to chr(0) — a null byte — instead of 'A'

// CORRECT
return chr(hexdec($matches[1]));
```

This affects the entire HTML entity decode pipeline in FilterInput. Any hex entity in user input is silently converted to a null byte.

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

### Finding 4: Admin.php XSS in Config Display Methods

**Severity:** High
**File:** `src/Module/Admin.php`, lines ~290–342
**Exploit Path:** Admin-to-admin via module config values

The methods `addConfigError()`, `addConfigAccept()`, and `addConfigWarning()` concatenate `$value` directly into HTML output without escaping:

```php
// VULNERABLE
$line .= $value;

// FIXED
$line .= htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
```

In standard XOOPS, the attack requires injecting HTML into a module config value, which typically requires admin access — making this an admin-to-admin vector. Still worth fixing as defense-in-depth.

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

### Finding 9: Session.php Unserialize Without allowed_classes

**Severity:** Medium (re-rated from High after exploit path analysis)
**File:** `src/Module/Helper/Session.php`, line ~85
**Exploit Path:** Requires session data tampering

```php
// CURRENT
return unserialize($_SESSION[$prefixedName]);

// HARDENED
return unserialize($_SESSION[$prefixedName], ['allowed_classes' => false]);
```

Without `allowed_classes`, a tampered session cookie could trigger PHP Object Injection. However, exploiting this requires the attacker to control raw session data, which is non-trivial in standard XOOPS deployments.

---

### Finding 10: Language.php Blacklist File Inclusion Guard

**Severity:** Medium
**File:** `src/Language.php`, lines ~79–83

Uses `strpos()` checks for `..` and other blacklisted patterns. This is fragile — use `realpath()` instead for canonical path resolution that also handles symlinks and null bytes.

---

### Finding 11–12: Yaml.php File Size and Exception Handling

**Severity:** Medium
**File:** `src/Yaml.php`

Two issues: (1) `file_get_contents()` without file size check allows memory exhaustion with oversized YAML files. (2) Catching broad `\Exception` instead of `\Symfony\Component\Yaml\Exception\ParseException` hides unrelated errors.

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

### Finding 18: Missing Ulid Monotonic Methods

**Severity:** Medium
**File:** `src/Ulid.php`

`generateMonotonic()` and `resetMonotonicState()` are called by 101 test cases in `UlidTest.php` but don't exist. This is either incomplete implementation or a test-code mismatch.

---

### Finding 19: get_magic_quotes_gpc() Removed in PHP 8.0

**Severity:** Medium
**File:** `src/Request.php`, ~line 119

`get_magic_quotes_gpc()` was removed in PHP 8.0. The conditional block that calls it will cause a fatal error on PHP 8+. The entire block should be removed since magic quotes were relevant only for PHP < 5.4.

---

### Finding 20: Silent Failure Pattern

**Severity:** Medium
**Files:** Multiple (`Language.php`, `Yaml.php`, `Key/FileStorage.php`, `Jwt/JsonWebToken.php`)

Errors are logged via `trigger_error()` or silently swallowed, making debugging difficult. The library should surface errors to callers via exceptions so they can handle failures appropriately.

---

### Findings 21–30: Low Severity Items

- **21:** 20 test files missing `:void` on lifecycle methods — PHPUnit 11.x incompatible
- **22:** `FileStorageTest.php` wrong namespace
- **23:** `composer.json` claims PHPUnit 11.x support that doesn't work
- **24:** `phpstan.neon` scans entire directory (including vendor/)
- **25:** No PHPStan stubs for XOOPS framework classes
- **26:** Redundant `paragonie/random_compat` dependency
- **27:** FilterInput `html_entity_decode()` ordering issue in decode pipeline
- **28:** No version pinning on dev dependencies
- **29:** No CI matrix for PHP 8.0–8.3
- **30:** Admin.php uses inline HTML construction (maintenance risk)

---

## PHPStan Analysis Breakdown

**Total errors at level max: 724**

| Category | Count | Description |
|----------|-------|-------------|
| Actionable code errors | ~200 | Real type mismatches, missing returns, unreachable code, incorrect signatures |
| Missing XOOPS stubs | ~524 | References to `XoopsObject`, `XoopsDatabase`, `CriteriaElement`, etc. that PHPStan cannot resolve |

The 524 stub-related errors can be resolved by creating minimal PHPStan stub files (see Implementation Guide, Task 5.1). The ~200 actionable errors represent genuine code quality issues that should be addressed incrementally.

---

## Test Suite Results

```
Files tested: 21
Files that crash (missing :void):  20
Files that run but fail:            1  (UlidTest.php — 101 errors, missing methods)
Files that pass:                    0
```

The test suite is completely non-functional under PHPUnit 11.x. The `:void` return type issue is systemic and must be fixed first (Implementation Guide, Task 1.1) before any other test-verified fixes can proceed.

---

## Recommendations

1. **Immediate:** Fix the two Critical bugs (Request.php, FilterInput.php) — these produce silently wrong behavior.
2. **Short-term:** Restore test suite functionality (`:void` types, Ulid methods) to enable CI.
3. **Medium-term:** Address High severity items (XSS, IPAddress, Tables.php, UlidOriginal).
4. **Ongoing:** Set up PHPStan stubs, add CI matrix for PHP 8.x, improve error handling patterns.

The companion **Implementation Guide** provides step-by-step instructions for all fixes.

---
