<?php

/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

namespace Xmf;

/**
 * Highlighter
 *
 * @category  Xmf\Highlighter
 * @package   Xmf
 * @author    Richard Griffith <richard@geekwright.com>
 * @copyright 2000-2026 XOOPS Project (https://xoops.org)
 * @license   GNU GPL 2.0 or later (https://www.gnu.org/licenses/gpl-2.0.html)
 * @link      https://xoops.org
 */
class Highlighter
{
    /**
     * mbstring encoding
     */
    public const ENCODING = 'UTF-8';

    /** distinct terms highlighted; each one rescans the whole body */
    private const MAX_TERMS = 32;

    /** elements whose content a browser does not parse as tags; plaintext never ends */
    private const RAW_TEXT_ELEMENTS = [
        'iframe', 'noembed', 'noframes', 'noscript', 'plaintext', 'script', 'style', 'textarea', 'title', 'xmp',
    ];

    /**
     * Apply highlight to words in body text
     *
     * Surround occurrences of words in body with pre in front and post
     * behind. Considers only occurrences of words outside of HTML tags.
     *
     * @param string|string[] $words words to highlight
     * @param string          $body  body of html text to highlight
     * @param string          $pre   string to begin a highlight
     * @param string          $post  string to end a highlight
     *
     * @return string highlighted body
     */
    public static function apply($words, $body, $pre = '<mark>', $post = '</mark>')
    {
        if (!is_array($words)) {
            $words = preg_replace('/[\s]+/', ' ', $words);
            $words = explode(' ', $words);
        }
        // each term rescans the body, wrappers included, and a term repeated in any case
        // wraps the same matches again, so skip repeats and bound the number of terms
        $seen = [];
        $unicodeTerms = [];
        $scans = 0;
        // text that is valid UTF-8 is matched with /iu, which treats simple case variants (ä, Ä) alike
        $validBody = preg_match('//u', $body . $pre . $post) === 1;
        foreach ($words as $word) {
            $word = (string) $word;
            // an empty term matches everywhere and never advances the scan
            if ($word === '') {
                continue;
            }
            // terms differing only in ASCII case match alike in every text segment
            $key = strtolower($word);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $unicode = self::matchesAsUtf8($word);
            $variant = $unicode && self::isCaseVariant($word, $unicodeTerms);
            if ($variant && $validBody) {
                continue;
            }
            if (++$scans > self::MAX_TERMS) {
                break;
            }
            // a variant may still match bytewise where the text is not valid UTF-8
            if ($variant) {
                $body = self::scan($word, (string) $body, (string) $pre, (string) $post, true);
                continue;
            }
            if ($unicode) {
                $unicodeTerms[] = $word;
            }
            $body = static::splitOnTag($word, $body, $pre, $post);
        }

        return $body;
    }

    /**
     * find needle in between html tags and add highlighting
     *
     * @param string $needle   string to find
     * @param string $haystack html text to find needle in
     * @param string $pre      insert before needle
     * @param string $post     insert after needle
     *
     * @return string highlighted haystack
     */
    protected static function splitOnTag($needle, $haystack, $pre, $post)
    {
        return self::scan((string) $needle, (string) $haystack, (string) $pre, (string) $post, false);
    }

    /**
     * Highlight needle in the text between markup
     *
     * @param bool $bytewiseOnly skip text segments that are valid UTF-8, where an earlier
     *                           case variant of the needle has wrapped every match already
     */
    private static function scan(string $needle, string $haystack, string $pre, string $post, bool $bytewiseOnly): string
    {
        if ($needle === '') {
            return $haystack;
        }
        $ret = '';
        $offset = 0;
        $length = strlen($haystack);
        $foreign = false;
        while ($offset < $length) {
            $found = preg_match('#<[/a-z!?]#i', $haystack, $match, PREG_OFFSET_CAPTURE, $offset);
            if ($found === false) {
                // a PCRE error: the rest may hold markup, so highlight nothing more
                return $ret . substr($haystack, $offset);
            }
            $start = $found === 1 ? $match[0][1] : $length;
            $text = substr($haystack, $offset, $start - $offset);
            $ret .= $bytewiseOnly && preg_match('//u', $text) === 1
                ? $text
                : self::highlightText($needle, $text, $pre, $post);
            $markup = $start < $length ? self::readMarkup($haystack, $start, $foreign) : '';
            $ret .= $markup;
            $offset = $start + strlen($markup);
        }

        return $ret;
    }

