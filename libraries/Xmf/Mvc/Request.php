<?php
/*
 * This file has its roots as part of the Mojavi package which was
 * Copyright (c) 2003 Sean Kerr. It has been incorporated into this
 * derivative work under the terms of the LGPL V2.1.
 * (license terms)
 */

namespace Xmf\Mvc;

/**
 * The Request object hold data related to a request including the
 * parameters (user/web input) established by the Controller as well
 * as attributes and error messages established by the action as the
 * request is proccessed. Request also provides methods for accessing
 * and managing the request data.
 *
 * @category  Xmf\Mvc\Request
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
class Request
{

    /**
     * An associative array of attributes.
     *
     * @since  1.0
     * @type   array
     */
    protected $attributes;

    /**
     * An associative array of errors.
     *
     * @since  1.0
     * @type   array
     */
    protected $errors;

    /**
     * The request method used to make this request.
     *
     * @since  1.0
     * @type   int
     */
    protected $method;

    /**
     * An associative array of user submitted parameters.
     *
     * @since  1.0
     * @type   array
     */
    protected $params;

    /**
     * Create a new Request instance.
     *
     * @param array &$params A parsed array of user submitted parameters.
     *
     * @since  1.0
     */
    public function __construct (&$params)
    {
        $this->attributes =  array();
        $this->errors     =  array();
        $this->method     = ($_SERVER['REQUEST_METHOD'] == 'POST')
                            ? \Xmf\Mvc::REQ_POST : \Xmf\Mvc::REQ_GET;
        $this->params     =& $params;
    }

    /**
     * Retrieve an attribute.
     *
     * @param string $name An attribute name.
     *
     * @return mixed An attribute value, if the given attribute exists,
     *               otherwise NULL.
     *
     * @since  1.0
     */
    public function & getAttribute ($name)
    {
        if (isset($this->attributes[$name])) {
            return $this->attributes[$name];
        }
        $null=null;

        return $null;
    }

    /**
     * Retrieve an indexed array of attribute names.
     *
     * @return array An array of attribute names.
     *
     * @since  1.0
     */
    public function getAttributeNames ()
    {
        return array_keys($this->attributes);
    }

    /**
     * Retrieve an associative array of all attributes.
     *
     * @return array An array of attributes.
     *
     * @return void
     * @since  1.0
     */
    public function & getAttributes ()
    {
        return $this->attributes;
    }

    /**
     * Retrieve a cookie.
     *
     * @param string $name A cookie name.
     *
     * @return string A cookie value, if the cookie exists, otherwise NULL.
     *
     * @since  1.0
     */
    public function & getCookie ($name)
    {
        if (isset($_COOKIE[$name])) {
            return $_COOKIE[$name];
        }
        $null=null;

        return $null;
    }

    /**
     * Retrieve an indexed array of cookie names.
     *
     * @return array An array of cookie names.
     *
     * @since  1.0
     */
    public function getCookieNames ()
    {
        return array_keys($_COOKIE);
    }

    /**
     * Retrieve an associative array of cookies.
     *
     * @return array An array of cookies.
     *
     * @since  1.0
     */
    public function & getCookies ()
    {
        return $_COOKIE;
    }

    /**
     * Retrieve an error message.
     *
     * @param string $name The name under which the message has been
     *                     registered. If the error is validation related,
     *                     it will be registered under a parameter name.
     *
     * @return string An error message if a validation error occured for
     *                      a parameter or was manually set, otherwise NULL.
     *
     * @since  1.0
     */
    public function getError ($name)
    {
        return (isset($this->errors[$name])) ? $this->errors[$name] : null;
    }

    /**
     * Retrieve an associative array of errors.
     *
     * @return array An array of errors, if any errors occured during validation
     *               or were manually set by the developer, otherwise NULL.
     *
     * @since  1.0
     */
    public function & getErrors ()
    {
        return $this->errors;
    }

    /**
     * Retrieve errors as an HTML string
     *
     * @param string $name_like restrict output to only errors with a name
     *                          starting with this string
     * @param string $joiner    used between multiple errors to build one string
     *
     * @return string HTML representation of errors
     *
     * @since  1.0
     */
    public function & getErrorsAsHtml ($name_like='',$joiner='<br />')
    {
        $erroroutput = null;
        if ($this->hasErrors()) {
            $errors = $this->getErrors();
            $erroroutput = '';
            foreach ($errors as $k => $v) {
                if (empty($name_like)) {
                    $erroroutput .= (empty($erroroutput)?'':$joiner) . $k.':'.$v;
                } else {
                    if (substr($k, 0, strlen($name_like))==$name_like) {
                        $erroroutput
                            .= (empty($erroroutput)?'':$joiner)
                            . substr($k, strlen($name_like)) . ':' . $v;
                    }
                }
            }
        }

        return $erroroutput;
    }

    /**
     * Retrieve the request method used for this request.
     *
     * @return int A request method that is one of the following:
     * - Xmf\Mvc::REQ_GET  - serve GET requests
     * - Xmf\Mvc::REQ_POST - serve POST requests
     *
     * @since  1.0
     */
    public function getMethod ()
    {
        return $this->method;
    }

    /**
     * Retrieve a user submitted parameter.
     *
     * @param string $name  A parameter name.
     * @param mixed  $value A default value.
     *
     * @return mixed A parameter value, if the given parameter exists,
     *               otherwise NULL.
     *
     * @since  1.0
     */
    public function & getParameter ($name, $value = null)
    {
        if (isset($this->params[$name])) {
            return $this->params[$name];

        } else {
            return $value;
        }
    }

    /**
     * Retrieve an indexed array of user submitted parameter names.
     *
     * @return array An array of parameter names.
     *
     * @since  1.0
     */
    public function getParameterNames ()
    {
        return array_keys($this->params);
    }

    /**
     * Retrieve an associative array of user submitted parameters.
     *
     * @return array An array of parameters.
     *
     * @since  1.0
     */
    public function & getParameters ()
    {
        return $this->params;
    }

    /**
     * Determine if an attribute exists.
     *
     * @param string $name An attribute name.
     *
     * @return bool TRUE if the given attribute exists, otherwise FALSE.
     *
     * @since  1.0
     */
    public function hasAttribute ($name)
    {
        return isset($this->attributes[$name]);
    }

    /**
     * Determine if a cookie exists.
     *
     * @param string $name A cookie name.
     *
     * @return bool TRUE if the given cookie exists, otherwise FALSE.
     *
     * @since  1.0
     */
    public function hasCookie ($name)
    {
        return isset($_COOKIE[$name]);
    }

    /**
     * Determine if an error has been set.
     *
     * @param string $name The name under which the message has been registered.
     *                      If the error is validation related, it will be
     *                      registered under a parameter name.
     *
     * @return bool TRUE if an error is set for the key, otherwise FALSE.
     *
     * @since  1.0
     */
    public function hasError ($name)
    {
        return isset($this->errors[$name]);
    }

    /**
     * Determine if any error has been set.
     *
     * @return bool TRUE if any error has been set, otherwise FALSE.
     *
     * @since  1.0
     */
    public function hasErrors ()
    {
        return (sizeof($this->errors) > 0);
    }

    /**
     * Determine if the request has a parameter.
     *
     * @param string $name A parameter name.
     *
     * @return bool TRUE if the given parameter exists, otherwise FALSE.
     *
     * @since  1.0
     */
    public function hasParameter ($name)
    {
        return isset($this->params[$name]);
    }

    /**
     * Remove an attribute.
     *
     * @param string $name An attribute name.
     *
     * @return mixed An attribute value, if the given attribute exists and has
     *               been removed, otherwise NULL.
     *
     * @since  1.0
     */
    public function & removeAttribute ($name)
    {
        if (isset($this->attributes[$name])) {
            $value =& $this->attributes[$name];
            unset($this->attributes[$name]);

            return $value;
        }
    }

    /**
     * Remove a parameter.
     *
     * @param string $name A parameter name.
     *
     * @return mixed A parameter value, if the given parameter exists and has
     *               been removed, otherwise NULL.
     *
     * @since  1.0
     */
    public function & removeParameter ($name)
    {
        if (isset($this->params[$name])) {
            $value =& $this->params[$name];
            unset($this->params[$name]);

            return $value;
        }
    }

    /**
     * Set an attribute.
     *
     * @param string $name  An attribute name.
     * @param mixed  $value An attribute value.
     *
     * @return void
     * @since  1.0
     */
    public function setAttribute ($name, $value)
    {
        $this->attributes[$name] =& $value;
    }

    /**
     * Set an attribute by reference.
     *
     * @param string $name   An attribute name.
     * @param mixed  &$value An attribute value.
     *
     * @return void
     * @since  1.0
     */
    public function setAttributeByRef ($name, &$value)
    {
        $this->attributes[$name] =& $value;
    }

    /**
     * Set an error message.
     *
     * @param string $name    The name under which to register the message.
     * @param string $message An error message.
     *
     * @return void
     * @since  1.0
     */
    public function setError ($name, $message)
    {
        $this->errors[$name] =& $message;
    }

    /**
     * Set multiple error messages.
     *
     * @param array $errors An associative array of error messages.
     *
     * @return void
     * @since  1.0
     */
    public function setErrors ($errors)
    {
        $keys  = array_keys($errors);
        $count = sizeof($keys);

        for ($i = 0; $i < $count; $i++) {
            $this->errors[$keys[$i]] = $errors[$keys[$i]];
        }
    }

    /**
     * Set the request method.
     *
     * @param int $method A request method that is one of the following:
     * - Xmf\Mvc::REQ_GET  - serve GET requests
     * - Xmf\Mvc::REQ_POST - serve POST requests
     *
     * @return void
     * @since  1.0
     */
    public function setMethod ($method)
    {
        $this->method = $method;
    }

    /**
     * Manually set a parameter.
     *
     * @param string $name  A parameter name.
     * @param mixed  $value A parameter value.
     *
     * @return void
     * @since  1.0
     */
    public function setParameter ($name, $value)
    {
        $this->params[$name] = $value;
    }

    /**
     * Manually set a parameter by reference.
     *
     * @param string $name   A parameter name.
     * @param mixed  &$value A parameter value.
     *
     * @return void
     * @since  1.0
     */
    public function setParameterByRef ($name, &$value)
    {
        $this->params[$name] =& $value;
    }

}
