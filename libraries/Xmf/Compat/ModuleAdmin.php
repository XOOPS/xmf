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
 * Xmf_Compat_ModuleAdmin provides a method compatible subset of the
 * Xoops 2.6 ModuleAdmin class for use in transition from 2.5 to 2.6
 *
 * @copyright       The XOOPS Project http://sourceforge.net/projects/xoops/
 * @license         http://www.fsf.org/copyleft/gpl.html GNU private license
 * @package         Xmf_Mvc
 * @since           1.0
 * @author          Richard Griffith
 */

defined('XMF_EXEC') or die('Xmf was not detected');

class Xmf_Compat_ModuleAdmin
{

    /**
     * The real ModuleAdmin object
     *
     * @var object
     */
    private $_ModuleAdmin = null;
    private $_version26 = null;
    private $_lastInfoBoxTitle = null;
    private $_paypal = '';


    /**
     * Constructor
     */
    function __construct()
    {
        $this->_version26 = Xmf_Compat_ModuleAdmin::is26();

        if($this->_version26) {
			$this->_ModuleAdmin = new XoopsModuleAdmin;

		}
		else {
			Xmf_Loader::loadFile(XOOPS_ROOT_PATH . '/Frameworks/moduleclasses/moduleadmin/moduleadmin.php');
			$this->_ModuleAdmin = new ModuleAdmin;
		}
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
	static public function is26()
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
	static public function menuImagePath($image)
	{
        if(Xmf_Compat_ModuleAdmin::is26()) {
			return($image);
		}
		else {
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
        if($this->_version26) {
			return $this->_ModuleAdmin->addConfigBoxLine($value, $type);
		}
		else {
			return $this->_ModuleAdmin->addConfigBoxLine($value, $type);
		}
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
        if($this->_version26) {
			return $this->_ModuleAdmin->addInfoBox($title, $type, $extra);
		}
		else {
			$this->_lastInfoBoxTitle = $title;
			return $this->_ModuleAdmin->addInfoBox($title);
		}
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
        if($this->_version26) {
			return $this->_ModuleAdmin->addInfoBoxLine($text, $type, $color);
		}
		else {
			return $this->_ModuleAdmin->addInfoBoxLine($this->_lastInfoBoxTitle, $text, '', $color, $type);
		}
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
        if($this->_version26) {
			return $this->_ModuleAdmin->addItemButton($title, $link, $icon, $extra);
		}
		else {
			return $this->_ModuleAdmin->addItemButton($title, $link, $icon, $extra);
		}
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
        if($this->_version26) {
			if($postion==null) $position = 'floatright';
			return $this->_ModuleAdmin->addItemButton($title, $link, $icon, $extra);
		}
		else {
			if($postion==null) $position = 'right';
			return $this->_ModuleAdmin->renderButton($position, $delimeter);
		}

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
        return $this->_ModuleAdmin->renderInfoBox();
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
        return $this->_ModuleAdmin->renderIndex();
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
        if($this->_version26) {
			$this->_ModuleAdmin->displayNavigation($menu);
		}
		else {
			echo $this->_ModuleAdmin->addNavigation($menu);
		}
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
        if($this->_version26) {
			return $this->_ModuleAdmin->renderAbout($logo_xoops);
		}
		else {
			return $this->_ModuleAdmin->renderAbout($this->_paypal, $logo_xoops);
		}
    }

    /**
     * set paypal for 2.5 renderAbout
     *
     * @param bool $logo_xoops
     *
     * @return bool|mixed|string
     */
    public function setPaypal($paypal = '')
    {
        if($this->_version26) {
			// nothing to do
		}
		else {
			$this->_paypal = $paypal;
		}
    }

    /**
     * @param bool $logo_xoops
     */
    public function displayAbout($logo_xoops = true)
    {
        echo $this->renderAbout($logo_xoops);
    }

}
?>
