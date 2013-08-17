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
 * The Controller dispatches requests to the proper action and controls
 * application flow.
 */
class Controller
{

    /**
     * A developer supplied authorization handler.
     *
     * @since  1.0
     * @type   AuthorizationHandler
     */
    protected $authorizationHandler;

    /**
     * A user requested content type.
     *
     * @since  1.0
     * @type   string
     */
    protected $contentType;

    /**
     * Currently processing action.
     *
     * @since  1.0
     * @type   string
     */
    protected $currentAction;

    /**
     * Currently processing unit.
     *
     * @since  1.0
     * @type   string
     */
    protected $currentUnit;

    /**
     * ExecutionChain instance.
     *
     * @since  1.0
     * @type   ExecutionChain
     */
    protected $execChain;

    /**
     * An associative array of template-ready data.
     *
     * @since  1.0
     * @type   array
     */
    protected $mojavi;

    /**
     * Determines how a view should be rendered.
     *
     * Possible render modes:
     * - Xmf\Mvc::RENDER_CLIENT - render to the client
     * - Xmf\Mvc::RENDER_VAR    - render to variable
     *
     * @since  1.0
     * @type   int
     */
    protected $renderMode;

    /**
     * A Request instance.
     *
     * @since  1.0
     * @type   Request
     */
    protected $request;

    /**
     * Originally requested action.
     *
     * @since  1.0
     * @type   string
     */
    protected $requestAction;

    /**
     * Originally requested unit.
     *
     * @since  1.0
     * @type   string
     */
    protected $requestUnit;

    /**
     * A developer supplied session handler.
     *
     * @since  1.0
     * @type   SessionHandler
     */
    protected $sessionHandler;

    /**
     * A User instance.
     *
     * @since  1.0
     * @type   User
     */
    protected $user;

    /**
     * A ModelManager instance
     *
     * @since  1.0
     * @type   object ModelManager
     */
    protected $modelManager;

    /**
     * Create a new Controller instance.
     *
     * _This should never be called manually._
     * Use static getInstance() method.
     *
     * @param object $externalCom ExternalCom object
     *
     * @since  1.0
     */
    protected function __construct (&$externalCom=null)
    {

        $this->contentType   =  $externalCom==null?'html':$externalCom; // not exactly
        $this->currentAction =  null;
        $this->currentUnit   =  null;
        $this->execChain     =  new ExecutionChain;
        $this->renderMode    =  \Xmf\Mvc::RENDER_CLIENT;
        $this->requestAction =  null;
        $this->requestUnit   =  null;

        // init Controller objects
        $this->authorizationHandler =  null;
        $this->request              =  new Request($this->parseParameters());
        $this->mojavi               =  array();
        $this->sessionHandler       =  null;
        $this->user                 =  null;

        $this->modelManager         =  new ModelManager;

    }

    /**
     * getComponentName - build filename of action, view, etc.
     *
     * @param $compType type (action, view, etc.)
     * @param $unitName Unit name
     * @param $actName  Action Name
     * @param $actView  View suffix (success, error, input, etc.)
     *
     * @return file name or null on error
     */
    protected function getComponentName ($compType, $unitName, $actName, $actView)
    {

        $cTypes=array(
            'action'     => array('dir'=>'actions', 'suffix'=>'Action.class.php')
        ,	'filter'     => array('dir'=>'filters', 'suffix'=>'Filter.class.php')
        ,	'filterlist' => array('dir'=>'filters', 'suffix'=>'.class.php')
        ,	'template'   => array('dir'=>'templates', 'suffix'=>'.php')
        ,	'view'       => array('dir'=>'views', 'suffix'=>"View_{$actView}.class.php")
        ,	'model'      => array('dir'=>'models', 'suffix'=>'.php')
        );

        $file=null;
        if (isset($cTypes[$compType])) {
            $c=$cTypes[$compType];

            $file = Config::get('UNITS_DIR') . "{$unitName}/{$c['dir']}/{$actName}{$c['suffix']}";
        }
        //trigger_error($file);
        return $file;

    }

