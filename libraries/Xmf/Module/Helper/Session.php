<?php

namespace Xmf\Module\Helper;

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
 * @version         $Id: Session.php 8065 2011-11-06 02:02:32Z beckmi $
 */

defined('XMF_EXEC') or die('Xmf was not detected');

class Session extends AbstractHelper
{
    /**
     * @var string
     */
    private $_prefix;

    /**
     * @var Xmf\Session
     */
    private $_session;

    /**
     * @return void
     */
    public function init()
    {
        $this->_prefix = $this->module->getVar('dirname') . '_';
        $this->_session = Xmf\Session::getInstance();
    }

    /**
     * @param  string $value
     * @return string
     */
    private function _prefix($value)
    {
        return $this->_prefix . $value;
    }

    /**
     * Sets a session variable
     * @param  string $name  name of variable
     * @param  mixed  $value value of variable
     * @return void
     * @access public
     */
    public function set($name, $value)
    {
        $this->_session->set($this->_prefix($name), $value);
    }

    /**
     * Fetches a session variable
     * @param  string $name name of variable
     * @return mixed  $value value of variable
     * @access public
     */
    public function get($name)
    {
        return $this->_session->get($this->_prefix($name));
    }

    /**
     * Deletes a session variable
     * @param  string $name name of variable
     * @return void
     * @access public
     */
    public function del($name)
    {
        $this->_session->del($this->_prefix($name));
    }

    /**
     * @return void
     */
    public function destroy()
    {
        foreach ($_SESSION as $key => $value) {
            if (false !== strpos($key, $this->_prefix)) {
                $this->_session->del($key);
            }
        }
    }
}
