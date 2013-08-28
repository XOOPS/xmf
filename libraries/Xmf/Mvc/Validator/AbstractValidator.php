<?php
/*
 * This file has its roots as part of the Mojavi package which was
 * Copyright (c) 2003 Sean Kerr. It has been incorporated into this
 * derivative work under the terms of the LGPL V2.1.
 * (license terms)
 */

namespace Xmf\Mvc\Validator;

/**
 * A Validator is an object which validates a user submitted parameter
 * conforms to specific rules. It can also modify parameter values,
 * providing input filtering capabilities.
 *
 * @category  Xmf\Mvc\Validator\AbstractValidator
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
abstract class AbstractValidator extends \Xmf\Mvc\ContextAware
{

    /**
     * The default error message for any occuring error.
     *
     * @since  1.0
     * @type   string
     */
    protected $message;

    /**
     * An associative array of initialization parameters.
     *
     * @since  1.0
     * @type   array
     */
    protected $params;

    /**
     * Create a new Validator instance.
     *
     * @since  1.0
     */
    public function __construct ()
    {
        $this->message = null;
        $this->params  = array();
    }

    /**
     * Execute the validator.
     *
     *  _This method should never be called manually._
     *
     * @param string &$value A user submitted parameter value.
     * @param string &$error error message variable to set if an error occurs.
     *
     * @return bool TRUE if the validator completes successfully, otherwise FALSE.
     *
     * @since  1.0
     */
    abstract public function execute(&$value, &$error);

    /**
     * Retrieve the default error message.
     *
     * This will return NULL unless an error message has been
     * specified with setErrorMessage()
     *
     * @return string An error message.
     *
     * @since  1.0
     */
    public function getErrorMessage()
    {
        return $this->message;
    }

    /**
     * Retrieve a parameter.
     *
     * @param string $name A parameter name.
     *
     * @return mixed parameter value if parameter exists, otherwise NULL
     *
     * @since  1.0
     */
    public function & getParameter($name)
    {
        if (isset($this->params[$name])) {
            return $this->params[$name];
        }

        return null;
    }

    /**
     * Initialize the validator.
     *
     * @param array $params An associative array of initialization parameters.
     *
     * @return void
     * @since  1.0
     */
    public function initialize($params)
    {
        $this->params = array_merge($this->params, $params);
    }

    /**
     * Set the default error message for any occuring error.
     *
     * @param string $message An error message.
     *
     * @return void
     * @since  1.0
     */
    public function setErrorMessage($message)
    {
        $this->message = $message;
    }

    /**
     * Set a validator parameter.
     *
     * @param string $name  A parameter name.
     * @param mixed  $value A parameter value.
     *
     * @return void
     * @since  1.0
     */
    public function setParameter($name, $value)
    {
        $this->params[$name] = $value;
    }

    /**
     * Set a validator parameter by reference.
     *
     * @param string $name   A parameter name.
     * @param mixed  &$value A parameter value.
     *
     * @return void
     * @since  1.0
     */
    public function setParameterByRef($name, &$value)
    {
        $this->params[$name] =& $value;
    }
}
