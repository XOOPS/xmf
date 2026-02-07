# XMF ChangeLog

## [Unreleased]

### Bug Fixes
* Fix `Request::setVar()` writing to literal key `'name'` instead of variable `$name` for ENV and SERVER superglobals
* Fix `FilterInput` hex entity decode (`&#xNN;`) producing null bytes instead of correct characters on PHP 7+

## [1.2.32] - 2025-02-06

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

**Bug fixes:**
* Fix `getDateTime()` to always return UTC regardless of system timezone
* Fix `decodeTime()` to fully validate the ULID via `isValid()` (rejects invalid chars, overflow, wrong length)
* Fix `decodeRandomness()` to validate the full ULID (including time portion), not just the random part
* Sanitize exception messages to avoid leaking full ULID values into logs

**Test improvements:**
* Replace sleep-based ordering tests with `generateMonotonic()` to eliminate CI flakiness
* Fix spec vector test comment and assertion (correct UTC time: 22:36:16, not 23:29:36)
* Fix `testDecodeTimeThrowsExceptionForInvalidCharacter` to place invalid char in time portion
* Fix `testGenerateMonotonicIncrementsRandomPortion` to always perform assertions (retry loop)
* Update all `@covers` annotations to use FQCN (`\Xmf\Ulid::method`) for PHPUnit 10+ compatibility
* Use `expectExceptionMessageMatches()` for more robust exception message assertions
* Reduce `testRandomnessDistribution` iterations (10000 to 1000) with wider tolerance to improve speed

## [1.2.31] - 2024-11-27
* Updated Debug for Kint changes (mamba)
* Added Issues Template (mamba)
* PHP 8.4 Implicitly nullable parameters (mamba)
* Update PhpUnit versions (mamba)
* Upgrade Smarty to 4.5.5

## [1.2.30] - 2024-05-30
* Upgrade Smarty to 4.5.3

## [1.2.29] - 2023-11-20
* Add Random::generateSecureRandomBytes()
* Replace random_bytes() with generateSecureRandomBytes() for PHP 5.6

## [1.2.28] - 2023-10-30
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

## [1.2.24] - 2021-03-25
* Fixes for PHP 5.3 compatibility

## [1.2.23] - 2021-02-15
* Additional fix in Debug for Kint 3.3

## [1.2.22] - 2021-02-13
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

## [1.2.18] - 2019-12-01
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

## [1.2.14] - 2018-03-30
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

## [1.1.2] - 2016-08-06
* Fix Can't check isUserAdmin on Anonymous

## [1.1.1] - 2016-07-28
* firebase/php-jwt to 4.0.0
* Bump min PHP to 5.3.9 to allow symfony/yaml 2.8.*

## [1.1.0] - 2016-07-14
* Add Xmf\Database\Migrate class to provide schema synchronization capabilities for modules
* Bug fixes in Xmf\Database\Tables including option to disable automatic quoting of values in update() and insert()

## [1.0.2] - 2016-06-01
* Fix issues with file name validation in Xmf\Language::loadFile()
* Add method Request::hasVar($name, $hash) to determine if a variable name exists in hash

## [1.0.1] - 2016-03-30
* Remove @version from docblock, consistent with XoopsCore25

## [1.0.0] - 2016-03-25
* Fix minor typos
* Add version to changelog

## [1.0.0-RC1] - 2016-03-04
* Preparation for release in XOOPS 2.5.8

## [0.0.0] - 2016-02-09
* Convert to library instead of module
* Preparing for 2.5.8 inclusion
* Sync with 2.6 current state
