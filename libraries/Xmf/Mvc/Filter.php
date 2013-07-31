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
 * A Filter provides a mechanism to perform additional processing in
 * response to a request, beyond the requested Action. It will be
 * invoked both before and after executuion as part of the FilterChain.
 *
 * A Filter's execute method is invoked by the FilterChain and must
 * invoke the FilterChain's execute method to advance the chain. When
 * that method returns, the filter will continue executing.
 *
 * The Controller will always add the ExecutionFilter to the end of
 * the FilterChain. This way all filters in the chain get a chance to
 * pre-process and post-process any Action.
 *
 */
abstract class Xmf_Mvc_Filter extends Xmf_Mvc_ContextAware
{

	/**
	 * An associative array of initialization parameters.
	 *
	 * @since  1.0
	 * @type   array
	 */
	protected $params;

	/**
	 * Create a new Filter instance.
	 *
	 * @since  1.0
	 */
	public function __construct ()
	{

		$this->params = array();

	}

	/**
	 * Execute the filter.
	 *
	 *  _This method should never be called manually._
	 *
	 * All filters must include this line to advance the FilterChain:
	 * @code $filterChain->execute(); @endcode
	 *
	 * @since  1.0
	 */
	abstract public function execute (&$filterChain);

	/**
	 * Initialize the filter.
	 *
	 * @todo **This does not appear to be used anywhere.** Remove
	 *
	 * @param array $params  An associative array of initialization parameters.
	 *
	 * @since  1.0
	 */
	public function initialize ($params)
	{

		$this->params = array_merge($this->params, $params);

	}

}

?>
