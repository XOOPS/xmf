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
 * A User object provides an interface to data representing an individual
 * user, allowing for access and managment of attributes and security
 * related data.
 *
 */
class Xmf_Mvc_User extends Xmf_Mvc_ContextAware
{

	/**
	 * The authenticated status of the user.
	 *
	 * @since  1.0
	 * @type   bool
	 */
	protected $authenticated;

	/**
	 * An associative array of attributes.
	 *
	 * @since  1.0
	 * @type   bool
	 */
	protected $attributes;

	/**
	 * Container instance.
	 *
	 * @since  1.0
	 * @type   bool
	 */
	protected $container;

	/**
	 * Security related data
	 *
	 * @since  1.0
	 * @type   mixed
	 */
	protected $secure;

	/**
	 * Create a new User instance.
	 *
	 * @since  1.0
	 */
	public function __construct ()
	{

		$this->authenticated = NULL;
		$this->attributes    = NULL;
		$this->container     = NULL;
		$this->secure        = NULL;

	}

	/**
	 * Clear all user data.
	 *
	 * @since  1.0
	 */
	public function clearAll ()
	{

		$this->authenticated = FALSE;
		$this->attributes    = NULL;
		$this->attributes    = array();
		$this->secure        = NULL;
		$this->secure        = array();

	}

	/**
	 * Clear all attribute namespaces and their associated attributes.
	 *
	 * @since  1.0
	 */
	public function clearAttributes ()
	{

		$this->attributes = NULL;
		$this->attributes = array();

	}

	/**
	 * Retrieve an attribute.
	 *
	 * @param string $name       An attribute name.
	 * @param string $namespace  An attribute namespace.
	 *
	 * @return mixed An attribute value, if the given attribute exists,
	 *               otherwise NULL.
	 *
	 * @since  1.0
	 */
	public function & getAttribute ($name, $namespace = 'org.mojavi')
	{

		$namespace =& $this->getAttributes($namespace);

		if ($namespace != NULL && isset($namespace[$name]))
		{

			return $namespace[$name];

		}
		$null=NULL;
		return $null;

	}

	/**
	 * Retrieve an indexed array of attribute names.
	 *
	 * @param string $namespace  An attribute namespace.
	 *
	 * @return array An array of attribute names if the given namespace exists,
	 *               otherwise NULL.
	 *
	 * @since  1.0
	 */
	public function getAttributeNames ($namespace = 'org.mojavi')
	{

		$namespace =& $this->getAttributes($namespace);

		return ($namespace != NULL) ? array_keys($namespace) : NULL;

	}

	/**
	 * Retrieve an indexed array of attribute namespaces.
	 *
	 * @return array An array of attribute namespaces.
	 *
	 * @since  1.0
	 */
	public function getAttributeNamespaces ()
	{

		return array_keys($this->attributes);

	}

	/**
	 * Retrieve an associative array of namespace attributes.
	 *
	 * @param string $namespace  An attribute namespace.
	 * @param bool   Whether or not to auto-create the attribute namespace
	 *               if it doesn't already exist.
	 *
	 * @return array An array of attributes, if the given namespace exists,
	 *               otherwise NULL.
	 *
	 * @since  1.0
	 */
	public function & getAttributes ($namespace, $create = FALSE)
	{

		if (isset($this->attributes[$namespace]))
		{

			return $this->attributes[$namespace];

		} else if ($create)
		{

			$this->attributes[$namespace] = array();

			return $this->attributes[$namespace];

		}
		$null=NULL;
		return $null;

	}

	/**
	 * Retrieve the container.
	 *
	 * @return Container A Container instance.
	 *
	 * @since  1.0
	 */
	public function & getContainer ()
	{

		return $this->container;

	}

	/**
	 * Determine if the user has an attribute.
	 *
	 * @param string $name       An attribute name.
	 * @param string $namespace  An attribute namespace.
	 *
	 * @return bool TRUE if the given attribute exists, otherwise FALSE.
	 *
	 * @since  1.0
	 */
	public function hasAttribute ($name, $namespace = 'org.mojavi')
	{

		$namespace =& $this->getAttributes($namespace);

		return ($namespace != NULL && isset($namespace[$name])) ? TRUE : FALSE;

	}

