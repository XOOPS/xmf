<?php

namespace Xmf\Mvc;

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
 * @license         http://www.fsf.org/copyleft/gpl.html GNU private license
 * @package         Xmf\Mvc
 * @since           1.0
 * @author          Richard Griffith
 */

defined('XMF_EXEC') or die('Xmf was not detected');

/**
 * Xmf_MvcXoopsTplRender is used by the XoopsSmartyRenderer if a render
 * mode of Xmf_Mvc::RENDER_VAR (render to variable) is requested.
 */
class XoopsTplRender extends \Xmf\Template\AbstractTemplate
{
    /**
     * @var string
     */
    private $_title = '';

    /**
     * @return void
     */
    protected function init()
    {

    }

    /**
     * Render the feed and display it directly
     *
     * @return void
     */
    protected function render()
    {

    }

    /**
     * Assign a template variable
     *
     * @param string $name
     * @param string $value
     */
    public function setAttribute($name,$value)
    {
        $this->tpl->assign($name, $value);
    }

    public function setXTemplate($name)
    {
        $this->setTemplate($name);
    }

}