    /**
     * Determine if an action exists.
     *
     * @param string $unitName A unit name.
     * @param string $actName  An action name.
     *
     * @return bool TRUE if the given unit has the given action,
     *              otherwise FALSE.
     *
     * @since  1.0
     */
    public function actionExists ($unitName, $actName)
    {
        $file = $this->getComponentName ('action', $unitName, $actName, '');

        return (is_readable($file));

    }

    /**
     * Dispatch a request.
     *
     * _Optional parameters for unit and action exist if you wish to
     * run Mojavi as a page controller._
     *
     * @param string $unitName A unit name.
     * @param string $actName  An action name.
     */
    public function dispatch ($unitName = null, $actName = null)
    {

        if ($this->user === null) {
            // no user type has been set, use the default no privilege user
            $this->user = new User;
        }

        // we always have a session controlled by XOOPS so nix the
        // USE_SESSIONS check and session initialization code

        // set session container
        if ($this->user->getContainer() == NULL) {
            $this->user->setContainer(new SessionContainer);
        }

        $this->user->load();

        // alias mojavi and request objects for easy access
        $mojavi  =& $this->mojavi;
        $request =& $this->request;

        // use default unit and action only if both have not been specified
        if ($unitName == null && !$request->hasParameter(Config::get('UNIT_ACCESSOR', 'unit')) &&
            $actName == null && !$request->hasParameter(Config::get('ACTION_ACCESSOR', 'action')))
        {

            $actName = Config::get('DEFAULT_ACTION', 'DefaultIndex');
            $unitName = Config::get('DEFAULT_UNIT', 'Default');

        } else {

            // has a unit been specified via dispatch()?
            if ($unitName == NULL) {

                // unit not specified via dispatch(), check parameters
                $unitName = $request->getParameter(Config::get('UNIT_ACCESSOR', 'unit'));
                if (empty($unitName)) {
                    $unitName = Config::get('DEFAULT_UNIT', 'Default');
                }
            }

            // has an action been specified via dispatch()?
            if ($actName == NULL) {

                // an action hasn't been specified via dispatch(), let's check
                // the parameters
                $actName = $request->getParameter(Config::get('ACTION_ACCESSOR', 'action'));

                if ($actName == NULL) {

                    // does an Index action exist for this unit?
                    if ($this->actionExists($unitName, 'Index')) {

                        // ok, we found the Index action
                        $actName = 'Index';

                    }
                    if (empty($actName)) {
                        $actName = Config::get('DEFAULT_ACTION', 'DefaultIndex');
                    }

                }

            }

        }

        // if $unitName or $actName equal NULL, we don't set them. we'll let
        // ERROR_404_ACTION do it's thing inside forward()

        // replace unwanted characters
        $actName = preg_replace('/[^a-z0-9\-_]+/i', '', $actName);
        $unitName = preg_replace('/[^a-z0-9\-_]+/i', '', $unitName);

        // set request unit and action
        $this->requestAction      = $actName;
        $this->requestUnit        = $unitName;
        $mojavi['request_action'] = $actName;
        $mojavi['request_unit']   = $unitName;

        // paths
        $mojavi['controller_path']     = $this->getControllerPath();
        $mojavi['current_action_path'] = $this->getControllerPath($unitName,
                                                                  $actName);

        $mojavi['current_unit_path'] = $this->getControllerPath($unitName);
        $mojavi['request_action_path'] = $this->getControllerPath($unitName,
                                                                  $actName);

        $mojavi['request_unit_path'] = $this->getControllerPath($unitName);

        // process our originally request action
        $this->forward($unitName, $actName);

        // shutdown ModelManager
        $this->modelManager->shutdown();

        // store user data
        $this->user->store();

        // cleanup session handler
        if ($this->sessionHandler !== NULL) {

            $this->sessionHandler->cleanup();

        }

        // cleanup loggers
        //$logManager =& LogManager::getInstance();
        //$logManager->cleanup();
        //$logger->stopTime('MVC dispatch');

    }

