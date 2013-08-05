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
 * Clean validator cleans parameter of various nastiness and conforms
 * it to a specified type
 *
 */
class Clean extends AbstractValidator
{

    /**
     * Create a new Clean validator instance.
     *
     * @since  1.0
     */
    public function __construct ()
    {

        $this->params['type'] = 'default';

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

        $value = trim($value);
        $value = \Xmf\Filter\Input::clean($value,$this->params['type']);

        return TRUE;

    }

   /**
    * Initialize the validator. This is only required to override
    * the default error messages.
    *
    * Initialization Parameters:
    *
    * Name    | Type   | Default | Required | Description
    * ------- | ------ | ------- | -------- | -----------
    * chars   | string | default | no       | type for Xmf\Filter\Input::clean()
    *
    * Error Messages:
    *
    * _none_ - this validator cannot fail
    *
    * @param mixed $params An associative array of initialization parameters,
    *                      or a scalar type string for Xmf\Filter\Input::clean()
    *
    * @since  1.0
    */
    public function initialize ($params)
    {
        if (is_array($params)) {
            parent::initialize($params);
        } else {
            $this->params['type']=$params;
        }

    }
}
