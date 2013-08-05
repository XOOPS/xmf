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
 * StripValidator strips characters from a parameter.
 *
 */
class Strip extends AbstractValidator
{

    /**
     * Create a new Strip Validator instance.
     *
     * @since  1.0
     */
    public function __construct ()
    {

        $this->params['chars'] = array();

    }

    /**
     * Execute this validator.
     *
     * @param string $value A user submitted parameter value.
     * @param string $error The error message variable to be set if an error occurs.
     *
     * @return bool always returns TRUE
     *
     * @since  1.0
     */
    public function execute (&$value, &$error)
    {

        $length = strlen($value);
        $newval = '';

        for ($i = 0; $i < $length; $i++) {

            if (!in_array($value{$i}, $this->params['chars'])) {

                $newval .= $value{$i};

            }

        }

        $value = $newval;

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
    * chars   | array | n/a     | yes      | an indexed array of characters to be stripped
    *
    * Error Messages:
    *
    * _none_ - this validator cannot fail
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
