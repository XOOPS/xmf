<?php
/*
 * This file has its roots as part of the Mojavi package which was
 * Copyright (c) 2003 Sean Kerr. It has been incorporated into this
 * derivative work under the terms of the LGPL V2.1.
 * (license terms)
 */

namespace Xmf\Mvc\Validator;

/**
 * Confirm Validator provides a constraint on a parameter by ensuring
 * the value is equal to another parameters value. This is useful for
 * double entry confirmation for email addresses, account numbers, etc.
 *
 * @category  Xmf\Mvc\Validator\Confirm
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
class Confirm extends AbstractValidator
{

    /**
     * Create a new Confirm Validator instance.
     *
     * @since  1.0
     */
    public function __construct ()
    {
        parent::__construct();

        $this->params['confirm']       = '';
        $this->params['confirm_error'] = 'Does not match';
        $this->params['sensitive']     = true;
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
        global $xoopsDB;

        $found = false;

        $confirm = $this->Request()->getParameter($this->params['confirm']);

        if ($this->params['sensitive']) {
            $confirmed=(strcmp($value, $confirm)===0);
        } else {
            $confirmed=(strcasecmp($value, $confirm)===0);
        }

        if (!$confirmed) {
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
     * @return void
     * @since  1.0
     */
    public function initialize ($params)
    {
        if (is_array($params)) {
            parent::initialize($params);
        } else {
            $this->params['confirm']=$params;
        }
    }
}
