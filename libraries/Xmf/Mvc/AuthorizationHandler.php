<?php
/*
 * This file has its roots as part of the Mojavi package which was
 * Copyright (c) 2003 Sean Kerr. It has been incorporated into this
 * derivative work under the terms of the LGPL V2.1.
 * (license terms)
 */

namespace Xmf\Mvc;

/**
 * An AuthorizationHandler determines the method for authorizing a user's
 * action requests.
 *
 * @category  Xmf\Mvc\AuthorizationHandler
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
abstract class AuthorizationHandler extends ContextAware
{

    /**
     * Create a new AuthorizationHandler instance.
     */
    public function __construct ()
    {

    }

    /**
     * Determine the user authorization status for an action request.
     *
     *  _This should never be called manually._
     *
     * @param object &$action An Action instance.
     *
     * @return bool true if authorized, false otherwise
     */
    abstract public function execute(&$action);
}
