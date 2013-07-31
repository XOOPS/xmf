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
 * The Controller dispatches requests to the proper action and controls
 * application flow.
 */
class Xmf_Mvc_Controller
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
	 * Currently processing module.
	 *
	 * @since  1.0
	 * @type   string
	 */
	protected $currentModule;

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
	 * - Xmf_Mvc::RENDER_CLIENT - render to the client
	 * - Xmf_Mvc::RENDER_VAR    - render to variable
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
	 * Originally requested module.
	 *
	 * @since  1.0
	 * @type   string
	 */
	protected $requestModule;

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
	 * @param object $externalCom  ExternalCom object
	 *
	 * @since  1.0
	 */
	protected function __construct (&$externalCom=null)
	{

		$this->contentType   =  $externalCom==null?'html':$externalCom; // not exactly
		$this->currentAction =  NULL;
		$this->currentModule =  NULL;
		$this->execChain     =  new Xmf_Mvc_ExecutionChain;
		$this->renderMode    =  Xmf_Mvc::RENDER_CLIENT;
		$this->requestAction =  NULL;
		$this->requestModule =  NULL;

		// init Controller objects
		$this->authorizationHandler =  NULL;
		$this->request              =  new Xmf_Mvc_Request($this->parseParameters());
		$this->mojavi               =  array();
		$this->sessionHandler       =  NULL;
		$this->user                 =  NULL;

		$this->modelManager         =  new Xmf_Mvc_ModelManager;

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
			'action'     => array('dir'=>'actions', 'suffix'=>'Action.class.php')
		,	'filter'     => array('dir'=>'filters', 'suffix'=>'Filter.class.php')
		,	'filterlist' => array('dir'=>'filters', 'suffix'=>'.class.php')
		,	'template'   => array('dir'=>'templates', 'suffix'=>'.php')
		,	'view'       => array('dir'=>'views', 'suffix'=>"View_{$actView}.class.php")
		,	'model'      => array('dir'=>'models', 'suffix'=>'.php')
		);

		$file=null;
		if(isset($cTypes[$compType])) {
			$c=$cTypes[$compType];

			$file = Xmf_Mvc_Config::get('MODULES_DIR') . "{$modName}/{$c['dir']}/{$actName}{$c['suffix']}";
		}
		//trigger_error($file);
		return $file;

	}

	/**
	 * Determine if an action exists.
	 *
	 * @param string $modName A module name.
	 * @param string $actName An action name.
	 *
	 * @return bool TRUE if the given module has the given action,
	 *              otherwise FALSE.
	 *
	 * @since  1.0
	 */
	public function actionExists ($modName, $actName)
	{
		$file = $this->getComponentName ('action', $modName, $actName, '');

		return (is_readable($file));

	}

	/**
	 * Dispatch a request.
	 *
	 * _Optional parameters for module and action exist if you wish to
	 * run Mojavi as a page controller._
	 *
	 * @param string $modName A module name.
	 * @param string $actName An action name.
	 */
	public function dispatch ($modName = NULL, $actName = NULL)
	{
		$logger=XoopsLogger::getInstance();
		$logger->startTime('MVC dispatch');
		// register error handler as default logger's standard() method
		//$logger =& LogManager::getLogger();

		// set error handler
		//set_error_handler(array(&$logger, 'standard'));

		if ($this->user === NULL)
		{

			// no user type has been set, use the default no privilege user
			$this->user = new Xmf_Mvc_User;

		}

		// we always have a session controlled by XOOPS so nix the
		// USE_SESSIONS check and session initialization code

		// set session container
		if($this->user->getContainer() == NULL)
		{
			$this->user->setContainer(new Xmf_Mvc_SessionContainer);
		}

		$this->user->load();

		// alias mojavi and request objects for easy access
		$mojavi  =& $this->mojavi;
		$request =& $this->request;

		// use default module and action only if both have not been specified
		if ($modName == NULL && !$request->hasParameter(Xmf_Mvc_Config::get('MODULE_ACCESSOR', 'module')) &&
			$actName == NULL && !$request->hasParameter(Xmf_Mvc_Config::get('ACTION_ACCESSOR', 'action')))
		{

			$actName = Xmf_Mvc_Config::get('DEFAULT_ACTION', 'DefaultIndex');
			$modName = Xmf_Mvc_Config::get('DEFAULT_MODULE', 'Default');

		} else
		{

			// has a module been specified via dispatch()?
			if ($modName == NULL)
			{

				// a module hasn't been specified via dispatch(), let's check
				// the parameters
				$modName = $request->getParameter(Xmf_Mvc_Config::get('MODULE_ACCESSOR', 'module'));
				if (empty($modName)) {
					$modName = Xmf_Mvc_Config::get('DEFAULT_MODULE', 'Default');
				}
			}

			// has an action been specified via dispatch()?
			if ($actName == NULL)
			{

				// an action hasn't been specified via dispatch(), let's check
				// the parameters
				$actName = $request->getParameter(Xmf_Mvc_Config::get('ACTION_ACCESSOR', 'action'));

				if ($actName == NULL)
				{

					// does an Index action exist for this module?
					if ($this->actionExists($modName, 'Index'))
					{

						// ok, we found the Index action
						$actName = 'Index';

					}
					if (empty($actName))
					{
						$actName = Xmf_Mvc_Config::get('DEFAULT_ACTION', 'DefaultIndex');
					}

				}

			}

		}

		// if $modName or $actName equal NULL, we don't set them. we'll let
		// ERROR_404_ACTION do it's thing inside forward()

		// replace unwanted characters
		$actName = preg_replace('/[^a-z0-9\-_]+/i', '', $actName);
		$modName = preg_replace('/[^a-z0-9\-_]+/i', '', $modName);

		// set request modules and action
		$this->requestAction      = $actName;
		$this->requestModule      = $modName;
		$mojavi['request_action'] = $actName;
		$mojavi['request_module'] = $modName;

		// paths
		$mojavi['controller_path']     = $this->getControllerPath();
		$mojavi['current_action_path'] = $this->getControllerPath($modName,
																  $actName);

		$mojavi['current_module_path'] = $this->getControllerPath($modName);
		$mojavi['request_action_path'] = $this->getControllerPath($modName,
																  $actName);

		$mojavi['request_module_path'] = $this->getControllerPath($modName);

		// process our originally request action
		$this->forward($modName, $actName);

		// shutdown ModelManager
		$this->modelManager->shutdown();

		// store user data
		$this->user->store();

		// cleanup session handler
		if ($this->sessionHandler !== NULL)
		{

			$this->sessionHandler->cleanup();

		}

		// cleanup loggers
		//$logManager =& LogManager::getInstance();
		//$logManager->cleanup();
		$logger->stopTime('MVC dispatch');

	}

	private function loadRequired($filename)
	{
		if(!Xmf_Loader::loadFile($filename))
		{
			die (sprintf('Failed to load %s',$filename));
		}
	}

	private function ifExistsRequire($filename)
	{
		return Xmf_Loader::loadFile($filename);
	}

	/**
	 * Forward the request to an action.
	 *
	 * @param string $modName A module name.
	 * @param string $actName An action name.
	 *
	 * @since  1.0
	 */
	public function forward ($modName, $actName)
	{

		if ($this->currentModule == $modName &&
			$this->currentAction == $actName)
		{

			$error = 'Recursive forward on module ' . $modName . ', action ' .
					 $actName;

			trigger_error($error, E_USER_ERROR);

			exit;

		}

		// execute module configuration, if it exists
		$this->ifExistsRequire(Xmf_Mvc_Config::get('MODULES_DIR') . $modName . '/config.php');

		if ($this->actionExists($modName, $actName))
		{

			// create the action instance
			$action = $this->getAction($modName, $actName);

		} else
		{

			// the requested action doesn't exist
			$action = NULL;

		}

		// track old module/action
		$oldAction = $this->currentAction;
		$oldModule = $this->currentModule;

		// add module and action to execution chain, and update current vars
		$this->execChain->addRequest($modName, $actName, $action);
		$this->updateCurrentVars($modName, $actName);

		if ($action === NULL)
		{

			// requested action doesn't exist
			$actName = Xmf_Mvc_Config::get('ERROR_404_ACTION', 'PageNotFound');
			$modName = Xmf_Mvc_Config::get('ERROR_404_MODULE', 'Default');

			if (!$this->actionExists($modName, $actName))
			{

				// cannot find error 404 module/action
				$error = 'Invalid configuration setting(s): ' .
						 'ERROR_404_MODULE (' . $modName . ') or ' .
						 'ERROR_404_ACTION (' . $actName . ')';

				trigger_error($error, E_USER_ERROR);

				exit;

			}

			// add another request since the action is non-existent
			$action = $this->getAction($modName, $actName);

			$this->execChain->addRequest($modName, $actName, $action);
			$this->updateCurrentVars($modName, $actName);

		}

		$filterChain = new Xmf_Mvc_FilterChain;

		// map filters
		$this->mapGlobalFilters($filterChain);
		$this->mapModuleFilters($filterChain, $modName);

		// and last but not least, the main execution filter
		$filterChain->register(new Xmf_Mvc_ExecutionFilter);

		// execute filters
		$filterChain->execute($this, $this->request, $this->user);

		// update current vars
		$this->updateCurrentVars($oldModule, $oldAction);

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

		$url = Xmf_Mvc_Config::get('SCRIPT_PATH');

		$divider  = '&';
		$equals   = '=';
		$url     .= '?';

		$keys  = array_keys($params);
		$count = sizeof($keys);

		for ($i = 0; $i < $count; $i++)
		{

			if ($i > 0)
			{

				$url .= $divider;

			}

			$url .= urlencode($keys[$i]) . $equals .
					urlencode($params[$keys[$i]]);

		}

		return $url;

	}

	/**
	 * Generate a URL for a given module, action and parameters
	 *
	 * @param string $modName  a module name
	 * @param string $actName  an action name
	 * @param array  $params   an associative array of additional URL parameters
	 *
	 * @return string A URL to a Mojavi resource.
	 *
	 * @since  1.0
	 */
	public function getControllerPathWithParams($modName, $actName, $params)
	{

		$url=$this->getControllerPath($modName, $actName);
		if(strpos($url,'?')===false)
		{
			$url .= '?';
		}

		$divider  = '&';
		$equals   = '=';

		$keys  = array_keys($params);
		$count = sizeof($keys);

		for ($i = 0; $i < $count; $i++)
		{

			if ($i > 0)
			{

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
	 * @param string $modName A module name.
	 * @param string $actName An action name.
	 *
	 * @return Action An Action instance, if the action exists, otherwise
	 *                an error will be reported.
	 *
	 * @since  1.0
	 */
	public function getAction ($modName, $actName)
	{

		$file = $this->getComponentName ('action', $modName, $actName, '');

		$this->loadRequired($file);

		$action = $actName . 'Action';

		// fix for same name actions
		$modAction = $modName . '_' . $action;

		if (class_exists($modAction))
		{

			$action =& $modAction;

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
	 * @param string A module name.
	 * @param string An action name.
	 *
	 * @return string An absolute web path representing the controller file,
	 *                which also includes module and action names.
	 *
	 * @since  1.0
	 */
	public function getControllerPath ($modName = NULL, $actName = NULL)
	{

		$path = Xmf_Mvc_Config::get('SCRIPT_PATH');
		//$path = $_SERVER['SCRIPT_NAME'];

		$varsep = '?';

		if (!(empty($modName) || $modName==Xmf_Mvc_Config::get('DEFAULT_MODULE', 'Default'))) {
			$path .= $varsep."module=$modName";
			$varsep = '&';
		}
		if (!empty($actName)) {
			$path .= $varsep."action=$actName";
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
	 * Retrieve the name of the currently processing module.
	 *
	 * / If the request has not been forwarded, this will return the
	 *   the originally requested module./
	 *
	 * @since  1.0
	 */
	public function getCurrentModule ()
	{

		return $this->currentModule;

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
	 * @param object $externalCom  ExternalCom object
	 *
	 * @return Controller A Controller instance.
	 *
	 * @since  1.0
	 */
	public static function & getInstance (&$externalCom=null)
	{

		static $instance;

		if ($instance === NULL)
		{
			$controllerClass=__CLASS__; // get_called_class(); not available PHP<5.3
			$instance = new $controllerClass($externalCom);

		}
		Xmf_Mvc_Context::set($instance);
		return $instance;

	}

	/**
	 * Retrieve an absolute file-system path home directory of the currently
	 * processing module.
	 *
	 *  _ If the request has been forwarded, this will return the directory of
	 *    the forwarded module._
	 *
	 * @return string A module directory.
	 *
	 * @since  1.0
	 */
	public function getModuleDir ()
	{

		return (Xmf_Mvc_Config::get('MODULES_DIR') . $this->currentModule . '/');

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
	 * - Xmf_Mvc::RENDER_CLIENT  - render to the client
	 * - Xmf_Mvc::RENDER_VAR     - render to variable
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
	 * Retrieve the name of the originally requested module.
	 *
	 * @return string A module name.
	 *
	 * @since  1.0
	 */
	public function getRequestModule ()
	{

		return $this->requestModule;

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
	 * @param string A module name.
	 * @param string An action name.
	 * @param string A view name.
	 *
	 * @return View A View instance.
	 */
	public function getView ($modName, $actName, $viewName)
	{

		$file = $this->getComponentName ('view', $modName, $actName, $viewName);

		$this->loadRequired($file);

		$view =  $actName . 'View';

		// fix for same name views
		$modView = $modName . '_' . $view;

		if (class_exists($modView))
		{

			$view =& $modView;

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

		if (!isset($list))
		{

			$file = Xmf_Mvc_Config::get('MODULES_DIR') . 'GlobalFilterList.php';

			if ($this->ifExistsRequire($file))
			{

				$list = new GlobalFilterList;
				$list->registerFilters($filterChain, $this, $this->request,
									   $this->user);

			}

		} else
		{

			$list->registerFilters($filterChain, $this, $this->request,
								   $this->user);

		}

	}

	/**
	 * Map all filters for a given module.
	 *
	 * @param FilterChain A FilterChain instance.
	 * @param modName     A module name.
	 *
	 * @since  1.0
	 */
	public function mapModuleFilters (&$filterChain, $modName)
	{

		static $cache;

		if (!isset($cache))
		{

			$cache = array();

		}

		$listName = $modName . 'FilterList';

		if (!isset($cache[$listName]))
		{

			$file = $this->getComponentName ('filterlist', $modName, "{$listName}", '');

			if ($this->ifExistsRequire($file))
			{

				$list             =  new $listName;
				$cache[$listName] =& $list;

				// register filters
				$list->registerFilters($filterChain, $this, $this->request,
									   $this->user);

			}

		} else
		{

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
		 * Xmf_Request::get($hash = 'default', $mask = 0)
		 * bitmask values for $mask are:
		 *   -  XMF_REQUEST_NOTRIM    (1)  set to skip trim() on values
		 *   -  XMF_REQUEST_ALLOWRAW  (2)  set to disable html check
		 *   -  XMF_REQUEST_ALLOWHTML (4)  set to allow all html, clear for 'safe' only
		 *
		 * We will clean agressively. Raw values are not overwritten, so
		 * code can go back and get directly with different options if
		 * needed. Cleaning also handles magic_quotes_gpc clean up.
		 *
		 */

		$values = array();

		// load GET params into $values array
		$values = array_merge($values, Xmf_Request::get('GET', 0));

		// load POST params into $values array
		$values = array_merge($values, Xmf_Request::get('POST', 0));

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
	 * - Xmf_Mvc::RENDER_CLIENT - render to the client
	 * - Xmf_Mvc::RENDER_VAR    - render to variable
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
	 * Update current module and action data.
	 *
	 * @param string A module name.
	 * @param string An action name.
	 *
	 * @since  1.0
	 */
	protected function updateCurrentVars ($modName, $actName)
	{

		// alias objects for easy access
		$mojavi =& $this->mojavi;

		$this->currentModule = $modName;
		$this->currentAction = $actName;

		// names
		$mojavi['current_action'] = $actName;
		$mojavi['current_module'] = $modName;

		// directories
		$mojavi['module_dir']   = Xmf_Mvc_Config::get('MODULES_DIR');
		$mojavi['template_dir'] = Xmf_Mvc_Config::get('MODULES_DIR') . $modName .
								  '/templates/';

		// paths
		$mojavi['current_action_path'] = $this->getControllerPath($modName,
																  $actName);
		$mojavi['current_module_path'] = $this->getControllerPath($modName);

	}

	/**
	 * Determine if a view exists.
	 *
	 * @param string A module name.
	 * @param string An action name.
	 * @param string A view name.
	 *
	 * @return bool <b>TRUE</b>, if the view exists, otherwise <b>FALSE</b>.
	 *
	 * @since  1.0
	 */
	public function viewExists ($modName, $actName, $viewName)
	{

		$file = $this->getComponentName ('view', $modName, $actName, $viewName);

		return (is_readable($file));

	}

	/**
	 * Retrieve a filter implementation instance.
	 *
	 * @param string $name - A filter name.
	 * @param string $modName - A unit (module) name, defaults to current unit
	 *
	 * @return a Filter instance.
	 */
	public function getFilter ($name, $unitName='')
	{

		if(empty($unitName)) { $unitName = $this->currentModule; }
		$file = $this->getComponentName ('filter', $unitName, $name, '');

		$this->loadRequired($file);

		$filter =  $name . 'Filter';
		// fix for same name filters
		$unitFilter = $unitName . '_' . $filter;
		if (class_exists($unitFilter))
		{
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

?>
