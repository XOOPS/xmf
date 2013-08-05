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
 * @version         $Id: Print.php 8065 2011-11-06 02:02:32Z beckmi $
 */

defined('XMF_EXEC') or die('Xmf was not detected');

class Xmf_Template_Print extends Xmf_Template_Abstract
{
    /**
     * @var string
     */
    private $_title = '';

    /**
     * @var string
     */
    private $_description = '';

    /**
     * @var string
     */
    private $_content = '';

    /**
     * @var bool
     */
    private $_pagetitle = false;

    /**
     * @var int
     */
    private $_width = 680;

    /**
     * @return void
     */
    protected function init()
    {
        $this->setTemplate(XMF_ROOT_PATH . '/templates/xmf_print.html');
    }

    protected function render()
    {
        $this->tpl->assign('xmf_print_pageTitle', $this->_pagetitle ? $this->_pagetitle : $this->_title);
        $this->tpl->assign('xmf_print_title', $this->_title);
        $this->tpl->assign('xmf_print_description', $this->_description);
        $this->tpl->assign('xmf_print_content', $this->_content);
        $this->tpl->assign('xmf_print_width', $this->_width);
    }

    /**
     * @param string $content
     */
    public function setContent($content)
    {
        $this->_content = $content;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->_content;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->_description = $description;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->_description;
    }

    /**
     * @param boolean $pagetitle
     */
    public function setPagetitle($pagetitle)
    {
        $this->_pagetitle = $pagetitle;
    }

    /**
     * @return boolean
     */
    public function getPagetitle()
    {
        return $this->_pagetitle;
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->_title = $title;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->_title;
    }

    /**
     * @param int $width
     */
    public function setWidth($width)
    {
        $this->_width = $width;
    }

    /**
     * @return int
     */
    public function getWidth()
    {
        return $this->_width;
    }

}