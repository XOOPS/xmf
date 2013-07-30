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
 * @author          Grégory Mage (Aka Mage)
 * @author          trabis <lusopoemas@gmail.com>
 * @version         $Id: Adminnav.php 8065 2011-11-06 02:02:32Z beckmi $
 */

defined('XMF_EXEC') or die('Xmf was not detected');

class Xmf_Template_Adminnav extends Xmf_Template_Abstract
{
    /**
     * @var array
     */
    private $_adminmenu = array();

    /**
     * @var XoopsModule
     */
    private $_module;

    /**
     * @var string
     */
    private $_title = '';

    /**
     * @var string
     */
    private $_link = '';

    /**
     * @var string
     */
    private $_icon = '';

    /**
     * @return void
     */
    protected function init()
    {
        $this->setModule($GLOBALS['xoopsModule']);
    }

    /**
     * @param XoopsModule $module
     * @return void
     */
    public function setModule(XoopsModule $module)
    {
        $this->_module = $module;
        $module->loadAdminMenu();
        $this->_adminmenu = $this->_module->adminmenu;

        foreach ($this->_adminmenu as $menu) {
            if (stripos($_SERVER['REQUEST_URI'], $menu['link']) !== false) {
                $this->_title = $menu['title'];
                $this->_link = $menu['link'];
                $this->_icon = $menu['icon'];
            }
        }
    }

    /**
     * @return void
     */
    protected function render()
    {
        if (is_object($GLOBALS['xoTheme'])) {
            $GLOBALS['xoTheme']->addStylesheet(XMF_CSS_URL . '/admin.css');
        }
        $ret = "";
        $navigation = "";
        $path = XOOPS_URL . "/modules/" . $this->_module->getVar('dirname') . "/";

        if ($this->_icon) {
            $navigation .= $this->_title . " | ";
            $ret = "<div class=\"CPbigTitle\" style=\"background-image: url(" . $path . $this->_icon . "); background-repeat: no-repeat; background-position: left; padding-left: 50px;\"><strong>" . $this->_title . "</strong></div><br />";
        } else {
            if ($this->_link) {
                $navigation .= "<a href = '../" . $this->_link . "'>" . $this->_title . "</a> | ";
            }
        }

        if (substr(XOOPS_VERSION, 0, 9) < 'XOOPS 2.5') {
            $navigation .= "<a href = '../../system/admin.php?fct=preferences&op=showmod&mod=" . $this->_module->getVar('mid') . "'>" . _MI_SYSTEM_ADMENU6 . "</a>";
            $ret = $navigation . "<br /><br />" . $ret;
        }

        $this->tpl->assign('dummy_content', $ret);
    }

}