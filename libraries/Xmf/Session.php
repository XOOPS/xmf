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
 * @author          trabis <lusopoemas@gmail.com>
 * @author          Harry Fuecks (PHP Anthology Volume II)
 * @version         $Id: Session.php 8065 2011-11-06 02:02:32Z beckmi $
 */

defined('XMF_EXEC') or die('Xmf was not detected');

class Xmf_Session
{
    /**
     * Session constructor<br />
     * Starts the session with session_start()
     * <b>Note:</b> that if the session has already started,
     * session_start() does nothing
     * @access public
     */
    private function __construct()
    {
        @session_start();
    }

    /**
     * Sets a session variable
     * @param string $name name of variable
     * @param mixed $value value of variable
     * @return void
     * @access public
     */
    public function set($name, $value)
    {
        $_SESSION[$name] = $value;
    }

    /**
     * Fetches a session variable
     * @param string $name name of variable
     * @return mixed $value value of session variable
     * @access public
     */
    public function get($name)
    {
        if (isset($_SESSION[$name])) {
            return $_SESSION[$name];
        } else {
            return false;
        }
    }

    /**
     * Deletes a session variable
     * @param string $name name of variable
     * @return void
     * @access public
     */
    public function del($name)
    {
        unset($_SESSION[$name]);
    }


    /**
     * Destroys the whole session
     * @return void
     * @access public
     */
    public function destroy()
    {
        $_SESSION = array();
        session_destroy();
    }

    /**
     * @static
     * @return Xmf_Session
     */
    static public function getInstance()
    {
        static $_sess;
        if (!isset($_sess)) {
            $class = __CLASS__;
            $_sess = new $class();
        }
        return $_sess;
    }
}