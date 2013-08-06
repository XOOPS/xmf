<?php

namespace Xmf\Mvc;

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
 * A ValidatorManager provides the mechanism to register specific
 * validator objects, the contolling properties for those objects and
 * the input parameters to be validated. The ValidatorManager also
 * provides the method to execute the registered validations.
 *
 * The ExecutionFilter establishes a ValidatorManager, and invokes the
 * Action::registerValidators() method to establish the validations
 * to be performed.
 *
 */
class ValidatorManager extends ContextAware
{

    /**
     * An associative array of parameter validators.
     *
     * @since  1.0
     * @type   array
     */
    protected $validators;

    /**
     * Create a new ValidatorManager instance.
     *
     * @since  1.0
     */
    public function __construct ()
    {

        $this->validators = array();

    }

    /**
     * Execute all validators.
     *
     * _This method should never be called manually._
     *
     * @since  1.0
     */
    public function execute ()
    {

        $keys    = array_keys($this->validators);
        $count   = sizeof($keys);
        $success = TRUE;

        for ($i = 0; $i < $count; $i++) {

            $param    =  $keys[$i];
            $value    =& $this->Request()->getParameter($param);
            $required =  $this->validators[$param]['required'];

            if (isset($this->validators[$param]['validators'])) {

                // loop through each validator for this parameter
                $error    = NULL;
                $subCount = sizeof($this->validators[$param]['validators']);

                for ($x = 0; $x < $subCount; $x++) {

                    $validator =& $this->validators[$param]['validators'][$x];

                    if (!$validator->execute($value, $error)) {

                        if ($validator->getErrorMessage() == NULL) {

                            $this->Request()->setError($param, $error);

                        } else {

                            $this->Request()->setError($param,
                                               $validator->getErrorMessage());

                        }

                        $success = FALSE;

                        break;

                    }

                }
            }

            if ($required && ($value == NULL || (is_string($value) && strlen($value) == 0) || (is_array($value) && count($value)))) {
                //var_dump($value);
                // param is required but doesn't exist
                $message = $this->validators[$param]['message'];

                $this->Request()->setError($param, $message);

                $success = FALSE;

            }

        }

        return $success;

    }

    /**
     * Register a validator.
     *
     * @param string $param     A parameter name to be validated.
     * @param        $validator A Validator instance.
     *
     * @since  1.0
     */
    public function register ($param, &$validator)
    {

        if (!isset($this->validators[$param])) {

            $this->validators[$param] = array();

        }

        if (!isset($this->validators[$param]['validators'])) {

            $this->validators[$param]['validators'] = array();

        }

        // add this validator to the list for this parameter
        $this->validators[$param]['validators'][] =& $validator;

        // if a required status has not yet been specified, set one.
        if (!isset($this->validators[$param]['required'])) {

            $this->setRequired($param, false);

        }

    }

    /**
     * Set the required status of a parameter.
     *
     * @param string $name     A parameter name.
     * @param bool   $required The required status.
     * @param string $message  Error message to be set if the parameter
     *                          has not been sent or has a length of 0.
     *
     * @since  1.0
     */
    public function setRequired ($name, $required=true, $message = null)
    {

        if (!isset($this->validators[$name])) {

            $this->validators[$name] = array();

        }

        $this->validators[$name]['required'] = $required;
        $this->validators[$name]['message']  = empty($message)?'Required':$message;

    }

    /**
     * Add a complete validation
     *
     * @param string $name          A parameter name.
     * @param string $validatorName The name of the validator class (minus Xmf\Mvc\Validator_)
     * @param mixed  $initParms     $params for a Xmf\Mvc\Validator::initialize()
     *
     * @since  1.0
     */

    public function addValidation($name, $validatorName, $initParms=null)
    {
        $validatorClass = '\Xmf\Mvc\Validator\\'.$validatorName;
        if (class_exists($validatorClass)) {
            $validator = new $validatorClass;
            if (!empty($initParms)) {
                $validator->initialize($initParms);
            }
            $this->register($name, $validator);
        } else {
            trigger_error("Class \"$validatorClass\" was not found", E_USER_WARNING);
        }
    }

}
