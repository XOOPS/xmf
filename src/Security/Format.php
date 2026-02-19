<?php declare(strict_types=1);

namespace Xmf\Security;

/**
 * Format types for serialization (PHP 7.4+ compatible)
 */
final class Format
{
    public const JSON = 'json';
    public const PHP = 'php';
    public const LEGACY = 'legacy';  // base64-encoded PHP serialize
    public const AUTO = 'auto';      // auto-detect format

    private function __construct() {} // Prevent instantiation
}