    protected function loadRequired($filename)
    {
        if (!\Xmf\Loader::loadFile($filename)) {
            die (sprintf('Failed to load %s',$filename));
        }
    }

    private function ifExistsRequire($filename)
    {
        return \Xmf\Loader::loadFile($filename);
    }

    /**
     * Forward the request to an action.
     *
     * @param string $unitName A unit name.
     * @param string $actName An action name.
     *
     * @since  1.0
     */
    public function forward ($unitName, $actName)
    {

        if ($this->currentUnit == $unitName &&
            $this->currentAction == $actName)
        {

            $error = 'Recursive forward on unit ' . $unitName . ', action ' .
                     $actName;

            trigger_error($error, E_USER_ERROR);

            exit;

        }

        // execute unit configuration, if it exists
        $this->ifExistsRequire(Config::get('UNITS_DIR') . $unitName . '/config.php');

        if ($this->actionExists($unitName, $actName)) {

            // create the action instance
            $action = $this->getAction($unitName, $actName);

        } else {

            // the requested action doesn't exist
            $action = NULL;

        }

        // track old unit/action
        $oldAction = $this->currentAction;
        $oldUnit = $this->currentUnit;

        // add unit and action to execution chain, and update current vars
        $this->execChain->addRequest($unitName, $actName, $action);
        $this->updateCurrentVars($unitName, $actName);

        if ($action === NULL) {

            // requested action doesn't exist
            $actName = Config::get('ERROR_404_ACTION', 'PageNotFound');
            $unitName = Config::get('ERROR_404_UNIT', 'Default');

            if (!$this->actionExists($unitName, $actName)) {

                // cannot find error 404 unit/action
                $error = 'Invalid configuration setting(s): ' .
                         'ERROR_404_UNIT (' . $unitName . ') or ' .
                         'ERROR_404_ACTION (' . $actName . ')';

                trigger_error($error, E_USER_ERROR);

                exit;

            }

            // add another request since the action is non-existent
            $action = $this->getAction($unitName, $actName);

            $this->execChain->addRequest($unitName, $actName, $action);
            $this->updateCurrentVars($unitName, $actName);

        }

        $filterChain = new FilterChain;

        // map filters
        $this->mapGlobalFilters($filterChain);
        $this->mapUnitFilters($filterChain, $unitName);

        // and last but not least, the main execution filter
        $filterChain->register(new ExecutionFilter);

        // execute filters
        $filterChain->execute($this, $this->request, $this->user);

        // update current vars
        $this->updateCurrentVars($oldUnit, $oldAction);

    }

    /**
     * Generate a formatted Mojavi URL.
     *
     * @param array $params An associative array of URL parameters.
     *
     * @return string A URL to a Mojavi resource.
     *
     * @since  1.0
     */
    public function genURL ($params)
    {

        $url = Config::get('SCRIPT_PATH');

        $divider  = '&';
        $equals   = '=';
        $url     .= '?';

        $keys  = array_keys($params);
        $count = sizeof($keys);

        for ($i = 0; $i < $count; $i++) {

            if ($i > 0) {

                $url .= $divider;

            }

            $url .= urlencode($keys[$i]) . $equals .
                    urlencode($params[$keys[$i]]);

        }

        return $url;

    }

    /**
     * Generate a URL for a given unit, action and parameters
     *
     * @param string $unitName a unit name
     * @param string $actName an action name
     * @param array  $params  an associative array of additional URL parameters
     *
     * @return string A URL to a Mojavi resource.
     *
     * @since  1.0
     */
    public function getControllerPathWithParams($unitName, $actName, $params)
    {

        $url=$this->getControllerPath($unitName, $actName);
        if (strpos($url,'?')===false) {
            $url .= '?';
        }

        $divider  = '&';
        $equals   = '=';

        $keys  = array_keys($params);
        $count = sizeof($keys);

        for ($i = 0; $i < $count; $i++) {

            if ($i > 0) {

                $url .= $divider;

            }

            $url .= urlencode($keys[$i]) . $equals .
                    urlencode($params[$keys[$i]]);

        }

        return $url;

    }

