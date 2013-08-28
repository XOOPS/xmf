<?php
/*
 * This file has its roots as part of the Mojavi package which was
 * Copyright (c) 2003 Sean Kerr. It has been incorporated into this
 * derivative work under the terms of the LGPL V2.1.
 * (license terms)
 */

namespace Xmf\Mvc;

/**
 * ExecutionChain is a list of actions to be performed
 * The Controller establishes the ExecutionChain, while the
 * ExecutionFilter processes the chain.
 *
 * The Execution chain allows access to the state of all executed
 * actions resulting from a request.
 *
 * @category  Xmf\Mvc\ExecutionChain
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
class ExecutionChain
{

    /**
     * An indexed array of executed actions.
     *
     * @since  1.0
     * @type   array
     */
    protected $chain;

    /**
     * Create a new ExecutionChain instance.
     *
     * @since  1.0
     */
    public function __construct ()
    {
        $this->chain = array();
    }

    /**
     * Add an action request to the chain.
     *
     * @param string $unitName A unit name.
     * @param string $actName  An action name.
     * @param string &$action  An Action instance.
     *
     * @return void
     * @since  1.0
     */
    public function addRequest ($unitName, $actName, &$action)
    {
        $this->chain[] = array('unit_name'   => $unitName,
                               'action_name' => $actName,
                               'action'      => &$action,
                               'microtime'   => microtime());
    }

    /**
     * Retrieve the Action instance at the given index.
     *
     * @param int $index The index from which you're retrieving.
     *
     * @return Action An Action instance, if the given index exists and
     *                the action was executed, otherwise NULL.
     *
     * @since  1.0
     */
    public function & getAction ($index)
    {
        if (sizeof($this->chain) > $index && $index > -1) {
            return $this->chain[$index]['action'];
        }
        $null=null;

        return $null;
    }

    /**
     * Retrieve the action name associated with the request at the given index.
     *
     * @param int $index The index from which you're retrieving.
     *
     * @return string An action name, if the given index exists, otherwise NULL.
     *
     * @since  1.0
     */
    public function getActionName ($index)
    {

        if (sizeof($this->chain) > $index && $index > -1) {
            return $this->chain[$index]['action_name'];
        }

        return null;
    }

    /**
     * Retrieve the unit name associated with the request at the given index.
     *
     * @param int $index The index from which you're retrieving.
     *
     * @return string A unit name if the given index exists, otherwise NULL.
     *
     * @since  1.0
     */
    public function getUnitName ($index)
    {
        if (sizeof($this->chain) > $index && $index > -1) {
            return $this->chain[$index]['unit_name'];
        }

        return null;
    }

    /**
     * Retrieve a request and its associated data.
     *
     * @param int $index The index from which you're retrieving.
     *
     * @return array An associative array of information about an action
     *               request if the given index exists, otherwise NULL.
     *
     * @since  1.0
     */
    public function & getRequest ($index)
    {
        if (sizeof($this->chain) > $index && $index > -1) {
            return $this->chain[$index];
        }
        $null=null;

        return $null;
    }

    /**
     * Retrieve all requests and their associated data.
     *
     * @return array An indexed array of action requests.
     *
     * @since  1.0
     */
    public function & getRequests ()
    {
        return $this->chain;
    }

    /**
     * Retrieve the size of the chain.
     *
     * @return int The size of the chain.
     *
     * @since  1.0
     */
    public function getSize ()
    {
        return sizeof($this->chain);
    }
}
