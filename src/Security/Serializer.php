<?php declare(strict_types=1);

namespace Xmf\Security;

use JsonException;
use RuntimeException;
use UnexpectedValueException;

/**
 * Secure serialization toolkit for XOOPS/XMF
 *
 * Design principles:
 * - Explicit is better than implicit
 * - Secure by default (no objects unless explicitly allowed)
 * - Simple, focused API
 * - Compatible with PHP 7.4+
 */
final class Serializer
{
    private const MAX_SIZE = 5_000_000; // 5MB
    private const JSON_DEPTH = 512;
    private const JSON_FLAGS = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR;

    /** @var \Closure|null Optional logger for legacy format detection */
    private static $legacyLogger = null;
    private static bool $debugMode = false;
    private static array $debugLog = [];
    private static ?float $startTime = null;

    // ═══════════════════════════════════════════════════════════
    // JSON Methods (Recommended for new code)
    // ═══════════════════════════════════════════════════════════

    /**
     * Serialize data to JSON
     *
     * @param mixed $data
     * @return string
     * @throws JsonException On encoding failure
     */
    public static function toJson($data): string
    {
        return json_encode($data, self::JSON_FLAGS);
    }

    /**
     * Deserialize JSON string
     *
     * @param string $json
     * @return mixed
     * @throws JsonException On invalid JSON
     * @throws UnexpectedValueException On empty input
     */
    public static function fromJson(string $json)
    {
        if ($json === '') {
            throw new UnexpectedValueException('Cannot deserialize empty JSON');
        }

        return json_decode($json, true, self::JSON_DEPTH, JSON_THROW_ON_ERROR);
    }

    // ═══════════════════════════════════════════════════════════
    // PHP Serialize Methods (For complex data structures)
    // ═══════════════════════════════════════════════════════════

    /**
     * Serialize data using PHP's native format
     *
     * @param mixed $data
     * @return string
     * @throws \InvalidArgumentException On unsupported types
     */
    public static function toPhp($data): string
    {
        if (is_resource($data)) {
            throw new \InvalidArgumentException('Cannot serialize resources');
        }
        if ($data instanceof \Closure) {
            throw new \InvalidArgumentException('Cannot serialize closures');
        }

        return serialize($data);
    }

    // ═══════════════════════════════════════════════════════════
    //  Debug Mode for Serializer
    // ═══════════════════════════════════════════════════════════

    /**
     * Enable or disable debug mode
     *
     * @param bool $enable
     * @return void
     */
    public static function enableDebug(bool $enable = true): void
    {
        self::$debugMode = $enable;
        if ($enable) {
            self::$startTime = microtime(true);
            self::$debugLog = [];
        }
    }

    /**
     * Get collected debug statistics
     *
     * @return array
     */
    public static function getDebugStats(): array
    {
        if (!self::$debugMode) {
            return [];
        }

        $totalTime = microtime(true) - self::$startTime;
        $formats = array_count_values(array_column(self::$debugLog, 'format'));

        return [
            'total_operations' => count(self::$debugLog),
            'total_time' => round($totalTime, 4),
            'formats_detected' => $formats,
            'slow_operations' => array_filter(self::$debugLog, fn($log) => $log['time'] > 0.01),
            'errors' => array_filter(self::$debugLog, fn($log) => isset($log['error']))
        ];
    }

    /**
     * Internal helper to log debug info
     *
     * @param string      $operation
     * @param string      $format
     * @param float       $time
     * @param string|null $error
     * @return void
     */
    private static function debug(string $operation, string $format, float $time, ?string $error = null): void
    {
        if (!self::$debugMode) {
            return;
        }

        self::$debugLog[] = [
            'operation' => $operation,
            'format' => $format,
            'time' => round($time, 6),
            'memory' => memory_get_usage(true),
            'error' => $error,
            'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)[2] ?? null
        ];
    }

    /**
     * Deserialize PHP serialized string (secure by default)
     *
     * @param string $payload The serialized string
     * @param array $allowedClasses Whitelist of allowed classes (empty = no objects)
     * @return mixed
     * @throws RuntimeException On security violation
     * @throws UnexpectedValueException On deserialization failure
     */
    public static function fromPhp(string $payload, array $allowedClasses = [])
    {
        $start = self::$debugMode ? microtime(true) : 0;

        try {
            self::validateInput($payload);
            self::validateSecurity($payload, empty($allowedClasses));
            $result = self::unserialize($payload, $allowedClasses);

            if (self::$debugMode) {
                self::debug('fromPhp', Format::PHP, microtime(true) - $start);
            }

            return $result;
        } catch (\Throwable $e) {
            if (self::$debugMode) {
                self::debug('fromPhp', Format::PHP, microtime(true) - $start, $e->getMessage());
            }
            throw $e;
        }
    }

