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
 * A Validator is an object which validates a user submitted parameter
 * conforms to specific rules. It can also modify parameter values,
 * providing input filtering capabilities.
 *
 */
abstract class Xmf_Mvc_Validator extends Xmf_Mvc_ContextAware
{

	/**
	 * The default error message for any occuring error.
	 *
	 * @since  1.0
	 * @type   string
	 */
	protected $message;

	/**
	 * An associative array of initialization parameters.
	 *
	 * @since  1.0
	 * @type   array
	 */
	protected $params;

	/**
	 * Create a new Validator instance.
	 *
	 * @since  1.0
	 */
	public function __construct ()
	{

		$this->message = NULL;
		$this->params  = array();

	}

	/**
	 * Execute the validator.
	 *
	 *  _This method should never be called manually._
	 *
	 * @param string $value   A user submitted parameter value.
	 * @param string $error   The error message variable to be set if an error occurs.
	 *
	 * @return bool TRUE if the validator completes successfully, otherwise FALSE.
	 *
	 * @since  1.0
	 */
	abstract public function execute (&$value, &$error);

	/**
	 * Retrieve the default error message.
	 *
	 * This will return NULL unless an error message has been
	 * specified with setErrorMessage()
	 *
	 * @return string An error message.
	 *
	 * @since  1.0
	 */
	public function getErrorMessage ()
	{

		return $this->message;

	}

	/**
	 * Retrieve a parameter.
	 *
	 * @param string $name A parameter name.
	 *
	 * @return mixed A parameter value, if the given parameter exists, otherwise NULL.
	 *
	 * @since  1.0
	 */
	public function & getParameter ($name)
	{

		if (isset($this->params[$name]))
		{

			return $this->params[$name];

		}

		return NULL;

	}

	/**
	 * Initialize the validator.
	 *
	 * @param array $params An associative array of initialization parameters.
	 *
	 * @since  1.0
	 */
	public function initialize ($params)
	{

		$this->params = array_merge($this->params, $params);

	}

	/**
	 * Set the default error message for any occuring error.
	 *
	 * @param  string $message An error message.
	 *
	 * @since  1.0
	 */
	public function setErrorMessage ($message)
	{

		$this->message = $message;

	}

	/**
	 * Set a validator parameter.
	 *
	 * @param string $name   A parameter name.
	 * @param mixed  $value  A parameter value.
	 *
	 * @since  1.0
	 */
	public function setParameter ($name, $value)
	{

		$this->params[$name] = $value;

	}

	/**
	 * Set a validator parameter by reference.
	 *
	 * @param string $name   A parameter name.
	 * @param mixed  $value  A parameter value.
	 *
	 * @since  1.0
	 */
	public function setParameterByRef ($name, &$value)
	{

		$this->params[$name] =& $value;

	}

}

?>
