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
 * ChoiceValidator provides a constraint on a parameter by making sure
 * the value is or is not allowed in a list of choices.
 *
 */
class Xmf_Mvc_Validator_Choice extends Xmf_Mvc_Validator
{

	/**
	 * Create a new Choice Validator instance.
	 *
	 * @since  1.0
	 */
	function __construct ()
	{

		parent::__construct();

		$this->params['choices']       = array();
		$this->params['choices_error'] = 'Invalid value';
		$this->params['sensitive']     = FALSE;
		$this->params['valid']         = TRUE;

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

		$found = FALSE;

		if (!$this->params['sensitive'])
		{

			$newValue = strtolower($value);

		} else
		{

			$newValue =& $value;

		}

		// is the value in our choices list?
		if (in_array($newValue, $this->params['choices']))
		{

			$found = TRUE;

		}

		if (($this->params['valid'] && !$found) ||
			(!$this->params['valid'] && $found))
		{

			$error = $this->params['choices_error'];

			return FALSE;

		}

		return TRUE;

	}

   /**
	* Initialize the validator.
	*
	* Initialization Parameters:
	*
	* Name      | Type  | Default | Required | Description
	* --------- | ----- | ------- | -------- | -----------
	* choices   | array | n/a     | yes      | an indexed array choices
	* sensitive | bool  | FALSE   | no       | whether or not the choices are case-sensitive
	* valid     | bool  | TRUE    | no       | whether or not list of choices contains valid or invalid values
	*
	* Error Messages:
	*
	* Name          | Default
	* ------------- | -------
	* choices_error | Invalid value
	*
	* @param array $params An associative array of initialization parameters.
	*
	* @since  1.0
	*/
	function initialize ($params)
	{

		parent::initialize($params);

		if ($this->params['sensitive'] == FALSE)
		{

			// strtolower all choices
			$count = sizeof($this->params['choices']);

			for ($i = 0; $i < $count; $i++)
			{

				$this->params['choices'][$i] = strtolower($this->params['choices'][$i]);

			}


		}

	}

}

?>
