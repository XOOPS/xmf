# XMF ChangeLog

## [1.2.33] - 2026-02-08



## [1.2.33-beta2] - 2026-02-07

### Bug Fixes
* Fix `Request::setVar()` writing to literal key `'name'` instead of variable `$name` for ENV and SERVER superglobals
* Fix `FilterInput` hex entity decode (`&#xNN;`) producing null bytes instead of correct characters on PHP 7+; use `html_entity_decode()` for proper Unicode support
* Fix `Metagen::html2text()` discarding `preg_replace_callback()` result for numeric HTML entities; use `html_entity_decode()` with `self::ENCODING` for codepoints > 255
* Fix XSS in `Module\Admin` config methods (`addConfigError`, `addConfigAccept`, `addConfigWarning`) by escaping output with charset-aware `htmlspecialchars()`
* Fix `IPAddress::normalize()` passing `inet_pton()` false result to `inet_ntop()` for invalid IPs
* Fix `Language` using unqualified `XOOPS_ROOT_PATH` constant (resolved as `Xmf\XOOPS_ROOT_PATH` in namespaced context)
* Fix `Request::getInt()`, `getFloat()`, `getBool()` return types to match PHPDoc via explicit casts
* Fix `Migrate::getTargetDefinitions()` checking `null` instead of `false` for `Yaml::read()` failure
* Fix `Tables::executeQueue()` passing potentially non-string `$ddl` to `execSql()` after `renderTableCreate()` failure
* Fix `JsonWebToken::create()` passing `ArrayObject` to `JWT::encode()` which only accepts `array`

### Security
* Harden `Key\FileStorage::save()` to use `var_export()` instead of string interpolation for PHP code generation
* Add `allowed_classes => false` to `unserialize()` in `Module\Helper\Session::get()` to prevent PHP Object Injection
* Harden `Language::loadFile()` with `realpath()` and boundary-safe directory validation to prevent path traversal
* Add 2MB file size limit to `Yaml::read()` and `Yaml::readWrapped()` to prevent memory exhaustion
* Harden `Yaml` with `is_readable()` pre-check, suppressed `file_get_contents()` warnings, `filesize()` false checks; widen exception catches to `\Throwable`
* Preserve `Jwt\JsonWebToken::decode()` `object|false` API contract (no longer throws on decode failure)
* Remove dead `get_magic_quotes_gpc()` calls from `Request` (function removed in PHP 8.0)

### Ulid class overhaul (breaking changes)

**New features:**
* Add `Ulid::generateMonotonic()` for strictly increasing ULIDs within the same millisecond
* Add `Ulid::resetMonotonicState()` to reset monotonic generation state
* Add `Ulid::currentTimeMillis()` as the canonical way to get millisecond timestamps
* Add `Ulid::getDateTime()` to extract a `DateTimeImmutable` (UTC) from a ULID
* Add `Ulid::compare()` for case-insensitive ULID comparison
* Add `Ulid::toBinary()` / `Ulid::fromBinary()` for 16-byte binary conversion (requires ext-bcmath)
* Add `Ulid::toUuid()` / `Ulid::fromUuid()` for UUID bidirectional conversion (requires ext-bcmath)
* Add `Ulid::decode()` to split a ULID into its time and randomness components
* Add `Ulid::isValid()` for full ULID validation including timestamp overflow check
* Add `BINARY_LENGTH`, `MAX_TIME`, `TIME_LENGTH`, `RANDOM_LENGTH`, `ULID_LENGTH` constants
* Enforce 64-bit PHP requirement across all timestamp operations (32-bit builds throw `RuntimeException`)
* `generateMonotonic()` handles random portion overflow by advancing the logical timestamp

**Breaking changes:**
* `decodeRandomness()` now returns a 16-character base32 string instead of an integer
* `microtimeToUlidTime()` is deprecated; use `currentTimeMillis()` instead
  - The old method incorrectly subtracted a Y2K epoch offset; the new behavior uses standard Unix epoch milliseconds per the ULID spec
* `encodeRandomness()` reimplemented with optimized bit-packing algorithm for correct 80-bit extraction

**Ulid bug fixes:**
* Fix `getDateTime()` to always return UTC regardless of system timezone
* Fix `decodeTime()` to fully validate the ULID via `isValid()` (rejects invalid chars, overflow, wrong length)
* Fix `decodeRandomness()` to validate the full ULID (including time portion), not just the random part
* Sanitize exception messages to avoid leaking full ULID values into logs

