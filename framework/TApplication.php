<?php
/**
 * TApplication class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System
 */

/**
 * Includes TErrorHandler class
 */
require_once(PRADO_DIR.'/Exceptions/TErrorHandler.php');
/**
 * Includes THttpRequest class
 */
require_once(PRADO_DIR.'/Web/THttpRequest.php');
/**
 * Includes THttpResponse class
 */
require_once(PRADO_DIR.'/Web/THttpResponse.php');
/**
 * Includes THttpSession class
 */
require_once(PRADO_DIR.'/Web/THttpSession.php');
/**
 * Includes TAuthorizationRule class
 */
require_once(PRADO_DIR.'/Security/TAuthorizationRule.php');
/**
 * Includes TPageService class (default service)
 */
require_once(PRADO_DIR.'/Web/Services/TPageService.php');


/**
 * TApplication class.
 *
 * TApplication coordinates modules and services, and serves as a configuration
 * context for all Prado components.
 *
 * TApplication uses a configuration file to specify the settings of
 * the application, the modules, the services, the parameters, and so on.
 *
 * TApplication adopts a modular structure. A TApplication instance is a composition
 * of multiple modules. A module is an instance of class implementing
 * {@link IModule} interface. Each module accomplishes certain functionalities
 * that are shared by all Prado components in an application.
 * There are default modules and user-defined modules. The latter offers extreme
 * flexibility of extending TApplication in a plug-and-play fashion.
 * Modules cooperate with each other to serve a user request by following
 * a sequence of lifecycles predefined in TApplication.
 *
 * TApplication has four modes that can be changed by setting {@link setMode Mode}
 * property (in the application configuration file).
 * - <b>Off</b> mode will prevent the application from serving user requests.
 * - <b>Debug</b> mode is mainly used during application development. It ensures
 *   the cache is always up-to-date if caching is enabled. It also allows
 *   exceptions are displayed with rich context information if they occur.
 * - <b>Normal</b> mode is mainly used during production stage. Exception information
 *   will only be recorded in system error logs. The cache is ensured to be
 *   up-to-date if it is enabled.
 * - <b>Performance</b> mode is similar to <b>Normal</b> mode except that it
 *   does not ensure the cache is up-to-date.
 *
 * TApplication dispatches each user request to a particular service which
 * finishes the actual work for the request with the aid from the application
 * modules.
 *
 * TApplication maintains a lifecycle with the following stages:
 * - [construct] : construction of the application instance
 * - [initApplication] : load application configuration and instantiate modules and the requested service
 * - BeginRequest : this event happens right after application initialization
 * - Authentication : this event happens when authentication is needed for the current request
 * - PostAuthentication : this event happens right after the authentication is done for the current request
 * - Authorization : this event happens when authorization is needed for the current request
 * - PostAuthorization : this event happens right after the authorization is done for the current request
 * - LoadState : this event happens when application state needs to be loaded
 * - PostLoadState : this event happens right after the application state is loaded
 * - PreRunService : this event happens right before the requested service is to run
 * - RunService : this event happens when the requested service runs
 * - PostRunService : this event happens right after the requested service finishes running
 * - SaveState : this event happens when application needs to save its state
 * - PostSaveState : this event happens right after the application saves its state
 * - EndRequest : this is the last stage an application runs
 * - [destruct] : destruction of the application instance
 * Modules and services can attach their methods to one or several of the above
 * events and do appropriate processing when the events are raised. By this way,
 * the application is able to coordinate the activities of modules and services
 * in the above order. To terminate an application before the whole lifecycle
 * completes, call {@link completeRequest}.
 *
 * Examples:
 * - Create and run a Prado application:
 * <code>
 * $application=new TApplication($configFile);
 * $application->run();
 * </code>
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System
 * @since 3.0
 */
class TApplication extends TComponent
{
	/**
	 * application state
	 */
	const STATE_OFF='Off';
	const STATE_DEBUG='Debug';
	const STATE_NORMAL='Normal';
	const STATE_PERFORMANCE='Performance';

