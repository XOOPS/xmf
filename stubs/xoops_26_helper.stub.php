<?php

/**
 * PHPStan stub for XOOPS 2.6 Xoops\Module\Helper\HelperAbstract
 */

namespace Xoops\Module\Helper;

abstract class HelperAbstract
{
    /**
     * @return \XoopsModule
     */
    public function getModule() { return new \XoopsModule(); }

    /**
     * @param string $path
     * @return string
     */
    public function path($path = '') { return ''; }
}