    // ═══════════════════════════════════════════════════════════
    // Legacy Support (For migration from old XOOPS data)
    // ═══════════════════════════════════════════════════════════

    /**
     * Create legacy format (base64-encoded serialized data)
     *
     * @deprecated Use toJson() for new code
     * @param mixed $data
     * @return string
     */
    public static function toLegacy($data): string
    {
        return base64_encode(serialize($data));
    }

    /**
     * Deserialize legacy format (handles plain, base64, and optional gzip)
     *
     * @param string $payload
     * @param array $allowedClasses Whitelist of allowed classes
     * @return mixed
     */
    public static function fromLegacy(string $payload, array $allowedClasses = [])
    {
        self::validateInput($payload);

        // Try plain PHP serialize first
        $result = self::tryUnserialize($payload, $allowedClasses);
        if ($result !== null) {
            return $result;
        }

        // Check if it looks like base64 before attempting decode
        if (!self::isLikelyBase64($payload)) {
            throw new RuntimeException('Invalid legacy format: not serialized or base64');
        }

        // Try base64-decoded
        $decoded = base64_decode($payload, true);
        if ($decoded === false) {
            throw new RuntimeException('Invalid legacy format: base64 decode failed');
        }

        // Validate size after decoding
        self::validateSize($decoded);

        // Check for optional gzip compression
        if (self::isGzip($decoded)) {
            $unzipped = @gzdecode($decoded);
            if ($unzipped === false) {
                throw new RuntimeException('Gzip decompression failed');
            }
            $decoded = $unzipped;
            self::validateSize($decoded);
        }

        self::logLegacy($payload);
        self::validateSecurity($decoded, empty($allowedClasses));

        return self::unserialize($decoded, $allowedClasses);
    }

    // ═══════════════════════════════════════════════════════════
    // Smart Methods (Format detection and conversion)
    // ═══════════════════════════════════════════════════════════

    /**
     * Deserialize with automatic format detection
     *
     * @param string $payload
     * @param array $allowedClasses For PHP/legacy formats
     * @return mixed
     */
    public static function from(string $payload, array $allowedClasses = [])
    {
        $format = self::detect($payload);

        // Use detected format if confident
        if ($format === Format::JSON) {
            return self::fromJson($payload);
        } elseif ($format === Format::PHP) {
            return self::fromPhp($payload, $allowedClasses);
        } elseif ($format === Format::LEGACY) {
            return self::fromLegacy($payload, $allowedClasses);
        }

        // Format::AUTO - try sequence for migrations
        try {
            return self::fromJson($payload);
        } catch (\Throwable $e) {
            // Not JSON
        }

        if (self::looksLikeSerialized(ltrim($payload))) {
            return self::fromPhp($payload, $allowedClasses);
        }

        return self::fromLegacy($payload, $allowedClasses);
    }

    /**
     * Detect the serialization format without deserializing
     *
     * @param string $payload
     * @return string One of Format::* constants
     */
    public static function detect(string $payload): string
    {
        if ($payload === '') {
            return Format::AUTO;
        }

        $trimmed = ltrim($payload);

        // Check JSON
        if (isset($trimmed[0]) && ($trimmed[0] === '{' || $trimmed[0] === '[')) {
            if (self::isValidJson($payload)) {
                return Format::JSON;
            }
        }

        // Check PHP serialized
        if (self::looksLikeSerialized($trimmed)) {
            return Format::PHP;
        }

        // Check base64-encoded serialized
        if (self::isLikelyBase64($payload)) {
            $decoded = base64_decode($payload, true);
            if ($decoded !== false && self::looksLikeSerialized($decoded)) {
                return Format::LEGACY;
            }
        }

        return Format::AUTO;
    }

    // ═══════════════════════════════════════════════════════════
    // Convenience Methods (Type-safe helpers)
    // ═══════════════════════════════════════════════════════════