	/**
	 * Page service ID
	 */
	const PAGE_SERVICE_ID='page';
	/**
	 * Application configuration file name
	 */
	const CONFIG_FILE='application.xml';
	/**
	 * Runtime directory name
	 */
	const RUNTIME_PATH='runtime';
	/**
	 * Config cache file
	 */
	const CONFIGCACHE_FILE='config.cache';
	/**
	 * Global data file
	 */
	const GLOBAL_FILE='global.cache';

	/**
	 * @var array list of events that define application lifecycles
	 */
	private static $_steps=array(
		'onBeginRequest',
		'onAuthentication',
		'onPostAuthentication',
		'onAuthorization',
		'onPostAuthorization',
		'onLoadState',
		'onPostLoadState',
		'onPreRunService',
		'onRunService',
		'onPostRunService',
		'onSaveState',
		'onPostSaveState',
		'onEndRequest'
	);

	/**
	 * @var string application ID
	 */
	private $_id;
	/**
	 * @var string unique application ID
	 */
	private $_uniqueID;
	/**
	 * @var boolean whether the request is completed
	 */
	private $_requestCompleted=false;
	/**
	 * @var integer application state
	 */
	private $_step;
	/**
	 * @var IService current service instance
	 */
	private $_service=null;
	/**
	 * @var TPageService page service
	 */
	private $_pageService=null;
	/**
	 * @var array list of application modules
	 */
	private $_modules;
	/**
	 * @var TMap list of application parameters
	 */
	private $_parameters;
	/**
	 * @var string configuration file
	 */
	private $_configFile;
	/**
	 * @var string application base path
	 */
	private $_basePath;
	/**
	 * @var string directory storing application state
	 */
	private $_runtimePath;
	/**
	 * @var boolean if any global state is changed during the current request
	 */
	private $_stateChanged=false;
	/**
	 * @var array global variables (persistent across sessions, requests)
	 */
	private $_globals=array();
	/**
	 * @var string cache file
	 */
	private $_cacheFile;
	/**
	 * @var TErrorHandler error handler module
	 */
	private $_errorHandler=null;
	/**
	 * @var THttpRequest request module
	 */
	private $_request=null;
	/**
	 * @var THttpResponse response module
	 */
	private $_response=null;
	/**
	 * @var THttpSession session module, could be null
	 */
	private $_session=null;
	/**
	 * @var ICache cache module, could be null
	 */
	private $_cache=null;
	/**
	 * @var IStatePersister application state persister
	 */
	private $_statePersister=null;
	/**
	 * @var IUser user instance, could be null
	 */
	private $_user=null;

	/**
	 * @var TGlobalization module, could be null
	 */
	private $_globalization=null;

	/**
	 * @var TAuthorizationRuleCollection collection of authorization rules
	 */
	private $_authRules=null;
	/**
	 * @var string application mode
	 */
	private $_mode='Debug';

	/**
	 * Constructor.
	 * Sets application base path and initializes the application singleton.
	 * Application base path refers to the root directory where application
	 * data and code not directly accessible by Web users are stored.
	 * If there is a file named <b>application.xml</b> under the base path,
	 * it is assumed to be the application configuration file and will be loaded
	 * and parsed when the application runs. By default, the base path is assumed
	 * to be the <b>protected</b> directory under the directory containing
	 * the current running script.
	 * @param string application base path (absolute or relative to current running script)
	 * @param boolean whether to cache application configuration. Defaults to true.
	 * @throws TConfigurationException if configuration file cannot be read or the runtime path is invalid.
	 */
	public function __construct($basePath='protected',$cacheConfig=true)
	{
		parent::__construct();

		// register application as a singleton
		Prado::setApplication($this);

		// determine configuration path and file
		if(($this->_basePath=realpath($basePath))===false || !is_dir($this->_basePath))
			throw new TConfigurationException('application_basepath_invalid',$basePath);
		$configFile=$this->_basePath.'/'.self::CONFIG_FILE;
		$this->_configFile=is_file($configFile) ? $configFile : null;
		$this->_runtimePath=$this->_basePath.'/'.self::RUNTIME_PATH;
		if(!is_dir($this->_runtimePath) || !is_writable($this->_runtimePath))
			throw new TConfigurationException('application_runtimepath_invalid',$this->_runtimePath);

		$this->_cacheFile=$cacheConfig ? $this->_runtimePath.'/'.self::CONFIGCACHE_FILE : null;

		// generates unique ID by hashing the configuration path
		$this->_uniqueID=md5($this->_basePath);
	}