	/**
	 * Determine the authenticated status of the user.
	 *
	 * @return bool TRUE if the user is authenticated, otherwise FALSE.
	 *
	 * @since  1.0
	 */
	public function isAuthenticated ()
	{

		return ($this->authenticated === TRUE) ? TRUE : FALSE;

	}

	/**
	 * Load data from the container.
	 *
	 * _This method should never be called manually._
	 *
	 * @since  1.0
	 */
	public function load ()
	{

		if ($this->container !== NULL)
		{

			$this->container->load($this->authenticated, $this->attributes,
								   $this->secure);

		}

	}

	/**
	 * Merge a new set of attributes with the existing set.
	 *
	 * @param array $attributes  An associative array of attributes.
	 *
	 * @since  1.0
	 */
	public function mergeAttributes ($attributes)
	{

		$keys  = array_keys($attributes);
		$count = sizeof($keys);

		for ($i = 0; $i < $count; $i++)
		{

			if (isset($this->attributes[$keys[$i]]))
			{

				// namespace already exists, merge values only
				$subKeys  = array_keys($attributes[$keys[$i]]);
				$subCount = sizeof($subKeys);

				for ($x = 0; $x < $subCount; $x++)
				{

					$this->attributes[$keys[$i]][$subKeys[$x]] =& $attributes[$keys[$i]][$subKeys[$x]];

				}

			} else
			{

				// merge entire value
				$this->attributes[$keys[$i]] =& $attributes[$keys[$i]];

			}

		}

	}

	/**
	 * Remove an attribute.
	 *
	 * @param string $name       An attribute name.
	 * @param string $namespace  An attribute namespace.
	 *
	 * @return mixed An attribute value, if the given attribute exists and has
	 *               been removed, otherwise NULL.
	 *
	 * @since  1.0
	 */
	public function & removeAttribute ($name, $namespace = 'org.mojavi')
	{

		$namespace =& $this->getAttributes($namespace);

		if ($namespace !== NULL && isset($namespace[$name]))
		{

			$value =& $namespace[$name];

			unset($namespace[$name]);

			return $value;

		}
		$null=NULL;
		return $null;

	}

	/**
	 * Remove an attribute namespace and all associated attributes.
	 *
	 * @param string $namespace  An attribute namespace.
	 *
	 * @since  1.0
	 */
	public function removeAttributes ($namespace = 'org.mojavi')
	{

		$namespace =& $this->getAttributes($namespace);
		$namespace =  NULL;

	}

	/**
	 * Set an attribute.
	 *
	 * @param string $name       An attribute name.
	 * @param mixed  $value      An attribute value.
	 * @param string $namespace  An attribute namespace.
	 *
     * @since  1.0
	 */
	public function setAttribute ($name, $value, $namespace = 'org.mojavi')
	{

		$namespace        =& $this->getAttributes($namespace, TRUE);
		$namespace[$name] =  $value;

	}

	/**
	 * Set an attribute by reference.
	 *
	 * @param string $name       An attribute name.
	 * @param mixed  $value      An attribute value.
	 * @param string $namespace  An attribute namespace.
	 *
	 * @since  1.0
	 */
	public function setAttributeByRef ($name, &$value, $namespace = 'org.mojavi')
	{

		$namespace        =& $this->getAttributes($namespace, TRUE);
		$namespace[$name] =& $value;

	}

	/**
	 * Set the authenticated status of the user.
	 *
	 * @param bool The authentication status.
	 *
	 * @since  1.0
	 */
	public function setAuthenticated ($status)
	{

		$this->authenticated = $status;

	}

	/**
	 * Set the container.
	 *
	 * @param $container A Container instance.
	 *
	 * @since  1.0
	 */
	public function setContainer (&$container)
	{

		$this->container =& $container;

	}

	/**
	 * Store data in the container.
	 *
	 *  _This method should never be called manually._
	 *
	 * @since  1.0
	 */
	public function store ()
	{

		if ($this->container !== NULL)
		{

			$this->container->store($this->authenticated, $this->attributes,
									$this->secure);

		}

	}

}

?>
