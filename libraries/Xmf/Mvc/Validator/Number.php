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
 * NumberValidator verifies a parameter contains only numeric characters and can
 * be constrained with minimum and maximum values.
 *
 */
class Xmf_Mvc_Validator_Number extends Xmf_Mvc_Validator
{

	/**
	 * Create a new Number Validator instance.
	 *
	 * @since  1.0
	 */
	public function __construct ()
	{

		$this->params['max']          = -1;
		$this->params['max_error']    = 'Value is too high';
		$this->params['min']          = -1;
		$this->params['min_error']    = 'Value is too low';
		$this->params['number_error'] = 'Value is not numeric';
		$this->params['strip']        = TRUE;

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

		if ($this->params['strip'])
		{

			$value = preg_replace('/[^0-9\.\-]*/', '', $value);
			if($value!='') {
				$value = Xmf_Filter_Input::clean($value,'float') . '';
			}

		}

		if (!is_numeric($value))
		{

			$error = $this->params['number_error'];

			return FALSE;

		}

		if ($this->params['min'] > -1 && $value < $this->params['min'])
		{

			$error = $this->params['min_error'];

			return FALSE;

		}

		if ($this->params['max'] > -1 && $value > $this->params['max'])
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
	* Name  | Type    | Default | Required | Description
	* ----- | ------- | ------- | -------- | --------------------
	* max   | int     | n/a     | no       | a maximum value
	* min   | int     | n/a     | no       | a minimum value
	* strip | boolean | true    | no       | strip non-numeric characters other than periods and hypens
	*
	* Error Messages:
	*
	* Name         | Default
	* ------------ | --------------
	* max_error    | Value is too high</td>
	* min_error    | Value is too low</td>
	* number_error | Value is not numeric</td>
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
