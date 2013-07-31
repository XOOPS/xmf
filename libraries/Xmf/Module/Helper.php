<?php
/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

/**
 * @copyright       The XOOPS Project http://sourceforge.net/projects/xoops/
 * @license         http://www.fsf.org/copyleft/gpl.html GNU public license
 * @package         Xmf
 * @since           0.1
 * @author          trabis <lusopoemas@gmail.com>
 * @version         $Id: Helper.php 8065 2011-11-06 02:02:32Z beckmi $
 */

defined('XMF_EXEC') or die('Xmf was not detected');

class Xmf_Module_Helper
{
    /**
     * @var string
     */
    protected $_dirname;

    /**
     * @var XoopsModule
     */
    protected $_object;

    /**
     * @var array of XoopsObjectHandler|XoopsPersistableObjectHandler
     */
    protected $_handler;

    /**
     * @var array
     */
    protected $_config;

    /**
     * @var bool
     */
    protected $_debug;

    /**
     * @var array of Xmf_Module_Helper_Abstract
     */
    protected $_helper;

    /**
     * @param $dirname
     */
    private function __construct($dirname)
    {
        $this->_dirname = $dirname;
    }

    /**
     * @static
     * @param string $dirname
     * @return Xmf_Module_Helper
     */
    public static function getInstance($dirname = 'notsetyet')
    {
        static $instance = array();
        if (!isset($instance[$dirname])) {
            $class = __CLASS__;
            $instance[$dirname] = new $class($dirname);
        }
        return $instance[$dirname];

    }

    /**
     * @return XoopsModule
     */
    public function getModule()
    {
        if ($this->_object == null) {
            $this->_initObject();
        }
        if (!is_object($this->_object)) {
            $this->addLog("ERROR :: Module '{$this->_dirname}' does not exist");
        }
        return $this->_object;
    }
    /** TODO eliminate this and replace with 2.6 style getModule **/
    public function getObject() { return $this->getModule(); }

    /**
     * @param string $name
     * @return mixed
     */
    public function getConfig($name)
    {
        if ($this->_config == null) {
            $this->_initConfig();
        }
        if (!$name) {
            $this->addLog("Getting all config");
            return $this->_config;
        }

        if (!isset($this->_config[$name])) {
            $this->addLog("ERROR :: Config '{$name}' does not exist");
            $ret = null;
            return $ret;
        }

        $this->addLog("Getting config '{$name}' : " . $this->_config[$name]);
        return $this->_config[$name];
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return
     */
    public function setConfig($name, $value = null)
    {
        if ($this->_config == null) {
            $this->_initConfig();
        }

        $this->_config[$name] = $value;

        $this->addLog("Setting config '{$name}' : " . $this->_config[$name]);
        return $this->_config[$name];
    }

    /**
     * @param string $name
     * @return bool|XoopsObjectHandler|XoopsPersistableObjectHandler
     */
    public function getHandler($name)
    {
        $ret = false;
        $name = strtolower($name);
        if (!isset($this->_handler[$name])) {
            $this->_initHandler($name);
        }

        if (!isset($this->_handler[$name])) {
            $this->addLog("ERROR :: Handler '{$name}' does not exist");
        } else {
            $this->addLog("Getting handler '{$name}'");
            $ret = $this->_handler[$name];
        }
        return $ret;
    }

    /**
     * @param string $name
     * @return bool|Xmf_Module_Helper_Abstract
     */
    public function getHelper($name)
    {
        $ret = false;
        $name = strtolower($name);
        if (!isset($this->_helper[$name])) {
            $this->_initHelper($name);
        }

        if (!isset($this->_helper[$name])) {
            $this->addLog("ERROR :: Helper '{$name}' does not exist");
        } else {
            $this->addLog("Getting helper '{$name}'");
            $ret = $this->_helper[$name];
        }
        return $ret;
    }

    /**
     * @param string $name
     * @return bool
     */
    protected function _initHelper($name)
    {
        $this->addLog('INIT ' . $name . ' HELPER');
        $uname = ucfirst($name);
        if (file_exists($hnd_file = XMF_LIBRARIES_PATH . "/Xmf/Module/Helper/{$uname}.php")) {
            include_once $hnd_file;
            $class = "Xmf_Module_Helper_{$uname}";
            if (class_exists($class)) {
                $this->_helper[$name] = new $class($this->getObject());
                $this->addLog("Loading Helper '{$name}'");
                return true;
            }
        }
        $this->addLog("ERROR :: Helper '{$name}' could not be loaded");
        return false;
    }

    /**
     * @return void
     */
    protected function _initObject()
    {
        global $xoopsModule;
        if (isset($xoopsModule) && is_object($xoopsModule) && $xoopsModule->getVar('dirname') == $this->_dirname) {
            $this->_object = $xoopsModule;
        } else {
            /* @var $module_handler XoopsModuleHandler */
            $module_handler = xoops_getHandler('module');
            $this->_object = $module_handler->getByDirname($this->_dirname);
        }
        $this->addLog('INIT MODULE OBJECT');
    }

    /**
     * @return void
     */
    protected function _initConfig()
    {
        $this->addLog('INIT CONFIG');
        global $xoopsModule;
        if (isset($xoopsModule) && is_object($xoopsModule) && $xoopsModule->getVar('dirname') == $this->_dirname) {
            global $xoopsModuleConfig;
            $this->_config =& $xoopsModuleConfig;
        } else {
            /* @var $config_handler XoopsConfigHandler */
            $config_handler = xoops_gethandler('config');
            $this->_config = $config_handler->getConfigsByCat(0, $this->getObject()->getVar('mid'));
        }
    }

    /**
     * @param string $name
     * @return void
     */
    protected function _initHandler($name)
    {
        $this->addLog('INIT ' . $name . ' HANDLER');

        if (!isset($this->_handler[$name])) {
            if (file_exists($hnd_file = XOOPS_ROOT_PATH . "/modules/{$this->_dirname}/class/{$name}.php")) {
                include_once $hnd_file;
            }
            $class = ucfirst(strtolower($this->_dirname)) . ucfirst(strtolower($name)) . 'Handler';
            if (class_exists($class)) {
                $db = XoopsDatabaseFactory::getDatabaseConnection();
                $this->_handler[$name] = new $class($db);
                $this->addLog("Loading class '{$class}'");
            } else {
                $this->addLog("ERROR :: Class '{$class}' could not be loaded");
            }
        }
    }

    /**
     * @param string $name
     * @param null $language
     * @return bool
     */
    public function loadLanguage($name, $language = null)
    {
        if ($ret = Xmf_Language::load($name, $this->_dirname, $language)) {
            $this->addLog("Loading language '{$name}'");
        } else {
            $this->addLog("ERROR :: Language '{$name}' could not be loaded");
        }
        return $ret;
    }

    /**
     * @param bool $bool
     * @return void
     */
    public function setDebug($bool = true)
    {
        $this->_debug = (bool)$bool;
    }

    /**
     * @param string $log
     * @return void
     */
    public function addLog($log)
    {
        if ($this->_debug) {
            if (is_object($GLOBALS['xoopsLogger'])) {
                $GLOBALS['xoopsLogger']->addExtra(is_object($this->_object) ? $this->_object->name()
                        : $this->_dirname, $log);
            }
        }
    }
}
