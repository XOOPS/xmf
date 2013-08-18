<?php
/*
 * This file has its roots as part of the Mojavi package which was
 * Copyright (c) 2003 Sean Kerr. It has been incorporated into this
 * derivative work under the terms of the LGPL V2.1.
 * (license terms)
 */

namespace Xmf\Mvc\Validator;

/**
 * Lookup Validator provides a constraint on a parameter by making sure
 * the value is found in the specified table.
 *
 * @category  Xmf\Mvc\Validator\Lookup
 * @package   Xmf
 * @author    Richard Griffith <richard@geekwright.com>
 * @author    Sean Kerr <skerr@mojavi.org>
 * @copyright 2013 The XOOPS Project http://sourceforge.net/projects/xoops/
 * @copyright 2003 Sean Kerr
 * @license   http://www.fsf.org/copyleft/gpl.html GNU public license
 * @version   Release: 1.0
 * @link      http://xoops.org
 * @since     1.0
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
        $this->params['table_error'] = 'lookup_table parameter is invalid';
        $this->params['column_error'] = 'lookup_column parameter is invalid';
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

        $table = $this->_cleanName($this->params['lookup_table']);
        $column = $this->_cleanName($this->params['lookup_column']);

        if (empty($table)) {
            $error = $this->params['table_error'];

            return false;
        }

        if (empty($column)) {
            $error = $this->params['column_error'];

            return false;
        }

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
     * Clean database names
     *
     * @param string $name A table name
     *
     * @return string cleaned name
     */
    private function _cleanName($name)
    {
        $name = trim($name);
        if (preg_match('#^[a-zA-Z0-9._]*$#i', $name)) {
            return $name;
        } else {
            return null; // Contains illegal characters
        }
    }

    /**
     * Initialize the validator.
     *
     * Initialization Parameters:
     *
     * Name          | Type   | Default | Required | Description
     * ------------- | ------ | ------- | -------- | -----------
     * lookup_table  | string | n/a     | yes      | database table name
     * lookup_column | string | n/a     | yes      | column to match value
     *
     * Error Messages:
     *
     * Name          | Default
     * ------------- | -------
     * lookup_error  | Lookup failed
     * table_error   | lookup_table parameter is invalid
     * column_error  | lookup_column parameter is invalid
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
