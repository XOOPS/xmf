# XMF Library — Implementation & Testing Guide

**Claude Code Instructions for All Fixes**
Based on Code Review Report Rev. 3 — February 2026 (Updated to reflect current codebase state)

---

## Scope

This document provides step-by-step Claude Code instructions for implementing and testing every fix identified in the XMF Code Review. Each task includes: the exact file and line numbers, the current broken code, the corrected replacement, a test strategy, and the commands to verify. Tasks are ordered by dependency: infrastructure fixes first (PHPUnit compatibility), then correctness bugs, then security hardening, then improvements.

**Total: 18 implementation tasks organized into 6 phases.**

> **Note:** Several tasks have already been completed in the current codebase. Completed tasks are marked with a checkmark below. Remaining open tasks still contain the original implementation instructions.

---

## Overview

| Phase | Tasks | Focus | Status |
|-------|-------|-------|--------|
| 0 | 0.1–0.2 | Prerequisites & Environment | Done |
| 1 | 1.1–1.3 | Test Infrastructure | All completed |
| 2 | 2.1–2.2 | Critical Correctness Bugs | Open |
| 3 | 3.1–3.4 | High Severity Bugs | 3.2 completed; 3.1, 3.3, 3.4 open |
| 4 | 4.1–4.6 | Security Hardening | 4.2, 4.3, 4.4 (partial), 4.6 completed; 4.1, 4.5 open |
| 5 | 5.1–5.2 | Architectural Improvements | All completed |
| 6 | 6.1 | Final Validation | Open |

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

### Task 0.2 — Create Baseline

```bash
git checkout -b xmf-fixes
vendor/bin/phpunit 2>&1 | tail -20          # expect: all failures
vendor/bin/phpstan analyse src/ --level max --memory-limit=512M 2>&1 | tail -5
```

Record the baseline numbers — you will compare against these in Phase 6.

---

## Phase 1: Test Infrastructure

### Task 1.1 — Add `:void` Return Types to PHPUnit Lifecycle Methods `[Low]` — COMPLETED

**Status:** All test lifecycle methods (`setUp()`, `tearDown()`, `setUpBeforeClass()`, `tearDownAfterClass()`) now have `:void` return types across all test files.

---

### Task 1.2 — Fix FileStorageTest Namespace `[Low]` — COMPLETED

**Status:** Namespace corrected to `Xmf\Test\Key` (matching `autoload-dev` PSR-4 mapping in `composer.json`).

---

### Task 1.3 — Implement Ulid Monotonic Methods `[Medium]` — COMPLETED

**Status:** `generateMonotonic()` and `resetMonotonicState()` methods are implemented in `src/Ulid.php`. All 101 Ulid tests pass.

---

## Phase 2: Critical Correctness Bugs

### Task 2.1 — Fix `Request::setVar()` Superglobal Assignment `[Critical]`

**File:** `src/Request.php` (lines ~442, 445)

**Problem:** Literal string `'name'` is used instead of the variable `$name`. This means `setVar()` for ENV and SERVER cases always writes to `$_ENV['name']` instead of the intended key.

**Before:**

```php
case 'ENV':
    $_ENV['name'] = $value;
    break;
case 'SERVER':
    $_SERVER['name'] = $value;
    break;
```

**After:**

```php
case 'ENV':
    $_ENV[$name] = $value;
    break;
case 'SERVER':
    $_SERVER[$name] = $value;
    break;
```

**Verify:**

```bash
vendor/bin/phpunit tests/unit/RequestTest.php
```

---

### Task 2.2 — Fix FilterInput Hex Entity Decode `[Critical]`

**File:** `src/FilterInput.php` (line ~557)

**Problem:** `chr('0x' . $matches[1])` passes a string to `chr()`, which expects an integer. On PHP 7+, the string `'0x41'` is cast to `0` (not `65`), so hex entities like `&#x41;` decode to a null byte instead of `'A'`.

**Before:**

```php
return chr('0x' . $matches[1]);
```

**After:**

```php
return chr(hexdec($matches[1]));
```

**Verify:**

```php
// Inline test
$fi = FilterInput::getInstance();
$input = '&#x41;&#x42;&#x43;';
$result = $fi->process($input);
assert(strpos($result, 'ABC') !== false, 'Hex entity decode should produce ABC');
```

---

## Phase 3: High Severity Bugs

### Task 3.1 — Fix Metagen `preg_replace_callback` Result `[High]`

**File:** `src/Metagen.php` (lines ~476–482)

**Problem:** The return value of `preg_replace_callback()` is never assigned back to the variable, so the transformation has no effect.

**Before:**

```php
preg_replace_callback($pattern, $callback, $text);
```

**After:**

```php
$text = preg_replace_callback($pattern, $callback, $text);
```

**Verify:**

```bash
vendor/bin/phpunit tests/unit/MetagenTest.php
```

---

### Task 3.2 — Fix Admin.php XSS in Config Methods `[High]` — COMPLETED

**Status:** All three methods (`addConfigError()`, `addConfigAccept()`, `addConfigWarning()`) in `src/Module/Admin.php` now use `htmlspecialchars($value, ENT_QUOTES, ...)` for output escaping.

---

### Task 3.3 — Fix IPAddress `inet_pton` Null Check `[High]`

**File:** `src/IPAddress.php` (lines ~65–68)

**Problem:** `inet_pton()` returns `false` for invalid IP strings, which is then passed directly to `inet_ntop()`, causing a warning or unexpected behavior.

