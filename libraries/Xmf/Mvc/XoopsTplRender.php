<?php
/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

namespace Xmf\Mvc;

/**
 * XoopsTplRender is used by the XoopsSmartyRenderer if a render
 * mode of Xmf\Mvc::RENDER_VAR (render to variable) is requested.
 *
 * @category  Xmf\Mvc\XoopsTplRender
 * @package   Xmf
 * @author    Richard Griffith <richard@geekwright.com>
 * @copyright 2013 The XOOPS Project http://sourceforge.net/projects/xoops/
 * @license   http://www.fsf.org/copyleft/gpl.html GNU public license
 * @version   Release: 1.0
 * @link      http://xoops.org
 * @since     1.0
 */
class XoopsTplRender extends \Xmf\Template\AbstractTemplate
{
    /**
     * @var string
     */
    private $_title = '';

    /**
     * initialize
     *
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
     * @param string $name  attribute name
     * @param string $value attribute value
     *
     * @return void
     */
    public function setAttribute($name, $value)
    {
        $this->tpl->assign($name, $value);
    }

    /**
     * Assign a template
     *
     * @param string $name template name
     *
     * @return void
     */
    public function setXTemplate($name)
    {
        $this->setTemplate($name);
    }
}
