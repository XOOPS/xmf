<?php
/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

/**
 * Xmf_Mvc_Model abstract model interface
 *
 * @copyright       The XOOPS Project http://sourceforge.net/projects/xoops/
 * @license         http://www.fsf.org/copyleft/gpl.html GNU private license
 * @package         Xmf_Mvc
 * @since           1.0
 * @author          Richard Griffith
 */

die('not ready');

/**
 * A Xmf_Mvc_Model defines a business process rule set consisting of
 * - business objects (one or more database objects and relating rules)
 * - presentation rules appropriate to input and display
 * - validation rules
 * - methods for specific actions unique to the business process
 * - retrieve rule sets approriate to a specific process step (i.e entry form, entry validation, etc.)
 * - triggers for announcing completion of specific process actions (i.e. new object created)
 * - (more)
 *
 * Models are loaded and tracked by the Controller, and thus they are
 * available to any ContextAware object (such as Action and View.)
 */

abstract class Xmf_Mvc_Model extends Xmf_Mvc_ContextAware
{
	protected $object;

	public function __construct()
	{
		$object=null;
	}

	/**
	 * initialize the model
	 *
	 * concrete implementations should establish the data model
	 *
	 */
	public function initalize()
	{
		return;
	}

	/**
	 * cleanup the model
	 *
	 * concrete implementations should establish the data model
	 *
	 */
	public function cleanup(&modelManager)
	{
		return;
	}

}
?>
