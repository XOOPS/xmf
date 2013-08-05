<?php

namespace Xmf\Mvc\Validator;

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
 * @package         Xmf\Mvc
 * @since           1.0
 */

/**
 * String provides a constraint on a parameter by making sure
 * the value matches required minimum and maximum lengths and contains
 * only allowable characters
 *
 */
class String extends AbstractValidator
{

    /**
     * Create a new Strings Validator instance.
     *
     * @since  1.0
     */
    public function __construct ()
    {

        $this->params['allowed']     = FALSE;
        $this->params['chars']       = array();
        $this->params['chars_error'] = 'Value contains an invalid character';
        $this->params['max']         = -1;
        $this->params['max_error']   = 'Value is too long';
        $this->params['min']         = -1;
        $this->params['min_error']   = 'Value is too short';
        $this->params['trim']        = TRUE;

    }

    /**
     * Execute this validator.
     *
     * @param string $value A user submitted parameter value.
     * @param string $error The error message variable to be set if an error occurs.
     *
     * @return bool TRUE if the validator completes successfully, otherwise FALSE.
     *
     * @since  1.0
     */
    public function execute (&$value, &$error)
    {

        $count = sizeof($this->params['chars']);

        if ($this->params['trim']) {

            $value = trim($value);

        }

        if (function_exists('mb_strlen')) {
            $length = mb_strlen($value,defined('_CHARSET')?constant('_CHARSET'):'UTF-8');
        } else {
            $length = strlen($value);
        }

        if ($this->params['min'] > -1 && $length < $this->params['min']) {

            $error = $this->params['min_error'];

            return FALSE;

        }

        if ($this->params['max'] > -1 && $length > $this->params['max']) {

            $error = $this->params['max_error'];

            return FALSE;

        }

        if ($count > 0) {

            for ($i = 0; $i < $length; $i++) {

                $found = FALSE;

                for ($x = 0; $x < $count; $x++) {

                    if ($value[$i] == $this->params['chars'][$x]) {

                        $found = TRUE;

                        break;

                    }

                }

                if (($this->params['allowed'] && !$found) ||
                    (!$this->params['allowed'] && $found))
                {

                    $error = $this->params['chars_error'];

                    return FALSE;

                }

            }

        }

        return TRUE;

    }

   /**
    * Initialize the validator. This is only required to override
    * the default error messages.
    *
    * Initialization Parameters:
    *
    * Name    | Type  | Default | Required | Description
    * ------- | ----- | ------- | -------- | -----------
    * allowed | bool  | FALSE   | yes      | does *chars* array contain allowed (TRUE) or disallowed (FALSE) values.
    * chars   | array | n/a     | yes      | an indexed array of characters
    * max     | int   | n/a     | no       | a maximum length
    * min     | int   | n/a     | no       | a minimum length
    * trim    | bool  | TRUE    | no       | whether or not to trim the value before comparison
    *
    * Error Messages:
    *
    * Name        | Default
    * ----------- | -------
    * chars_error | Value contains an invalid character
    * max_error   | Value is too long
    * min_error   | Value is too short
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