	public function __destruct()
	{
		$this->onExitApplication();
	}

	/**
	 * Executes the lifecycles of the application.
	 * This is the main entry function that leads to the running of the whole
	 * Prado application.
	 */
	public function run()
	{
		try
		{
			$this->initApplication();
			$n=count(self::$_steps);
			$this->_step=0;
			$this->_requestCompleted=false;
			while($this->_step<$n)
			{
				if($this->_mode===self::STATE_OFF)
					throw new THttpException(503,'application_service_unavailable');
				$method=self::$_steps[$this->_step];
				Prado::trace("Executing $method",'System.TApplication');
				$this->$method();
				if($this->_requestCompleted && $this->_step<$n-1)
					$this->_step=$n-1;
				else
					$this->_step++;
			}
		}
		catch(Exception $e)
		{
			$this->onError($e);
		}
	}

	/**
	 * Completes current request processing.
	 * This method can be used to exit the application lifecycles after finishing
	 * the current cycle.
	 */
	public function completeRequest()
	{
		$this->_requestCompleted=true;
	}

	/**
	 * Returns a global value.
	 *
	 * A global value is one that is persistent across users sessions and requests.
	 * @param string the name of the value to be returned
	 * @param mixed the default value. If $key is not found, $defaultValue will be returned
	 * @return mixed the global value corresponding to $key
	 */
	public function getGlobalState($key,$defaultValue=null)
	{
		return isset($this->_globals[$key])?$this->_globals[$key]:$defaultValue;
	}

	/**
	 * Sets a global value.
	 *
	 * A global value is one that is persistent across users sessions and requests.
	 * Make sure that the value is serializable and unserializable.
	 * @param string the name of the value to be set
	 * @param mixed the global value to be set
	 * @param mixed the default value. If $key is not found, $defaultValue will be returned
	 */
	public function setGlobalState($key,$value,$defaultValue=null)
	{
		$this->_stateChanged=true;
		if($value===$defaultValue)
			unset($this->_globals[$key]);
		else
			$this->_globals[$key]=$value;
	}

	/**
	 * Clears a global value.
	 *
	 * The value cleared will no longer be available in this request and the following requests.
	 * @param string the name of the value to be cleared
	 */
	public function clearGlobalState($key)
	{
		$this->_stateChanged=true;
		unset($this->_globals[$key]);
	}

	/**
	 * Loads global values from persistent storage.
	 * This method is invoked when {@link onLoadState OnLoadState} event is raised.
	 * After this method, values that are stored in previous requests become
	 * available to the current request via {@link getGlobalState}.
	 */
	protected function loadGlobals()
	{
		$this->_globals=$this->getApplicationStatePersister()->load();
	}

	/**
	 * Saves global values into persistent storage.
	 * This method is invoked when {@link onSaveState OnSaveState} event is raised.
	 */
	protected function saveGlobals()
	{
		if(!$this->_stateChanged)
			return;
		$this->getApplicationStatePersister()->save($this->_globals);
	}

	/**
	 * @return string application ID
	 */
	public function getID()
	{
		return $this->_id;
	}

	/**
	 * @param string application ID
	 */
	public function setID($value)
	{
		$this->_id=$value;
	}

	/**
	 * @return string an ID that unique identifies this Prado application from the others
	 */
	public function getUniqueID()
	{
		return $this->_uniqueID;
	}

