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
 * Lookup Validator provides a constraint on a parameter by making sure
 * the value is found in the specified table.
 *
 */
class Lookup extends AbstractValidator
{

    /**
     * Create a new Lookup Validator instance.
     *
     * @since  1.0
     */
    public function __construct ()
    {

        parent::__construct();

        $this->params['lookup_column'] = '';
        $this->params['lookup_table']  = '';
        $this->params['lookup_error']  = 'Lookup failed';

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
        global $xoopsDB;

        $found = FALSE;

        if (empty($this->params['lookup_table'])) {
            $error = 'lookup_table parameter is not set';

            return FALSE;
        }

        if (empty($this->params['lookup_column'])) {
            $error = 'lookup_column parameter is not set';

            return false;
        }

        $table=mysql_real_escape_string($this->params['lookup_table']);
        $column=mysql_real_escape_string($this->params['lookup_column']);

        $sql='SELECT count(*) as cnt FROM `' . $xoopsDB->prefix($table) . '`';
        $sql.= ' WHERE ' . $column . ' = ' . $xoopsDB->quoteString($value);

        $count=0;
        $result = $xoopsDB->query($sql);
        if ($result) {
            list ($count) = $xoopsDB->fetchRow($result);
        }

        if ($count==0) {
            $error = $this->params['lookup_error'];

            return false;
        }

        return true;

    }

   /**
    * Initialize the validator.
    *
    * Initialization Parameters:
    *
    * Name          | Type   | Default | Required | Description
    * ------------- | ------ | ------- | -------- | -----------
    * lookup_table  | string | n/a     | yes      | database table for lookup
    * lookup_column | string | n/a     | yes      | column to match value in lookup_table
    *
    * Error Messages:
    *
    * Name          | Default
    * ------------- | -------
    * lookup_error  | Lookup failed
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
