<?php

namespace Xmf\Module;

/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

/**
 * Xmf\Module\Admin provides helpful methods for module administration
 * uses.
 *
 * Xmf\Module\Admin also provides a method compatible subset of the
 * Xoops 2.6 ModuleAdmin class for use in transition from 2.5 to 2.6
 *
 * @copyright       The XOOPS Project http://sourceforge.net/projects/xoops/
 * @license         http://www.fsf.org/copyleft/gpl.html GNU private license
 * @package         Xmf
 * @since           1.0
 * @author          Richard Griffith
 */

defined('XMF_EXEC') or die('Xmf was not detected');

class Admin
{

    /**
     * The real ModuleAdmin object
     *
     * @var object
     */
    private static $_ModuleAdmin = null;
    private $_version26 = null;
    private $_lastInfoBoxTitle = null;
    private static $_paypal = '';

    /**
     * Constructor
     */
    private function __construct()
    {
        $this->_version26 = self::is26();
    }

    /**
     * Retrieve a module admin instance
     *
     * If we are on 2.6 this will be the a XoopsModuleAdmin instance.
     * Older systems with the Frameworks based admin class will get
     * an instance of this class which provides compatible methods
     * built from the old Frameworks version.
     *
     * **Always use this to get the ModuleAdmin instance if you use
     * anything (even the static methods) of this class.**
     *
     * @return object a ModuleAdmin instance.
     *
     * @since  1.0
     */
    public static function & getInstance ()
    {

        static $instance;

        if ($instance === NULL) {
            if (class_exists('XoopsModuleAdmin',true)) {
                $instance  = new \XoopsModuleAdmin;
                self::$_ModuleAdmin = $instance;
            } else {
                \Xmf\Loader::loadFile(XOOPS_ROOT_PATH . '/Frameworks/moduleclasses/moduleadmin/moduleadmin.php');
                self::$_ModuleAdmin = new \ModuleAdmin;
                $instance  = new \Xmf\Module\Admin;
            }

        }

        return $instance;

    }

    /**
     * Are we in a 2.6 environment?
     *
     * just to help with other admin things than ModuleAdmin
     *
     * not part of 2.6 module admin
     *
     * @return bool true if we are in a 2.6 environment
     */
    public static function is26()
    {
        return class_exists('Xoops',false);
    }

    /**
     * Get an appropriate imagePath for menu.php use.
     *
     * just to help with other admin things than ModuleAdmin
     *
     * not part of 2.6 module admin
     *
     * @return bool true if we are in a 2.6 environment
     */
    public static function menuIconPath($image)
    {
        if (self::is26()) {
            return($image);
        } else {
            $path='../../Frameworks/moduleclasses/icons/32/';

            return($path.$image);
        }
    }

    /**
     * Add config line
     *
     * @param string $value
     * @param string $type
     *
     * @return bool
     */
    public function addConfigBoxLine($value = '', $type = 'default')
    {
        return self::$_ModuleAdmin->addConfigBoxLine($value, $type);
    }

    /**
     * Add Info box
     *
     * @param        $title
     * @param string $type
     * @param string $extra
     *
     * @return bool
     */
    public function addInfoBox($title, $type = 'default', $extra = '')
    {
        $this->_lastInfoBoxTitle = $title;

        return self::$_ModuleAdmin->addInfoBox($title);
    }

    /**
     * Add line to the info box
     *
     * @param string $text
     * @param string $type
     * @param string $color
     *
     * @return bool
     */
    public function addInfoBoxLine($text = '', $type = 'default', $color = 'inherit')
    {
        return self::$_ModuleAdmin->addInfoBoxLine($this->_lastInfoBoxTitle, $text, '', $color, $type);
    }

    /**
     * Add Item button
     *
     * @param        $title
     * @param        $link
     * @param string $icon
     * @param string $extra
     *
     * @return bool
     */
    public function addItemButton($title, $link, $icon = 'add', $extra = '')
    {
        return self::$_ModuleAdmin->addItemButton($title, $link, $icon, $extra);
    }

