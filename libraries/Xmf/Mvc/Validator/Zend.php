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
 * Xmf\Mvc\Validator\Zend invokes a Zend framework validator
 *
 *  _This is a POC example only, and is not part of the mvc specification_
 *
 *  Adding this to the xmf composer.json and updating makes this possible:
 *
 * "require": {
 *     ...
 *     "zendframework/zend-validator" : "~2.2",
 *     "zendframework/zend-i18n" : "~2.2",
 *     "zendframework/zend-uri" : "~2.2"
 * }
 *
 * Then the $params to initialize could be specified like this to enable,
 * for example, a credit card validator:
 *
 * array('validator' => 'CreditCard')
 */
class Zend extends AbstractValidator
{

    private $_zvalidator;

    /**
     * Create a new Email Validator instance.
     *
     * @since 1.0
     */
    public function __construct ()
    {

        $this->_zvalidator = '';
        $this->params = array();

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
        $class = "Zend\\Validator\\" . $this->_zvalidator;
        if (class_exists($class,true)) {
            $validator = new $class($this->params);
            if (is_object($validator)) {
                if ($validator->isValid($value)) {
                    return true;
                } else {
                    $messages = $validator->getMessages();
                    $error = current($messages);

                    return false;
                }
            }
        }
        $error = 'Validator not found';

        return false;
    }

   /**
    * Initialize the validator. This is only required to override
    * the default error messages.
    *
    * Initialization Parameters:
    *
    * Name      | Type   | Default | Required | Description
    * --------- | ------ | ------- | -------- | ------------
    * validator | string | n/a     | yes      | Zend Validator to use
    * (key)     | mixed  | n/a     | n/a      | 'key'=>'value'
    *
    * Error Messages:
    *
    * Name        | Default
    * ----------- | --------
    * n/a         | as returned by validator
    *
    * @param array $params An associative array of initialization parameters.
    *
    * @since  1.0
    */
    public function initialize ($params)
    {
        $this->_zvalidator = '';
        $this->params = array();
        foreach ($params as $key => $value) {
            if (strcasecmp($key,'validator')===0) {
                $this->_zvalidator = $value;
            } else {
                $this->params[$key]=$value;
            }
        }
    }
}