    /**
     * Retrieve an action implementation instance.
     *
     * @param string $unitName A unit name.
     * @param string $actName An action name.
     *
     * @return Action An Action instance, if the action exists, otherwise
     *                an error will be reported.
     *
     * @since  1.0
     */
    public function getAction ($unitName, $actName)
    {

        $file = $this->getComponentName ('action', $unitName, $actName, '');

        $this->loadRequired($file);

        $action = $actName . 'Action';

        // fix for same name actions
        $unitAction = $unitName . '_' . $action;

        if (class_exists($unitAction)) {

            $action =& $unitAction;

        }

        return new $action;

    }

    /**
     * Retrieve the developer supplied authorization handler.
     *
     * @return AuthorizationHandler An AuthorizationHandler instance, if an
     *                              authorization handler has been set,
     *                              otherwise NULL.
     *
     * @since  1.0
     */
    public function & getAuthorizationHandler ()
    {
        return $this->authorizationHandler;

    }

    /**
     * Retrieve the user supplied content type.
     *
     * @since  1.0
     */
    public function getContentType ()
    {
        return $this->contentType;

    }

    /**
     * Retrieve an absolute web path to the public controller file.
     *
     * @param string $unitName A unit name.
     * @param string $actName  An action name.
     *
     * @return string An absolute web path representing the controller file,
     *                which also includes unit and action names.
     *
     * @since  1.0
     */
    public function getControllerPath ($unitName = null, $actName = null)
    {

        $path = Config::get('SCRIPT_PATH');
        //$path = $_SERVER['SCRIPT_NAME'];

        $varsep = '?';

        if (!(empty($unitName) || $unitName==Config::get('DEFAULT_UNIT', 'Default'))) {
            $path .= $varsep.Config::get('UNIT_ACCESSOR','unit')."=$unitName";
            $varsep = '&';
        }
        if (!empty($actName)) {
            $path .= $varsep.Config::get('ACTION_ACCESSOR','action')."=$actName";
            $varsep = '&';
        }

        return $path;

    }

    /**
     * Retrieve the name of the currently processing action.
     *
     * / If the request has not been forwarded, this will return the
     *   the originally requested action./
     *
     * @since  1.0
     */
    public function getCurrentAction ()
    {
        return $this->currentAction;

    }

    /**
     * Retrieve the name of the currently processing unit.
     *
     * / If the request has not been forwarded, this will return the
     *   the originally requested unit./
     *
     * @since  1.0
     */
    public function getCurrentUnit ()
    {
        return $this->currentUnit;

    }

    /**
     * Retrieve the execution chain.
     *
     * @return ExecutionChain An ExecutionChain instance.
     *
     * @since  1.0
     */
    public function & getExecutionChain ()
    {
        return $this->execChain;

    }

    /**
     * Retrieve the single instance of Controller.
     *
     * @param object $externalCom ExternalCom object
     *
     * @return Controller A Controller instance.
     *
     * @since  1.0
     */
    public static function & getInstance (&$externalCom=null)
    {

        static $instance;

        if ($instance === NULL) {
            $controllerClass = get_called_class(); // not available PHP<5.3
            $instance = new $controllerClass($externalCom);

        }
        Context::set($instance);

        return $instance;

    }

    /**
     * Retrieve an absolute file-system path home directory of the currently
     * processing unit.
     *
     *  _ If the request has been forwarded, this will return the directory of
     *    the forwarded unit._
     *
     * @return string A unit directory.
     *
     * @since  1.0
     */
    public function getUnitDir ()
    {
        return (Config::get('UNITS_DIR') . $this->currentUnit . '/');

    }

    /**
     * Retrieve the Mojavi associative array.
     *
     * @return array An associative array of template-ready data.
     *
     * @since  1.0
     */
    public function & getMojavi ()
    {
        return $this->mojavi;

    }