	/**
	 * @return string application mode (Off|Debug|Normal|Peformance), defaults to Debug.
	 */
	public function getMode()
	{
		return $this->_mode;
	}

	/**
	 * @param string application mode. Valid values include Off, Debug, Normal, or Peformance
	 */
	public function setMode($value)
	{
		$this->_mode=TPropertyValue::ensureEnum($value,array(self::STATE_OFF,self::STATE_DEBUG,self::STATE_NORMAL,self::STATE_PERFORMANCE));
	}

	/**
	 * @return string configuration path
	 */
	public function getBasePath()
	{
		return $this->_basePath;
	}

	/**
	 * @return string configuration file path
	 */
	public function getConfigurationFile()
	{
		return $this->_configFile;
	}

	/**
	 * Gets the directory storing application-level persistent data.
	 * @return string application state path
	 */
	public function getRuntimePath()
	{
		return $this->_runtimePath;
	}

	/**
	 * @return IService the currently requested service
	 */
	public function getService()
	{
		return $this->_service;
	}

	/**
	 * Adds a module to application.
	 * Note, this method does not do module initialization.
	 * @param string ID of the module
	 * @param IModule module object
	 */
	public function setModule($id,IModule $module)
	{
		if(isset($this->_modules[$id]))
			throw new TConfigurationException('application_moduleid_duplicated',$id);
		else
			$this->_modules[$id]=$module;
	}

	/**
	 * @return IModule the module with the specified ID, null if not found
	 */
	public function getModule($id)
	{
		return isset($this->_modules[$id])?$this->_modules[$id]:null;
	}

	/**
	 * @return array list of loaded application modules, indexed by module IDs
	 */
	public function getModules()
	{
		return $this->_modules;
	}

	/**
	 * Returns the list of application parameters.
	 * Since the parameters are returned as a {@link TMap} object, you may use
	 * the returned result to access, add or remove individual parameters.
	 * @return TMap the list of application parameters
	 */
	public function getParameters()
	{
		return $this->_parameters;
	}

	/**
	 * @return TPageService page service
	 */
	public function getPageService()
	{
		if(!$this->_pageService)
		{
			$this->_pageService=new TPageService;
			$this->_pageService->init(null);
		}
		return $this->_pageService;
	}

	/**
	 * Registers the page service instance.
	 * This method should only be used by framework developers.
	 * @param TPageService page service
	 */
	public function setPageService(TPageService $service)
	{
		$this->_pageService=$service;
	}

	/**
	 * @return THttpRequest the request module
	 */
	public function getRequest()
	{
		if(!$this->_request)
		{
			$this->_request=new THttpRequest;
			$this->_request->init(null);
		}
		return $this->_request;
	}

	/**
	 * @param THttpRequest the request module
	 */
	public function setRequest(THttpRequest $request)
	{
		$this->_request=$request;
	}

	/**
	 * @return THttpResponse the response module
	 */
	public function getResponse()
	{
		if(!$this->_response)
		{
			$this->_response=new THttpResponse;
			$this->_response->init(null);
		}
		return $this->_response;
	}

	/**
	 * @param THttpRequest the request module
	 */
	public function setResponse(THttpResponse $response)
	{
		$this->_response=$response;
	}

	/**
	 * @return THttpSession the session module, null if session module is not installed
	 */
	public function getSession()
	{
		if(!$this->_session)
		{
			$this->_session=new THttpSession;
			$this->_session->init(null);
		}
		return $this->_session;
	}

	/**
	 * @param THttpSession the session module
	 */
	public function setSession(THttpSession $session)
	{
		$this->_session=$session;
	}

	/**
	 * @return TErrorHandler the error hanlder module
	 */
	public function getErrorHandler()
	{
		if(!$this->_errorHandler)
		{
			$this->_errorHandler=new TErrorHandler;
			$this->_errorHandler->init(null);
		}
		return $this->_errorHandler;
	}