    /**
     * Whether a valid UTF-8 term matches an earlier one under /iu (simple case folding)
     *
     * @param string[] $terms earlier valid UTF-8 terms
     */
    private static function isCaseVariant(string $word, array $terms): bool
    {
        foreach ($terms as $term) {
            if (preg_match('/\A' . preg_quote($term, '/') . '\z/iu', $word) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * The markup starting at $start, read as a browser's HTML tokenizer reads it:
     * - a comment runs to "-->" or "--!>" ("<!-->" and "<!--->" are complete comments);
     * - "<!", "<?" and "</" not followed by a letter are bogus comments ending at the first ">";
     * - in a tag, a quote opens a value only right after "=", so a quote inside an unquoted
     *   value or an attribute name is literal, and a ">" inside a quoted value does not end it;
     * - a raw text element (script, style, textarea, title, ...) is markup up to its end tag,
     *   since nothing inside it is a tag or text a highlight may go into.
     * Where the tokenizer's state depends on what this reader does not track, the rest of
     * the haystack is returned, so nothing after it is highlighted:
     * - after an svg or math start tag, CDATA and raw text element names, which foreign
     *   content parses differently ("<svg><style>" holds tags, "<script />" closes itself);
     * - script text holding "<!--", whose escape states let "</script" not end it;
     * - noscript holding markup, which is parsed as tags when scripting is off.
     * An unclosed comment, quote, tag or raw text element runs to the end as well.
     *
     * @param string $haystack html text
     * @param int    $start    offset of a "<" followed by "/", "!", "?" or a letter
     * @param bool   $foreign  set once an svg or math start tag is read
     *
     * @return string
     */
    private static function readMarkup(string $haystack, int $start, bool &$foreign): string
    {
        $rest = static fn (): string => substr($haystack, $start);
        if (substr_compare($haystack, '<!--', $start, 4) === 0) {
            if (preg_match('/\G-?>/', $haystack, $abrupt, 0, $start + 4) === 1) {
                return substr($haystack, $start, 4 + \strlen($abrupt[0]));
            }
            if (preg_match('/--!?>/', $haystack, $close, PREG_OFFSET_CAPTURE, $start + 4) !== 1) {
                return $rest();
            }
            return substr($haystack, $start, $close[0][1] + \strlen($close[0][0]) - $start);
        }
        $isEndTag = $haystack[$start + 1] === '/';
        $nameStart = $start + ($isEndTag ? 2 : 1);
        if (preg_match('#\G[a-z][^\t\n\f\r />]*#i', $haystack, $name, 0, $nameStart) !== 1) {
            $end = strpos($haystack, '>', $start + 2);
            if ($end === false) {
                return $rest();
            }
            // a DOCTYPE ends at the first ">" too, even inside a quoted identifier
            return $foreign && substr_compare($haystack, '<![CDATA[', $start, 9) === 0
                ? $rest()
                : substr($haystack, $start, $end + 1 - $start);
        }
        $end = self::endOfTag($haystack, $nameStart + \strlen($name[0]));
        $element = strtolower($name[0]);
        if (!$isEndTag && ($element === 'svg' || $element === 'math')) {
            $foreign = true;
        }
        if ($isEndTag || !\in_array($element, self::RAW_TEXT_ELEMENTS, true)) {
            return substr($haystack, $start, $end - $start);
        }
        if ($foreign || $element === 'plaintext') {
            return $rest();
        }
        $found = preg_match('#</' . $element . '[\t\n\f\r />]#i', $haystack, $close, PREG_OFFSET_CAPTURE, $end);
        if ($found !== 1) {
            return $rest();
        }
        $content = substr($haystack, $end, $close[0][1] - $end);
        if (
            ($element === 'script' && str_contains($content, '<!--'))
            || ($element === 'noscript' && preg_match('#<[/a-z!?]#i', $content) === 1)
        ) {
            return $rest();
        }
        $closeEnd = self::endOfTag($haystack, $close[0][1] + 2 + \strlen($element));

        return substr($haystack, $start, $closeEnd - $start);
    }

    /**
     * The offset just past the ">" that ends a tag whose attributes begin at $pos,
     * or the length of the haystack when the tag is not closed
     *
     * @param string $haystack html text
     * @param int    $pos      offset after the tag name
     */
    private static function endOfTag(string $haystack, int $pos): int
    {
        $length = \strlen($haystack);
        $space = "\t\n\f\r ";
        while (($pos += strspn($haystack, $space . '/', $pos)) < $length) {
            if ($haystack[$pos] === '>') {
                return $pos + 1;
            }
            // an attribute name; its first character may be "=" or a quote
            $pos += 1 + strcspn($haystack, $space . '/>=', $pos + 1);
            $pos += strspn($haystack, $space, $pos);
            if ($pos >= $length || $haystack[$pos] !== '=') {
                continue;
            }
            $pos += 1 + strspn($haystack, $space, $pos + 1);
            if ($pos < $length && ($haystack[$pos] === '"' || $haystack[$pos] === "'")) {
                $quote = strpos($haystack, $haystack[$pos], $pos + 1);
                if ($quote === false) {
                    return $length;
                }
                $pos = $quote + 1;
            } else {
                // an unquoted value ends at a space or ">"; a quote in it is literal
                $pos += strcspn($haystack, $space . '>', $pos);
            }
        }

        return $length;
    }

    /**
     * Surround every case-insensitive occurrence of needle in plain text, in one pass
     *
     * @param string $needle string to find
     * @param string $text   text without markup
     * @param string $pre    insert before needle
     * @param string $post   insert after needle
     *
     * @return string
     */
    private static function highlightText(string $needle, string $text, string $pre, string $post): string
    {
        // the "u" modifier reads both as ENCODING, UTF-8; a needle that is not valid UTF-8,
        // or too long to compile (13,105 four-byte or 32,764 one-byte characters), would fail
        // with a warning, a text that is not valid returns null quietly
        // ponytail: a needle over 8 KB matches only ASCII letters in either case
        if (self::matchesAsUtf8($needle)) {
            $wrapped = preg_replace_callback(
                '/' . preg_quote($needle, '/') . '/iu',
                static fn (array $m): string => $pre . $m[0] . $post,
                $text
            );
            if ($wrapped !== null) {
                return $wrapped;
            }
        }
        // not valid UTF-8 or too long: match bytes, ASCII letters in either case
        $ret = '';
        $offset = 0;
        $length = strlen($needle);
        // lower-case once: stripos() lower-cases the whole text again on every call
        $lowerText = strtolower($text);
        $lowerNeedle = strtolower($needle);
        while (($pos = strpos($lowerText, $lowerNeedle, $offset)) !== false) {
            $ret .= substr($text, $offset, $pos - $offset) . $pre . substr($text, $pos, $length) . $post;
            $offset = $pos + $length;
        }

        return $ret . substr($text, $offset);
    }

    /**
     * Whether a needle can be matched by the "u" pattern: valid UTF-8 and short enough
     * to compile. Otherwise highlightText() matches bytes.
     */
    private static function matchesAsUtf8(string $needle): bool
    {
        return \strlen($needle) <= 8192 && preg_match('//u', $needle) === 1;
    }
}
