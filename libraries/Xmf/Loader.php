<?php
/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

/**
 * @copyright       The XOOPS Project http://sourceforge.net/projects/xoops/
 * @license         http://www.fsf.org/copyleft/gpl.html GNU public license
 * @package         Xmf
 * @since           0.1
 * @author          trabis <lusopoemas@gmail.com>
 * @version         $Id: Loader.php 8065 2011-11-06 02:02:32Z beckmi $
 */

class Xmf_Loader
{
    /**
     * @static
     * @param string $file
     * @param bool $once
     * @return bool
     */
    public static function loadFile($file, $once = true)
    {
        self::_securityCheck($file);
        if (file_exists($file)) {
            if ($once) {
                include_once $file;
            } else {
                include $file;
            }
            return true;
        }
        return false;
    }

    /**
     * @static
     * @param string $class
     * @return bool
     */
    public static function loadClass($class)
    {
        if (class_exists($class, false) || interface_exists($class, false)) {
            return true;
        }

        $file = str_replace('_', DIRECTORY_SEPARATOR, $class) . '.php';
        if (!self::loadFile(dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . $file)) {
            return false;
        }

        if (!class_exists($class, false) && !interface_exists($class, false)) {
            trigger_error("File \"$file\" does not exist or class \"$class\" was not found in the file", E_USER_WARNING);
            return false;
        }
        return true;
    }

    /**
     * Ensure that filename does not contain exploits
     *
     * @param  string $filename
     * @return void
     * @throws Zend_Exception
     */
    protected static function _securityCheck($filename)
    {
        /**
         * Security check
         */
        if (preg_match('/[^a-z0-9\\/\\\\_.:-]/i', $filename)) {
            exit('Security check: Illegal character in filename');
        }
    }
}