	/**
	 * @param TErrorHandler the error hanlder module
	 */
	public function setErrorHandler(TErrorHandler $handler)
	{
		$this->_errorHandler=$handler;
	}

	/**
	 * @return IStatePersister application state persister
	 */
	public function getApplicationStatePersister()
	{
		if(!$this->_statePersister)
		{
			$this->_statePersister=new TApplicationStatePersister;
			$this->_statePersister->init(null);
		}
		return $this->_statePersister;
	}

	/**
	 * @param IStatePersister  application state persister
	 */
	public function setApplicationStatePersister(IStatePersister $persister)
	{
		$this->_statePersister=$persister;
	}

	/**
	 * @return ICache the cache module, null if cache module is not installed
	 */
	public function getCache()
	{
		return $this->_cache;
	}

	/**
	 * @param ICache the cache module
	 */
	public function setCache(ICache $cache)
	{
		$this->_cache=$cache;
	}

	/**
	 * @return IUser the application user
	 */
	public function getUser()
	{
		return $this->_user;
	}

	/**
	 * @param IUser the application user
	 */
	public function setUser(IUser $user)
	{
		$this->_user=$user;
	}

	public function getGlobalization()
	{
		return $this->_globalization;
	}

	public function setGlobalization(TGlobalization $handler)
	{
		$this->_globalization = $handler;
	}

	/**
	 * @return TAuthorizationRuleCollection list of authorization rules for the current request
	 */
	public function getAuthorizationRules()
	{
		if($this->_authRules===null)
			$this->_authRules=new TAuthorizationRuleCollection;
		return $this->_authRules;
	}

	/**
	 * Loads configuration and initializes application.
	 * Configuration file will be read and parsed (if a valid cached version exists,
	 * it will be used instead). Then, modules are created and initialized;
	 * Afterwards, the requested service is created and initialized.
	 * @param string configuration file path (absolute or relative to current executing script)
	 * @param string cache file path, empty if no present or needed
	 * @throws TConfigurationException if module is redefined of invalid type, or service not defined or of invalid type
	 */
	protected function initApplication()
	{
		Prado::trace('Initializing application','System.TApplication');

		Prado::setPathOfAlias('Application',$this->_basePath);

		if($this->_configFile===null)
		{
			$this->getRequest()->setAvailableServices(array(self::PAGE_SERVICE_ID));
			$this->_service=$this->getPageService();
			return;
		}

		if($this->_cacheFile===null || @filemtime($this->_cacheFile)<filemtime($this->_configFile))
		{
			$config=new TApplicationConfiguration;
			$config->loadFromFile($this->_configFile);
			if($this->_cacheFile!==null)
			{
				if(($fp=fopen($this->_cacheFile,'wb'))!==false)
				{
					fputs($fp,Prado::serialize($config));
					fclose($fp);
				}
				else
					syslog(LOG_WARNING, 'Prado application config cache file "'.$this->_cacheFile.'" cannot be created.');
			}
		}
		else
		{
			$config=Prado::unserialize(file_get_contents($this->_cacheFile));
		}

		// set path aliases and using namespaces
		foreach($config->getAliases() as $alias=>$path)
			Prado::setPathOfAlias($alias,$path);
		foreach($config->getUsings() as $using)
			Prado::using($using);

		// set application properties
		foreach($config->getProperties() as $name=>$value)
			$this->setSubProperty($name,$value);

		// load parameters
		$this->_parameters=new TMap;
		foreach($config->getParameters() as $id=>$parameter)
		{
			if(is_array($parameter))
			{
				$component=Prado::createComponent($parameter[0]);
				foreach($parameter[1] as $name=>$value)
					$component->setSubProperty($name,$value);
				$this->_parameters->add($id,$component);
			}
			else
				$this->_parameters->add($id,$parameter);
		}

		// load and init modules specified in app config
		$this->_modules=array();
		foreach($config->getModules() as $id=>$moduleConfig)
		{
			Prado::trace("Loading module $id ({$moduleConfig[0]})",'System.TApplication');

			$module=Prado::createComponent($moduleConfig[0]);
			$this->setModule($id,$module);
			foreach($moduleConfig[1] as $name=>$value)
				$module->setSubProperty($name,$value);
			$module->init($moduleConfig[2]);
		}

		// load service
		$services=$config->getServices();
		$serviceIDs=array_keys($services);
		array_unshift($serviceIDs,self::PAGE_SERVICE_ID);
		$request=$this->getRequest();
		$request->setAvailableServices($serviceIDs);

		if(($serviceID=$request->getServiceID())===null)
			$serviceID=self::PAGE_SERVICE_ID;
		if(isset($services[$serviceID]))
		{
			$serviceConfig=$services[$serviceID];
			$service=Prado::createComponent($serviceConfig[0]);
			if(!($service instanceof IService))
				throw new THttpException(500,'application_service_unknown',$serviceID);
			$this->_service=$service;
			foreach($serviceConfig[1] as $name=>$value)
				$service->setSubProperty($name,$value);
			$service->init($serviceConfig[2]);
		}
		else
			$this->_service=$this->getPageService();
	}

