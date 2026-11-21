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

use Symfony\Component\Yaml\Yaml as VendorYaml;

/**
 * Yaml dump and parse methods
 *
 * YAML is a serialization format most useful when human readability
 * is a consideration. It can be useful for configuration files, as
 * well as import and export functions.
 *
 * This file is a front end for a separate YAML package present in the
 * vendor directory. The intent is to provide a consistent interface
 * no mater what underlying library is actually used.
 *
 * At present, this class expects the symfony/yaml package.
 *
 * @category  Xmf\Yaml
 * @package   Xmf
 * @author    Richard Griffith <richard@geekwright.com>
 * @copyright 2000-2026 XOOPS Project (https://xoops.org)
 * @license   GNU GPL 2.0 or later (https://www.gnu.org/licenses/gpl-2.0.html)
 * @link      https://xoops.org
 * @see       http://www.yaml.org/
 */
class Yaml
{
    private const SIZE_ERROR = 'YAML file exceeds maximum size of 2MB';

    /**
     * Dump an PHP array as a YAML string
     *
     * @param mixed   $var    Variable which will be dumped
     * @param integer $inline Nesting level where you switch to inline YAML
     * @param integer $indent Number of spaces to indent for nested nodes
     *
     * @return string|bool YAML string or false on error
     *
     * @throws \RuntimeException when the YAML library fails, see {@see self::logError()}
     */
    public static function dump($var, $inline = 4, $indent = 4)
    {
        try {
            $ret = VendorYaml::dump($var, $inline, $indent);
        } catch (\Throwable $e) {
            static::logError($e);
            $ret = false;
        }
        return $ret;
    }

    /**
     * Load a YAML string into a PHP array
     *
     * @param string $yamlString YAML dump string
     *
     * @return array|boolean PHP array or false on error
     *
     * @throws \RuntimeException when the YAML library fails, see {@see self::logError()}
     */
    public static function load($yamlString)
    {
        try {
            $ret = VendorYaml::parse($yamlString);
        } catch (\Throwable $e) {
            static::logError($e);
            $ret = false;
        }
        return $ret;
    }

    /**
     * Read a file containing YAML into a PHP array
     *
     * @param string $yamlFile filename of YAML file
     *
     * @return array|false PHP array or false on error
     *
     * @throws \RuntimeException when the YAML library fails, see {@see self::logError()}
     */
    public static function read($yamlFile)
    {
        if (!file_exists($yamlFile)) {
            return false;
        }
        $maxSize = 2 * 1024 * 1024; // 2 MB
        $fileSize = @filesize($yamlFile);
        if ($fileSize === false) {
            trigger_error('Unable to determine YAML file size', E_USER_WARNING);
            return false;
        }
        if ($fileSize > $maxSize) {
            trigger_error(self::SIZE_ERROR, E_USER_WARNING);
            return false;
        }
        if (!is_readable($yamlFile)) {
            trigger_error('Failed to read YAML file (not readable): ' . basename($yamlFile), E_USER_WARNING);
            return false;
        }
        try {
            // the size check above is metadata only; a growing file or a device can return more
            $yamlString = @file_get_contents($yamlFile, false, null, 0, $maxSize + 1);
            if ($yamlString === false) {
                trigger_error('Failed to read YAML file: ' . basename($yamlFile), E_USER_WARNING);
                return false;
            }
            if (strlen($yamlString) > $maxSize) {
                trigger_error(self::SIZE_ERROR, E_USER_WARNING);
                return false;
            }
            $ret = VendorYaml::parse($yamlString);
        } catch (\Throwable $e) {
            static::logError($e);
            $ret = false;
        }
        return $ret;
    }

