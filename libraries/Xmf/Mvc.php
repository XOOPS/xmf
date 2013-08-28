<?php
/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

namespace Xmf;

/**
 * Xmf\Mvc provides static contstants used in other Mvc classes
 *
 * @category  Xmf\Mvc
 * @package   Xmf
 * @author    Richard Griffith <richard@geekwright.com>
 * @copyright 2011-2013 The XOOPS Project http://sourceforge.net/projects/xoops/
 * @license   http://www.fsf.org/copyleft/gpl.html GNU public license
 * @version   Release: 1.0
 * @link      http://xoops.org
 * @since     1.0
 */
class Mvc
{

    const RENDER_CLIENT = 1;
    const RENDER_VAR    = 2;
    const REQ_NONE      = 1;
    const REQ_GET       = 2;
    const REQ_POST      = 4;
    const REQ_ALL       = 6;
    const VIEW_ALERT    = 'alert';
    const VIEW_ERROR    = 'error';
    const VIEW_INDEX    = 'index';
    const VIEW_INPUT    = 'input';
    const VIEW_NONE     =  null;
    const VIEW_SUCCESS  = 'success';
}