	/**
	 * Raises OnError event.
	 * This method is invoked when an exception is raised during the lifecycles
	 * of the application.
	 * @param mixed event parameter
	 */
	public function onError($param)
	{
		Prado::log($param->getMessage(),TLogger::ERROR,'System.TApplication');
		$this->getErrorHandler()->handleError($this,$param);
		$this->raiseEvent('OnError',$this,$param);
	}

	/**
	 * Raises OnBeginRequest event.
	 * At the time when this method is invoked, application modules are loaded
	 * and initialized, user request is resolved and the corresponding service
	 * is loaded and initialized. The application is about to start processing
	 * the user request.
	 */
	public function onBeginRequest()
	{
		$this->raiseEvent('OnBeginRequest',$this,null);
	}

	/**
	 * Raises OnAuthentication event.
	 * This method is invoked when the user request needs to be authenticated.
	 */
	public function onAuthentication()
	{
		$this->raiseEvent('OnAuthentication',$this,null);
	}

	/**
	 * Raises OnPostAuthentication event.
	 * This method is invoked right after the user request is authenticated.
	 */
	public function onPostAuthentication()
	{
		$this->raiseEvent('OnPostAuthentication',$this,null);
	}

	/**
	 * Raises OnAuthorization event.
	 * This method is invoked when the user request needs to be authorized.
	 */
	public function onAuthorization()
	{
		$this->raiseEvent('OnAuthorization',$this,null);
	}

	/**
	 * Raises OnPostAuthorization event.
	 * This method is invoked right after the user request is authorized.
	 */
	public function onPostAuthorization()
	{
		$this->raiseEvent('OnPostAuthorization',$this,null);
	}

	/**
	 * Raises OnLoadState event.
	 * This method is invoked when the application needs to load state (probably stored in session).
	 */
	public function onLoadState()
	{
		$this->loadGlobals();
		$this->raiseEvent('OnLoadState',$this,null);
	}

	/**
	 * Raises OnPostLoadState event.
	 * This method is invoked right after the application state has been loaded.
	 */
	public function onPostLoadState()
	{
		$this->raiseEvent('OnPostLoadState',$this,null);
	}

	/**
	 * Raises OnPreRunService event.
	 * This method is invoked right before the service is to be run.
	 */
	public function onPreRunService()
	{
		$this->raiseEvent('OnPreRunService',$this,null);
	}

	/**
	 * Raises OnRunService event.
	 * This method is invoked when the service runs.
	 */
	public function onRunService()
	{
		$this->raiseEvent('OnRunService',$this,null);
		if($this->_service)
			$this->_service->run();
	}

	/**
	 * Raises OnPostRunService event.
	 * This method is invoked right after the servie is run.
	 */
	public function onPostRunService()
	{
		$this->raiseEvent('OnPostRunService',$this,null);
	}

