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
 * A View object is the presentation layer associated with an Action.
 *
 */
abstract class View extends ContextAware
{

    /**
     * Create a new View instance.
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
     * @return Renderer A Renderer instance.
     *
     * @since  1.0
     */
    abstract public function & execute ();

}
