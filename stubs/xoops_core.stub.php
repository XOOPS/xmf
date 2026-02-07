<?php

/**
 * PHPStan stub for XOOPS core classes and utilities
 */

class Xoops
{
    /**
     * @return static
     */
    public static function getInstance() {}

    /**
     * @return object
     */
    public function events() {}

    /**
     * @param string $name
     * @return string
     */
    public function path($name) {}

    /**
     * @param string $name
     * @return string
     */
    public function url($name) {}

    /**
     * @return object
     */
    public function theme() {}

    /**
     * @return object
     */
    public function tpl() {}
}

class XoopsCache
{
    /**
     * @return static
     */
    public static function getInstance() {}

    /**
     * @param string $key
     * @return mixed
     */
    public function read($key) {}

    /**
     * @param string $key
     * @param mixed $value
     * @param int $duration
     * @return bool
     */
    public function write($key, $value, $duration = 0) {}

    /**
     * @param string $key
     * @return bool
     */
    public function delete($key) {}
}

class XoopsLoad
{
    /**
     * @param string $name
     * @return void
     */
    public static function load($name) {}
}

class XoopsFormSelectGroup
{
    /**
     * @param string $caption
     * @param string $name
     * @param bool $include_anon
     * @param mixed $value
     * @param int $size
     * @param bool $multiple
     */
    public function __construct($caption, $name, $include_anon = false, $value = null, $size = 1, $multiple = false) {}

    /**
     * @return string
     */
    public function render() {}
}

/**
 * Frameworks ModuleAdmin class (XOOPS 2.5)
 */
class ModuleAdmin
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

class XoopsLogger
{
    /**
     * @return static
     */
    public static function getInstance() {}

    /**
     * @param string $msg
     * @return void
     */
    public function addExtra($msg) {}
}
