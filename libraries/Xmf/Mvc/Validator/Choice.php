<?php
/*
 * This file has its roots as part of the Mojavi package which was
 * Copyright (c) 2003 Sean Kerr. It has been incorporated into this
 * derivative work under the terms of the LGPL V2.1.
 * (license terms)
 */

namespace Xmf\Mvc\Validator;

/**
 * ChoiceValidator provides a constraint on a parameter by making sure
 * the value is or is not allowed in a list of choices.
 *
 * @category  Xmf\Mvc\Validator\Choice
 * @package   Xmf
 * @author    Richard Griffith <richard@geekwright.com>
 * @author    Sean Kerr <skerr@mojavi.org>
 * @copyright 2013 The XOOPS Project http://sourceforge.net/projects/xoops/
 * @copyright 2003 Sean Kerr
 * @license   GNU GPL 2 or later (http://www.gnu.org/licenses/old-licenses/gpl-2.0.html)
 * @version   Release: 1.0
 * @link      http://xoops.org
 * @since     1.0
 */
class Choice extends AbstractValidator
{

    /**
     * Create a new Choice Validator instance.
     *
     * @since  1.0
     */
    public function __construct ()
    {
        parent::__construct();

        $this->params['choices']       = array();
        $this->params['choices_error'] = 'Invalid value';
        $this->params['sensitive']     = false;
        $this->params['valid']         = true;
    }

    /**
     * Execute this validator.
     *
     * @param string &$value A user submitted parameter value.
     * @param string &$error The error message variable to be set if an error occurs.
     *
     * @return bool TRUE if the validator completes successfully, otherwise FALSE.
     *
     * @since  1.0
     */
    public function execute (&$value, &$error)
    {
        $found = false;

        if (!$this->params['sensitive']) {
            $newValue = strtolower($value);
        } else {
            $newValue =& $value;
        }

        // is the value in our choices list?
        if (in_array($newValue, $this->params['choices'])) {
            $found = true;
        }

        if (($this->params['valid'] && !$found)
            || (!$this->params['valid'] && $found)
        ) {
            $error = $this->params['choices_error'];

            return false;
        }

        return true;
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
     * @return void
     * @since  1.0
     */
    public function initialize ($params)
    {

        parent::initialize($params);

        if ($this->params['sensitive'] == false) {

            // strtolower all choices
            $count = sizeof($this->params['choices']);

            for ($i = 0; $i < $count; $i++) {

                $this->params['choices'][$i] = strtolower($this->params['choices'][$i]);

            }

        }

    }
}