    /**
     * Retrieve the global render mode.
     *
     * @return int One of two possible render modes:
     * - Xmf\Mvc::RENDER_CLIENT  - render to the client
     * - Xmf\Mvc::RENDER_VAR     - render to variable
     *
     * @since  1.0
     */
    public function getRenderMode ()
    {
        return $this->renderMode;

    }

    /**
     * Retrieve the request instance.
     *
     * @return Request A Request instance.
     *
     * @since  1.0
     */
    public function & getRequest ()
    {
        return $this->request;

    }

    /**
     * Retrieve the name of the originally requested action.
     *
     * @return string An action name.
     *
     * @since  1.0
     */
    public function getRequestAction ()
    {
        return $this->requestAction;

    }

    /**
     * Retrieve the name of the originally requested unit.
     *
     * @return string A unit name.
     *
     * @since  1.0
     */
    public function getRequestUnit()
    {
        return $this->requestUnit;

    }

    /**
     * Retrieve the developer supplied session handler.
     *
     * @return SessionHandler A SessionHandler instance, if a session handler
     *                        has been set, otherwise <b>NULL</b>.
     *
     * @since  1.0
     */
    public function & getSessionHandler ()
    {
        return $this->sessionHandler;

    }

    /**
     * Retrieve the currently requesting user.
     *
     * @return User A User instance.
     *
     * @since  1.0
     */
    public function & getUser ()
    {
        return $this->user;

    }

    /**
     * Retrieve a view implementation instance.
     *
     * @param string A unit name.
     * @param string An action name.
     * @param string A view name.
     *
     * @return View A View instance.
     */
    public function getView ($unitName, $actName, $viewName)
    {

        $file = $this->getComponentName ('view', $unitName, $actName, $viewName);

        $this->loadRequired($file);

        $view =  $actName . 'View';

        // fix for same name views
        $unitView = $unitName . '_' . $view;

        if (class_exists($unitView)) {

            $view =& $unitView;

        }

        return new $view;

    }

    /**
     * Map global filters.
     *
     * @param FilterChain A FilterChain instance.
     *
     * @since  1.0
     */
    public function mapGlobalFilters (&$filterChain)
    {

        static $list;

        if (!isset($list)) {

            $file = Config::get('UNITS_DIR') . 'GlobalFilterList.php';

            if ($this->ifExistsRequire($file)) {

                $list = new GlobalFilterList;
                $list->registerFilters($filterChain, $this, $this->request,
                                       $this->user);

            }

        } else {

            $list->registerFilters($filterChain, $this, $this->request,
                                   $this->user);

        }

    }

    /**
     * Map all filters for a given unit.
     *
     * @param FilterChain A FilterChain instance.
     * @param unitName     A unit name.
     *
     * @since  1.0
     */
    public function mapUnitFilters (&$filterChain, $unitName)
    {

        static $cache;

        if (!isset($cache)) {

            $cache = array();

        }

        $listName = $unitName . 'FilterList';

        if (!isset($cache[$listName])) {

            $file = $this->getComponentName ('filterlist', $unitName, "{$listName}", '');

            if ($this->ifExistsRequire($file)) {

                $list             =  new $listName;
                $cache[$listName] =& $list;

                // register filters
                $list->registerFilters($filterChain, $this, $this->request,
                                       $this->user);

            }

        } else {

            $cache[$listName]->registerFilters($filterChain, $this,
                                               $this->request, $this->user);

        }

    }

    /**
     * Parse and retrieve all PATH/REQUEST parameters.
     *
     * @return array An associative array of parameters.
     *
     * @since  1.0
     */
    protected function & parseParameters ()
    {
        /**
         * \Xmf\Request::get($hash = 'default', $mask = 0)
         * bitmask values for $mask are:
         *   -  \Xmf\Request::NOTRIM    (1)  set to skip trim() on values
         *   -  \Xmf\Request::ALLOWRAW  (2)  set to disable html check
         *   -  \Xmf\Request::ALLOWHTML (4)  set to allow all html, clear for 'safe' only
         *
         * We will clean agressively. Raw values are not overwritten, so
         * code can go back and get directly with different options if
         * needed. Cleaning also handles magic_quotes_gpc clean up.
         *
         */

        $values = array();

        // load GET params into $values array
        $values = array_merge($values, \Xmf\Request::get('GET', 0));

        // load POST params into $values array
        $values = array_merge($values, \Xmf\Request::get('POST', 0));

        return $values;

    }

