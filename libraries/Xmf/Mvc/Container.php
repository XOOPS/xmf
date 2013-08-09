<?php
/*
 * This file has its roots as part of the Mojavi package which was
 * Copyright (c) 2003 Sean Kerr. It has been incorporated into this
 * derivative work under the terms of the LGPL V2.1.
 * (license terms)
 */

namespace Xmf\Mvc;

/**
 * A Container provides storage for user data.
 *
 * @category  Xmf\Mvc\Container
 * @package   Xmf
 * @author    Richard Griffith <richard@geekwright.com>
 * @author    Sean Kerr
 * @copyright 2013 The XOOPS Project http://sourceforge.net/projects/xoops/
 * @copyright Portions Copyright (c) 2003 Sean Kerr
 * @license   http://www.fsf.org/copyleft/gpl.html GNU public license
 * @version   Release: 1.0
 * @since     1.0
 */
interface Container
{

    /**
     * Load user data.
     *
     * _This should never be called manually._
     *
     * @param bool  &$authenticated The authenticated status.
     * @param array &$attributes    An associative array of attributes.
     * @param mixed &$secure        Security related data.
     *
     * @return void
     */
    public function load (&$authenticated, &$attributes, &$secure);

    /**
     * Store user data.
     *
     * _This should never be called manually._
     *
     * @param bool  &$authenticated The authenticated status.
     * @param array &$attributes    An associative array of attributes.
     * @param mixed &$secure        Security related data.
     *
     * @return void
     */
    public function store (&$authenticated, &$attributes, &$secure);
}
