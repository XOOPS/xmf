<?php

/**
 * This file has its roots as part of the Mojavi package which was
 * Copyright (c) 2003 Sean Kerr. It has been incorporated into this
 * derivative work under the terms of the LGPL V2.1.
 * (license terms)
 *
 * @author          Richard Griffith
 * @copyright       The XOOPS Project http://sourceforge.net/projects/xoops/
 * @copyright       Portions Copyright (c) 2003 Sean Kerr
 * @license         (license terms)
 * @package         Xmf_Mvc
 * @since           1.0
 */

/**
 * SessionContainer is a Container implementation that stores data in
 * and loads data from the PHP session. The items defined in the
 * Container interface are stored as a single array in $_SESSION with
 * a key based on the XOOPS modules dirname.
 *
 * We may have multiple mvc based modules, as well as multiple
 * invocations of the controller in a single transaction (i.e. Mvc
 * blocks,) we will segregate our session data by XOOPS module
 * by including the module name in the name.
 *
 */
class Xmf_Mvc_SessionContainer extends Xmf_Mvc_ContextAware implements Xmf_Mvc_Container
{

	/**
	 * Create a new SessionContainer instance.
	 *
	 * @since  1.0
	 */
	public function __construct ()
	{

	}

	/**
	 * Load user data.
	 *
	 * _This should never be called manually._
	 *
	 * @param bool  $authenticated  The authenticated status.
	 * @param array $attributes     An associative array of attributes.
	 * @param mixed $secure         Security related data.
	 *
	 * @since  1.0
	 */
	public function load (&$authenticated, &$attributes, &$secure)
	{
		$name=$this->name();
		$rawsession = Xmf_Request::getString($name,'','session');
		$rawsession = null;
		if(isset($_SESSION[$name])) $rawsession=$_SESSION[$name];

		if (empty($rawsession))
		{
			$authenticated = FALSE;
			$attributes    = array();
			$secure        = array();

		} else
		{
			$session=Xmf_Filter_Input::clean(unserialize($rawsession),'default');
			$authenticated = $session['authenticated'];
			$attributes    = $session['attributes'];
			$secure        = $session['secure'];
		}
	}

	/**
	 * Store user data.
	 *
	 * _This should never be called manually._
	 *
	 * @param bool  $authenticated  The authenticated status.
	 * @param array $attributes     An associative array of attributes.
	 * @param mixed $secure         Security related data.
	 *
	 * @since  1.0
	 */
	public function store (&$authenticated, &$attributes, &$secure)
	{
		$name=$this->name();
		if(empty($authenticated) && empty($attributes) && empty($secure)) {
			if(isset($_SESSION[$name])) {
				$_SESSION[$name]=null;
				unset($_SESSION[$name]);
			}
		} else {
			$session=array();
			$session['authenticated'] = $authenticated;
			$session['attributes']    = $attributes;
			$session['secure']        = $secure;

			$_SESSION[$name] = serialize($session);
		}
	}

	/**
	 * Get a name for our session storage
	 *
	 * Include XOOPS module so we don't mix context between Mvc modules.
	 *
	 * @since  1.0
	 */
	private function name()
	{
		// if we can get this from the controller, do it
		// this is significant for blocks
		if(method_exists($this->Controller(),'modGetVar')) {
			$name=$this->Controller()->modGetVar('dirname');
		} else {
			$name=$GLOBALS['xoopsModule']->getVar('dirname');
		}
		return $name.'_attributes';
	}

}

?>
