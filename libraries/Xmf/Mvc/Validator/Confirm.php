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
 * Confirm Validator provides a constraint on a parameter by ensuring
 * the value is equal to another parameters value. This is useful for
 * double entry confirmation for email addresses, account numbers, etc.
 *
 */
class Xmf_Mvc_Validator_Confirm extends Xmf_Mvc_Validator
{

	/**
	 * Create a new Confirm Validator instance.
	 *
	 * @since  1.0
	 */
	function __construct ()
	{

		parent::__construct();

		$this->params['confirm']       = '';
		$this->params['confirm_error'] = 'Does not match';
		$this->params['sensitive']     = true;

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
	function execute (&$value, &$error)
	{
		global $xoopsDB;

		$found = FALSE;

		$confirm = $this->Request()->getParameter($this->params['confirm']);

		if($this->params['sensitive']) {
			$confirmed=(strcmp($value,$confirm)===0);
		} else {
			$confirmed=(strcasecmp($value,$confirm)===0);
		}

		if(!$confirmed) {
			$error = $this->params['confirm_error'];
		}
		return $confirmed;

	}

   /**
	* Initialize the validator.
	*
	* Initialization Parameters:
	*
	* Name          | Type   | Default | Required | Description
	* ------------- | ------ | ------- | -------- | -----------
	* confirm       | string | _n/a_   | yes      | name of parameter to match
	* sensitive     | string | TRUE    | yes      | If true, comparison is case sensitive
	*
	* Error Messages:
	*
	* Name          | Default
	* ------------- | -------
	* confirm_error | Does not match
	*
	* @param mixed $params An scalar parameter name of the value to confirm,
	*                      or an associative array of initialization parameters.
	*
	* @since  1.0
	*/
	function initialize ($params)
	{

		if(is_array($params)) {
			parent::initialize($params);
		}
		else {
			$this->params['confirm']=$params;
		}

	}

}

?>
