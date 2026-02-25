# XMF Library — Implementation & Testing Guide

**Claude Code Instructions for All Fixes**
Based on Code Review Report Rev. 2 — February 2026

---

## Scope

This document provides step-by-step Claude Code instructions for implementing and testing every fix identified in the XMF Code Review. Each task includes: the exact file and line numbers, the current broken code, the corrected replacement, a test strategy, and the commands to verify. Tasks are ordered by dependency: infrastructure fixes first (PHPUnit compatibility), then correctness bugs, then security hardening, then improvements.

**Total: 18 implementation tasks organized into 6 phases.**

---

## Overview

| Phase | Tasks | Focus |
|-------|-------|-------|
| 0 | 0.1–0.2 | Prerequisites & Environment |
| 1 | 1.1–1.3 | Test Infrastructure |
| 2 | 2.1–2.2 | Critical Correctness Bugs |
| 3 | 3.1–3.4 | High Severity Bugs |
| 4 | 4.1–4.6 | Security Hardening |
| 5 | 5.1–5.2 | Architectural Improvements |
| 6 | 6.1 | Final Validation |

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

### Task 1.1 — Add `:void` Return Types to PHPUnit Lifecycle Methods `[Low]`

**File:** All 20 test files in `tests/unit/`

**Problem:** PHPUnit 11.x requires `:void` return types on `setUp()`, `tearDown()`, `setUpBeforeClass()`, and `tearDownAfterClass()`. All 20 test files (except `UlidTest.php`) are missing these, causing every test to fail.

**Before:**

```php
protected function setUp()
{
    // ...
}
```

**After:**

```php
protected function setUp(): void
{
    // ...
}
```

Apply this to **every** lifecycle method in all 20 test files. A quick way:

```bash
# Find all affected files
grep -rn "function setUp()" tests/unit/
grep -rn "function tearDown()" tests/unit/
grep -rn "function setUpBeforeClass()" tests/unit/
grep -rn "function tearDownAfterClass()" tests/unit/
```

**Verify:**

```bash
vendor/bin/phpunit tests/unit/FilterInputTest.php
vendor/bin/phpunit tests/unit/MetagenTest.php
vendor/bin/phpunit tests/unit/RequestTest.php
# All three should no longer crash on return type errors
```

---

### Task 1.2 — Fix FileStorageTest Namespace `[Low]`

**File:** `tests/unit/Key/FileStorageTest.php`

**Problem:** Uses namespace `Xmf\Key` instead of `Xmf\Test\Key` (per `autoload-dev` PSR-4 mapping in `composer.json`).

**Before:**

```php
namespace Xmf\Key;
```

**After:**

```php
namespace Xmf\Test\Key;
```

**Verify:**

```bash
vendor/bin/phpunit tests/unit/Key/FileStorageTest.php
```

---

### Task 1.3 — Implement Ulid Monotonic Methods `[Medium]`

**File:** `src/Ulid.php`

**Problem:** Tests call `generateMonotonic()` and `resetMonotonicState()` but these methods do not exist. The test file `UlidTest.php` has 101 tests that all fail because of this.

**Fix:** Add two static properties and three methods to the `Ulid` class:

```php
private static ?int $lastTime = null;
private static ?string $lastRandom = null;

public static function generateMonotonic(): string
{
    $time = static::currentTimeMillis();

    if ($time === static::$lastTime && static::$lastRandom !== null) {
        static::$lastRandom = static::incrementBase32(static::$lastRandom);
    } else {
        static::$lastRandom = static::encodeRandomness();
        static::$lastTime = $time;
    }

    return static::encodeTime($time) . static::$lastRandom;
}

public static function resetMonotonicState(): void
{
    static::$lastTime = null;
    static::$lastRandom = null;
}

private static function incrementBase32(string $encoded): string
{
    $chars = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';
    $arr = str_split($encoded);

    for ($i = count($arr) - 1; $i >= 0; $i--) {
        $pos = strpos($chars, $arr[$i]);
        if ($pos < 31) {
            $arr[$i] = $chars[$pos + 1];
            return implode('', $arr);
        }
        $arr[$i] = '0';
    }

    // All positions carried over — wrap around to all zeros.
    // Overflow is astronomically unlikely (1 in 2^80 per millisecond),
    // and a silent wrap is safer in production than crashing.
    return implode('', $arr);
}
```

