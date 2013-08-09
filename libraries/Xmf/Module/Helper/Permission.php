<?php
/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

namespace Xmf\Module\Helper;

defined('XMF_EXEC') or die('Xmf was not detected');

include_once XOOPS_ROOT_PATH . '/kernel/groupperm.php';

/**
 * Manage session variables for a module. Session variable will be
 * prefixed with the module name to separate them from variables set
 * by other modules or system functions.
 *
 * @category  Xmf\Module\Helper\Permission
 * @package   Xmf
 * @author    trabis <lusopoemas@gmail.com>
 * @author    Richard Griffith <richard@geekwright.com>
 * @copyright 2011-2013 The XOOPS Project http://sourceforge.net/projects/xoops/
 * @license   http://www.fsf.org/copyleft/gpl.html GNU public license
 * @version   Release: 1.0
 * @since     1.0
 */
class Permission extends AbstractHelper
{
    /**
     * @var int
     */
    private $_mid;

    /**
     * @var XoopsDatabase
     */
    private $_db;

    /**
     * @var XoopsGrouppermHandler
     */
    private $_perm;

    /**
     * Initialize parent::__constuct calls this after verifying module object.
     *
     * @return void
     */
    public function init()
    {
        $this->_mid = $this->module->getVar('mid');
        $this->_db = XoopsDatabaseFactory::getDatabaseConnection();
        $this->_perm = new XoopsGroupPermHandler($this->_db);
    }

    /*
     * Returns permissions for a certain type
     *
     * @param string $gperm_name "global", "forum" or "topic" (should perhaps have "post" as well - but I don't know)
     * @param int    $id         id of the item (forum, topic or possibly post) to get permissions for
     *
     * @return array of groups with permission
     */
    public function getGrantedGroups($gperm_name, $id = null)
    {
        static $groups;

        if (!isset($groups[$gperm_name]) || ($id != null && !isset($groups[$gperm_name][$id]))) {
            //Get groups allowed for an item id
            $allowedgroups = $this->_perm->getGroupIds($gperm_name, $id, $this->_mid);
            $groups[$gperm_name][$id] = $allowedgroups;
        }
        //Return the permission array
        return isset($groups[$gperm_name][$id]) ? $groups[$gperm_name][$id] : array();
    }

    /**
     * This can't be doing what the name implies, as it doesn't use the
     * oddly named required parameter.
     *
     * Seems to get all the groupperms for the module, and gperm_name if
     * specified.
     *
     * TODO - evaluate if this adds value and can be rescued
     *
     * @param array $itemsObj_array_keys
     * @param  bool  $gperm_name
     *
     * @return array of [gperm_name][gperm_id] = gperm_groupid
     *               or [gperm_id] = gperm_groupid
     */
    public function getGrantedGroupsForIds($itemsObj_array_keys, $gperm_name = false)
    {
        static $groups;
        static $all_permissions_fetched;

        if ($gperm_name) {
            if (isset($groups[$gperm_name])) {
                return $groups[$gperm_name];
            }
        } else {
            if ($all_permissions_fetched) {
                return $groups;
            } else {
                $all_permissions_fetched = true;
            }
        }

        $criteria = new CriteriaCompo();
        $criteria->add(new Criteria('gperm_modid', $this->_mid));

        if ($gperm_name) {
            $criteria->add(new Criteria('gperm_name', $gperm_name));
        }

        //Instead of calling groupperm handler and get objects, we will save some memory and do it our way
        $limit = $start = 0;
        $sql = 'SELECT * FROM ' . $this->_db->prefix('group_permission');
        if (isset($criteria) && is_subclass_of($criteria, 'criteriaelement')) {
            $sql .= ' ' . $criteria->renderWhere();
            $limit = $criteria->getLimit();
            $start = $criteria->getStart();
        }
        $result = $this->_db->query($sql, $limit, $start);

        while ($myrow = $this->_db->fetchArray($result)) {
            $groups[$myrow['gperm_name']][$myrow['gperm_id']][] = $myrow['gperm_groupid'];
        }

        //Return the permission array
        if ($gperm_name) {
            return isset($groups[$gperm_name]) ? $groups[$gperm_name] : array();
        } else {
            return isset($groups) ? $groups : array();
        }

    }

