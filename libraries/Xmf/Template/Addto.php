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
 * @version         $Id: Addto.php 8065 2011-11-06 02:02:32Z beckmi $
 */

defined('XMF_EXEC') or die('Xmf was not detected');

class Xmf_Template_Addto extends Xmf_Template_Abstract
{
    /**
     * @var int
     */
    private $_layout;

    /**
     * @var int
     */
    private $_method;

    /**
     * @return void
     */
    protected function init()
    {
        $this->setTemplate(XMF_ROOT_PATH . '/templates/xmf_addto.html');
        $this->_layout = 0;
        $this->_method = 1;
    }

    /**
     * @param int $value
     * @return void
     */
    public function setLayout($value)
    {
        $layout = intval($value);
        if ($layout < 0 || $layout > 3) {
            $layout = 0;
        }
        $this->_layout = $layout;

    }

    /**
     * @param int $value
     * @return void
     */
    public function setMethod($value)
    {
        $method = intval($value);
        if ($method < 0 || $method > 1) {
            $method = 1;
        }
        $this->_method = $method;
    }

    /**
     * @return void
     */
    protected function render()
    {
        if (is_object($GLOBALS['xoTheme'])) {
            $GLOBALS['xoTheme']->addStylesheet(XMF_LIBRARIES_URL . '/addto/addto.css');
        }
        $this->tpl->assign('xmf_addto_method', $this->_method);
        $this->tpl->assign('xmf_addto_layout', $this->_layout);
        $this->tpl->assign('xmf_addto_url', XMF_LIBRARIES_URL . '/addto');
    }
}
