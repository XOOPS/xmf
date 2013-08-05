<?php

namespace Xmf\Template;

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
 * @version         $Id: Abstract.php 8065 2011-11-06 02:02:32Z beckmi $
 */

defined('XMF_EXEC') or die('Xmf was not detected');

include_once XOOPS_ROOT_PATH . '/class/template.php';

abstract class AbstractTemplate
{
    /**
     * @var XoopsTpl
     */
    protected $tpl;

    /**
     * @var string
     */
    private $_template;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->tpl = new \XoopsTpl();
        $this->_template = "db:system_dummy.html";
        $this->init();
    }

    /**
     * Classes must implement this method instead of using constructors
     *
     * @abstract
     */
    abstract protected function init();

    /**
     * Classes must implement this method for assigning content to $_tpl
     *
     * @abstract
     */
    abstract protected function render();

    /**
     * Used in init methods to set the template used by $_tpl
     *
     * @param  string $template Path to the template file
     * @return void
     */
    protected function setTemplate($template = '')
    {
        $this->_template = $template;
    }

    /**
     * Use this method to disable XoopsLogger
     *
     * @return void
     */
    protected function disableLogger()
    {
        error_reporting(0);
        if (is_object($GLOBALS['xoopsLogger'])) {
            $GLOBALS['xoopsLogger']->activated = false;
        }
    }

    /**
     * Returns the rendered template
     *
     * @return bool|mixed|string
     */
    public function fetch()
    {
        $this->render();

        return $this->tpl->fetch($this->_template);
    }

    /**
     * Echo/Display the rendered template
     *
     * @return void
     */
    public function display()
    {
        echo $this->fetch();
    }

}
