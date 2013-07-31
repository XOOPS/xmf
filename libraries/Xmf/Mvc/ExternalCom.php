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
 * Provide communications with programs outside standard web interface.
 *
 * This interface provides parameter and attribute methods similar
 * to request. This object is intended to be passed to the controller
 * with getInstance. From there, Actions and Views can use these
 * methods to get parameters (input) and set attributes (output.)
 * Also communicates a module directory.
 *
 * The primary envisioned use is to allow Mvc to function in a XOOPS
 * block capacity, i.e.
 * -  $externalCom->setParameterArray($options);
 * -  Xmf_Mvc_XoopsController::getInstance($externalCom) -> dispatch(unit,action);
 * -  $block = $externalCom->getAttributes();
 * but other uses are possible.
 *
 * Borrows heavily from Xmf_Mvc_Request
 *
 */
class Xmf_Mvc_ExternalCom
{
	/**
	 * @var XOOPS module dirname
	 */
	private $dirname = null;

	/**
	 * @var parameters - will be array when used
	 */
	private $params = null;

	/**
	 * @var attributes - will be array when used
	 */
	private $attributes = null;

	/**
	 * Create a new Xmf_Mvc_ExternalCom instance.
	 *
	 * @since  1.0
	 */
	public function __construct ()
	{

	}

	/**
	 * Retrieve the dirname
	 *
	 * @return string content of $this->dirname
	 *
	 * @since  1.0
	 */
	public function getDirname()
	{
		return $this->dirname;
	}

	/**
	 * Set the dirname
	 *
	 * @param string $name  XOOPS module dirname
	 *
	 * @since  1.0
	 */
	public function setDirname($name)
	{
		$this->dirname = $name;
	}

	/**
	 * Retrieve an attribute.
	 *
	 * @param string $name  An attribute name.
	 *
	 * @return mixed An attribute value, if the given attribute exists,
	 *               otherwise NULL.
	 *
	 * @since  1.0
	 */
	public function & getAttribute ($name)
	{
		if (isset($this->attributes[$name]))
		{
			return $this->attributes[$name];
		}
		$null=NULL;
		return $null;
	}

	/**
	 * Retrieve an indexed array of attribute names.
	 *
	 * @return array An array of attribute names.
	 *
	 * @since  1.0
	 */
	public function getAttributeNames ()
	{
		return array_keys($this->attributes);
	}

	/**
	 * Retrieve an associative array of all attributes.
	 *
	 * @return array An array of attributes.
	 *
	 * @since  1.0
	 */
	public function & getAttributes ()
	{
		return $this->attributes;
	}

	/**
	 * Retrieve a parameter.
	 *
	 * @param string $name   A parameter name.
	 * @param mixed  $value  A default value.
	 *
	 * @return mixed A parameter value, if the given parameter exists,
	 *               otherwise NULL.
	 *
	 * @since  1.0
	 */
	public function & getParameter ($name, $value = null)
	{
		if (isset($this->params[$name]))
		{
			return $this->params[$name];
		} else
		{
			return $value;
		}
	}

	/**
	 * Retrieve an indexed array of parameter names.
	 *
	 * @return array An array of parameter names.
	 *
	 * @since  1.0
	 */
	public function getParameterNames ()
	{

		return array_keys($this->params);

	}

	/**
	 * Retrieve an associative array of parameters.
	 *
	 * @return array An array of parameters.
	 *
	 * @since  1.0
	 */
	public function & getParameters ()
	{

		return $this->params;

	}

	/**
	 * Determine if an attribute exists.
	 *
	 * @param string $name  An attribute name.
	 *
	 * @return bool TRUE if the given attribute exists, otherwise FALSE.
	 *
	 * @since  1.0
	 */
	public function hasAttribute ($name)
	{

		return isset($this->attributes[$name]);

	}

	/**
	 * Determine if the request has a parameter.
	 *
	 * @param string $name  A parameter name.
	 *
	 * @return bool TRUE if the given parameter exists, otherwise FALSE.
	 *
	 * @since  1.0
	 */
	public function hasParameter ($name)
	{

		return isset($this->params[$name]);

	}

	/**
	 * Remove an attribute.
	 *
	 * @param string $name  An attribute name.
	 *
	 * @return mixed An attribute value, if the given attribute exists and has
	 *               been removed, otherwise NULL.
	 *
	 * @since  1.0
	 */
	public function & removeAttribute ($name)
	{

		if (isset($this->attributes[$name]))
		{

			$value =& $this->attributes[$name];

			unset($this->attributes[$name]);

			return $value;

		}

	}

	/**
	 * Remove a parameter.
	 *
	 * @param string $name  A parameter name.
	 *
	 * @return mixed A parameter value, if the given parameter exists and has
	 *               been removed, otherwise NULL.
	 *
	 * @since  1.0
	 */
	public function & removeParameter ($name)
	{

		if (isset($this->params[$name]))
		{

			$value =& $this->params[$name];

			unset($this->params[$name]);

			return $value;

		}

	}

	/**
	 * Set an attribute.
	 *
	 * @param string $name   An attribute name.
	 * @param mixed  $value  An attribute value.
	 *
	 * @since  1.0
	 */
	public function setAttribute ($name, $value)
	{

		$this->attributes[$name] =& $value;

	}

	/**
	 * Set an attribute by reference.
	 *
	 * @param string $name   An attribute name.
	 * @param mixed  $value  An attribute value.
	 *
	 * @since  1.0
	 */
	public function setAttributeByRef ($name, &$value)
	{

		$this->attributes[$name] =& $value;

	}

	/**
	 * Manually set a parameter.
	 *
	 * @param string $name  A parameter name.
	 * @param mixed  $value A parameter value.
	 *
	 * @since  1.0
	 */
	public function setParameter ($name, $value)
	{

		$this->params[$name] = $value;

	}

	/**
	 * Manually set a parameter by reference.
	 *
	 * @param string $name  A parameter name.
	 * @param mixed  $value A parameter value.
	 *
	 * @since  1.0
	 */
	public function setParameterByRef ($name, &$value)
	{

		$this->params[$name] =& $value;

	}

	/**
	 * Manually set all parameters at once by overwriting with array.
	 *
	 * @param array $value A parameter array
	 *
	 * @since  1.0
	 */
	public function setParameterArray (&$value)
	{

		$this->params = $value;

	}


}

?>
