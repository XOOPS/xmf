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
 * RegexValidator provides a constraint on a parameter by making sure
 * the value is or is not matched by the supplied regular expression
 *
 */
class Xmf_Mvc_Validator_Regex extends Xmf_Mvc_Validator
{

	/**
	 * Create a new Regex Validator instance.
	 *
	 * @since  1.0
	 */
	public function __construct ()
	{

		$this->params['match']         = TRUE;
		$this->params['pattern_error'] = 'Pattern does not match';

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

		$match = preg_match($this->params['pattern'], $value);

		if ($this->params['match'] && !$match)
		{

			// pattern doesn't match
			$error = $this->params['pattern_error'];

			return FALSE;

		} else if (!$this->params['match'] && $match)
		{

			// pattern matches
			$error = $this->params['pattern_error'];

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
	* Name    | Type   | Default | Required | Description
	* ------- | ------ | ------- | -------- | ------------
	* match   | bool   | TRUE    | no       | whether or not the pattern must match (TRUE) or must not match (FALSE)
	* pattern | string | n/a     | yes      | a regular expression pattern for preg_match
	*
	* Error Messages:
	*
	* Name          | Default
	* ------------- | ----------------------
	* pattern_error | Pattern does not match
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
