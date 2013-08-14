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
 * @copyright 2011-2013 The XOOPS Project http://sourceforge.net/projects/xoops/
 * @license   http://www.fsf.org/copyleft/gpl.html GNU public license
 * @version   Release: 1.0
 * @link      http://xoops.org
 * @since     1.0
 */
class Config
{

    private static $config = array();
    private static $compatmode = false;

   /**
    * Get a configuration value.
    *
    * @param   string $name     Name of a configuration option
    * @param   mixed  $default  A default value returned used if the
    *                           requested named option is not set.
    *
    * @return  mixed  The value of the directive, or null if not set.
    *
    * @since      1.0
    */
    public static function get($name, $default = null)
    {
        if (array_key_exists($name,self::$config)) {
            return self::$config[$name];
        } else {
            return $default;
        }
    }

   /**
    * Set a configuration value.
    *
    * @param  string $name   Name of the configuration option
    * @param  mixed  $value  Value of the configuration option
    *
    * @since      1.0
    */
    public static function set($name, $value)
    {
        self::$config[$name] = $value;
        if(is_scalar($value) & self::$compatmode) define($name, $value);  // Transitional hack

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
        $return = self::$config;

        return $return;
    }

   /**
    * Set sompatibility mode
    *
    * In compatibility mode, configuration options set defines
    * for old mojavi code that does not use the Xmf\Mvc\Config
    *
    * @param  boolean  $value
    *
    * @since      1.0
    */
    public static function setCompatmode($value)
    {
        self::$compatmode = $value;
    }

}
