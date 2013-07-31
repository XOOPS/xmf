<?php

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
 * @package         Xmf_Mvc
 * @since           1.0
 */

/**
 * PrivilegeUser extends User to allows privileges to be assigned.
 *
 */
class Xmf_Mvc_PrivilegeUser extends Xmf_Mvc_User
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
	 * @param string $name       A privilege name.
	 * @param string $namespace  A privilege namespace.
	 *
	 * @since  1.0
	 */
	public function addPrivilege ($name, $namespace = 'org.mojavi')
	{

		$namespace        =& $this->getPrivilegeNamespace($namespace, TRUE);
		$namespace[$name] =  TRUE;

	}

	/**
	 * Clear all privilege namespaces and their associated privileges.
	 *
	 * @since  1.0
	 */
	public function clearPrivileges ()
	{

		$this->secure = NULL;
		$this->secure = array();

	}

	/**
	 * Retrieve a privilege namespace.
	 *
	 * @param string $namespace  A privilege namespace.
	 * @param bool   $create     Whether or not to auto-create the privilege
	 *                           namespace if it doesn't already exist.
	 *
	 * @return array A privilege namespace if the given namespace exists, otherwise NULL.
	 *
	 * @since  1.0
	 */
	public function & getPrivilegeNamespace ($namespace, $create = FALSE)
	{

		if (isset($this->secure[$namespace]))
		{

			return $this->secure[$namespace];

		} else if ($create)
		{

			$this->secure[$namespace] = array();

			return $this->secure[$namespace];

		}

		$null = NULL;
		return $null;
	}

	/**
	 * Retrieve an indexed array of privilege namespaces.
	 *
	 * @return array An array of privileges.
	 *
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
	 * @return array An array of privilege names, if the given namespace exists, otherwise NULL.
	 *
	 * @since  1.0
	 */
	public function & getPrivileges ($namespace = 'org.mojavi')
	{

		$namespace =& $this->getPrivilegeNamespace($namespace);

		if ($namespace !== NULL)
		{

			return array_keys($namespace);

		}

		$null = NULL;
		return $null;
	}

	/**
	 * Determine if the user has a privilege.
	 *
	 * @param string A privilege name.
	 * @param string A privilege namespace.
	 *
	 * @return bool TRUE if the user has the given privilege, otherwise FALSE.
	 *
	 * @since  1.0
	 */
	public function hasPrivilege ($name, $namespace = 'org.mojavi')
	{

		$namespace =& $this->getPrivilegeNamespace($namespace);

		return ($namespace !== NULL && isset($namespace[$name])) ? TRUE : FALSE;

	}

	/**
	 * Load user data from the container.
	 *
	 *  _This should never be called manually._
	 *
	 * @since  1.0
	 */
	public function load ()
	{

		if ($this->container !== NULL)
		{

			parent::load();

		}

	}

	/**
	 * Merge a new indexed array of privileges with the existing array.
	 *
	 * @param array $privileges  An indexed array of privileges.
	 *
	 * @since  1.0
	 */
	public function mergePrivileges ($privileges)
	{

		$keys  = array_keys($privileges);
		$count = sizeof($keys);

		for ($i = 0; $i < $count; $i++)
		{

			if (isset($this->secure[$keys[$i]]))
			{

				// namespace already exists, merge values only
				$subKeys  = array_keys($privileges[$keys[$i]]);
				$subCount = sizeof($subKeys);

				for ($x = 0; $x < $subCount; $x++)
				{

					$this->secure[$keys[$i]][$subKeys[$x]] = TRUE;

				}

			} else
			{

				// add entire namespace and related privileges
				$this->secure[$keys[$i]] =& $privileges[$keys[$i]];

			}

		}

	}

	/**
	 * Remove a privilege.
	 *
	 * @param string $name       A privilege name.
	 * @param string $namespace  A privilege namespace.
	 *
	 * @since  1.0
	 */
	public function & removePrivilege ($name, $namespace = 'org.mojavi')
	{

		$namespace =& $this->getPrivilegeNamespace($namespace);

		if ($namespace !== NULL && isset($namespace[$name]))
		{

			unset($namespace[$name]);

		}

	}

	/**
	 * Remove a privilege namespace and all associated privileges.
	 *
	 * @param string $namespace  A privilege namespace.
	 *
	 * @since  1.0
	 */
	public function removePrivileges ($namespace = 'org.mojavi')
	{

		$namespace =& $this->getPrivilegeNamespace($namespace);
		$namespace =  NULL;

	}

}

?>
