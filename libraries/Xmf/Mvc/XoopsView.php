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
 * XoopsView provides a XOOPS enhanced View object.
 *
 * @copyright       The XOOPS Project http://sourceforge.net/projects/xoops/
 * @license         http://www.fsf.org/copyleft/gpl.html GNU private license
 * @package         Xmf\Mvc
 * @since           1.0
 * @author          Richard Griffith
 */

/**
 * XoopsView provides specific custoomization to a View object to
 * facilitate use in a XOOPS environment. Specifically:
 * - A XoopsSmartyRenderer is automatically instantiated as Renderer()
 * - (more to come)
 *
 */
abstract class XoopsView extends View
{

    private static $renderer = null;
    private static $form = null;

    public function & Renderer()
    {
        if (is_null(self::$renderer)) {
            self::$renderer = new XoopsSmartyRenderer();
        }

        return self::$renderer;
    }

    public function & Form()
    {
        if (is_null(self::$form)) {
            self::$form = new Lib\Form();
        }

        return self::$form;
    }

}
