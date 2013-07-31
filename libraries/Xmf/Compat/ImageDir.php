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

class Xmf_Compat_ImageDir
{

	/**
	 * Are we in a 2.6 environment?
	 *
	 * @return bool true if we are in a 2.6 environment
	 */
	static public function is26()
	{
		return class_exists('Xoops',false);
	}

	/**
	 * Get an appropriate URL for system provided icons.
	 *
	 * Things which were in Frameworks in 2.5 are in media in 2.6,
	 * making it harder to use and rely on the standard icons.
	 *
	 * not part of 2.6, just a transition assist
	 *
	 * @param string $name the image name to provide URL for, or blank
	 *                     to just get the URL path.
	 * @param string $size the icon size (directory). Valid values are
	 *                     16, 32 or /. A '/' slash will simply set the
	 *                     path to the icon directory and append $image.
	 * @return bool true if we are in a 2.6 environment
	 */
	static public function iconUrl($name='',$size='32')
	{
		switch ($size) {
			case '16':
				$path='16/';
				break;
			case '/':
				$path='';
				break;
			default:
			case '32':
				$path='32/';
				break;
		}


        if(Xmf_Compat_ImageDir::is26()) {
			$path='/media/xoops/images/icons/'.$path;
		}
		else {
			$path='/Frameworks/moduleclasses/icons/'.$path;
		}
		return(XOOPS_URL . $path . $name);
	}

}
?>
