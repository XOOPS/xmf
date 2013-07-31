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
 * Email Validator verifies an email address has a correct format.
 *
 */
class Xmf_Mvc_Validator_Email extends Xmf_Mvc_Validator
{

	/**
	 * Create a new Email Validator instance.
	 *
	 * @since 1.0
	 */
	public function __construct ()
	{

		$this->params['email_error'] = 'Invalid email address';
		$this->params['max']         = -1;
		$this->params['max_error']   = 'Email address is too long';
		$this->params['min']         = -1;
		$this->params['min_error']   = 'Email address is too short';

	}

	/**
	 * Execute this validator.
	 *
	 * @param string $value   A user submitted parameter value.
	 * @param string $error   The error message variable to be set if an error occurs.
	 *
	 * @return bool TRUE if the validator completes successfully, otherwise FALSE.
	 *
	 * @since  1.0
	 */
	public function execute (&$value, &$error)
	{
		$value=trim($value);

//        if (!preg_match('/^[a-z0-9\-\._]+@[a-z0-9]([0-9a-z\-]*[a-z0-9]\.){1,}' .
//                        '[a-z]{1,4}$/i', $value))
//        if (!preg_match('/^[a-z0-9_.+-]+@[a-z0-9-]+\.[a-z0-9-.]+$/i', $value))
		if (!checkEmail($value)) // use XOOPS function
		{

			$error = $this->params['email_error'];

			return FALSE;

		}

		$length = strlen($value);

		if ($this->params['min'] > -1 && $length < $this->params['min'])
		{

			$error = $this->params['min_error'];

			return FALSE;

		}

		if ($this->params['max'] > -1 && $length > $this->params['max'])
		{

			$error = $this->params['max_error'];

			return FALSE;

		}

		return TRUE;

	}

   /**
	* Initialize the validator. This is only required to override
	* the default error messages.
	*
	* Initialization Parameters:
	*
	* Name | Type | Default | Required | Description
	* ---- | ---- | ------- | -------- | ------------
	* max  | int  | n/a     | no       | a maximum length
	* min  | int  | n/a     | no       | a minimum length
	*
	* Error Messages:
	*
	* Name        | Default
	* ----------- | --------
	* email_error | Invalid email address
	* max_error   | Email address is too long
	* min_error   | Email address is too short
	*
	*
	* @param array $params An associative array of initialization parameters.
	*
	* @since  1.0
	*/
	public function initialize ($params)
	{

		parent::initialize($params);

	}


}

?>