    /**
     * Deserialize expecting an array result
     *
     * @param string $payload
     * @param string $format
     * @return array
     */
    public static function toArray(string $payload, string $format = Format::AUTO): array
    {
        $data = null;
        if ($format === Format::JSON) {
            $data = self::fromJson($payload);
        } elseif ($format === Format::PHP) {
            $data = self::fromPhp($payload);
        } elseif ($format === Format::LEGACY) {
            $data = self::fromLegacy($payload);
        } elseif ($format === Format::AUTO) {
            $data = self::from($payload);
        }

        if (!is_array($data)) {
            throw new UnexpectedValueException(
                sprintf('Expected array, got %s', self::getDebugType($data))
            );
        }

        return $data;
    }

    /**
     * Deserialize expecting a specific class instance
     *
     * @param string $payload
     * @param string $className
     * @param string $format
     * @return object
     */
    public static function toObject(string $payload, string $className, string $format = Format::PHP): object
    {
        $data = null;
        if ($format === Format::PHP) {
            $data = self::fromPhp($payload, [$className]);
        } elseif ($format === Format::LEGACY) {
            $data = self::fromLegacy($payload, [$className]);
        } else {
            throw new RuntimeException('Objects only supported in PHP/Legacy formats');
        }

        if (!$data instanceof $className) {
            throw new UnexpectedValueException(
                sprintf('Expected %s, got %s', $className, self::getDebugType($data))
            );
        }

        return $data;
    }

    /**
     * Safe deserialization with fallback
     *
     * @param string $payload
     * @param mixed $default
     * @param string $format
     * @param array $allowedClasses
     * @return mixed
     */
    public static function tryFrom(string $payload, $default = null, string $format = Format::AUTO, array $allowedClasses = [])
    {
        try {
            if ($format === Format::JSON) {
                return self::fromJson($payload);
            } elseif ($format === Format::PHP) {
                return self::fromPhp($payload, $allowedClasses);
            } elseif ($format === Format::LEGACY) {
                return self::fromLegacy($payload, $allowedClasses);
            } elseif ($format === Format::AUTO) {
                return self::from($payload, $allowedClasses);
            }

            return $default;
        } catch (\Throwable $e) {
            return $default;
        }
    }