    /**
     * Render all items buttons
     *
     * @param string $position
     * @param string $delimiter
     *
     * @return string
     */
    public function renderButton($position = null, $delimiter = "&nbsp;")
    {
        if($postion==null) $position = 'right';

        return self::$_ModuleAdmin->renderButton($position, $delimeter);
    }

    /**
     * @param string $position
     * @param string $delimiter
     */
    public function displayButton($position = null, $delimiter = "&nbsp;")
    {
        echo $this->renderButton($position, $delimiter);
    }

    /**
     * Render InfoBox
     */
    public function renderInfoBox()
    {
        return self::$_ModuleAdmin->renderInfoBox();
    }

    public function displayInfoBox()
    {
        echo $this->renderInfoBox();
    }

    /**
     * Render index page for admin
     */
    public function renderIndex()
    {
        return self::$_ModuleAdmin->renderIndex();
    }

    public function displayIndex()
    {
        echo $this->renderIndex();
    }

    /**
     * @param string $menu
     */
    public function displayNavigation($menu = '')
    {
        echo self::$_ModuleAdmin->addNavigation($menu);
    }

    /**
     * Render about page
     *
     * @param bool $logo_xoops
     *
     * @return bool|mixed|string
     */
    public function renderAbout($logo_xoops = true)
    {
        return self::$_ModuleAdmin->renderAbout(self::$_paypal, $logo_xoops);
    }

    /**
     * set paypal for 2.5 renderAbout
     *
     * @param bool $logo_xoops
     *
     * @return bool|mixed|string
     */
    public static function setPaypal($paypal = '')
    {
        self::$_paypal = $paypal;
    }

    /**
     * @param bool $logo_xoops
     */
    public function displayAbout($logo_xoops = true)
    {
        echo $this->renderAbout($logo_xoops);
    }

// new
    /**
     * Add error to config box
     *
     * @param string $value the error message
     *
     * @return bool
     */
    public static function addConfigError($value = '')
    {
        if (self::is26()) {
            $type='error';
        } else {
            $path=XOOPS_URL.'/Frameworks/moduleclasses/icons/16/';
            $line = "";
            $line .= "<span style='color : red; font-weight : bold;'>";
            $line .= "<img src='" . $path . "off.png' >";
            $line .= $value;
            $line .= "</span>";
            $value=$line;
            $type = 'default';
        }

        return self::$_ModuleAdmin->addConfigBoxLine($value, $type);
    }

    /**
     * Add accept (OK) message to config box
     *
     * @param string $value the OK message
     *
     * @return bool
     */
    public static function addConfigAccept($value = '')
    {
        if (self::is26()) {
            $type='accept';
        } else {
            $path=XOOPS_URL.'/Frameworks/moduleclasses/icons/16/';
            $line = "";
            $line .= "<span style='color : green;'>";
            $line .= "<img src='" . $path . "on.png' >";
            $line .= $value;
            $line .= "</span>";
            $value=$line;
            $type = 'default';
        }

        return self::$_ModuleAdmin->addConfigBoxLine($value, $type);
    }

    /**
     * Check for installed module and version and do addConfigBoxLine()
     *
     * @param string  $moddir     - module directory name
     * @param integer $minversion - minimum acceptable module version (100 = V1.00)
     *
     * @return bool true if requested version of the module is available
     */
    public static function checkModuleVersion($moddir,$minversion)
    {
        \Xmf\Language::load('main', 'xmf');
        $return=false;
        $helper=\Xmf\Module\Helper::getInstance($moddir);
        if (is_object($helper) && is_object($helper->getModule())) {
            $mod_modversion=$helper->getModule()->getVar('version');
            $mod_version_f = $mod_modversion/100;
            $min_version_f = $minversion/100;
            $value = sprintf(_AM_XMF_DEMOMVC_MODULE_VERSION,strtoupper($moddir),$min_version_f, $mod_version_f);
            if ($mod_modversion>=$minversion) {
                self::addConfigAccept($value);
                $return=true;
            } else {
                self::addConfigError($value);
            }
        } else {
            $value = sprintf(_AM_XMF_DEMOMVC_MODULE_NOTFOUND,strtoupper($moddir),$minversion/100);
            self::addConfigError($value);
        }

        return $return;
    }

}