    /**
     * Save a PHP array as a YAML file
     *
     * @param array   $var      variable which will be dumped
     * @param string  $yamlFile filename of YAML file
     * @param integer $inline   Nesting level where you switch to inline YAML
     * @param integer $indent   Number of spaces to indent for nested nodes
     *
     * @return integer|boolean number of bytes written, or false on error
     *
     * @throws \RuntimeException when the YAML library fails, see {@see self::logError()}
     */
    public static function save($var, $yamlFile, $inline = 4, $indent = 4)
    {
        try {
            $yamlString = VendorYaml::dump($var, $inline, $indent);
        } catch (\Throwable $e) {
            static::logError($e);
            return false;
        }
        return self::writeFile($yamlFile, $yamlString);
    }

    /**
     * Dump an PHP array as a YAML string with a php wrapper
     *
     * The wrap is a php __halt_compiler() guard followed by YAML section markers
     * '---' and '...'. The guard stops the PHP lexer entirely, preventing the
     * file contents from being parsed or revealed by serving the file directly
     * from a poorly configured server.
     *
     * @param mixed   $var    Variable which will be dumped
     * @param integer $inline Nesting level where you switch to inline YAML
     * @param integer $indent Number of spaces to indent for nested nodes
     *
     * @return string|boolean YAML string or false on error
     *
     * @throws \RuntimeException when the YAML library fails, see {@see self::logError()}
     */
    public static function dumpWrapped($var, $inline = 4, $indent = 4)
    {
        try {
            $yamlString = VendorYaml::dump($var, $inline, $indent);
            $ret = empty($yamlString) ? false : "<?php __halt_compiler(); ?>\n---\n" . $yamlString . "\n...\n";
        } catch (\Throwable $e) {
            static::logError($e);
            $ret = false;
        }
        return $ret;
    }

    /**
     * Load a YAML string with a php wrapper into a PHP array
     *
     * Supports both the current __halt_compiler() guard format and the legacy
     * PHP block comment format. Content between '---' and '...' markers is
     * extracted regardless of wrapper style.
     *
     * @param string $yamlString YAML dump string
     *
     * @return array|boolean PHP array or false on error
     *
     * @throws \RuntimeException when the YAML library fails, see {@see self::logError()}
     */
    public static function loadWrapped($yamlString)
    {
        try {
            $lines = preg_split('/\R/', $yamlString);
            if ($lines === false) {
                trigger_error('Failed to split wrapped YAML content', E_USER_WARNING);
                return false;
            }
            $count = count($lines);
            for ($index = $count; --$index > 0;) {
                if ('...' === $lines[$index]) {
                    array_splice($lines, $index);
                    break;
                }
            }
            $count = count($lines);
            for ($index = 0; ++$index < $count;) {
                if ('---' === $lines[$index]) {
                    array_splice($lines, 0, $index);
                    break;
                }
            }
            $unwrapped = implode("\n", $lines);
            $ret = VendorYaml::parse($unwrapped);
        } catch (\Throwable $e) {
            static::logError($e);
            $ret = false;
        }
        return $ret;
    }

    /**
     * Read a file containing YAML with a php wrapper into a PHP array
     *
     * Supports both the current __halt_compiler() guard format and the legacy
     * PHP block comment format. Content between '---' and '...' markers is
     * extracted regardless of wrapper style.
     *
     * @param string $yamlFile filename of YAML file
     *
     * @return array|false PHP array or false on error
     *
     * @throws \RuntimeException when the YAML library fails, see {@see self::logError()}
     */
    public static function readWrapped($yamlFile)
    {
        if (!file_exists($yamlFile)) {
            return false;
        }
        $maxSize = 2 * 1024 * 1024; // 2 MB
        $fileSize = @filesize($yamlFile);
        if ($fileSize === false) {
            trigger_error('Unable to determine YAML file size', E_USER_WARNING);
            return false;
        }
        if ($fileSize > $maxSize) {
            trigger_error(self::SIZE_ERROR, E_USER_WARNING);
            return false;
        }
        if (!is_readable($yamlFile)) {
            trigger_error('Failed to read YAML file (not readable): ' . basename($yamlFile), E_USER_WARNING);
            return false;
        }
        // the size check above is metadata only; a growing file or a device can return more
        $yamlString = @file_get_contents($yamlFile, false, null, 0, $maxSize + 1);
        if ($yamlString === false) {
            trigger_error('Failed to read YAML file: ' . basename($yamlFile), E_USER_WARNING);
            return false;
        }
        if (strlen($yamlString) > $maxSize) {
            trigger_error(self::SIZE_ERROR, E_USER_WARNING);
            return false;
        }
        try {
            $ret = static::loadWrapped($yamlString);
        } catch (\Throwable $e) {
            static::logError($e);
            $ret = false;
        }
        return $ret;
    }

