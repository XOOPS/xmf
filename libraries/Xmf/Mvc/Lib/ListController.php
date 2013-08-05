<?php

namespace Xmf\Mvc\Lib;

/**
 * ListController provides list support using instructions found in model.
 *
 * @author          Richard Griffith
 * @package         Xmf\Mvc
 * @since           1.0
 */
die ('not ready');

class ListController extends \Xmf\Mvc\ContextAware
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

    public function __construct($handler_name)
    {
        $this->handler =& $this->Controller()->getHandler($handler_name);
    }

    public function Criteria($column, $value = '', $operator = '=', $prefix = '', $function = '')
    {
        $criteria=new Criteria($column, $value, $operator, $prefix, $function);
    }
    public function setSort($column)
    {
        $criteria->setSort($column);
    }
    public function setOrder($asc_desc)
    {
        $criteria->setOrder($asc_desc);
    }
    public function fetch($perpage, $start_name, $extras) {}
    public function renderImageNav($offset=4) {}
    public function renderNav($offset=4) {}
    public function renderSelect($showbutton=false) {}
    // XoopsPageNav ($total_items, $items_perpage, $current_start, $start_name="start", $extra_arg="")

}