	/**
	 * Raises OnSaveState event.
	 * This method is invoked when the application needs to save state (probably stored in session).
	 */
	public function onSaveState()
	{
		$this->raiseEvent('OnSaveState',$this,null);
		$this->saveGlobals();
	}

	/**
	 * Raises OnPostSaveState event.
	 * This method is invoked right after the application state has been saved.
	 */
	public function onPostSaveState()
	{
		$this->raiseEvent('OnPostSaveState',$this,null);
	}

	/**
	 * Raises OnEndRequest event.
	 * This method is invoked when the application completes the processing of the request.
	 */
	public function onEndRequest()
	{
		$this->raiseEvent('OnEndRequest',$this,null);
	}

	/**
	 * Raises OnExitApplication event.
	 * This method is invoked when the application instance is being destructed.
	 */
	public function onExitApplication()
	{
		$this->raiseEvent('OnExitApplication',$this,null);
	}
}


/**
 * TApplicationConfiguration class.
 *
 * This class is used internally by TApplication to parse and represent application configuration.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System
 * @since 3.0
 */
class TApplicationConfiguration extends TComponent
{
	/**
	 * @var array list of application initial property values, indexed by property names
	 */
	private $_properties=array();
	/**
	 * @var array list of namespaces to be used
	 */
	private $_usings=array();
	/**
	 * @var array list of path aliases, indexed by alias names
	 */
	private $_aliases=array();
	/**
	 * @var array list of module configurations
	 */
	private $_modules=array();
	/**
	 * @var array list of service configurations
	 */
	private $_services=array();
	/**
	 * @var array list of parameters
	 */
	private $_parameters=array();

	/**
	 * Parses the application configuration file.
	 * @param string configuration file name
	 * @throws TConfigurationException if there is any parsing error
	 */
	public function loadFromFile($fname)
	{
		$configPath=dirname($fname);
		$dom=new TXmlDocument;
		$dom->loadFromFile($fname);

		// application properties
		foreach($dom->getAttributes() as $name=>$value)
			$this->_properties[$name]=$value;

		// paths
		if(($pathsNode=$dom->getElementByTagName('paths'))!==null)
		{
			foreach($pathsNode->getElementsByTagName('alias') as $aliasNode)
			{
				if(($id=$aliasNode->getAttribute('id'))!==null && ($path=$aliasNode->getAttribute('path'))!==null)
				{
					$path=str_replace('\\','/',$path);
					if(preg_match('/^\\/|.:\\/|.:\\\\/',$path))	// if absolute path
						$p=realpath($path);
					else
						$p=realpath($configPath.'/'.$path);
					if($p===false || !is_dir($p))
						throw new TConfigurationException('appconfig_aliaspath_invalid',$id,$path);
					if(isset($this->_aliases[$id]))
						throw new TConfigurationException('appconfig_alias_redefined',$id);
					$this->_aliases[$id]=$p;
				}
				else
					throw new TConfigurationException('appconfig_alias_invalid');
			}
			foreach($pathsNode->getElementsByTagName('using') as $usingNode)
			{
				if(($namespace=$usingNode->getAttribute('namespace'))!==null)
					$this->_usings[]=$namespace;
				else
					throw new TConfigurationException('appconfig_using_invalid');
			}
		}

		// application modules
		if(($modulesNode=$dom->getElementByTagName('modules'))!==null)
		{
			foreach($modulesNode->getElementsByTagName('module') as $node)
			{
				$properties=$node->getAttributes();
				if(($id=$properties->itemAt('id'))===null)
					throw new TConfigurationException('appconfig_moduleid_required');
				if(($type=$properties->remove('class'))===null && isset($this->_modules[$id]) && $this->_modules[$id][2]===null)
					$type=$this->_modules[$id][0];
				if($type===null)
					throw new TConfigurationException('appconfig_moduletype_required',$id);
				$node->setParent(null);
				$this->_modules[$id]=array($type,$properties->toArray(),$node);
			}
		}

		// services
		if(($servicesNode=$dom->getElementByTagName('services'))!==null)
		{
			foreach($servicesNode->getElementsByTagName('service') as $node)
			{
				$properties=$node->getAttributes();
				if(($id=$properties->itemAt('id'))===null)
					throw new TConfigurationException('appconfig_serviceid_required');
				if(($type=$properties->remove('class'))===null && isset($this->_services[$id]) && $this->_services[$id][2]===null)
					$type=$this->_services[$id][0];
				if($type===null)
					throw new TConfigurationException('appconfig_servicetype_required',$id);
				$node->setParent(null);
				$this->_services[$id]=array($type,$properties->toArray(),$node);
			}
		}

		// parameters
		if(($parametersNode=$dom->getElementByTagName('parameters'))!==null)
		{
			foreach($parametersNode->getElementsByTagName('parameter') as $node)
			{
				$properties=$node->getAttributes();
				if(($id=$properties->remove('id'))===null)
					throw new TConfigurationException('appconfig_parameterid_required');
				if(($type=$properties->remove('class'))===null)
				{
					if(($value=$properties->remove('value'))===null)
						$this->_parameters[$id]=$node;
					else
						$this->_parameters[$id]=$value;
				}
				else
					$this->_parameters[$id]=array($type,$properties->toArray());
			}
		}
	}

