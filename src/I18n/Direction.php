<?php
declare(strict_types=1);

namespace Xmf\I18n;

final class Direction
{
    public const LTR  = 'ltr';
    public const RTL  = 'rtl';
    public const AUTO = 'auto';
    private static ?string $cachedDir            = null;
    private static array   $cacheByLocale        = [];
    private static bool    $rtlDeprecationWarned = false;
    private const MAX_LOCALE_CACHE = 50;
    private const RTL_LANGS        = [
        'ar',  // Arabic
        'arc', // Aramaic
        'bcc', // Southern Balochi
        'bqi', // Bakhtiari
        'ckb', // Central Kurdish
        'dv',  // Dhivehi
        'fa',  // Persian
        'glk', // Gilaki
        'he',  // Hebrew
        'iw',  // Hebrew (old code)
        'khw', // Khowar
        'ks',  // Kashmiri
        'ku',  // Kurdish
        'mzn', // Mazanderani
        'pnb', // Western Punjabi
        'ps',  // Pashto
        'sd',  // Sindhi
        'ug',  // Uyghur
        'ur',  // Urdu
        'yi',  // Yiddish
    ];
    private const RTL_SCRIPTS      = ['Arab', 'Hebr', 'Thaa', 'Syrc', 'Nkoo', 'Adlm', 'Mand', 'Samr'];

    /**
     * Get text direction for a locale.
     *
     * @param string|null $locale Locale code, or null for global locale
     * @return string 'ltr' or 'rtl'
     */
    public static function dir(?string $locale = null): string
    {
        $isGlobal = ($locale === null);

        if ($isGlobal && self::$cachedDir !== null) {
            return self::$cachedDir;
        }

        $resolved = $locale ?? (defined('_LANGCODE') ? (string)_LANGCODE : 'en');

        if (!$isGlobal && isset(self::$cacheByLocale[$resolved])) {
            return self::$cacheByLocale[$resolved];
        }

        $result = null;

        // Priority 1: Explicit _TEXT_DIRECTION (normalized for robustness)
        if ($isGlobal && defined('_TEXT_DIRECTION')) {
            $decl = strtolower(trim((string)_TEXT_DIRECTION));
            if (in_array($decl, [self::RTL, self::LTR], true)) {
                $result = $decl;
            } else {
                trigger_error(
                    'Constant _TEXT_DIRECTION has invalid value "' . _TEXT_DIRECTION . '". Expected \'ltr\' or \'rtl\'.',
                    E_USER_WARNING
                );
                // Fall through to next priority
            }
        }

        // Priority 2: Legacy _RTL constant
        if ($result === null && $isGlobal && defined('_RTL')) {
            if (!self::$rtlDeprecationWarned) {
                trigger_error(
                    'Constant _RTL is deprecated. Define _TEXT_DIRECTION as \'ltr\' or \'rtl\' instead.',
                    E_USER_DEPRECATED
                );
                self::$rtlDeprecationWarned = true;
            }
            $result = _RTL ? self::RTL : self::LTR;
        }

        // Priority 3: Auto-detect from locale
        if ($result === null) {
            $result = self::detect($resolved);
        }

        // Cache
        if ($isGlobal) {
            self::$cachedDir = $result;
        } else {
            if (count(self::$cacheByLocale) >= self::MAX_LOCALE_CACHE) {
                array_shift(self::$cacheByLocale);
            }
            self::$cacheByLocale[$resolved] = $result;
        }

        return $result;
    }

    /**
     * Check if a locale uses right-to-left text direction.
     *
     * @param string|null $locale Locale code, or null for global locale
     * @return bool True if RTL, false if LTR
     */
    public static function isRtl(?string $locale = null): bool
    {
        return self::dir($locale) === self::RTL;
    }

    /**
     * Core detection logic.
     *
     * @param string $locale Resolved locale code (never null)
     * @return string 'ltr' or 'rtl'
     */
    private static function detect(string $locale): string
    {
        $locale = trim($locale);
        if ($locale === '') {
            return self::LTR;
        }

        $norm    = str_replace('_', '-', strtolower($locale));
        $primary = explode('-', $norm, 2)[0];

        if (in_array($primary, self::RTL_LANGS, true)) {
            return self::RTL;
        }

        if (extension_loaded('intl')) {
            try {
                $script = \Locale::getScript($norm) ?: '';
                if (in_array($script, self::RTL_SCRIPTS, true)) {
                    return self::RTL;
                }
            } catch (\Throwable $e) {
                $debug = (defined('XOOPS_DEBUG_MODE') && XOOPS_DEBUG_MODE)
                         || (defined('XOOPS_DEBUG') && XOOPS_DEBUG);
                if ($debug) {
                    error_log('Direction: ICU script detection failed for locale "' . $locale . '": ' . $e->getMessage());
                }
            }
        }

        return self::LTR;
    }

    /**
     * Clear cached direction (useful for testing or runtime locale changes).
     *
     * @return void
     */
    public static function clearCache(): void
    {
        self::$cachedDir            = null;
        self::$cacheByLocale        = [];
        self::$rtlDeprecationWarned = false;  // Reset for tests
    }
}
