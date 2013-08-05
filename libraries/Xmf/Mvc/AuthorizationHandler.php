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
 * An AuthorizationHandler determines the method for authorizing a user's
 * action requests.
 *
 */
abstract class AuthorizationHandler extends ContextAware
{

    /**
     * Create a new AuthorizationHandler instance.
     *
     * @since  1.0
     */
    public function __construct ()
    {

    }

    /**
     * Determine the user authorization status for an action request.
     *
     *  _This should never be called manually._
     *
     * @param $action     An Action instance.
     *
     * @since  1.0
     */
    abstract public function execute (&$action);

}