    /**
     * Save a PHP array as a YAML file with a php wrapper
     *
     * The wrap is a php header that surrounds the yaml with section markers,
     * '---' and '...' along with php comment markers. The php wrapper keeps the
     * yaml file contents from being revealed by serving the file directly from
     * a poorly configured server.
     *
     * @param array   $var      variable which will be dumped
     * @param string  $yamlFile filename of YAML file
     * @param integer $inline   Nesting level where you switch to inline YAML
     * @param integer $indent   Number of spaces to indent for nested nodes
     *
     * @return integer|boolean number of bytes written, or false on error
     *
     * @throws \RuntimeException when the YAML library fails, see {@see self::logError()}
     */
    public static function saveWrapped($var, $yamlFile, $inline = 4, $indent = 4)
    {
        try {
            $yamlString = static::dumpWrapped($var, $inline, $indent);
        } catch (\Throwable $e) {
            static::logError($e);
            return false;
        }
        // Nothing to write: dumpWrapped() gave false, and writing that would empty
        // an existing file.
        if (!is_string($yamlString)) {
            return false;
        }
        return self::writeFile($yamlFile, $yamlString);
    }

    /**
     * Write YAML text to a file
     *
     * A failed write is a warning and false, as a failed read is in read(). The
     * warning names the file without its path. Whatever the file function throws
     * (a ValueError for a file name with a NUL byte, a TypeError for one that is
     * no string) is a failed write too; that warning names the class of the error.
     *
     * @param string $yamlFile   filename of YAML file
     * @param string $yamlString text to write
     *
     * @return integer|false number of bytes written, or false on error
     */
    private static function writeFile($yamlFile, $yamlString)
    {
        try {
            // PHP's own warning would carry the full path.
            $bytes = @file_put_contents($yamlFile, $yamlString);
        } catch (\Throwable $e) {
            trigger_error('Failed to write YAML file (' . get_class($e) . ')', E_USER_WARNING);
            return false;
        }
        if (false === $bytes) {
            trigger_error('Failed to write YAML file: ' . basename($yamlFile), E_USER_WARNING);
        }
        return $bytes;
    }

    /**
     * Report a failure of the YAML library that a method caught
     *
     * XOOPS 2.6 receives it as a core.exception event and the calling method
     * returns false. Everywhere else it is thrown. Before 1.3.4 it was raised with
     * trigger_error(E_USER_ERROR), which stopped the page in the XOOPS error
     * handler as an uncaught exception does now; PHP 8.4 deprecated that call.
     *
     * @param \Throwable $e the failure
     *
     * @return void
     *
     * @throws \RuntimeException the failure itself when it is one (the parse and dump
     *                           exceptions of the library are), otherwise wrapped in one
     */
    protected static function logError($e)
    {
        if (class_exists('Xoops')) {
            \Xoops::getInstance()->events()->triggerEvent('core.exception', $e);

            return;
        }

        // Passed on as it is, so a nested call (readWrapped() into loadWrapped())
        // does not wrap the same failure twice.
        throw $e instanceof \RuntimeException ? $e : new \RuntimeException($e->getMessage(), 0, $e);
    }
}
