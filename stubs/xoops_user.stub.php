<?php

/**
 * PHPStan stub for XOOPS user and permission classes
 */

class XoopsUser extends XoopsObject
{
    /**
     * @return int
     */
    public function uid() {}

    /**
     * @return string
     */
    public function uname() {}

    /**
     * @return bool
     */
    public function isAdmin() {}

    /**
     * @return array
     */
    public function getGroups() {}
}

class XoopsGrouppermHandler extends XoopsObjectHandler
{
    /**
     * @param string $gperm_name
     * @param int $gperm_itemid
     * @param int|array $gperm_groupid
     * @param int $gperm_modid
     * @return bool
     */
    public function checkRight($gperm_name, $gperm_itemid, $gperm_groupid, $gperm_modid = 1) {}

    /**
     * @param string $gperm_name
     * @param int|array $gperm_groupid
     * @param int $gperm_modid
     * @return array
     */
    public function getItemIds($gperm_name, $gperm_groupid, $gperm_modid = 1) {}
}
