<?php

/**
 * ListController provides list support using instructions found in model.
 *
 * @author          Richard Griffith
 * @package         Xmf_Mvc
 * @since           1.0
 */
die ('not ready');

class Xmf_Mvc_Lib_ListController extends Xmf_Mvc_ContextAware
{

	private $handler=null;
	private $criteria=null;
	private $total_items;
	private $items_perpage;
	private $current_start;
	private $start_name = 'start';
	private $extra_arg = array();
	private $results;
	private $pageNav;

	function __construct($handler_name)
	{
		$this->handler =& $this->Controller()->getHandler($handler_name);
	}

	function Criteria($column, $value = '', $operator = '=', $prefix = '', $function = '') {
		$criteria=new Criteria($column, $value, $operator, $prefix, $function);
	}
	function setSort($column) {
		$criteria->setSort($column);
	}
	function setOrder($asc_desc) {
		$criteria->setOrder($asc_desc);
	}
	function fetch($perpage, $start_name, $extras) {}
	function renderImageNav($offset=4) {}
	function renderNav($offset=4) {}
	function renderSelect($showbutton=false) {}
	// XoopsPageNav ($total_items, $items_perpage, $current_start, $start_name="start", $extra_arg="")

}

?>
