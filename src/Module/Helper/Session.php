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
 * by other modules or system functions. All data is serialized, so
 * any arbitrary data (i.e. array) can be stored.
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
        if (self::holdsObject($value)) {
            // get() refuses objects, so nothing is stored that would read back as false
            throw new \RuntimeException('Session values may not contain objects or enums');
        }
        $prefixedName = $this->prefix($name);
        $_SESSION[$prefixedName] = serialize($value);
    }

    /**
     * Fetch a named session variable respecting our module prefix
     *
     * Only scalars, null and arrays of them are read back. A stored object or
     * enum, a value that is not the string set() stores, or data unserialize()
     * cannot read returns false. Trailing bytes after the value are ignored as
     * unserialize() does.
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

        // with allowed_classes off no __wakeup() or __unserialize() runs, so every notice
        // raised here comes from unserialize() itself (malformed data, a C: class body)
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

        // ponytail: allowed_classes=false still returns __PHP_Incomplete_Class for O:/C: and,
        // on PHP 8.1+, autoloads and returns an E: enum case; refusing any object in the
        // result covers both. A token walk before unserialize() would also stop the autoload.
        return self::holdsObject($value) ? false : $value;
    }

    /**
     * Whether a value is, or contains anywhere in nested arrays, an object (an enum case is one).
     * The walk is bounded: more than 32 nesting levels (a self-referencing R: payload) or more
     * than 100000 visited values (sibling r: references shared across levels multiply the
     * walk) count as an object, so the check fails closed instead of running away.
     *
     * @param mixed $value  value to inspect
     * @param int   $budget visits left, decremented across the whole walk
     * @param int   $depth  nesting level of $value
     *
     * @return bool
     */
    private static function holdsObject($value, &$budget = 100000, $depth = 0)
    {
        if (is_object($value) || $depth > 32 || --$budget < 0) {
            return true;
        }
        if (is_array($value)) {
            foreach ($value as $item) {
                if (self::holdsObject($item, $budget, $depth + 1)) {
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
