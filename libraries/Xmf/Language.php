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
 * Language
 *
 * TODO fix
 *
 * @category  Xmf\Module\Language
 * @package   Xmf
 * @author    trabis <lusopoemas@gmail.com>
 * @copyright 2011-2013 The XOOPS Project http://sourceforge.net/projects/xoops/
 * @license   http://www.fsf.org/copyleft/gpl.html GNU public license
 * @version   Release: 1.0
 * @link      http://xoops.org
 * @since     1.0
 */
class Language
{
    /**
     * Returns a translated string
     *
     * @static
     * @param  string       $string
     * @param  string       $default
     * @return mixed|string
     */
    public static function _($string, $default = null)
    {
        if (defined(strtoupper($string))) {
            return constant(strtoupper($string));
        } else {
            return self::translate($string, $default);
        }
    }

    /**
     * @static
     * @param  string $string
     * @param  string $default
     * @return string
     */
    public static function translate($string, $default = null)
    {
        if (isset($default)) {
            $string = '';
        }

        return $string;
    }

    /**
     * @static
     * @param  string $name
     * @param  string $domain
     * @param  string $language
     * @return bool
     */
    public static function load($name, $domain = '', $language = null)
    {
        $language = empty($language) ? $GLOBALS['xoopsConfig']['language'] : $language;
        $path = XOOPS_ROOT_PATH . '/' . ((empty($domain) || 'global' == $domain) ? ''
            : "modules/{$domain}/") . 'language';
        if (!$ret = Loader::loadFile("{$path}/{$language}/{$name}.php")) {
            $ret = Loader::loadFile("{$path}/english/{$name}.php");
        }

        return $ret;
    }
}
