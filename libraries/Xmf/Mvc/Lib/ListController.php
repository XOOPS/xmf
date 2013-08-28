<?php
/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

namespace Xmf\Mvc\Lib;

/**
 * ListController provides list support using instructions found in model.
 *
 * @category  Xmf\Mvc\Lib\ListController
 * @package   Xmf
 * @author    Richard Griffith <richard@geekwright.com>
 * @copyright 2013 The XOOPS Project http://sourceforge.net/projects/xoops/
 * @license   GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @version   Release: 1.0
 * @link      http://xoops.org
 * @since     1.0
 */
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
        die ('not ready');
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
