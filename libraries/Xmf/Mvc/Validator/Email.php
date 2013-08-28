<?php
/*
 * This file has its roots as part of the Mojavi package which was
 * Copyright (c) 2003 Sean Kerr. It has been incorporated into this
 * derivative work under the terms of the LGPL V2.1.
 * (license terms)
 */

namespace Xmf\Mvc\Validator;

/**
 * Email Validator verifies an email address has a correct format.
 *
 * @category  Xmf\Mvc\Validator\Email
 * @package   Xmf
 * @author    Richard Griffith <richard@geekwright.com>
 * @author    Sean Kerr <skerr@mojavi.org>
 * @copyright 2013 The XOOPS Project http://sourceforge.net/projects/xoops/
 * @copyright 2003 Sean Kerr
 * @license   GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @version   Release: 1.0
 * @link      http://xoops.org
 * @since     1.0
 */
class Email extends AbstractValidator
{

    /**
     * Create a new Email Validator instance.
     *
     * @since 1.0
     */
    public function __construct ()
    {
        parent::__construct();

        $this->params['email_error'] = 'Invalid email address';
        $this->params['max']         = -1;
        $this->params['max_error']   = 'Email address is too long';
        $this->params['min']         = -1;
        $this->params['min_error']   = 'Email address is too short';
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
        $value=trim($value);

        if (!\checkEmail($value)) { // use XOOPS function

            $error = $this->params['email_error'];

            return false;

        }

        $length = strlen($value);

        if ($this->params['min'] > -1 && $length < $this->params['min']) {

            $error = $this->params['min_error'];

            return false;

        }

        if ($this->params['max'] > -1 && $length > $this->params['max']) {

            $error = $this->params['max_error'];

            return false;

        }

        return true;

    }

    /**
     * Initialize the validator. This is only required to override
     * the default error messages.
     *
     * Initialization Parameters:
     *
     * Name | Type | Default | Required | Description
     * ---- | ---- | ------- | -------- | ------------
     * max  | int  | n/a     | no       | a maximum length
     * min  | int  | n/a     | no       | a minimum length
     *
     * Error Messages:
     *
     * Name        | Default
     * ----------- | --------
     * email_error | Invalid email address
     * max_error   | Email address is too long
     * min_error   | Email address is too short
     *
     * @param array $params An associative array of initialization parameters.
     *
     * @return void
     * @since  1.0
     */
    public function initialize ($params)
    {

        parent::initialize($params);

    }
}