**Ulid test improvements:**
* Replace sleep-based ordering tests with `generateMonotonic()` to eliminate CI flakiness
* Fix spec vector test comment and assertion (correct UTC time: 22:36:16, not 23:29:36)
* Fix `testDecodeTimeThrowsExceptionForInvalidCharacter` to place invalid char in time portion
* Fix `testGenerateMonotonicIncrementsRandomPortion` to always perform assertions (retry loop)
* Update all `@covers` annotations to use FQCN (`\Xmf\Ulid::method`) for PHPUnit 10+ compatibility
* Use `expectExceptionMessageMatches()` for more robust exception message assertions
* Reduce `testRandomnessDistribution` iterations (10000 to 1000) with wider tolerance to improve speed

### Changed
* Use strict comparison (`===`) instead of loose (`==`) in `FilterInput` attribute filtering and `Database\Tables` column lookups
* Fix `FileStorageTest` namespace from `Xmf\Key` to `Xmf\Test\Key` to match autoload-dev configuration
* Fix trailing semicolons in `@throws` PHPDoc tags in `Jwt\JsonWebToken::create()`
* Update composer.json by @mambax7 in #119
* Update composer, phpstan, phpcs by @mambax7 in #122

### Removed
* Remove redundant `paragonie/random_compat` dependency (native in PHP 7+)

### Infrastructure
* Add PHPStan stub files for XOOPS framework classes to eliminate ~524 false-positive errors
* Configure `phpstan.neon` to scan stubs directory
* Move changelog to `CHANGELOG.md` at repo root; `docs/changelog.md` now redirects
* Simplify `.scrutinizer.yml` to analysis-only; move `stubs/` from `excluded_paths` to `dependency_paths` for constant/class resolution
* Add dedicated PHPStan, PHPCS, and code coverage jobs to GitHub Actions CI workflow
* Generate PHPStan baseline (`phpstan-baseline.neon`) with ~546 existing errors for incremental cleanup
* Add `composer baseline` script with backup/restore safety for PHPStan baseline regeneration
* Update `.gitignore` to exclude `build/` directory (coverage output)
* Update `.gitattributes` with export-ignore for PHPStan, PHPUnit, and stub files
* Add GitHub Copilot custom instructions (`.github/copilot-instructions.md`) and reusable XOOPS template

### Tests
* Add unit tests for `Request::setVar()` ENV and SERVER branches
* Add unit tests for `FilterInput` hex and decimal entity decode
* Add unit test for `Metagen::html2text()` numeric entity conversion
* Add unit test for `IPAddress::normalize()` invalid IP handling
* Add unit tests for `Yaml::read()` and `Yaml::readWrapped()` file size limits

## [1.2.33-beta1] - 2025-09-10

* updating copyright by @mambax7 in #114
* update composer.json by @mambax7 in #115
* add php_codesniffer and phpstan by @mambax7 in #116
* add SendmailRunner by @mambax7 in #117
* update phpstan and php_codesniffer versions by @mambax7 in #118

## [1.2.32] - 2025-03-10

* Update firebase/php-jwt to 6.11.0 with PHP 7.4 compatibility

## [1.2.31] - 2024-11-28
* Updated Debug for Kint changes (mamba)
* Added Issues Template (mamba)
* PHP 8.4 Implicitly nullable parameters (mamba)
* Update PhpUnit versions (mamba)
* Upgrade Smarty to 4.5.5

## [1.2.30] - 2024-06-16
* Upgrade Smarty to 4.5.3

## [1.2.29] - 2023-12-05
* Add Random::generateSecureRandomBytes()
* Replace random_bytes() with generateSecureRandomBytes() for PHP 5.6

## [1.2.28] - 2023-11-01
* Updates to library dependencies
* PHP 8.0 Error Suppression operator issues
* Handle case of no permissionHandler found
* Adds ULID support
* Cosmetic and code improvements

## [1.2.27] - 2023-03-19
* Update to firebase/php-jwt 6.0.0

## [1.2.26] - 2022-04-16
* Add Xmf\Module\Helper\Permission::getItemIds($gperm_name, $gperm_groupid)
* Use new module version in XoopsCore25
* Fix issues in Xmf\Database\Tables and Xmf\Database\Migrate
* Fix some issues related to new PHP versions

