<?php
/*
 * This file has its roots as part of the Mojavi package which was
 * Copyright (c) 2003 Sean Kerr. It has been incorporated into this
 * derivative work under the terms of the LGPL V2.1.
 * (license terms)
 */

namespace Xmf\Mvc\Validator;

/**
 * StripValidator strips characters from a parameter.
 *
 * @category  Xmf\Mvc\Validator\Strip
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
class Strip extends AbstractValidator
{

    /**
     * Create a new Strip Validator instance.
     *
     * @since  1.0
     */
    public function __construct ()
    {
        parent::__construct();

        $this->params['chars'] = array();
    }

    /**
     * Execute this validator.
     *
     * @param string &$value A user submitted parameter value.
     * @param string &$error The error message variable to be set if an error occurs.
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

        return true;
    }

    /**
     * Initialize the validator. This is only required to override
     * the default error messages.
     *
     * Initialization Parameters:
     *
     * Name    | Type  | Default | Required | Description
     * ------- | ----- | ------- | -------- | -----------
     * chars   | array | n/a     | yes      | indexed array of characters strip
     *
     * Error Messages:
     *
     * _none_ - this validator cannot fail
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
