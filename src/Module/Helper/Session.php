<?php
/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

namespace Xmf\Module\Helper;

/**
 * Manage session variables for a module. Session variable will be
 * prefixed with the module name to separate it from variables set
 * by other modules or system functions. Values are serialized: scalars,
 * null and arrays of them (nested at most 32 levels, at most 100000 values
 * in all) can be stored; objects and enums cannot.
 *
 * @category  Xmf\Module\Helper\Session
 * @package   Xmf
 * @author    trabis <lusopoemas@gmail.com>
 * @author    Richard Griffith <richard@geekwright.com>
 * @copyright 2011-2018 XOOPS Project (https://xoops.org)
 * @license   GNU GPL 2.0 or later (https://www.gnu.org/licenses/gpl-2.0.html)
 * @link      https://xoops.org
 */
class Session extends AbstractHelper
{
    /** @var int nesting levels beyond which an array is refused */
    const MAX_DEPTH = 32;

    /** @var int values (arrays and their items) beyond which a value is refused */
    const MAX_VALUES = 100000;

    /**
     * @var string
     */
    protected $prefix;

    /**
     * Initialize parent::__construct calls this after verifying module object.
     *
     * @return void
     */
    public function init()
    {
        $this->prefix = $this->module->getVar('dirname') . '_';
    }

    /**
     * Add our module prefix to a name
     *
     * @param string $name name to prefix
     *
     * @return string module prefixed name
     */
    protected function prefix($name)
    {
        $prefixedName = $this->prefix . $name;

        return $prefixedName;
    }

    /**
     * Sets a named session variable respecting our module prefix
     *
     * @param string $name  name of variable
     * @param mixed  $value value of variable
     *
     * @return void
     */
    public function set($name, $value)
    {
        if (self::isRefused($value)) {
            // the same limits get() applies, so nothing is stored that would read back as false
            throw new \RuntimeException(
                'Session values must be scalars, null or arrays of them, nested at most '
                . self::MAX_DEPTH . ' levels and holding at most ' . self::MAX_VALUES
                . ' values; objects and enums are not allowed'
            );
        }
        $prefixedName = $this->prefix($name);
        $_SESSION[$prefixedName] = serialize($value);
    }

    /**
     * Fetch a named session variable respecting our module prefix
     *
     * Only scalars, null and arrays of them are read back. A stored object or
     * enum, a value that is not the string set() stores, data unserialize()
     * cannot read, or an array over the limits in isRefused() returns false.
     * Trailing bytes after the value are ignored as unserialize() does. An
     * entry holding null counts as not set, as del() leaves it.
     *
     * @param string $name    name of variable
     * @param mixed  $default default value to return if config $name is not set
     *
     * @return mixed  $value value of session variable, $default if not set, or false if unreadable
     */
    public function get($name, $default = false)
    {
        $prefixedName = $this->prefix($name);
        if (!isset($_SESSION[$prefixedName])) {
            return $default;
        }
        $stored = $_SESSION[$prefixedName];
        // set() stores a string; anything else was written around it and is not read
        if (!is_string($stored)) {
            return false;
        }
        // allowed_classes=false leaves O: and C: as __PHP_Incomplete_Class but on PHP 8.1+
        // still autoloads an E: enum class and returns its case, so an enum token at a
        // value position (after ; or { or at the start) is refused before unserialize()
        // runs. ponytail: this also refuses a string whose content holds such text, where
        // master's Serializer walks the grammar instead; the result is only a false here.
        // A PCRE error (false) refuses too, so the check never fails open.
        if (0 !== preg_match('/(?:^|[;{])E:\d+:"/', $stored)) {
            return false;
        }

        // nothing but unserialize() itself runs here (no class is loaded or woken up),
        // so every notice is its own: malformed data, a C: class body
        set_error_handler(
            static function () {
                return true;
            },
            E_WARNING | E_NOTICE
        );
        try {
            $value = unserialize($stored, ['allowed_classes' => false]);
        } finally {
            restore_error_handler();
        }

        return self::isRefused($value) ? false : $value;
    }

    /**
     * Whether a value is refused: it is, or contains anywhere in nested arrays, an object
     * (an enum case is one), or its walk passes the limits. Those bound a self-referencing
     * R: payload (nesting) and sibling r: references shared across levels (visits), so the
     * check fails closed instead of running away.
     *
     * @param mixed $value  value to inspect
     * @param int   $budget visits left, decremented across the whole walk
     * @param int   $depth  nesting level of $value
     *
     * @return bool
     */
    private static function isRefused($value, &$budget = self::MAX_VALUES, $depth = 0)
    {
        if (is_object($value) || $depth > self::MAX_DEPTH || --$budget < 0) {
            return true;
        }
        if (is_array($value)) {
            foreach ($value as $item) {
                if (self::isRefused($item, $budget, $depth + 1)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Deletes a named session variable respecting our module prefix
     *
     * @param string $name name of variable
     *
     * @return void
     */
    public function del($name)
    {
        $prefixedName = $this->prefix($name);
        $_SESSION[$prefixedName] = null;
        unset($_SESSION[$prefixedName]);
    }

    /**
     * Delete all session variable starting with our module prefix
     *
     * @return void
     */
    public function destroy()
    {
        foreach ($_SESSION as $key => $value) {
            if (0 == substr_compare($key, $this->prefix, 0, strlen($this->prefix))) {
                $_SESSION[$key] = null;
                unset($_SESSION[$key]);
            }
        }
    }
}
