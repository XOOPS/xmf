<?php
/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

namespace Xmf\Mvc;

/**
 * Config provides a runtime registry for configuration options.
 *
 * Inspired by David Zülke's work in Agavi.
 *
 * @category  Xmf\Mvc\Config
 * @package   Xmf
 * @author    Richard Griffith <richard@geekwright.com>
 * @copyright 2013 The XOOPS Project http://sourceforge.net/projects/xoops/
 * @license   GNU GPL 2 or later (http://www.gnu.org/licenses/old-licenses/gpl-2.0.html)
 * @version   Release: 1.0
 * @link      http://xoops.org
 * @since     1.0
 */
class Config
{

    private static $_config = array();
    private static $_compatmode = false;

    /**
     * Get a configuration value.
     *
     * @param string $name    Name of a configuration option
     * @param mixed  $default A default value returned used if the
     *                        requested named option is not set.
     *
     * @return  mixed  The value of the directive, or null if not set.
     *
     * @since      1.0
     */
    public static function get($name, $default = null)
    {
        if (array_key_exists($name, self::$_config)) {
            return self::$_config[$name];
        } else {
            return $default;
        }
    }

    /**
     * Set a configuration value.
     *
     * @param string $name  Name of the configuration option
     * @param mixed  $value Value of the configuration option
     *
     * @return void
     */
    public static function set($name, $value)
    {
        self::$_config[$name] = $value;
        if (is_scalar($value) & self::$_compatmode) {
            define($name, $value);  // Transitional hack
        }
    }

    /**
     * Get a list of configuration values.
     *
     * @return  array  An array of confguration values
     *
     * @since      1.0
     */
    public static function getConfigs()
    {
        $return = self::$_config;

        return $return;
    }

    /**
     * Set sompatibility mode
     *
     * In compatibility mode, configuration options set defines
     * for old mojavi code that does not use the Xmf\Mvc\Config
     *
     * @param boolean $value true to enable compatibility mode
     *
     * @return void
     */
    public static function setCompatmode($value)
    {
        self::$_compatmode = $value;
    }
}
