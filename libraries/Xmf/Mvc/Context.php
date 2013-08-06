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
 * Xmf/Mvc/Context is a shared context for Mvc classes
 *
 * @copyright       The XOOPS Project http://sourceforge.net/projects/xoops/
 * @license         http://www.fsf.org/copyleft/gpl.html GNU private license
 * @package         Xmf\Mvc
 * @since           1.0
 * @author          Richard Griffith
 */

defined('XMF_EXEC') or die('Xmf was not detected');

/**
 * Context is a shared context for Mvc classes
 *
 * The controller establishes the context object, while all others
 * gain access by extending the Xmf\Mvc\ContextAware class
 *
 */
class Context
{

    private static $context = null;

   /**
    * Get the context object
    *
    * @return  object  The context object
    *
    * @since      1.0
    */
    public static function & get()
    {
        if (!is_null(self::$context)) {
            return self::$context;
        }
        die('Context not established');
    }

   /**
    * Set the context
    *
    * @param  object $context - the controller creating the context
    *
    * @since      1.0
    */
    public static function set(&$context)
    {
        self::$context =& $context;
    }
}
