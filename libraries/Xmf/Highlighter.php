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
 * @category  Xmf\Module\Highlighter
 * @package   Xmf
 * @author    Richard Griffith <richard@geekwright.com>
 * @copyright 2011-2013 The XOOPS Project http://sourceforge.net/projects/xoops/
 * @license   http://www.fsf.org/copyleft/gpl.html GNU public license
 * @version   Release: 1.0
 * @link      http://xoops.org
 * @since     1.0
 */
class Highlighter
{
    /**
     * @var string
     */
    public static $highlightArg = '';

    /**
     * Apply highlight to words in body text
     *
     * Surround occurances of words in body with pre in front and post
     * behing. Considers only occurances of words outside of HTML tags.
     *
     * @param mixed  $words words to highlight
     * @param string $body  body of html text to highlight
     * @param string $pre   string to begin a highlight
     * @param string $post  string to end a highlight
     *
     * @return string highlighted body
     */
    public static function apply($words, $body, $pre='<strong>', $post='</strong>')
    {
        if (!is_array($words)) {
            $words=str_replace('  ', ' ', $words);
            $words=explode(' ', $words);
        }
        foreach ($words as $word) {
            $body=Highlighter::_splitOnTag($word, $body, $pre, $post);
        }

        return $body;
    }

    /**
     * add highlighting
     *
     * @param array $capture callback argument from preg_replace_callback
     *
     * @return void
     */
    private static function _addHighlightCallback($capture)
    {
        $haystack=$capture[1];
        $p1=stripos($haystack, self::$highlightArg['needle']);
        $l1=strlen(self::$highlightArg['needle']);
        $ret='';
        while ($p1!==false) {
            $ret .= substr($haystack, 0, $p1) . self::$highlightArg['pre']
                . substr($haystack, $p1, $l1) . self::$highlightArg['post'];
            $haystack=substr($haystack, $p1+$l1);
            $p1=stripos($haystack, self::$highlightArg['needle']);
        }
        $ret.=$haystack.$capture[2];

        return $ret;
    }

    /**
     * find needle in between html tags
     *
     * @param string $needle   string to find
     * @param string $haystack html text to find needle in
     * @param string $pre      insert before needle
     * @param string $post     insert after needle
     *
     * @return void
     */
    private static function _splitOnTag($needle, $haystack, $pre, $post)
    {
        self::$highlightArg = compact('needle', 'pre', 'post');

        return preg_replace_callback(
            '#((?:(?!<[/a-z]).)*)([^>]*>|$)#si',
            '\Xmf\Highlighter::_addHighlightCallback',
            $haystack
        );
    }

}
