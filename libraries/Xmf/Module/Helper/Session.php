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
 * prefixed with the module name to separate them from variables set
 * by other modules or system functions.
 *
 * @category  Xmf\Module\Helper\Session
 * @package   Xmf
 * @author    trabis <lusopoemas@gmail.com>
 * @author    Richard Griffith <richard@geekwright.com>
 * @copyright 2011-2013 The XOOPS Project http://sourceforge.net/projects/xoops/
 * @license   http://www.fsf.org/copyleft/gpl.html GNU public license
 * @version   Release: 1.0
 * @since     1.0
 */
class Session extends AbstractHelper
{
    /**
     * @var string
     */
    private $_prefix;

    /**
     * Initialize parent::__constuct calls this after verifying module object.
     *
     * @return void
     */
    public function init()
    {
        $this->_prefix = $this->module->getVar('dirname') . '_';
    }

    /**
     * Add our module prefix to a name
     *
     * @param string $name name to prefix
     *
     * @return string module prefixed name
     */
    private function _prefix($name)
    {
        return $this->_prefix . $name;
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
        $prefixedName = $this->_prefix($name);
        $_SESSION[$prefixedName] = $value;
    }

    /**
     * Fetch a named session variable respecting our module prefix
     *
     * @param string $name name of variable
     *
     * @return mixed  $value value of session variable or false if not set
     */
    public function get($name)
    {
        $prefixedName = $this->_prefix($name);
        if (isset($_SESSION[$prefixedName])) {
            return $_SESSION[$prefixedName];
        } else {
            return false;
        }
    }

    /**
     * Deletes a names session variable respecting our module prefix
     *
     * @param string $name name of variable
     *
     * @return void
     */
    public function del($name)
    {
        $prefixedName = $this->_prefix($name);
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
            if (0 == substr_compare($key, $this->_prefix, strlen($this->_prefix))) {
                $_SESSION[$key] = null;
                unset($_SESSION[$key]);
            }
        }
    }
}