    /**
     * Returns permissions for a certain type
     *
     * @param string $gperm_name "global", "forum" or "topic" (should perhaps have "post" as well - but I don't know)
     * @param int    $id         id of the item (forum, topic or possibly post) to get permissions for
     *
     * @return array
     */
    public function getGrantedItems($gperm_name, $id = null)
    {
        global $xoopsUser;

        static $permissions;

        if (!isset($permissions[$gperm_name]) || ($id != null && !isset($permissions[$gperm_name][$id]))) {

            $permissions[$gperm_name] = array();

            //Instead of calling groupperm handler and get objects, we will save some memory and do it our way
            $criteria = new CriteriaCompo(new Criteria('gperm_name', $gperm_name));
            $criteria->add(new Criteria('gperm_modid', $this->_mid));

            //Get user's groups
            $groups = is_object($xoopsUser) ? $xoopsUser->getGroups() : array(XOOPS_GROUP_ANONYMOUS);
            $criteria2 = new CriteriaCompo();
            foreach ($groups as $gid) {
                $criteria2->add(new Criteria('gperm_groupid', $gid), 'OR');
            }
            $criteria->add($criteria2);

            $sql = 'SELECT * FROM ' . $this->_db->prefix('group_permission');
            $sql .= ' ' . $criteria->renderWhere();

            $result = $this->_db->query($sql, 0, 0);

            while ($myrow = $this->_db->fetchArray($result)) {
                $permissions[$gperm_name][] = $myrow['gperm_itemid'];
            }

            $permissions[$gperm_name] = array_unique($permissions[$gperm_name]);

        }
        //Return the permission array
        return isset($permissions[$gperm_name]) ? $permissions[$gperm_name] : array();
    }

    /**
     * Check if user is granted permission for an item
     *
     * @param  string $gperm_name
     * @param  int    $id
     * @return bool
     */
    public function isGranted($gperm_name, $id = null)
    {
        static $permissions;

        if ($id == null) return false;

        if (!isset($permissions[$gperm_name]) || !isset($permissions[$gperm_name][$id])) {
            $userpermissions = in_array($id, $this->getGrantedItems($gperm_name)) ? true : false;
            $permissions[$gperm_name][$id] = $userpermissions;
        }

        return $permissions[$gperm_name][$id];
    }

    /**
     * Update permissions for a specific item
     *
     * updatePermissions()
     *
     * @param  array   $groups    : group with granted permission
     * @param  int     $itemid    : itemid on which we are setting permissions
     * @param  string  $perm_name : name of the permission
     * @return boolean : TRUE if the no errors occured
     */
    public function updatePermissions($groups, $itemid, $perm_name)
    {
        // First, if the permissions are already there, delete them
        if (!$this->_perm->deleteByModule($this->_mid, $perm_name, $itemid)) {
            return false;
        }

        // Save the new permissions
        if (count($groups) > 0) {
            foreach ($groups as $group_id) {
                if (!$this->_perm->addRight($perm_name, $itemid, $group_id, $this->_mid)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @param  array  $groups
     * @param  id     $itemid
     * @param  string $perm_name
     * @return bool
     */
    public function saveItemPermissions($groups, $itemid, $perm_name)
    {
        $result = true;

        // First, if the permissions are already there, delete them
        $this->_perm->deleteByModule($this->_mid, $perm_name, $itemid);

        // Save the new permissions
        if (count($groups) > 0) {
            foreach ($groups as $group_id) {
                echo $group_id . "-";
                echo $this->_perm->addRight($perm_name, $itemid, $group_id, $this->_mid);
            }
        }

        return $result;
    }


    /**
     * Delete all permissions for a specific item and/or name
     *
     * @param  int     $itemid     : id of the item for which to delete the permissions
     * @param  string  $gperm_name
     * @return boolean : TRUE if the no errors occured
     */
    public function deletePermissions($itemid = null, $gperm_name = null)
    {
        return $this->_perm->deleteByModule($this->_mid, $gperm_name, $itemid);
    }

    /**
     * Checks if the user has access to a specific permission on a given object
     *
     * @param  string  $gperm_name   name of the permission to test
     * @param  int     $gperm_itemid id of the object to check
     * @return boolean : TRUE if user has access, FALSE if not
     **/
    public function accessGranted($gperm_name, $gperm_itemid)
    {
        $gperm_groupid = $this->getUserGroups();

        return $this->_perm->checkRight($gperm_name, $gperm_itemid, $gperm_groupid, $this->_mid);
    }

    /**
     * Get groups user belong to, even for annonymous user
     *
     * @return array of groups the current user is associted with
     */
    public function getUserGroups()
    {
        if (class_exists('Xoops', false)) {
            $groupids = $this->xoops()->isUser() ? $this->xoops()->user->getGroups() : array(XOOPS_GROUP_ANONYMOUS);
        } else  {
            $groupids = is_object($GLOBALS['xoopsUser']) ? $GLOBALS['xoopsUser']->getGroups() : array(XOOPS_GROUP_ANONYMOUS);
        }

        return $groupids;
    }

}
