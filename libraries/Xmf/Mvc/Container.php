<?php

/**
 * This file has its roots as part of the Mojavi package which was
 * Copyright (c) 2003 Sean Kerr. It has been incorporated into this
 * derivative work under the terms of the LGPL V2.1.
 * (license terms)
 *
 * @author          Richard Griffith
 * @author          Sean Kerr
 * @copyright       The XOOPS Project http://sourceforge.net/projects/xoops/
 * @copyright       Portions Copyright (c) 2003 Sean Kerr
 * @license         (license terms)
 * @package         Xmf_Mvc
 * @since           1.0
 */

/**
 * A Container provides storage for user data.
 *
 */
interface Xmf_Mvc_Container
{

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
	public function load (&$authenticated, &$attributes, &$secure);

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
	public function store (&$authenticated, &$attributes, &$secure);
}

?>