## [1.2.25] - 2021-05-07
* Add \Xmf\Module\Admin::renderNavigation() method

## [1.2.24] - 2021-03-26
* Fixes for PHP 5.3 compatibility

## [1.2.23] - 2021-02-16
* Additional fix in Debug for Kint 3.3

## [1.2.22] - 2021-02-14
* Fixes in Debug for Kint 3.3

## [1.2.21] - 2021-02-13
* Library updates
* XOOPS standardization
* Minor code cleanups

## [1.2.20] - 2020-08-18
* \Xmf\Module\Helper\AbstractHelper::serializeForHelperLog() fix logging of a resource type
* Unit test updates for latest version of Webmozart\Assert

## [1.2.19] - 2020-02-13
* \Xmf\Yaml::read() eliminate PHP warning if specified file does not exist

## [1.2.18] - 2019-12-02
* PHP 7.4 ready
* Fix error in Database\Table::loadTableFromYamlFile()
* Add Uuid::packAsBinary() and Uuid::unpackBinary() methods
* Add Module/Helper/GenericHelper::uploadPath() and uploadUrl() methods
* Add proxy support in IPAddress::fromRequest()

## [1.2.17] - 2019-03-27
* Docblock corrections

## [1.2.16] - 2018-11-29
* Fix database column quoting

## [1.2.15] - 2018-10-01
* Fix database column quoting for prefix indexes
* Add dirname() method to helper classes
* Changes Request::hasVar() default for $hash to 'default'

## [1.2.14] - 2018-03-31
* Add serialization to non-scalar log data
* Improved handling of custom key storage
* Add some unit testing
* Add roave/security-advisories requirement to catch security issues at build time
* Synchronization with XoopsCore

## [1.2.12] - 2017-11-12
* Updates the supporting Kint library to version 2.2

## [1.2.11] - 2017-11-12
* Adds support for UUID generation using the Xmf\Uuid class

## [1.2.10] - 2017-07-24
* Fixes issues in Xmf\Random appearing under PHP 7.1
* Xmf\Random will now avoid the mcrypt extension if at all possible, and use the native random_bytes() function in PHP 7+

## [1.2.9] - 2017-05-19
* Fixes issues in Xmf\Highlighter and Xmf\Metagen

## [1.2.8] - 2017-05-07
* Add a missing option in \Xmf\Module\Helper\Permission::checkPermission()

## [1.2.7] - 2017-04-29
* Fixes issue with Xmf\Metagen::generateSeoTitle

## [1.2.6] - 2017-04-18
* Fixes issues with Xmf\Request::MASK_ALLOW_HTML

## [1.2.5] - 2017-04-03
* Updates to kint-php/kint

## [1.2.4] - 2017-03-06
* Adds Xmf\Assert

## [1.2.3] - 2017-03-03
* Synchronizes some minor docblock changes

## [1.2.2] - 2017-02-25
* Corrects issues with Yaml:readWrapped()

## [1.2.0] - 2016-11-02
* Separates the stop word logic from MetaGen into a new StopWords class
* Deprecates MetaGen::checkStopWords()

## [1.1.4] - 2016-09-11
* Handle non-ascii text in Metagen::generateKeywords()

## [1.1.3] - 2016-08-13
* Fix XoopsRequest class not found in StripSlashesRecursive method

## [1.1.2] - 2016-08-07
* Fix Can't check isUserAdmin on Anonymous

## [1.1.1] - 2016-07-28
* firebase/php-jwt to 4.0.0
* Bump min PHP to 5.3.9 to allow symfony/yaml 2.8.*

## [1.1.0] - 2016-07-15
* Add Xmf\Database\Migrate class to provide schema synchronization capabilities for modules
* Bug fixes in Xmf\Database\Tables including option to disable automatic quoting of values in update() and insert()

## [1.0.2] - 2016-05-31
* Fix issues with file name validation in Xmf\Language::loadFile()
* Add method Request::hasVar($name, $hash) to determine if a variable name exists in hash

## [1.0.1] - 2016-03-30
* Remove @version from docblock, consistent with XoopsCore25

## [1.0.0] - 2016-03-28
* Fix minor typos
* Add version to changelog

## [1.0.0-RC1] - 2016-03-04
* Preparation for release in XOOPS 2.5.8

## [0.0.0] - 2016-02-09
* Convert to library instead of module
* Preparing for 2.5.8 inclusion
* Sync with 2.6 current state
