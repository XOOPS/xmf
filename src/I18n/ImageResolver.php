<?php
declare(strict_types=1);

namespace Xmf\I18n;

final class ImageResolver
{
    /** @var array<string,string> */
    private static array $cache = [];
    private const MAX_CACHE_SIZE = 200;

    /**
     * Resolve an image path with language/direction fallbacks.
     *
     * Order (base = images/arrow.png, lang = pt-BR, dir = rtl):
     *   images/arrow.pt-br.png
     *   images/arrow.pt.png
     *   images/arrow.rtl.png
     *   images/arrow.ltr.png
     *   images/arrow.png
     */
    public static function resolve(string $basePath, ?string $lang = null, ?string $dir = null): string
    {
        if ($basePath === '') {
            return '';
        }

        // Absolute URL? Return as-is
        if (\preg_match('#^(https?:)?//#i', $basePath) === 1) {
            return $basePath;
        }

        $lang = $lang ?? (defined('_LANGCODE') ? (string) _LANGCODE : 'en');
        $dir  = $dir  ?? Direction::dir($lang);
        if ($dir !== Direction::LTR && $dir !== Direction::RTL) {
            $dir = Direction::dir($lang);
        }

        $cacheKey = \md5($basePath . "\0" . \strtolower($lang) . "\0" . $dir);
        if (isset(self::$cache[$cacheKey])) {
            return self::$cache[$cacheKey];
        }

        $parts     = \pathinfo($basePath);
        $dirname   = ($parts['dirname'] ?? '') === '.' ? '' : ($parts['dirname'] ?? '');
        $filename  = $parts['filename']  ?? '';
        $extension = $parts['extension'] ?? '';
        if ($filename === '' || $extension === '') {
            return $basePath; // malformed path
        }

        $candidates = [];
        foreach (self::expandLang($lang) as $l) {
            $candidates[] = ($dirname !== '' ? "{$dirname}/" : '') . "{$filename}.{$l}.{$extension}";
        }
        $candidates[] = ($dirname !== '' ? "{$dirname}/" : '') . "{$filename}.{$dir}.{$extension}";
        $candidates[] = $basePath;

        $root = defined('XOOPS_ROOT_PATH') ? rtrim((string) XOOPS_ROOT_PATH, '/') : '';

        $result = $basePath;
        foreach ($candidates as $rel) {
            $full = ($root !== '' ? $root . '/' : '') . ltrim($rel, '/');
            if (\is_file($full)) {
                $result = $rel;
                break;
            }
        }

        self::remember($cacheKey, $result);
        return $result;
    }

    /** @return string[] e.g., 'pt-BR' → ['pt-br','pt'] */
    private static function expandLang(string $lang): array
    {
        $lang = \strtolower(\str_replace('_', '-', \trim($lang)));
        if ($lang === '') return [];
        $parts = \explode('-', $lang, 2);
        return isset($parts[1]) ? [$lang, $parts[0]] : [$lang];
    }

    private static function remember(string $key, string $value): void
    {
        if (\count(self::$cache) >= self::MAX_CACHE_SIZE) {
            \array_shift(self::$cache);
        }
        self::$cache[$key] = $value;
    }

    /** testing hook */
    public static function clearCache(): void
    {
        self::$cache = [];
    }
}
