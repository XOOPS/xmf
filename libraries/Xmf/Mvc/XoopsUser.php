<?php

namespace Xmf\Mvc;

/**
 * This file has its roots as part of the Mojavi package which was
 * Copyright (c) 2003 Sean Kerr. It has been incorporated into this
 * derivative work under the terms of the LGPL V2.1.
 * (license terms)
 *
 * @author          Richard Griffith
 * @author          Sean Kerr
 * @copyright       The XOOPS Project http://sourceforge.net/projects/xoops/
 * @copyright       Portions Copyright (c) 2003 Sean Kerr
 * @license         (license terms)
 * @package         Xmf\Mvc
 * @since           1.0
 */

/**
 * XoopsUser implements a User object using the XOOPS user for
 * authentication and XOOPS group permissions for privileges.
 * It implements a hasPrivilege() method consistent with the
 * PrivilegeUser object, but nothing else from that class.
 * Xmf\Mvc\XoopsUser is intended for use with Xmf\Mvc\XoopsAuthHandler.
 *
 */
class XoopsUser extends User
{

    // array of permissions that map mojavie namespace and name to
    // xoops group permission name and id
    private $permissons;
    private $privilege_checked;
    private $xoopsuser;

    public function __construct ()
    {
        global $xoopsUser;

        $this->authenticated = false;
        $this->xoopsuser = null;
        if (is_object($xoopsUser)) {
            $this->authenticated = true;
            $this->xoopsuser =& $xoopsUser;
        }
        $this->attributes    = NULL;
        $this->container     = NULL;
        $this->secure        = NULL;
        $this->permissions   = array();
        $this->privilege_checked = NULL;

    }

    /**
     * Determine the authenticated status of the user.
     *
     * @return bool TRUE if the user is authenticated, otherwise FALSE
     *
     * @since  1.0
     */
    public function isAuthenticated ()
    {
        global $xoopsUser;
        if (is_object($xoopsUser)) {
            $this->authenticated = true;
            $this->xoopsuser =& $xoopsUser;
        }

        return $this->authenticated;
    }

    /**
     * return privilege checked on last call to hasPrivilege
     *
     * @return array of name, namespace last checked
     */
    public function lastPrivilegeChecked ()
    {
        return $this->privilege_checked;
    }

    /**
     * Determine if the user has a privilege.
     *
     * @param name      Privilege name.
     * @param namespace Privilege namespace.
     *
     * @return TRUE, if the user has the given privilege, otherwise FALSE.
     */
    public function hasPrivilege ($name, $namespace)
    {

        $this->privilege_checked=array($name, $namespace);

        if (is_object($this->xoopsuser)) {
            $groups = $this->xoopsuser->getGroups();
        } else {
            $groups = XOOPS_GROUP_ANONYMOUS;
        }

        $module_id = $this->Controller()->modGetVar('mid');
        $gperm_handler = xoops_gethandler('groupperm');

        $privilege = false;

        if (isset($this->permissions[$namespace]['items'][$name]['id'])) {
            $perm_id=$this->permissions[$namespace]['items'][$name]['id'];

            $privilege = $gperm_handler->checkRight($namespace, $perm_id, $groups, $module_id);
        } else {
            // this could be a per item permission
            if (is_numeric($name)) {
                $privilege = $gperm_handler->checkRight($namespace, $name, $groups, $module_id);
            }
            if (is_object($this->xoopsuser)) {
                $privilege = $this->xoopsuser->isAdmin();
            }
        }

        return $privilege;

    }

    /**
     * Set the permission map to give symbolic names to global permissions
     */
    public function setXoopsPermissionMap($permissions)
    {
        $this->permissions=$permissions;
    }

    // mimic a few common $xoopsUser calls for code brevity
    public function id($ignored='')
    {
        if ($this->xoopsuser) {
            return $this->xoopsuser->id();
        }

        return 0;
    }

    public function uname()
    {
        global $xoopsConfig;

        if ($this->xoopsuser) {
            return $this->xoopsuser->uname();
        }

        return $xoopsConfig['anonymous'];
    }

}
