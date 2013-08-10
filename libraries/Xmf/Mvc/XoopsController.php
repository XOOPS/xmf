<?php

namespace Xmf\Mvc;

/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

/**
 * The Xmf\Mvc\XoopsController is a XOOPS specific Controller implementation
 *
 * @copyright       The XOOPS Project http://sourceforge.net/projects/xoops/
 * @license         http://www.fsf.org/copyleft/gpl.html GNU private license
 * @package         Xmf\Mvc
 * @since           1.0
 * @author          Richard Griffith
 */

/**
 * XoopsController implements a controller with with specific
 * characteristics optimized for the XOOPS environment, including:
 * - XOOPS specific User and AuthorizationHandler
 * - XOOPS module helper
 * - XOOPS module appropriate configuration, defaults and autoloading
 */
class XoopsController extends Controller
{

    /**
     *  @var External communication block object
     */
    protected $externalCom;

    /**
     *  @var XOOPS Module directory name
     */
    protected $_dirname;

    /**
     *  @var XOOPS Module helper
     */
    protected $modhelper;

   /**
    * XOOPS specific controller constructor, sets user and
    * authorization handler to XOOPS specific onjects.
    *
    * @param object $externalCom  ExternalCom object
    *
    * @since  1.0
    */
    protected function __construct (&$externalCom=null)
    {
        $this->externalCom =& $externalCom;
        if (is_object($externalCom) && method_exists ($externalCom, 'getDirname')) {
            $this->_dirname = $externalCom->getDirname();
        } else {
            $this->_dirname = $GLOBALS['xoopsModule']->getVar('dirname');
        }
        $this->modhelper = \Xmf\Module\Helper::getHelper($this->_dirname);
        //$this->modhelper->setDebug(true);
        $pathname=XOOPS_ROOT_PATH .'/modules/'.$this->_dirname.'/';
        // set some reasonable defaults if config is empty
        if (!Config::get('MODULES_DIR', false)) {
            Config::setCompatmode(false);
            Config::set('MODULES_DIR', $pathname.'/xmfmvc/');
            Config::set('SCRIPT_PATH', XOOPS_URL .'/modules/'.$this->_dirname.'/index.php');
            Config::set('MODULE_ACCESSOR', 'module');
            Config::set('ACTION_ACCESSOR', 'action');
            Config::set('DEFAULT_MODULE', 'Default');
            Config::set('DEFAULT_ACTION', 'Index');
            Config::set('ERROR_404_MODULE', 'Default');
            Config::set('ERROR_404_ACTION', 'PageNotFound');
            Config::set('SECURE_MODULE', 'Default');
            Config::set('SECURE_ACTION', 'NoPermission');
        }

        // this will quietly ignore a missing config file
        $configfile=$pathname.'/config.php';
        \Xmf\Loader::loadFile($configfile, true);

        parent::__construct ();

        $this->user                 =  new XoopsUser();
        $this->authorizationHandler =  new XoopsAuthHandler();
        $this->user->setXoopsPermissionMap(Config::get('PermissionMap',array()));

    }

    /**
     * Retrieve the single instance of Controller.
     *
     * @param string $contentType A user supplied content type.
     *
     * @return Controller A Controller instance.
     *
     * @since  1.0
     */
    public static function & getInstance (&$externalCom=null)
    {

        static $instance;

        if ($instance === NULL) {
            $controllerClass=__CLASS__;
            $instance = new $controllerClass($externalCom);

        }
        Context::set($instance);

        return $instance;

    }

    /**
     * getComponentName - build filename of action, view, etc.
     *
     * @param $compType type (action, view, etc.)
     * @param $modName Module name
     * @param $actName Name
     * @param $actView view suffix (success, error, input, etc.)
     *
     * @return file name or null on error
     */
    protected function getComponentName ($compType, $modName, $actName, $actView)
    {

        $cTypes=array(
            'action'     => array('dir'=>'actions', 'suffix'=>'Action.php')
        ,	'filter'     => array('dir'=>'filters', 'suffix'=>'Filter.php')
        ,	'filterlist' => array('dir'=>'filters', 'suffix'=>'.php')
        ,	'template'   => array('dir'=>'templates', 'suffix'=>'.php')
        ,	'view'       => array('dir'=>'views', 'suffix'=>"View_{$actView}.php")
        ,	'model'      => array('dir'=>'models', 'suffix'=>'.php')
        );

        $file=null;
        if (isset($cTypes[$compType])) {
            $c=$cTypes[$compType];

            $file = Config::get('MODULES_DIR') . "{$modName}/{$c['dir']}/{$actName}{$c['suffix']}";
        }
        //trigger_error($file);
        return $file;

    }

   /**
    * getExternalCom - get the ExternalCom object
    *
    * TODO - should this be in parent instead?
    *
    * @return object ExternalCom
    *
    * @since  1.0
    */
    public function getExternalCom()
    {
        return $this->externalCom;
    }

    // These methods provide quick access to some XOOPS objects.
    // The controller already is module aware and has a module
    // helper established. Share that.

    /**
     * getHandler - get XoopsObjectHandler
     *
     * @param string $name
     *
     * @return bool|XoopsObjectHandler|XoopsPersistableObjectHandler
     *
     * @since  1.0
     */
    public function getHandler($name)
    {
        return $this->modhelper->getHandler($name);
    }

    /**
     * modHelper - get module helper
     *
     * @return object Module Helper
     *
     * @since  1.0
     */
    public function modHelper($name)
    {
        return $this->modhelper;
    }

    /**
     * modGetVar - get varaible from XoopsModule
     *
     * @param string $name name of module variable
     *
     * @return mixed module getVar return
     *
     * @since  1.0
     */
    public function modGetVar($name)
    {
        return $this->modhelper->getModule()->getVar($name);
    }

    /**
     * modGetInfo - get modversion item
     *
     * @param string $name name of module info variable
     *
     * @return mixed module getInfo return
     *
     * @since  1.0
     */
    public function modGetInfo($name)
    {
        return $this->modhelper->getModule()->getInfo($name);
    }

    /**
     * modGetConfig - get a module configuration value
     *
     * @param string $name name of module configuration
     *
     * @return mixed module helper getConfig return
     *
     * @since  1.0
     */
    public function modGetConfig($name)
    {
        return $this->modhelper->getConfig($name);
    }

}
