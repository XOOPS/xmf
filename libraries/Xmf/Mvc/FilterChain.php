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
 * FilterChain controls the sequence of Filter execution.
 *
 */
class FilterChain
{

    /**
     * The current index at which the chain is processing.
     *
     * @since  1.0
     * @type   int
     */
    protected $index;

    /**
     * An indexed array of filters.
     *

     * @since  1.0
     * @type   array
     */
    protected $filters;

    /**
     * Create a new FilterChain instance.
     *
     * @since  1.0
     */
    public function __construct ()
    {

        $this->index = -1;
        $this->filters = array();

    }

    /**
     * Execute the next filter in the chain.
     *
     *  _This method should never be called manually._
     *
     * @since  1.0
     */
    public function execute ()
    {

        if (++$this->index < sizeof($this->filters)) {

            $this->filters[$this->index]->execute($this);

        }

    }

    /**
     * Register a filter.
     *
     * @param $filter A Filter instance.
     *
     * @since  1.0
     */
    public function register (&$filter)
    {

        $this->filters[] =& $filter;

    }

}
