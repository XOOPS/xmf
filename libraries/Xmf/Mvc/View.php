<?php
/*
 * This file has its roots as part of the Mojavi package which was
 * Copyright (c) 2003 Sean Kerr. It has been incorporated into this
 * derivative work under the terms of the LGPL V2.1.
 * (license terms)
 */

namespace Xmf\Mvc;

/**
 * A View object is the presentation layer associated with an Action.
 *
 * @category  Xmf\Mvc\View
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
abstract class View extends ContextAware
{

    /**
     * Create a new View instance.
     *
     * @return void
     *
     * @since  1.0
     */
    public function __construct ()
    {

    }

    /**
     * Cleanup temporary view data.
     *
     * _This method should never be called manually._
     *
     * @return void
     *
     * @since  1.0
     */
    public function cleanup ()
    {

    }

    /**
     * Initialize common view parameters.
     *
     * _This method should never be called manually._
     *
     * @return bool true if successful
     *
     * @since  1.0
     */
    public function initialize ()
    {
        return true;
    }

    /**
     * Render the presentation.
     *
     * _This method should never be called manually._
     *
     * @return object A Renderer instance.
     *
     * @since  1.0
     */
    abstract public function & execute ();

}