**Verify:**

```bash
vendor/bin/phpunit tests/unit/UlidTest.php
# Expected: 101 tests pass
```

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

### Task 3.2 — Fix Admin.php XSS in Config Methods `[High]`

**File:** `src/Module/Admin.php` (lines ~290–342)

**Problem:** `$value` is output unescaped in HTML context in the methods `addConfigError()`, `addConfigAccept()`, and `addConfigWarning()`. If a module config value contains user-influenced data, this is a stored XSS vector.

**Exploit path:** Admin-to-admin only in standard XOOPS (requires module config injection), so practical risk is moderate — but the fix is trivial.

**Before:**

```php
$line .= $value;
```

**After:**

```php
$line .= htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
```

Apply to **all three** methods: `addConfigError()`, `addConfigAccept()`, `addConfigWarning()`.

**Verify:**

```php
// Test that HTML entities are escaped in output
$admin = new Admin();
$admin->addConfigError('<script>alert(1)</script>');
$output = $admin->renderConfigErrors();
assert(strpos($output, '<script>') === false, 'XSS content should be escaped');
assert(strpos($output, '&lt;script&gt;') !== false, 'Content should be HTML-escaped');
```

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

### Task 3.4 — Fix UlidOriginal Bit Extraction `[High]`

**File:** `src/UlidOriginal.php` (lines ~62–82)

**Problem:** Flawed bit operations in `encodeRandomness()` produce non-uniform randomness distribution. The nibble extraction doesn't correctly map random bytes to Crockford base32 characters.

**Fix:** Replace the nibble extraction with proper random byte encoding:

