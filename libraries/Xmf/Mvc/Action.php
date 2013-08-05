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
 * All Action implementations must extend this class. An Action implementation
 * is used to execute business logic, which should be encapsulated in a model. A
 * model is a class that provides methods to manipulate data that is linked to
 * something, such as a database.
 *
 */
abstract class Action extends ContextAware
{

    /**
     * Create a new Action instance.
     *
     * @since  1.0
     */
    public function __construct ()
    {

    }

    /**
     * Execute all business logic.
     *
     *  _This method should never be called manually._
     *
     * @return mixed A single string value describing the view
     *   (Xmf\Mvc::VIEW_INPUT, Xmf\Mvc::VIEW_SUCCESS, Xmf\Mvc::VIEW_ERROR, etc.)
     *  or
     *   an indexed array coinciding with the following list:
     *    - *1st* index - module name
     *    - *2nd* index - action name
     *    - *3rd* index - view
     *
     * @since  1.0
     */
    abstract public function execute ();

    /**
     * Retrieve the default view.
     *
     * @return mixed see execute()
     *
     * @since  1.0
     */
    public function getDefaultView ()
    {
        return \Xmf\Mvc::VIEW_INPUT;

    }

    /**
     * Retrieve the privilege required to access this action.
     *
     * @return array An indexed array coinciding with the following list:
     *                  - *1st* index - privilege name
     *                  - *2nd* index - privilege namespace (optional)
     *
     * @see    isSecure()
     * @since  1.0
     */
    public function getPrivilege ()
    {
        return NULL;

    }

    /**
     * Retrieve the HTTP request method(s) this action will serve.
     *
     * @return int A request method that is one of, or a logical OR (|)
     *             combination of the following:
     *                 - Xmf\Mvc::REQ_GET  - serve GET requests
     *                 - Xmf\Mvc::REQ_POST - serve POST requests
     *
     * @since  1.0
     */
    public function getRequestMethods ()
    {
        return \Xmf\Mvc::REQ_GET | \Xmf\Mvc::REQ_POST;

    }

    /**
     * Handle a validation error.
     *
     * @return mixed see execute()
     *
     * @since  1.0
     */
    public function handleError ()
    {
        return \Xmf\Mvc::VIEW_ERROR;

    }

    /**
     * Execute action initialization procedure.
     *
     * @return bool TRUE if action initializes successfully, otherwise FALSE.
     *
     * @since  1.0
     */
    public function initialize ()
    {
        return TRUE;

    }

    /**
     * Determine if this action requires authentication.
     *
     * @return bool TRUE if this action requires authentication, otherwise FALSE.
     *
     * @since  1.0
     */
    public function isSecure ()
    {
        return FALSE;

    }

    /**
     * Register individual parameter validators.
     *
     *  _This method should never be called manually._
     *
     * @param $validatorManager A ValidatorManager instance.
     *
     * @since  1.0
     */
    public function registerValidators (&$validatorManager)
    {

    }

    /**
     * Validate the request as a whole.
     *
     *  _This method should never be called manually._
     *
     * @return bool TRUE if validation completes successfully, otherwise FALSE.
     *
     * @since  1.0
     */
    public function validate ()
    {
        return TRUE;

    }

}
