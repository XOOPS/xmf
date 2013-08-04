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
 * Xmf_Mvc_ContextAware makes shared context available
 *
 * Shared context makes the following available:
 * Controller() -
 *
 * @copyright       The XOOPS Project http://sourceforge.net/projects/xoops/
 * @license         http://www.fsf.org/copyleft/gpl.html GNU private license
 * @package         Xmf_Mvc
 * @since           1.0
 * @author          Richard Griffith
 */

defined('XMF_EXEC') or die('Xmf was not detected');

abstract class Xmf_Mvc_ContextAware
{

   /**
	* Instance of the full context. At present this is the controller
	*
	* @return object shared context
	*
	* @since      1.0
	*/
	protected function & Context()
	{
		return Xmf_Mvc_Context::get();
	}

   /**
	* Get the controller context
	*
	* @return object Xmf_Mvc_Controller instance
	* @since      1.0
	*/
	public function & Controller()
	{
		return $this->Context();
	}

   /**
	* Get the request context
	*
	* @return object Xmf_Mvc_Request instance
	* @since      1.0
	*/
	public function & Request()
	{
		return $this->Context()->getRequest();
	}

   /**
	* Get the user context
	*
	* @return object Xmf_Mvc_User instance
	* @since      1.0
	*/
	public function & User()
	{
		return $this->Context()-> getUser();
	}

   /**
	* Get the ModelManager instance
	*
	* @return object Xmf_Mvc_ModelManager instance
	* @since      1.0
	*/
	public function & Models()
	{
		return $this->Context()->getModels();
	}
}
?>