    /**
     * Redirect the request to another location.
     *
     * @param string A URL.
     *
     * @since  1.0
     */
    public function redirect ($url)
    {

        header('Location: ' . $url);

    }

    /**
     * Set the developer supplied authorization handler.
     *
     * @param Authorizationhandler An AuthorizationHandler instance.
     *
     * @since  1.0
     */
    public function setAuthorizationHandler (&$handler)
    {

        $this->authorizationHandler =& $handler;

    }

    /**
     * Set the content type.
     *
     * @param string A user supplied content type.
     *
     * @since  1.0
     */
    public function setContentType ($contentType)
    {

        $this->contentType = $contentType;

    }

    /**
     * Set the global render mode.
     *
     * @param int $mode Global render mode, which is one of the following two:
     * - Xmf\Mvc::RENDER_CLIENT - render to the client
     * - Xmf\Mvc::RENDER_VAR    - render to variable
     */
    public function setRenderMode ($mode)
    {

        $this->renderMode = $mode;

    }

    /**
     * Set the session handler.
     *
     * @param SessionHandler A SessionHandler instance.
     *
     * @since  1.0
     */
    public function setSessionHandler (&$handler)
    {

        $this->sessionHandler =& $handler;

    }

    /**
     * Set the user type.
     *
     * @param User A User instance.
     *
     * @since  1.0
     */
    public function setUser (&$user)
    {

        $this->user =& $user;

    }

    /**
     * Update current unit and action data.
     *
     * @param string A unit name.
     * @param string An action name.
     *
     * @since  1.0
     */
    protected function updateCurrentVars ($unitName, $actName)
    {

        // alias objects for easy access
        $mojavi =& $this->mojavi;

        $this->currentUnit = $unitName;
        $this->currentAction = $actName;

        // names
        $mojavi['current_action'] = $actName;
        $mojavi['current_unit'] = $unitName;

        // directories
        $mojavi['unit_dir']   = Config::get('UNITS_DIR');
        $mojavi['template_dir'] = Config::get('UNITS_DIR') . $unitName .
                                  '/templates/';

        // paths
        $mojavi['current_action_path'] = $this->getControllerPath($unitName,
                                                                  $actName);
        $mojavi['current_unit_path'] = $this->getControllerPath($unitName);

    }

    /**
     * Determine if a view exists.
     *
     * @param string A unit name.
     * @param string An action name.
     * @param string A view name.
     *
     * @return bool <b>TRUE</b>, if the view exists, otherwise <b>FALSE</b>.
     *
     * @since  1.0
     */
    public function viewExists ($unitName, $actName, $viewName)
    {

        $file = $this->getComponentName ('view', $unitName, $actName, $viewName);

        return (is_readable($file));

    }

    /**
     * Retrieve a filter implementation instance.
     *
     * @param string $name    - A filter name.
     * @param string $unitName - A unit name, defaults to current unit
     *
     * @return a Filter instance.
     */
    public function getFilter ($name, $unitName='')
    {

        if (empty($unitName)) { $unitName = $this->currentUnit; }
        $file = $this->getComponentName ('filter', $unitName, $name, '');

        $this->loadRequired($file);

        $filter =  $name . 'Filter';
        // fix for same name filters
        $unitFilter = $unitName . '_' . $filter;
        if (class_exists($unitFilter)) {
            $filter =& $unitFilter;
        }

        return new $filter;

    }

    /**
     * Retrieve the ModelManager instance.
     *
     * @return object ModelManager
     */
    public function & getModels ()
    {
        return $this->modelManager;
    }

}
