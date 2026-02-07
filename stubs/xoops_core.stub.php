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