	/**
	 * @return array list of application initial property values, indexed by property names
	 */
	public function getProperties()
	{
		return $this->_properties;
	}

	/**
	 * @return array list of path aliases, indexed by alias names
	 */
	public function getAliases()
	{
		return $this->_aliases;
	}

	/**
	 * @return array list of namespaces to be used
	 */
	public function getUsings()
	{
		return $this->_usings;
	}

	/**
	 * @return array list of module configurations
	 */
	public function getModules()
	{
		return $this->_modules;
	}

	/**
	 * @return array list of service configurations
	 */
	public function getServices()
	{
		return $this->_services;
	}

	/**
	 * @return array list of parameters
	 */
	public function getParameters()
	{
		return $this->_parameters;
	}
}

/**
 * TApplicationStatePersister class.
 * TApplicationStatePersister provides a file-based persistent storage
 * for application state. Application state, when serialized, is stored
 * in a file named 'global.cache' under the 'runtime' directory of the application.
 * Cache will be exploited if it is enabled.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System
 * @since 3.0
 */
class TApplicationStatePersister extends TModule implements IStatePersister
{
	/**
	 * Name of the value stored in cache
	 */
	const CACHE_NAME='prado:appstate';

	/**
	 * Initializes module.
	 * @param TXmlElement module configuration (may be null)
	 */
	public function init($config)
	{
		$this->getApplication()->setApplicationStatePersister($this);
	}

	/**
	 * @return string the file path storing the application state
	 */
	protected function getStateFilePath()
	{
		return $this->getApplication()->getRuntimePath().'/global.cache';
	}

	/**
	 * Loads application state from persistent storage.
	 * @return mixed application state
	 */
	public function load()
	{
		if(($cache=$this->getApplication()->getCache())!==null && ($value=$cache->get(self::CACHE_NAME))!==false)
			return unserialize($value);
		else
		{
			if(($content=@file_get_contents($this->getStateFilePath()))!==false)
				return unserialize($content);
			else
				return null;
		}
	}

	/**
	 * Saves application state in persistent storage.
	 * @param mixed application state
	 */
	public function save($state)
	{
		$content=serialize($state);
		$saveFile=true;
		if(($cache=$this->getApplication()->getCache())!==null)
		{
			if($cache->get(self::CACHE_NAME)===$content)
				$saveFile=false;
			else
				$cache->set(self::CACHE_NAME,$content);
		}
		if($saveFile)
		{
			$fileName=$this->getStateFilePath();
			if(version_compare(phpversion(),'5.1.0','>='))
				file_put_contents($fileName,$content,LOCK_EX);
			else
				file_put_contents($fileName,$content);
		}
	}

}
?>