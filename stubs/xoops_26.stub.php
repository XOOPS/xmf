<?php

/**
 * PHPStan stub for XOOPS 2.6 namespaced classes
 *
 * These classes only exist in XOOPS 2.6+ environments.
 * XMF code checks class_exists() before using them.
 */

namespace Xoops\Module;

class Admin
{
    /**
     * @param mixed $value
     * @param string $type
     * @return bool
     */
    public function addConfigBoxLine($value = '', $type = 'default') { return true; }

    /**
     * @param string $title
     * @return bool
     */
    public function addInfoBox($title) { return true; }

    /**
     * @param string $title
     * @param string $text
     * @param string $extra
     * @param string $color
     * @param string $type
     * @return bool
     */
    public function addInfoBoxLine($title, $text = '', $extra = '', $color = 'inherit', $type = 'default') { return true; }

    /**
     * @param string $title
     * @param string $link
     * @param string $icon
     * @param string $extra
     * @return bool
     */
    public function addItemButton($title, $link, $icon = 'add', $extra = '') { return true; }

    /**
     * @param string $position
     * @param string $delimiter
     * @return string
     */
    public function renderButton($position = 'right', $delimiter = '&nbsp;') { return ''; }

    /**
     * @return string
     */
    public function renderInfoBox() { return ''; }

    /**
     * @return string
     */
    public function renderIndex() { return ''; }

    /**
     * @param string $menu
     * @return string
     */
    public function addNavigation($menu = '') { return ''; }

    /**
     * @param string $paypal
     * @param bool $logo_xoops
     * @return string
     */
    public function renderAbout($paypal = '', $logo_xoops = true) { return ''; }
}

class Helper
{
    /**
     * @param string $dirname
     * @return static|false
     */
    public static function getHelper($dirname) { return new static(); }

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
