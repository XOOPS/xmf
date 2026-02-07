<?php

/**
 * PHPStan stub for XOOPS global functions and constants
 */

/**
 * @param string $name
 * @param string|null $optional_name
 * @return XoopsObjectHandler|XoopsPersistableObjectHandler|false
 */
function xoops_getHandler($name, $optional_name = null) {}

/**
 * @param string $url
 * @param int $time
 * @param string $message
 * @param bool $addredirect
 * @param bool $allowExternalLink
 * @return void
 */
function redirect_header($url, $time = 3, $message = '', $addredirect = true, $allowExternalLink = false) {}

/**
 * @param string $dirname
 * @return bool
 */
function xoops_isActiveModule($dirname) {}

// XOOPS Path Constants
define('XOOPS_ROOT_PATH', '');
define('XOOPS_URL', '');
define('XOOPS_VAR_PATH', '');
define('XOOPS_DB_NAME', '');
define('XOOPS_UPLOAD_URL', '');
define('XOOPS_UPLOAD_PATH', '');
define('XOOPS_GROUP_ANONYMOUS', 3);
define('XOOPS_GROUP_ADMIN', 1);
define('XOOPS_GROUP_USERS', 2);

// XMF Language Constants (loaded at runtime via Language::load('xmf'))
define('_DB_XMF_TABLE_IS_NOT_DEFINED', 'Table is not defined');
define('_AM_XMF_MODULE_INSTALLED', '%s is installed');
define('_AM_XMF_MODULE_NOT_INSTALLED', '%s is NOT installed');
define('_AM_XMF_MODULE_VERSION', '%s version %s (installed: %s)');
define('_AM_XMF_MODULE_NOTFOUND', '%s (%s) not found');
define('_XMF_STOPWORDS', '');
