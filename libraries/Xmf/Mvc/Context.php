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
 * Context is a shared context for Mvc classes
 *
 * The controller establishes the context object, while all others
 * gain access by extending the Xmf\Mvc\ContextAware class
 *
 * @category  Xmf\Mvc\Context
 * @package   Xmf
 * @author    Richard Griffith <richard@geekwright.com>
 * @copyright 2013 The XOOPS Project http://sourceforge.net/projects/xoops/
 * @license   http://www.fsf.org/copyleft/gpl.html GNU public license
 * @version   Release: 1.0
 * @link      http://xoops.org
 * @since     1.0
 */
class Context
{

    private static $_context = null;

    /**
     * Get the context object
     *
     * @return  object  The context object
     *
     * @since      1.0
     */
    public static function & get()
    {
        if (!is_null(self::$_context)) {
            return self::$_context;
        }
        die('Context not established');
    }

    /**
     * Set the context
     *
     * @param object &$context - the controller creating the context
     *
     * @return void
     */
    public static function set(&$context)
    {
        self::$_context =& $context;
    }
}
