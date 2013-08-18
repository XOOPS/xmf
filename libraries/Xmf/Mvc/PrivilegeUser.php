<?php
/*
 * This file has its roots as part of the Mojavi package which was
 * Copyright (c) 2003 Sean Kerr. It has been incorporated into this
 * derivative work under the terms of the LGPL V2.1.
 * (license terms)
 */

namespace Xmf\Mvc;

/**
 * PrivilegeUser extends User to allows privileges to be assigned.
 *
 * @category  Xmf\Mvc\PrivilegeUser
 * @package   Xmf
 * @author    Richard Griffith <richard@geekwright.com>
 * @author    Sean Kerr <skerr@mojavi.org>
 * @copyright 2013 The XOOPS Project http://sourceforge.net/projects/xoops/
 * @copyright 2003 Sean Kerr
 * @license   http://www.fsf.org/copyleft/gpl.html GNU public license
 * @version   Release: 1.0
 * @link      http://xoops.org
 * @since     1.0
 */
class PrivilegeUser extends User
{

    /**
     * Create a new PrivilegeUser instance.
     *
     * @since  1.0
     */
    public function __construct ()
    {
        parent::__construct();
        $this->secure = array();
    }

    /**
     * Add a privilege.
     *
     * @param string $name      A privilege name.
     * @param string $namespace A privilege namespace.
     *
     * @return void
     * @since  1.0
     */
    public function addPrivilege ($name, $namespace = 'org.mojavi')
    {
        $namespace        =& $this->getPrivilegeNamespace($namespace, true);
        $namespace[$name] =  true;
    }

    /**
     * Clear all privilege namespaces and their associated privileges.
     *
     * @return void
     * @since  1.0
     */
    public function clearPrivileges ()
    {
        $this->secure = null;
        $this->secure = array();
    }

    /**
     * Retrieve a privilege namespace.
     *
     * @param string $namespace A privilege namespace.
     * @param bool   $create    Whether or not to auto-create the privilege
     *                          namespace if it doesn't already exist.
     *
     * @return mixed A privilege namespace if the given namespace
     *               exists, otherwise NULL.
     *
     * @since  1.0
     */
    public function & getPrivilegeNamespace ($namespace, $create = false)
    {
        if (isset($this->secure[$namespace])) {
            return $this->secure[$namespace];
        } elseif ($create) {
            $this->secure[$namespace] = array();

            return $this->secure[$namespace];
        }

        $null = null;

        return $null;
    }

    /**
     * Retrieve an indexed array of privilege namespaces.
     *
     * @return array An array of privileges.
     *
     * @return void
     * @since  1.0
     */
    public function getPrivilegeNamespaces ()
    {
        return array_keys($this->secure);
    }

    /**
     * Retrieve an indexed array of namespace privileges.
     *
     * @param string $namespace A privilege namespace.
     *
     * @return array An array of privilege names, if the given
     *               namespace exists, otherwise NULL.
     *
     * @since  1.0
     */
    public function & getPrivileges ($namespace = 'org.mojavi')
    {
        $namespace =& $this->getPrivilegeNamespace($namespace);
        if ($namespace !== null) {
            return array_keys($namespace);
        }
        $null = null;

        return $null;
    }

    /**
     * Determine if the user has a privilege.
     *
     * @param string $name      A privilege name.
     * @param string $namespace A privilege namespace.
     *
     * @return bool TRUE if the user has the given privilege, otherwise FALSE.
     *
     * @since  1.0
     */
    public function hasPrivilege ($name, $namespace = 'org.mojavi')
    {
        $namespace =& $this->getPrivilegeNamespace($namespace);

        return ($namespace !== null && isset($namespace[$name])) ? true : false;
    }

    /**
     * Load user data from the container.
     *
     *  _This should never be called manually._
     *
     * @return void
     * @since  1.0
     */
    public function load ()
    {
        if ($this->container !== null) {
            parent::load();
        }
    }

    /**
     * Merge a new indexed array of privileges with the existing array.
     *
     * @param array $privileges An indexed array of privileges.
     *
     * @return void
     * @since  1.0
     */
    public function mergePrivileges ($privileges)
    {
        $keys  = array_keys($privileges);
        $count = sizeof($keys);

        for ($i = 0; $i < $count; $i++) {
            if (isset($this->secure[$keys[$i]])) {
                // namespace already exists, merge values only
                $subKeys  = array_keys($privileges[$keys[$i]]);
                $subCount = sizeof($subKeys);
                for ($x = 0; $x < $subCount; $x++) {
                    $this->secure[$keys[$i]][$subKeys[$x]] = true;
                }
            } else {
                // add entire namespace and related privileges
                $this->secure[$keys[$i]] =& $privileges[$keys[$i]];
            }
        }
    }

    /**
     * Remove a privilege.
     *
     * @param string $name      A privilege name.
     * @param string $namespace A privilege namespace.
     *
     * @return void
     * @since  1.0
     */
    public function & removePrivilege ($name, $namespace = 'org.mojavi')
    {
        $namespace =& $this->getPrivilegeNamespace($namespace);
        if ($namespace !== null && isset($namespace[$name])) {
            unset($namespace[$name]);
        }
    }

    /**
     * Remove a privilege namespace and all associated privileges.
     *
     * @param string $namespace A privilege namespace.
     *
     * @return void
     * @since  1.0
     */
    public function removePrivileges ($namespace = 'org.mojavi')
    {
        $namespace =& $this->getPrivilegeNamespace($namespace);
        $namespace =  null;
    }

}
