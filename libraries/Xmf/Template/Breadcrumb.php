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
 * @copyright       The XOOPS Project http://sourceforge.net/projects/xoops/
 * @license         http://www.fsf.org/copyleft/gpl.html GNU public license
 * @package         Xmf
 * @since           0.1
 * @author          trabis <lusopoemas@gmail.com>
 * @author          The SmartFactory <www.smartfactory.ca>
 * @version         $Id: Breadcrumb.php 8065 2011-11-06 02:02:32Z beckmi $
 */

defined('XMF_EXEC') or die('Xmf was not detected');

class Xmf_Template_Breadcrumb extends Xmf_Template_Abstract
{
    /**
     * @var array
     */
    private $_items = array();

    /**
     * @return void
     */
    protected function init()
    {
        $this->setTemplate(XMF_ROOT_PATH . '/templates/xmf_breadcrumb.html');
    }

    /**
     * @param array $items
     * @return void
     */
    public function setItems($items)
    {
        $this->_items = $items;
    }

    /**
     * @return void
     */
    protected function render()
    {
        $this->tpl->assign('xmf_breadcrumb_items', $this->_items);
    }
}