    /**
     * Try JSON-only deserialization (for mixed-field migrations)
     *
     * @param string $payload
     * @return mixed|null
     */
    public static function jsonOnly(string $payload)
    {
        if ($payload === '' || !isset(ltrim($payload)[0])) {
            return null;
        }

        if (!self::isValidJson($payload)) {
            return null;
        }

        try {
            return json_decode($payload, true, self::JSON_DEPTH, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return null;
        }
    }

    /**
     * Deserialize expecting scalar or null
     *
     * @param string $payload
     * @param string $format
     * @return string|int|float|bool|null
     */
    public static function scalarsOnly(string $payload, string $format = Format::AUTO)
    {
        if ($format === Format::JSON) {
            $v = self::fromJson($payload);
        } elseif ($format === Format::PHP) {
            $v = self::fromPhp($payload);
        } elseif ($format === Format::LEGACY) {
            $v = self::fromLegacy($payload);
        } else {
            $v = self::from($payload);
        }

        if (!is_scalar($v) && $v !== null) {
            throw new UnexpectedValueException('Expected scalar or null');
        }

        return $v;
    }

    // ═══════════════════════════════════════════════════════════
    // Configuration
    // ═══════════════════════════════════════════════════════════

    /**
     * Set a logger to track legacy format usage (for migration tracking)
     *
     * @param callable|null $logger fn(string $file, int $line, string $preview): void
     * @return void
     */
    public static function setLegacyLogger(?callable $logger): void
    {
        self::$legacyLogger = $logger ? \Closure::fromCallable($logger) : null;
    }

    // ═══════════════════════════════════════════════════════════
    // Private Helpers
    // ═══════════════════════════════════════════════════════════

    /**
     * @param string $payload
     * @return void
     * @throws UnexpectedValueException
     * @throws RuntimeException
     */
    private static function validateInput(string $payload): void
    {
        if ($payload === '') {
            throw new UnexpectedValueException('Cannot deserialize empty string');
        }

        if (strlen($payload) > self::MAX_SIZE) {
            throw new RuntimeException(
                sprintf('Payload exceeds maximum size of %d bytes', self::MAX_SIZE)
            );
        }
    }

    /**
     * @param string $payload
     * @return void
     * @throws RuntimeException
     */
    private static function validateSize(string $payload): void
    {
        if (strlen($payload) > self::MAX_SIZE) {
            throw new RuntimeException(
                sprintf('Decoded payload exceeds %d bytes', self::MAX_SIZE)
            );
        }
    }

    /**
     * @param string $payload
     * @param bool $noObjects
     * @return void
     * @throws RuntimeException
     */
    private static function validateSecurity(string $payload, bool $noObjects): void
    {
        if ($noObjects && strpos($payload, "\0") !== false) {
            throw new RuntimeException('NUL bytes detected in scalar/array payload');
        }
    }

    /**
     * @param string $payload
     * @param array $allowedClasses
     * @return mixed
     * @throws UnexpectedValueException
     */
    private static function unserialize(string $payload, array $allowedClasses)
    {
        $options = ['allowed_classes' => $allowedClasses ? array_values($allowedClasses) : false];

        set_error_handler(
            static function ($severity, $message) {
                return is_string($message) && stripos($message, 'unserialize') !== false;
            },
            E_WARNING | E_NOTICE
        );

        try {
            $result = unserialize($payload, $options);
        } finally {
            restore_error_handler();
        }

        if ($result === false && $payload !== 'b:0;') {
            throw new UnexpectedValueException('Failed to unserialize payload');
        }

        return $result;
    }

    /**
     * @param string $payload
     * @param array $allowedClasses
     * @return mixed|null
     */
    private static function tryUnserialize(string $payload, array $allowedClasses)
    {
        try {
            $options = ['allowed_classes' => $allowedClasses ? array_values($allowedClasses) : false];

            set_error_handler(
                static function ($severity, $message) {
                    return is_string($message) && stripos($message, 'unserialize') !== false;
                },
                E_WARNING | E_NOTICE
            );

            try {
                $result = unserialize($payload, $options);
            } finally {
                restore_error_handler();
            }

            return ($result === false && $payload !== 'b:0;') ? null : $result;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * @param string $str
     * @return bool
     */
    private static function looksLikeSerialized(string $str): bool
    {
        if (strlen($str) < 4) {
            return false;
        }

        $type = $str[0];
        $validTypes = ['a', 'O', 's', 'i', 'b', 'd', 'N', 'r', 'R', 'C'];

        return in_array($type, $validTypes, true)
               && (bool) preg_match('/^[aOsibdNRCr]:\d+[:;{]/', $str);
    }

    /**
     * @param string $bin
     * @return bool
     */
    private static function isGzip(string $bin): bool
    {
        return isset($bin[0], $bin[1]) && $bin[0] === "\x1f" && $bin[1] === "\x8b";
    }

    /**
     * @param string $s
     * @return bool
     */
    private static function isLikelyBase64(string $s): bool
    {
        $len = strlen($s);
        return $len >= 8 && ($len % 4) === 0 && preg_match('/^[A-Za-z0-9+\/]+={0,2}$/', $s) === 1;
    }

    /**
     * @param string $s
     * @return bool
     */
    private static function isValidJson(string $s): bool
    {
        // Use json_validate if available (PHP 8.3+)
        if (function_exists('json_validate')) {
            return json_validate($s, self::JSON_DEPTH);
        }

        // Fallback for older PHP versions
        try {
            json_decode($s, true, self::JSON_DEPTH, JSON_THROW_ON_ERROR);
            return true;
        } catch (\JsonException $e) {
            return false;
        }
    }

    /**
     * @param string $payload
     * @return void
     */
    private static function logLegacy(string $payload): void
    {
        if (self::$legacyLogger === null) {
            return;
        }

        $preview = substr($payload, 0, 50) . (strlen($payload) > 50 ? '...' : '');
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        $caller = $trace[2] ?? ['file' => 'unknown', 'line' => 0];

        (self::$legacyLogger)(
            $caller['file'] ?? 'unknown',
            $caller['line'] ?? 0,
            $preview
        );
    }

    /**
     * PHP 7.4 compatible version of get_debug_type()
     *
     * @param mixed $value
     * @return string
     */
    private static function getDebugType($value): string
    {
        if (function_exists('get_debug_type')) {
            return get_debug_type($value);
        }

        // PHP 7.4 fallback
        return is_object($value) ? get_class($value) : gettype($value);
    }
}
