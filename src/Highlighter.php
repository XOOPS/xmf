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
 * @copyright 2011-2018 XOOPS Project (https://xoops.org)
 * @license   GNU GPL 2.0 or later (https://www.gnu.org/licenses/gpl-2.0.html)
 * @link      https://xoops.org
 */
class Highlighter
{
    /**
     * mbstring encoding
     */
    const ENCODING = 'UTF-8';

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
        foreach ($words as $word) {
            // an empty term matches everywhere and never advances the scan
            if ($word === '') {
                continue;
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
        $needle = (string) $needle;
        $haystack = (string) $haystack;
        if ($needle === '') {
            return $haystack;
        }
        $ret = '';
        $offset = 0;
        $length = strlen($haystack);
        while ($offset < $length) {
            $found = preg_match('#<[/a-z!?]#i', $haystack, $match, PREG_OFFSET_CAPTURE, $offset);
            if ($found === false) {
                // a PCRE error: the rest may hold markup, so highlight nothing more
                return $ret . substr($haystack, $offset);
            }
            $start = $found === 1 ? $match[0][1] : $length;
            $ret .= self::highlightText($needle, substr($haystack, $offset, $start - $offset), $pre, $post);
            $markup = $start < $length ? self::readMarkup($haystack, $start) : '';
            $ret .= $markup;
            $offset = $start + strlen($markup);
        }

        return $ret;
    }

    /**
     * The markup starting at $start: a comment runs to "-->", "<?" is a comment a browser
     * ends at the first ">", and a ">" inside a quoted attribute value does not end a tag.
     * An unclosed comment or quote runs to the end, so nothing after it is highlighted.
     *
     * @param string $haystack html text
     * @param int    $start    offset of a "<" followed by "/", "!", "?" or a letter
     *
     * @return string
     */
    private static function readMarkup(string $haystack, int $start): string
    {
        $isComment = substr_compare($haystack, '<!--', $start, 4) === 0;
        if ($isComment || $haystack[$start + 1] === '?') {
            $close = $isComment ? '-->' : '>';
            $end = strpos($haystack, $close, $start + ($isComment ? 4 : 2));
            return $end === false ? substr($haystack, $start) : substr($haystack, $start, $end + \strlen($close) - $start);
        }
        $pos = $start + 2;
        while (($pos += strcspn($haystack, '>"\'', $pos)) < \strlen($haystack)) {
            if ($haystack[$pos] === '>') {
                return substr($haystack, $start, $pos + 1 - $start);
            }
            $quote = strpos($haystack, $haystack[$pos], $pos + 1);
            if ($quote === false) {
                break;
            }
            $pos = $quote + 1;
        }

        return substr($haystack, $start);
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
        if (\strlen($needle) <= 8192 && preg_match('//u', $needle) === 1) {
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
}
