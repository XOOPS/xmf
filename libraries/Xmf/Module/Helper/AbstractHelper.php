<?php

namespace Xmf\Module\Helper;

/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

/**
 * Xmf\Module\Helper\AbstractHelper defines the basis for various
 * helpers that simplfy routine module tasks.
 * uses.
 *
 * @category  Xmf\Module\Helper\AbstractHelper
 * @package   Xmf
 * @author    trabis <lusopoemas@gmail.com>
 * @author    Richard Griffith <richard@geekwright.com>
 * @copyright 2011-2013 The XOOPS Project http://sourceforge.net/projects/xoops/
 * @license   http://www.fsf.org/copyleft/gpl.html GNU public license
 * @version   Release: 1.0
 * @since     1.0
 */
abstract class AbstractHelper
{
    /**
     * @var XoopsModule
     */
    protected $module;

    /**
     * @param XoopsModule $module
     */
    public function __construct(XoopsModule $module = null)
    {
        if (!is_object($module)) {
            // check if we are running in 2.6
            if (class_exists('Xoops', false)) {
                $xoops=Xoops::getInstance();
                if ($xoops->isModule()) {
                    $module &= $xoops->module;
                }
            } else {
                $module &= $GLOBALS['xoopsModule'];
            }
        }
        $this->module = $module;
        if(is_object($module)) {
            $this->init();
        }
    }

    abstract public function init();
}
