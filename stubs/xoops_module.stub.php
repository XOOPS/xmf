<?php

/**
 * PHPStan stub for XOOPS module classes
 */

class XoopsObject
{
    /**
     * @param string $key
     * @param string $format
     * @return mixed
     */
    public function getVar($key, $format = 's') {}

    /**
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function setVar($key, $value) {}

    /**
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function assignVar($key, $value) {}

    /**
     * @return array
     */
    public function getVars() {}

    /**
     * @return bool
     */
    public function cleanVars() {}

    /**
     * @param string $key
     * @param int $data_type
     * @param mixed $value
     * @param bool $not_gpc
     * @param int $max_length
     * @param array $options
     * @return void
     */
    public function initVar($key, $data_type, $value = null, $not_gpc = false, $max_length = 0, $options = []) {}
}

class XoopsModule extends XoopsObject
{
    /**
     * @param string $key
     * @return mixed
     */
    public function getInfo($key = '') {}

    /**
     * @return void
     */
    public function loadAdminMenu() {}

    /**
     * @return array
     */
    public function getAdminMenu() {}

    /**
     * @return string
     */
    public function dirname() {}

    /**
     * @param string $dirname
     * @return XoopsModule|false
     */
    public static function getByDirname($dirname) {}

    /**
     * @param string $version1
     * @param string $version2
     * @param string $operator
     * @return bool
     */
    public function versionCompare($version1, $version2, $operator = '') {}
}

class XoopsObjectHandler
{
    /** @var XoopsDatabase */
    public $db;

    /**
     * @param XoopsDatabase|null $db
     */
    public function __construct($db = null) {}
}

class XoopsPersistableObjectHandler extends XoopsObjectHandler
{
    /**
     * @param int $id
     * @return XoopsObject|false
     */
    public function get($id) {}

    /**
     * @param CriteriaElement|null $criteria
     * @return array
     */
    public function getObjects($criteria = null) {}

    /**
     * @param CriteriaElement|null $criteria
     * @return int
     */
    public function getCount($criteria = null) {}

    /**
     * @param XoopsObject $object
     * @param bool $force
     * @return bool
     */
    public function insert($object, $force = false) {}

    /**
     * @param XoopsObject $object
     * @param bool $force
     * @return bool
     */
    public function delete($object, $force = false) {}
}

class XoopsModuleHandler extends XoopsPersistableObjectHandler
{
    /**
     * @param string $dirname
     * @return XoopsModule|false
     */
    public function getByDirname($dirname) {}
}

class XoopsConfigHandler
{
    /**
     * @param CriteriaElement|null $criteria
     * @return array
     */
    public function getConfigs($criteria = null) {}

    /**
     * @param int $conf_modid
     * @param int $conf_catid
     * @return array
     */
    public function getConfigsByCat($conf_catid, $conf_modid = 0) {}
}
