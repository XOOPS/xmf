# XMF — XOOPS Module Framework: Copilot Instructions

<!-- Generic XOOPS conventions: see .github/xoops-copilot-template.md for reuse in other repos -->

## About This Repository

XMF is a PHP utility library for the XOOPS CMS. It provides input filtering, database helpers, JWT tokens, meta tag generation, YAML handling, ULID/UUID generation, and module administration tools.

## Project Layout

```
src/                      # Library source (namespace: Xmf\)
tests/unit/               # PHPUnit tests (namespace: Xmf\Test\)
stubs/                    # PHPStan stub files for XOOPS core classes
.github/workflows/ci.yml  # GitHub Actions: tests, PHPStan, PHPCS, coverage
```

## Build & Test

```bash
composer install          # Install dependencies
composer test             # Run PHPUnit tests
composer analyse          # Run PHPStan (level max)
composer lint             # Check code style (PSR-12)
composer fix              # Auto-fix code style issues
composer baseline         # Regenerate PHPStan baseline
composer ci               # Run all checks (lint + analyse + test)
```

PHPUnit has two config files: `phpunit.xml.dist` (PHPUnit 9) and `phpunit10.xml.dist` (PHPUnit 10+). CI selects automatically based on the installed version.

## PHP Compatibility

Code must run on PHP 7.4 through 8.4. Do not use features exclusive to PHP 8.0+ (named arguments, match expressions, union type hints in signatures, enums, fibers, readonly properties). CI tests all versions in the matrix.

## Coding Conventions

- Follow PSR-12. Line length limit is 200 characters (see `phpcs.xml`).
- Every source file begins with the XOOPS copyright header block.
- Class docblocks include `@category`, `@package`, `@author`, `@copyright`, `@license`, and `@link` tags.
- Use `self::` for constants (e.g., `self::ENCODING`). PHPStan level max cannot resolve `static::` on constants and reports `mixed`.
- Prefer `\Throwable` in catch blocks over `\Exception` to cover both exceptions and errors.
- Use `trigger_error()` with `E_USER_WARNING` for non-fatal file operation failures. Use `basename()` in error messages to avoid exposing absolute paths.
- Suppress PHP-native warnings with `@` when a subsequent `=== false` check and explicit `trigger_error()` provide a cleaner error path (e.g., `@file_get_contents()`, `@filesize()`).

## XOOPS Compatibility Layer

Classes check `class_exists('Xoops', false)` to detect XOOPS 2.6+ and fall back to XOOPS 2.5 globals (`$GLOBALS['xoopsModule']`, `xoops_getHandler()`). Never assume XOOPS is present at runtime. This pattern allows XMF to work with both XOOPS generations and in standalone testing.

## XMF-Specific Architecture

- **Static utility classes**: Most classes expose static methods (`Request::getInt()`, `Yaml::read()`, `Metagen::generateSeoTitle()`). This is intentional — do not refactor to instance methods.
- **Singleton factories**: `FilterInput::getInstance()` caches instances keyed by configuration signature.
- **PHP-wrapped YAML**: `Yaml::dumpWrapped()` / `readWrapped()` embed YAML inside `<?php /* --- ... */ ?>` to prevent direct serving of config files. The `---` and `...` markers delimit the YAML content.
- **ENCODING constant**: `Metagen::ENCODING` and `Highlighter::ENCODING` are `'UTF-8'`. Always reference the class constant, never hardcode the string.

## Security Considerations

- All user input must go through `Request::getVar()` or `FilterInput::clean()`.
- `FilterInput` strips dangerous HTML tags and event handler attributes (`on*`). Blacklists include `<script>`, `<applet>`, `<iframe>`, and `javascript:` / `vbscript:` URIs.
- `Key\FileStorage::save()` uses `var_export()` for PHP code generation — never string interpolation.
- `Module\Helper\Session::get()` passes `['allowed_classes' => false]` to `unserialize()`.
- `Language::loadFile()` validates paths with `realpath()` to prevent directory traversal.
- `Yaml::read()` and `readWrapped()` enforce a 2 MB file size limit and check `is_readable()` before reading.

## Testing Guidelines

- Test classes extend `\PHPUnit\Framework\TestCase` in the `Xmf\Test\` namespace.
- Tests must be fully isolated — no XOOPS installation required. PHPStan stubs in `stubs/` provide type information only.
- Name test methods `test{MethodName}` or `test{MethodName}{Scenario}`.
- Use `try/finally` for temp file cleanup so files are removed even when assertions fail.
- Assert `fopen()` return values with `$this->assertNotFalse($fh, '...')` before writing.
- Suppress expected `trigger_error()` warnings with `@` (e.g., `@Yaml::read($oversizedFile)`).
- Use `ReflectionMethod::setAccessible(true)` sparingly, only to test protected/private methods.

## Static Analysis

PHPStan runs at level `max`. The baseline (`phpstan-baseline.neon`) tracks ~596 existing errors for incremental cleanup. New code must not introduce new PHPStan errors. Run `composer baseline` to regenerate after intentionally resolving baseline items. The baseline script clears the file first to ensure a complete capture.

Stubs in `stubs/` define XOOPS framework classes (`Xoops`, `XoopsModule`, `XoopsCache`, criteria classes, database handlers, etc.) so PHPStan can type-check XMF code without requiring a full XOOPS installation.

## CI Pipeline

GitHub Actions runs four jobs on every push and PR:

| Job | PHP | What it does |
|---|---|---|
| **Tests** | 7.4-8.4 matrix | `composer test` (includes lowest-deps run on 7.4) |
| **PHPStan** | 8.2 | `composer analyse` at level max |
| **Code Style** | 8.2 | `composer lint` (non-blocking — pre-existing issues) |
| **Coverage** | 8.2 | PHPUnit + Xdebug, uploads clover.xml to Scrutinizer |

Scrutinizer runs its own `php_analyzer` tool. It excludes `_archive/`, `tests/`, `vendor/`, `docs/`, and `stubs/`.

## Pull Request Checklist

1. Code follows PSR-12 and passes `composer lint` (or `composer fix`).
2. `composer analyse` passes with no new errors beyond the baseline.
3. `composer test` passes on all PHP versions (7.4-8.4).
4. New public methods have PHPDoc with `@param`, `@return`, and `@throws` tags.
5. New functionality has corresponding unit tests in `tests/unit/`.
6. Changes are documented in `CHANGELOG.md` under `[Unreleased]`.
7. No hardcoded encoding strings — use the class `ENCODING` constant.
8. File operations include proper error handling (exists -> size -> readable -> read -> check false).