```php
protected static function encodeRandomness(): string
{
    $bytes = random_bytes(10);
    $encoded = '';
    $chars = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

    // Encode 10 bytes into 16 base32 characters (80 bits)
    $bitBuffer = 0;
    $bitsInBuffer = 0;

    for ($i = 0; $i < 10; $i++) {
        $bitBuffer = ($bitBuffer << 8) | ord($bytes[$i]);
        $bitsInBuffer += 8;

        while ($bitsInBuffer >= 5) {
            $bitsInBuffer -= 5;
            $index = ($bitBuffer >> $bitsInBuffer) & 0x1F;
            $encoded .= $chars[$index];
        }
    }

    return $encoded;
}
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

### Task 4.2 — Add `allowed_classes` to Session Unserialize `[Medium]`

**File:** `src/Module/Helper/Session.php` (line ~85)

**Problem:** `unserialize()` without `allowed_classes` restriction allows PHP Object Injection if session data is ever tampered with.

**Before:**

```php
return unserialize($_SESSION[$prefixedName]);
```

**After:**

```php
return unserialize($_SESSION[$prefixedName], ['allowed_classes' => false]);
```

**Verify:**

```php
// Functional test: store and retrieve array data
$session = new Session();
$session->set('test_key', ['foo' => 'bar']);
$result = $session->get('test_key');
assert($result === ['foo' => 'bar'], 'Array data should round-trip correctly');
```

---

### Task 4.3 — Harden Language.php File Inclusion `[Medium]`

**File:** `src/Language.php` (lines ~79–83)

**Problem:** Uses blacklist-based `strpos()` checks to prevent directory traversal. This is fragile — should use `realpath()` for canonical path validation.

**Before:**

```php
// Blacklist checks with strpos for '..' etc.
```

**After:**

```php
$realPath = realpath($filePath);
$allowedDir = realpath(XOOPS_ROOT_PATH);
if ($realPath === false || strpos($realPath, $allowedDir) !== 0) {
    return false;
}
include $realPath;
```

**Verify:**

```php
// Test with path traversal attempt
$result = Language::load('../../etc/passwd');
assert($result === false, 'Path traversal should be blocked');
```

---

### Task 4.4 — Add File Size Check to Yaml.php `[Medium]`

**File:** `src/Yaml.php` (lines ~91, 205)

**Problem:** `file_get_contents()` called without file size check — could cause memory exhaustion on large files. Also catches broad `\Exception` instead of specific `ParseException`.

**Fix (file size check):**

```php
$maxSize = 2 * 1024 * 1024; // 2 MB
if (filesize($yamlFile) > $maxSize) {
    throw new \RuntimeException("YAML file exceeds maximum size of 2MB");
}
$content = file_get_contents($yamlFile);
```

**Fix (narrow exception):**

```php
// Before:
} catch (\Exception $e) {

// After:
} catch (\Symfony\Component\Yaml\Exception\ParseException $e) {
```

**Verify:**

```php
// Create a test file > 2MB
$tmpFile = tempnam(sys_get_temp_dir(), 'yaml');
file_put_contents($tmpFile, str_repeat("key: value\n", 300000));
try {
    Yaml::load($tmpFile);
    assert(false, 'Should have thrown RuntimeException');
} catch (\RuntimeException $e) {
    assert(strpos($e->getMessage(), 'maximum size') !== false);
}
unlink($tmpFile);
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

### Task 4.6 — Remove `get_magic_quotes_gpc()` Call `[Low]`

**File:** `src/Request.php` (~line 119)

**Problem:** `get_magic_quotes_gpc()` was removed in PHP 8.0. Calling it on PHP 8+ causes a fatal error.

**Fix:** Remove the entire conditional block that calls `get_magic_quotes_gpc()`. The function was only relevant for PHP < 5.4 where magic quotes could be enabled.

**Verify:**

```bash
php -l src/Request.php
vendor/bin/phpunit tests/unit/RequestTest.php
```

---

## Phase 5: Architectural Improvements

### Task 5.1 — Create PHPStan Stubs for XOOPS Classes `[Low]`

**Problem:** ~524 of 724 PHPStan errors are due to missing XOOPS class definitions (the framework classes that XMF depends on). These aren't real bugs — PHPStan just can't see the types.

**Fix:**

1. Create directory `stubs/`:

```bash
mkdir -p stubs
```

2. Create minimal stub files:

**`stubs/XoopsObject.stub.php`:**

```php
<?php
class XoopsObject {
    public function getVar(string $key, string $format = 's') { return ''; }
    public function setVar(string $key, $value): void {}
    public function assignVar(string $key, $value): void {}
    public function getVars(): array { return []; }
    public function cleanVars(): bool { return true; }
}
```

**`stubs/XoopsDatabase.stub.php`:**

```php
<?php
abstract class XoopsDatabase {
    abstract public function query(string $sql);
    abstract public function queryF(string $sql);
    abstract public function prefix(string $table = ''): string;
    abstract public function fetchArray($result): ?array;
    abstract public function fetchRow($result): ?array;
    abstract public function getInsertId(): int;
    abstract public function getAffectedRows(): int;
    abstract public function freeRecordSet($result): void;
}
```

**`stubs/XoopsModule.stub.php`:**

```php
<?php
class XoopsModule extends XoopsObject {
    public function getVar(string $key, string $format = 's') { return ''; }
    public function getInfo(string $key = '') { return ''; }
    public function loadAdminMenu(): void {}
}
```

**`stubs/CriteriaElement.stub.php`:**

```php
<?php
class CriteriaElement {
    public function render(): string { return ''; }
    public function renderWhere(): string { return ''; }
}
class CriteriaCompo extends CriteriaElement {
    public function add(CriteriaElement $criteria, string $condition = 'AND'): void {}
}
class Criteria extends CriteriaElement {
    public function __construct(string $column, $value = '', string $operator = '=', string $prefix = '', string $function = '') {}
}
```

3. Update `phpstan.neon`:

```yaml
parameters:
    level: max
    paths:
        - src
    scanDirectories:
        - stubs
    excludePaths:
        - tests/*
```

**Verify:**

```bash
vendor/bin/phpstan analyse src/ --level max --memory-limit=512M
# Should see significantly fewer errors (target: ~200 actionable → near-zero)
```

---

### Task 5.2 — Remove Redundant `paragonie/random_compat` `[Low]`

**File:** `composer.json`

**Problem:** `"paragonie/random_compat": "^9.99.100"` is a polyfill for `random_bytes()` / `random_int()`, which are native in PHP 7+. Since XMF requires `PHP >= 8.2`, this dependency is unnecessary.

**Fix:**

1. Remove from `composer.json` `require`:

```json
// Remove this line:
"paragonie/random_compat": "^9.99.100"
```

2. Run:

```bash
composer update
```

**Verify:**

```bash
grep -r "random_compat" src/
vendor/bin/phpunit
# Tests should still pass
```

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
