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
 * @author          The SmartFactory <www.smartfactory.ca>
 * @version         $Id: Adminmenu.php 8065 2011-11-06 02:02:32Z beckmi $
 */

defined('XMF_EXEC') or die('Xmf was not detected');

class Xmf_Template_Adminmenu extends Xmf_Template_Abstract
{
    /**
     * @var int
     */
    private $_currentoption = -1;

    /**
     * @var string
     */
    private $_breadcrumb = '';

    /**
     * @var bool
     */
    private $_submenus = false;

    /**
     * @var int
     */
    private $_currentsub = -1;

    /**
     * @var
     */
    private $_adminmenu;

    /**
     * @var array
     */
    private $_headermenu = array();

    /**
     * @var XoopsModule
     */
    private $_module;

    /**
     * @return void
     */
    protected function init()
    {
        $this->setTemplate(XMF_ROOT_PATH . '/templates/xmf_adminmenu.html');
        $this->setModule($GLOBALS['xoopsModule']);
    }

    /**
     * @param XoopsModule $module
     * @return void
     */
    public function setModule(XoopsModule $module)
    {
        $this->_module = $module;
        $this->_adminmenu = $module->loadAdminMenu();
        foreach ($this->_module->adminmenu as $i => $menu) {
            if (stripos($_SERVER['REQUEST_URI'], $menu['link']) !== false) {
                $this->_currentoption = $i;
                $this->_breadcrumb = $menu['title'];
            }
        }
    }

    /**
     * @param int $value
     * @return Xmf_Template_Adminmenu
     */
    public function setCurrentoption($value = 0)
    {
        $this->_currentoption = $value;
        return $this;
    }

    /**
     * @param string $value
     * @return Xmf_Template_Adminmenu
     */
    public function setBreadcrumb($value = '')
    {
        $this->_breadcrumb = $value;
        return $this;
    }

    /**
     * @param int $value
     * @return Xmf_Template_Adminmenu
     */
    public function setCurrentsub($value = 0)
    {
        $this->_currentsub = $value;
        return $this;
    }

    /**
     * @param bool $value
     * @return Xmf_Template_Adminmenu
     */
    public function setSubmenus($value = false)
    {
        $this->_submenus = $value;
        return $this;
    }

    /**
     * @return array
     */
    private function _getAdminmenu()
    {
        $ret = array();
        foreach ($this->_module->adminmenu as $key => $value) {
            $ret[$key] = $value;
            $ret[$key]['link'] = $GLOBALS['xoops']->url('modules/' . $this->_module->dirname() . '/' . $value['link']);
        }
        return $ret;
    }

    /**
     * @return array
     */
    private function _getHeadermenu()
    {
        Xmf_Language::load('menu', 'xmf');

        $headermenu = array();
        $modPath = XOOPS_ROOT_PATH . '/modules/' . $this->_module->getVar('dirname');
        $modUrl = XOOPS_URL . '/modules/' . $this->_module->getVar('dirname');

        $i = -1;

        if ($this->_module->getInfo('hasMain')) {
            $i++;
            $headermenu[$i]['title'] = _MENU_XMF_GOTOMOD;
            $headermenu[$i]['link'] = $modUrl;
        }

        if (is_array($this->_module->getInfo('config'))) {
            $i++;
            $headermenu[$i]['title'] = _MENU_XMF_PREFERENCES;
            $headermenu[$i]['link'] = XOOPS_URL . '/modules/system/admin.php?fct=preferences&amp;op=showmod&amp;&amp;mod=' . $this->_module->getVar('mid');
        }
        if (is_array($this->_module->getInfo('blocks'))) {
            $i++;
            $headermenu[$i]['title'] = _MENU_XMF_BLOCKS;
            $headermenu[$i]['link'] = XOOPS_URL . '/modules/system/admin.php?fct=blocksadmin&amp;selvis=-1&amp;selmod=-2&amp;selgrp=-1&amp;selgen=' . $this->_module->getVar('mid');
        }
        if ($this->_module->getInfo('hasComments')) {
            $i++;
            $headermenu[$i]['title'] = _MENU_XMF_COMMENTS;
            $headermenu[$i]['link'] = XOOPS_URL . '/modules/system/admin.php?fct=comments&amp;module=' . $this->_module->getVar('mid');
        }

        $i++;
        $headermenu[$i]['title'] = _MENU_XMF_UPDATE;
        $headermenu[$i]['link'] = XOOPS_URL . '/modules/system/admin.php?fct=modulesadmin&op=update&module=' . $this->_module->getVar('dirname');

        if (file_exists($modPath . '/admin/import.php')) {
            $i++;
            $headermenu[$i]['title'] = _MENU_XMF_IMPORT;
            $headermenu[$i]['link'] = $modUrl . '/admin/import.php';
        }

        if (file_exists($modPath . '/admin/clone.php')) {
            $i++;
            $headermenu[$i]['title'] = _MENU_XMF_CLONE;
            $headermenu[$i]['link'] = $modUrl . '/admin/clone.php';
        }

        if (file_exists($modPath . '/admin/about.php')) {
            $i++;
            $headermenu[$i]['title'] = _MENU_XMF_ABOUT;
            $headermenu[$i]['link'] = $modUrl . '/admin/about.php';
        }

        if ($this->_module->getInfo('help')) {
            $i++;
            $headermenu[$i]['title'] = _MENU_XMF_HELP;
            $headermenu[$i]['link'] = XOOPS_URL . '/modules/system/help.php?mid=' . $this->_module->getVar('mid') . '&amp;' . $this->_module->getInfo('help');
        }

        return $headermenu;
    }

    /**
     * @return void
     */
    protected function render()
    {

        Xmf_Language::load('modinfo', $this->_module->getVar('dirname'));
        Xmf_Language::load('admin', $this->_module->getVar('dirname'));

        $this->tpl->assign(array(
                'modulename' => $this->_module->getVar('name'), 'headermenu' => $this->_getHeadermenu(),
                'adminmenu' => $this->_getAdminmenu(), 'current' => $this->_currentoption,
                'breadcrumb' => $this->_breadcrumb, 'headermenucount' => count($this->_headermenu),
                'submenus' => $this->_submenus, 'currentsub' => $this->_currentsub,
                'submenuscount' => count($this->_submenus)
            ));
    }

}