**Before:**

```php
protected function normalize($ip)
{
    return inet_ntop(inet_pton($ip));
}
```

**After:**

```php
protected function normalize($ip)
{
    $packed = inet_pton($ip);
    if ($packed === false) {
        return false;
    }
    return inet_ntop($packed);
}
```

**Verify:**

```php
$addr = new IPAddress('not-an-ip');
// Should not produce PHP warnings; should handle gracefully
```

---

<!-- Task 3.4 (original ULID bit extraction fix) has been removed because it referred to a non-existent src/UlidOriginal.php file. The ULID implementation has since been updated in the codebase and is no longer tracked as a separate task here. -->
```

**Verify:**

```php
// Entropy distribution test
$charCounts = array_fill_keys(str_split('0123456789ABCDEFGHJKMNPQRSTVWXYZ'), 0);
for ($i = 0; $i < 10000; $i++) {
    $ulid = UlidOriginal::generate();
    $random = substr($ulid, 10); // last 16 chars are random
    foreach (str_split($random) as $c) {
        $charCounts[$c]++;
    }
}
$expected = (10000 * 16) / 32; // 5000 per character
foreach ($charCounts as $char => $count) {
    $deviation = abs($count - $expected) / $expected;
    assert($deviation < 0.1, "Character '$char' deviates >10% from expected ($count vs $expected)");
}
```

---

## Phase 4: Security Hardening

### Task 4.1 — Harden FileStorage PHP Code Generation `[Medium]`

**File:** `src/Key/FileStorage.php` (line ~91)

**Problem:** String interpolation used to generate PHP code. While `$data` currently always comes from `Random::generateKey()` (which returns hex from `hash('sha512', random_bytes(128))`), using `var_export()` is the defensive correct pattern.

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

### Task 4.2 — Add `allowed_classes` to Session Unserialize `[Medium]` — COMPLETED

**Status:** `unserialize()` now uses `['allowed_classes' => false]` in `src/Module/Helper/Session.php`.

---

### Task 4.3 — Harden Language.php File Inclusion `[Medium]` — COMPLETED

**Status:** `src/Language.php` now uses `realpath()` for canonical path validation, ensuring resolved paths are within the allowed directory.

---

### Task 4.4 — Add File Size Check to Yaml.php `[Medium]` — PARTIALLY COMPLETED

**Status:** File size check (2 MB limit) is implemented in both `read()` and `readWrapped()` methods. The broad `\Exception` catch (Finding 12) remains open for future improvement.

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

### Task 4.6 — Remove `get_magic_quotes_gpc()` Call `[Low]` — COMPLETED

**Status:** The `get_magic_quotes_gpc()` call and related conditional block have been removed from `src/Request.php`.

---

## Phase 5: Architectural Improvements

### Task 5.1 — Create PHPStan Stubs for XOOPS Classes `[Low]` — COMPLETED

**Status:** The `stubs/` directory exists with XOOPS class stub files. `phpstan.neon` is configured with `scanDirectories: - stubs` and scans `src/` only.

---

### Task 5.2 — Remove Redundant `paragonie/random_compat` `[Low]` — COMPLETED

**Status:** `paragonie/random_compat` is not present in `composer.json`. PHP ^8.2 includes `random_bytes()` and `random_int()` natively.

---

## Phase 6: Final Validation

### Task 6.1 — Full Suite Verification

Run the complete validation suite:

```bash
# 1. Full test suite
vendor/bin/phpunit
# Expected: All tests pass (0 failures, 0 errors)

# 2. Static analysis
vendor/bin/phpstan analyse src/ --level max --memory-limit=512M
# Expected: Significantly fewer errors than baseline

# 3. Code style (optional)
vendor/bin/phpcs src/ --standard=PSR12
# Auto-fix what you can:
vendor/bin/phpcbf src/ --standard=PSR12
```

Compare results against the Phase 0 baseline. Document any remaining items.

---

## Appendix A: File-to-Task Map

| File | Task(s) |
|------|---------|
| `src/Request.php` | 2.1, 4.6 |
| `src/FilterInput.php` | 2.2 |
| `src/Ulid.php` | 1.3 |
| `src/UlidOriginal.php` | 3.4 |
| `src/Metagen.php` | 3.1 |
| `src/Module/Admin.php` | 3.2 |
| `src/IPAddress.php` | 3.3 |
| `src/Key/FileStorage.php` | 4.1 |
| `src/Module/Helper/Session.php` | 4.2 |
| `src/Language.php` | 4.3 |
| `src/Yaml.php` | 4.4 |
| `src/Jwt/JsonWebToken.php` | 4.5 |
| `tests/unit/*` (20 files) | 1.1 |
| `tests/unit/Key/FileStorageTest.php` | 1.2 |
| `composer.json` | 5.2 |
| `phpstan.neon` | 5.1 |

---

## Appendix B: Test Commands Quick Reference

| Command | Purpose |
|---------|---------|
| `composer install` | Install all dependencies |
| `vendor/bin/phpunit` | Run full test suite |
| `vendor/bin/phpunit --filter TestName` | Run specific test |
| `vendor/bin/phpunit tests/unit/File.php` | Run specific file |
| `vendor/bin/phpstan analyse src/ --level max --memory-limit=512M` | Static analysis |
| `vendor/bin/phpcs src/ --standard=PSR12` | Code style check |
| `vendor/bin/phpcbf src/ --standard=PSR12` | Auto-fix code style |
| `php -l src/File.php` | Syntax check single file |

---
