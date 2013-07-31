<?php

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
 * @package         Xmf_Mvc
 * @since           1.0
 */

/**
 * ExecutionChain is a list of actions to be performed
 * The Controller establishes the ExecutionChain, while the
 * ExecutionFilter processes the chain.
 *
 * The Execution chain allows access to the state of all executed
 * actions resulting from a request.
 *
 */
class Xmf_Mvc_ExecutionChain
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
	 * @param string $modName  A module name.
	 * @param string $actName  An action name.
	 * @param string $action   An Action instance.
	 *
	 * @since  1.0
	 */
	public function addRequest ($modName, $actName, &$action)
	{

		$this->chain[] = array('module_name' => $modName,
							   'action_name' => $actName,
							   'action'      => &$action,
							   'microtime'   => microtime());

	}

	/**
	 * Retrieve the Action instance at the given index.
	 *
	 * @param int $index  The index from which you're retrieving.
	 *
	 * @return Action An Action instance, if the given index exists and
	 *                the action was executed, otherwise NULL.
	 *
	 * @since  1.0
	 */
	public function & getAction ($index)
	{

		if (sizeof($this->chain) > $index && $index > -1)
		{

			return $this->chain[$index]['action'];

		}
		$null=NULL;
		return $null;

	}

	/**
	 * Retrieve the action name associated with the request at the given index.
	 *
	 * @param int $index  The index from which you're retrieving.
	 *
	 * @return string An action name, if the given index exists, otherwise NULL.
	 *
	 * @since  1.0
	 */
	public function getActionName ($index)
	{

		if (sizeof($this->chain) > $index && $index > -1)
		{

			return $this->chain[$index]['action_name'];

		}

		return NULL;

	}

	/**
	 * Retrieve the module name associated with the request at the given index.
	 *
	 * @param int $index  The index from which you're retrieving.
	 *
	 * @return string A module name if the given index exists, otherwise NULL.
	 *
	 * @since  1.0
	 */
	public function getModuleName ($index)
	{

		if (sizeof($this->chain) > $index && $index > -1)
		{

			return $this->chain[$index]['module_name'];

		}

		return NULL;

	}

	/**
	 * Retrieve a request and its associated data.
	 *
	 * @param int $index  The index from which you're retrieving.
	 *
	 * @return array An associative array of information about an action
	 *               request if the given index exists, otherwise NULL.
	 *
	 * @since  1.0
 */
	public function & getRequest ($index)
	{

		if (sizeof($this->chain) > $index && $index > -1)
		{

			return $this->chain[$index];

		}
		$null=NULL;
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

?>
