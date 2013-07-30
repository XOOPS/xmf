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
 * @version         $Id: Adminindex.php 8065 2011-11-06 02:02:32Z beckmi $
 */

defined('XMF_EXEC') or die('Xmf was not detected');

class Xmf_Template_Adminindex extends Xmf_Template_Abstract
{
    /**
     * @var Xmf_Template_Buttonbox
     */
    private $_buttonBox;

    /**
     * @var Xmf_Template_Infobox
     */
    private $_infoBox;

    /**
     * @var Xmf_Template_Configbox
     */
    private $_configBox;

    /**
     * @var XoopsModule
     */
    private $_module;

    /**
     * @return void
     */
    protected function init()
    {
        Xmf_Language::load('main', 'xmf');

        $this->_module =& $GLOBALS['xoopsModule'];
        if (is_object($GLOBALS['xoTheme'])) {
            $GLOBALS['xoTheme']->addStylesheet(XMF_CSS_URL . '/admin.css');
        }
    }

    public function setModule(XoopsModule $module)
    {
        $this->_module = $module;
    }

    /**
     * @param Xmf_Template_Infobox $infoBox
     * @return void
     */
    public function addInfoBox(Xmf_Template_Infobox $infoBox)
    {
        $this->_infoBox = $infoBox;
    }

    /**
     * @param Xmf_Template_Configbox $configBox
     * @return void
     */
    public function addConfigBox(Xmf_Template_Configbox $configBox)
    {
        $this->_configBox = $configBox;
    }

    /**
     * @param Xmf_Template_Buttonbox $buttonBox
     * @return void
     */
    public function addButtonBox(Xmf_Template_Buttonbox $buttonBox)
    {
        $this->_buttonBox = $buttonBox;
    }

    /**
     * Creates menu icons for index page
     *
     * @return string
     */
    private function _renderMenuIndex()
    {
        $path = XOOPS_URL . "/modules/" . $this->_module->getVar('dirname') . "/";
        $pathsystem = XOOPS_URL . "/modules/system/";
        $this->_module->loadAdminMenu();
        $ret = "<div class=\"rmmenuicon\">\n";
        foreach (array_keys($this->_module->adminmenu) as $i) {
            if ($this->_module->adminmenu[$i]['link'] != 'admin/index.php') {
                $ret .= "<a href=\"../" . $this->_module->adminmenu[$i]['link'] . "\" title=\"" . $this->_module->adminmenu[$i]['title'] . "\">";
                $ret .= "<img src=\"" . $path . $this->_module->adminmenu[$i]['icon'] . "\" alt=\"" . $this->_module->adminmenu[$i]['title'] . "\" />";
                $ret .= "<span>" . $this->_module->adminmenu[$i]['title'] . "</span>";
                $ret .= "</a>";
            }
        }
        if ($this->_module->getInfo('help')) {
            if (substr(XOOPS_VERSION, 0, 9) >= 'XOOPS 2.5') {
                $ret .= "<a href=\"" . $pathsystem . "help.php?mid=" . $this->_module->getVar('mid', 's') . "&amp;" . $this->_module->getInfo('help') . "\" title=\"" . _AM_SYSTEM_HELP . "\">";
                $ret .= "<img width=\"32px\" src=\"" . XMF_IMAGES_URL . "/icons/32/help.png\" alt=\"" . _AM_SYSTEM_HELP . "\" /> ";
                $ret .= "<span>" . _AM_SYSTEM_HELP . "</span>";
                $ret .= "</a>";
            }
        }
        $ret .= "</div>\n<div style=\"clear: both;\"></div>\n";
        return $ret;
    }


    /**
     * @return string
     */
    private function _renderInfoBox()
    {
        $ret = "";
        if (is_object($this->_infoBox)) {
            $ret .= $this->_infoBox->fetch();
        }
        return $ret;
    }

    /**
     * @return string
     */
    private function _renderConfigBox()
    {
        $ret = "";
        if (is_object($this->_configBox)) {
            $ret .= $this->_configBox->fetch();
        }
        return $ret;
    }

    /**
     * @return string
     */
    private function _renderButtonBox()
    {
        $ret = "";
        if (is_object($this->_buttonBox)) {
            $ret .= $this->_buttonBox->fetch();
        }
        return $ret;
    }

    /**
     * @return void
     */
    protected function render()
    {
        $ret = "<table>\n<tr>\n";
        $ret .= "<td width=\"40%\">\n";
        $ret .= $this->_renderMenuIndex();
        $ret .= "</td>\n";
        $ret .= "<td width=\"60%\">\n";
        $ret .= $this->_renderInfoBox();
        $ret .= "</td>\n";
        $ret .= "</tr>\n";
        // If you use a config label
        if ($this->_module->getInfo('min_php') || $this->_module->getInfo('min_xoops') || is_object($this->_configBox)) {
            $ret .= "<tr>\n";
            $ret .= "<td colspan=\"2\">\n";
            $ret .= "<fieldset><legend class=\"label\">";
            $ret .= _AM_XMF_CONFIG;
            $ret .= "</legend>\n";
            // php version
            $path = XMF_IMAGES_URL . "/icons/16/";
            if ($this->_module->getInfo('min_php')) {
                if (phpversion() < $this->_module->getInfo('min_php')) {
                    $ret .= "<span style='color : red; font-weight : bold;'><img src='" . $path . "off.png' >" . sprintf(_AM_XMF_CONFIG_PHP, $this->_module->getInfo('min_php'), phpversion()) . "</span>\n";
                } else {
                    $ret .= "<span style='color : green;'><img src='" . $path . "on.png' >" . sprintf(_AM_XMF_CONFIG_PHP, $this->_module->getInfo('min_php'), phpversion()) . "</span>\n";
                }
                $ret .= "<br />";
            }
            // xoops version
            if ($this->_module->getInfo('min_xoops')) {
                if (substr(XOOPS_VERSION, 6, strlen(XOOPS_VERSION) - 6) < $this->_module->getInfo('min_xoops')) {
                    $ret .= "<span style='color : red; font-weight : bold;'><img src='" . $path . "off.png' >" . sprintf(_AM_XMF_CONFIG_XOOPS, $this->_module->getInfo('min_xoops'), substr(XOOPS_VERSION, 6, strlen(XOOPS_VERSION) - 6)) . "</span>\n";
                } else {
                    $ret .= "<span style='color : green;'><img src='" . $path . "on.png' >" . sprintf(_AM_XMF_CONFIG_XOOPS, $this->_module->getInfo('min_xoops'), substr(XOOPS_VERSION, 6, strlen(XOOPS_VERSION) - 6)) . "</span>\n";
                }
                $ret .= "<br />";
            }
            $ret .= $this->_renderConfigBox();

            $ret .= "</fieldset>\n";
            $ret .= "</td>\n";
            $ret .= "</tr>\n";
        }
        $ret .= "</table>\n";
        $this->tpl->assign('dummy_content', $ret);
    }

}