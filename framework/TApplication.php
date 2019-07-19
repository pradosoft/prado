<?php
/**
 * TApplication class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado
 */

namespace Prado;

use Prado\Exceptions\TErrorHandler;
use Prado\Exceptions\THttpException;
use Prado\Exceptions\TConfigurationException;
use Prado\I18N\TGlobalization;
use Prado\Security\TSecurityManager;
use Prado\Web\TAssetManager;
use Prado\Web\THttpRequest;
use Prado\Web\THttpResponse;
use Prado\Web\THttpSession;
use Prado\Util\TLogger;

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
 * - onBeginRequest : this event happens right after application initialization
 * - onAuthentication : this event happens when authentication is needed for the current request
 * - onAuthenticationComplete : this event happens right after the authentication is done for the current request
 * - onAuthorization : this event happens when authorization is needed for the current request
 * - onAuthorizationComplete : this event happens right after the authorization is done for the current request
 * - onLoadState : this event happens when application state needs to be loaded
 * - onLoadStateComplete : this event happens right after the application state is loaded
 * - onPreRunService : this event happens right before the requested service is to run
 * - runService : the requested service runs
 * - onSaveState : this event happens when application needs to save its state
 * - onSaveStateComplete : this event happens right after the application saves its state
 * - onPreFlushOutput : this event happens right before the application flushes output to client side.
 * - flushOutput : the application flushes output to client side.
 * - onEndRequest : this is the last stage a request is being completed
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
 * @package Prado
 * @since 3.0
 */
class TApplication extends \Prado\TComponent
{
	/**
	 * Page service ID
	 */
	const PAGE_SERVICE_ID = 'page';
	/**
	 * Application configuration file name
	 */
	const CONFIG_FILE_XML = 'application.xml';
	/**
	 * File extension for external config files
	 */
	const CONFIG_FILE_EXT_XML = '.xml';
	/**
	 * Configuration file type, application.xml and config.xml
	 */
	const CONFIG_TYPE_XML = 'xml';
	/**
	 * Application configuration file name
	 */
	const CONFIG_FILE_PHP = 'application.php';
	/**
	 * File extension for external config files
	 */
	const CONFIG_FILE_EXT_PHP = '.php';
	/**
	 * Configuration file type, application.php and config.php
	 */
	const CONFIG_TYPE_PHP = 'php';
	/**
	 * Runtime directory name
	 */
	const RUNTIME_PATH = 'runtime';
	/**
	 * Config cache file
	 */
	const CONFIGCACHE_FILE = 'config.cache';
	/**
	 * Global data file
	 */
	const GLOBAL_FILE = 'global.cache';

	/**
	 * @var array list of events that define application lifecycles
	 */
	private static $_steps = [
		'onBeginRequest',
		'onLoadState',
		'onLoadStateComplete',
		'onAuthentication',
		'onAuthenticationComplete',
		'onAuthorization',
		'onAuthorizationComplete',
		'onPreRunService',
		'runService',
		'onSaveState',
		'onSaveStateComplete',
		'onPreFlushOutput',
		'flushOutput'
	];

	/**
	 * @var string application ID
	 */
	private $_id;
	/**
	 * @var string unique application ID
	 */
	private $_uniqueID;
	/**
	 * @var bool whether the request is completed
	 */
	private $_requestCompleted = false;
	/**
	 * @var int application state
	 */
	private $_step;
	/**
	 * @var array available services and their configurations indexed by service IDs
	 */
	private $_services;
	/**
	 * @var IService current service instance
	 */
	private $_service;
	/**
	 * @var array list of loaded application modules
	 */
	private $_modules = [];
	/**
	 * @var array list of application modules yet to be loaded
	 */
	private $_lazyModules = [];
	/**
	 * @var \Prado\Collections\TMap list of application parameters
	 */
	private $_parameters;
	/**
	 * @var string configuration file
	 */
	private $_configFile;
	/**
	 * @var string configuration file extension
	 */
	private $_configFileExt;
	/**
	 * @var string configuration type
	 */
	private $_configType;
	/**
	 * @var string application base path
	 */
	private $_basePath;
	/**
	 * @var string directory storing application state
	 */
	private $_runtimePath;
	/**
	 * @var bool if any global state is changed during the current request
	 */
	private $_stateChanged = false;
	/**
	 * @var array global variables (persistent across sessions, requests)
	 */
	private $_globals = [];
	/**
	 * @var string cache file
	 */
	private $_cacheFile;
	/**
	 * @var TErrorHandler error handler module
	 */
	private $_errorHandler;
	/**
	 * @var THttpRequest request module
	 */
	private $_request;
	/**
	 * @var THttpResponse response module
	 */
	private $_response;
	/**
	 * @var THttpSession session module, could be null
	 */
	private $_session;
	/**
	 * @var ICache cache module, could be null
	 */
	private $_cache;
	/**
	 * @var IStatePersister application state persister
	 */
	private $_statePersister;
	/**
	 * @var IUser user instance, could be null
	 */
	private $_user;
	/**
	 * @var TGlobalization module, could be null
	 */
	private $_globalization;
	/**
	 * @var TSecurityManager security manager module
	 */
	private $_security;
	/**
	 * @var TAssetManager asset manager module
	 */
	private $_assetManager;
	/**
	 * @var TAuthorizationRuleCollection collection of authorization rules
	 */
	private $_authRules;
	/**
	 * @var TApplicationMode application mode
	 */
	private $_mode = TApplicationMode::Debug;

	/**
	 * @var string Customizable page service ID
	 */
	private $_pageServiceID = self::PAGE_SERVICE_ID;

	/**
	 * Constructor.
	 * Sets application base path and initializes the application singleton.
	 * Application base path refers to the root directory storing application
	 * data and code not directly accessible by Web users.
	 * By default, the base path is assumed to be the <b>protected</b>
	 * directory under the directory containing the current running script.
	 * @param string $basePath application base path or configuration file path.
	 *        If the parameter is a file, it is assumed to be the application
	 *        configuration file, and the directory containing the file is treated
	 *        as the application base path.
	 *        If it is a directory, it is assumed to be the application base path,
	 *        and within that directory, a file named <b>application.xml</b>
	 *        will be looked for. If found, the file is considered as the application
	 *        configuration file.
	 * @param bool $cacheConfig whether to cache application configuration. Defaults to true.
	 * @param int $configType configuration type. Defaults to CONFIG_TYPE_XML.
	 * @throws TConfigurationException if configuration file cannot be read or the runtime path is invalid.
	 */
	public function __construct($basePath = 'protected', $cacheConfig = true, $configType = self::CONFIG_TYPE_XML)
	{
		// register application as a singleton
		Prado::setApplication($this);
		$this->setConfigurationType($configType);
		$this->resolvePaths($basePath);

		if ($cacheConfig) {
			$this->_cacheFile = $this->_runtimePath . DIRECTORY_SEPARATOR . self::CONFIGCACHE_FILE;
		}

		// generates unique ID by hashing the runtime path
		$this->_uniqueID = md5($this->_runtimePath);
		$this->_parameters = new \Prado\Collections\TMap;
		$this->_services = [$this->getPageServiceID() => ['TPageService', [], null]];

		Prado::setPathOfAlias('Application', $this->_basePath);
	}

	/**
	 * Resolves application-relevant paths.
	 * This method is invoked by the application constructor
	 * to determine the application configuration file,
	 * application root path and the runtime path.
	 * @param string $basePath the application root path or the application configuration file
	 * @see setBasePath
	 * @see setRuntimePath
	 * @see setConfigurationFile
	 */
	protected function resolvePaths($basePath)
	{
		// determine configuration path and file
		if (empty($basePath) || ($basePath = realpath($basePath)) === false) {
			throw new TConfigurationException('application_basepath_invalid', $basePath);
		}
		if (is_dir($basePath) && is_file($basePath . DIRECTORY_SEPARATOR . $this->getConfigurationFileName())) {
			$configFile = $basePath . DIRECTORY_SEPARATOR . $this->getConfigurationFileName();
		} elseif (is_file($basePath)) {
			$configFile = $basePath;
			$basePath = dirname($configFile);
		} else {
			$configFile = null;
		}

		// determine runtime path
		$runtimePath = $basePath . DIRECTORY_SEPARATOR . self::RUNTIME_PATH;
		if (is_writable($runtimePath)) {
			if ($configFile !== null) {
				$runtimePath .= DIRECTORY_SEPARATOR . basename($configFile) . '-' . Prado::getVersion();
				if (!is_dir($runtimePath)) {
					if (@mkdir($runtimePath) === false) {
						throw new TConfigurationException('application_runtimepath_failed', $runtimePath);
					}
					@chmod($runtimePath, PRADO_CHMOD); //make it deletable
				}
				$this->setConfigurationFile($configFile);
			}
			$this->setBasePath($basePath);
			$this->setRuntimePath($runtimePath);
		} else {
			throw new TConfigurationException('application_runtimepath_invalid', $runtimePath);
		}
	}

	/**
	 * Executes the lifecycles of the application.
	 * This is the main entry function that leads to the running of the whole
	 * Prado application.
	 */
	public function run()
	{
		try {
			$this->initApplication();
			$n = count(self::$_steps);
			$this->_step = 0;
			$this->_requestCompleted = false;
			while ($this->_step < $n) {
				if ($this->_mode === TApplicationMode::Off) {
					throw new THttpException(503, 'application_unavailable');
				}
				if ($this->_requestCompleted) {
					break;
				}
				$method = self::$_steps[$this->_step];
				Prado::trace("Executing $method()", 'Prado\TApplication');
				$this->$method();
				$this->_step++;
			}
		} catch (\Exception $e) {
			$this->onError($e);
		}
		$this->onEndRequest();
	}

	/**
	 * Completes current request processing.
	 * This method can be used to exit the application lifecycles after finishing
	 * the current cycle.
	 */
	public function completeRequest()
	{
		$this->_requestCompleted = true;
	}

	/**
	 * @return bool whether the current request is processed.
	 */
	public function getRequestCompleted()
	{
		return $this->_requestCompleted;
	}

	/**
	 * Returns a global value.
	 *
	 * A global value is one that is persistent across users sessions and requests.
	 * @param string $key the name of the value to be returned
	 * @param mixed $defaultValue the default value. If $key is not found, $defaultValue will be returned
	 * @return mixed the global value corresponding to $key
	 */
	public function getGlobalState($key, $defaultValue = null)
	{
		return isset($this->_globals[$key]) ? $this->_globals[$key] : $defaultValue;
	}

	/**
	 * Sets a global value.
	 *
	 * A global value is one that is persistent across users sessions and requests.
	 * Make sure that the value is serializable and unserializable.
	 * @param string $key the name of the value to be set
	 * @param mixed $value the global value to be set
	 * @param null|mixed $defaultValue the default value. If $key is not found, $defaultValue will be returned
	 * @param bool $forceSave wheter to force an immediate GlobalState save. defaults to false
	 */
	public function setGlobalState($key, $value, $defaultValue = null, $forceSave = false)
	{
		$this->_stateChanged = true;
		if ($value === $defaultValue) {
			unset($this->_globals[$key]);
		} else {
			$this->_globals[$key] = $value;
		}
		if ($forceSave) {
			$this->saveGlobals();
		}
	}

	/**
	 * Clears a global value.
	 *
	 * The value cleared will no longer be available in this request and the following requests.
	 * @param string $key the name of the value to be cleared
	 */
	public function clearGlobalState($key)
	{
		$this->_stateChanged = true;
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
		$this->_globals = $this->getApplicationStatePersister()->load();
	}

	/**
	 * Saves global values into persistent storage.
	 * This method is invoked when {@link onSaveState OnSaveState} event is raised.
	 */
	protected function saveGlobals()
	{
		if ($this->_stateChanged) {
			$this->_stateChanged = false;
			$this->getApplicationStatePersister()->save($this->_globals);
		}
	}

	/**
	 * @return string application ID
	 */
	public function getID()
	{
		return $this->_id;
	}

	/**
	 * @param string $value application ID
	 */
	public function setID($value)
	{
		$this->_id = $value;
	}

	/**
	 * @return string page service ID
	 */
	public function getPageServiceID()
	{
		return $this->_pageServiceID;
	}

	/**
	 * @param string $value page service ID
	 */
	public function setPageServiceID($value)
	{
		$this->_pageServiceID = $value;
	}

	/**
	 * @return string an ID that uniquely identifies this Prado application from the others
	 */
	public function getUniqueID()
	{
		return $this->_uniqueID;
	}

	/**
	 * @return TApplicationMode application mode. Defaults to TApplicationMode::Debug.
	 */
	public function getMode()
	{
		return $this->_mode;
	}

	/**
	 * @param TApplicationMode $value application mode
	 */
	public function setMode($value)
	{
		$this->_mode = TPropertyValue::ensureEnum($value, '\Prado\TApplicationMode');
	}

	/**
	 * @return string the directory containing the application configuration file (absolute path)
	 */
	public function getBasePath()
	{
		return $this->_basePath;
	}

	/**
	 * @param string $value the directory containing the application configuration file
	 */
	public function setBasePath($value)
	{
		$this->_basePath = $value;
	}

	/**
	 * @return string the application configuration file (absolute path)
	 */
	public function getConfigurationFile()
	{
		return $this->_configFile;
	}

	/**
	 * @param string $value the application configuration file (absolute path)
	 */
	public function setConfigurationFile($value)
	{
		$this->_configFile = $value;
	}

	/**
	 * @return string the application configuration file (absolute path)
	 */
	public function getConfigurationType()
	{
		return $this->_configType;
	}

	/**
	 * @param string $value the application configuration type. 'xml' and 'php' are valid values
	 */
	public function setConfigurationType($value)
	{
		$this->_configType = $value;
	}

	/**
	 * @return string the application configuration type. default is 'xml'
	 */
	public function getConfigurationFileExt()
	{
		if ($this->_configFileExt === null) {
			switch ($this->_configType) {
				case TApplication::CONFIG_TYPE_PHP:
					$this->_configFileExt = TApplication::CONFIG_FILE_EXT_PHP;
					break;
				default:
					$this->_configFileExt = TApplication::CONFIG_FILE_EXT_XML;
			}
		}
		return $this->_configFileExt;
	}

	/**
	 * @return string the default configuration file name
	 */
	public function getConfigurationFileName()
	{
		static $fileName;
		if ($fileName == null) {
			switch ($this->_configType) {
				case TApplication::CONFIG_TYPE_PHP:
					$fileName = TApplication::CONFIG_FILE_PHP;
					break;
				default:
					$fileName = TApplication::CONFIG_FILE_XML;
			}
		}
		return $fileName;
	}

	/**
	 * @return string the directory storing cache data and application-level persistent data. (absolute path)
	 */
	public function getRuntimePath()
	{
		return $this->_runtimePath;
	}

	/**
	 * @param string $value the directory storing cache data and application-level persistent data. (absolute path)
	 */
	public function setRuntimePath($value)
	{
		$this->_runtimePath = $value;
		if ($this->_cacheFile) {
			$this->_cacheFile = $this->_runtimePath . DIRECTORY_SEPARATOR . self::CONFIGCACHE_FILE;
		}
		// generates unique ID by hashing the runtime path
		$this->_uniqueID = md5($this->_runtimePath);
	}

	/**
	 * @return IService the currently requested service
	 */
	public function getService()
	{
		return $this->_service;
	}

	/**
	 * @param IService $value the currently requested service
	 */
	public function setService($value)
	{
		$this->_service = $value;
	}

	/**
	 * Adds a module to application.
	 * Note, this method does not do module initialization.
	 * @param string $id ID of the module
	 * @param null|IModule $module module object or null if the module has not been loaded yet
	 */
	public function setModule($id, IModule $module = null)
	{
		if (isset($this->_modules[$id])) {
			throw new TConfigurationException('application_moduleid_duplicated', $id);
		} else {
			$this->_modules[$id] = $module;
		}
	}

	/**
	 * @param mixed $id
	 * @return IModule the module with the specified ID, null if not found
	 */
	public function getModule($id)
	{
		if (!array_key_exists($id, $this->_modules)) {
			return null;
		}

		// force loading of a lazy module
		if ($this->_modules[$id] === null) {
			$module = $this->internalLoadModule($id, true);
			$module[0]->init($module[1]);
		}

		return $this->_modules[$id];
	}

	/**
	 * Returns a list of application modules indexed by module IDs.
	 * Modules that have not been loaded yet are returned as null objects.
	 * @return array list of loaded application modules, indexed by module IDs
	 */
	public function getModules()
	{
		return $this->_modules;
	}

	/**
	 * Returns the list of application parameters.
	 * Since the parameters are returned as a {@link \Prado\Collections\TMap} object, you may use
	 * the returned result to access, add or remove individual parameters.
	 * @return \Prado\Collections\TMap the list of application parameters
	 */
	public function getParameters()
	{
		return $this->_parameters;
	}

	/**
	 * @return THttpRequest the request module
	 */
	public function getRequest()
	{
		if (!$this->_request) {
			$this->_request = new \Prado\Web\THttpRequest;
			$this->_request->init(null);
		}
		return $this->_request;
	}

	/**
	 * @param THttpRequest $request the request module
	 */
	public function setRequest(THttpRequest $request)
	{
		$this->_request = $request;
	}

	/**
	 * @return THttpResponse the response module
	 */
	public function getResponse()
	{
		if (!$this->_response) {
			$this->_response = new THttpResponse;
			$this->_response->init(null);
		}
		return $this->_response;
	}

	/**
	 * @param THttpRequest $response the request module
	 */
	public function setResponse(THttpResponse $response)
	{
		$this->_response = $response;
	}

	/**
	 * @return THttpSession the session module, null if session module is not installed
	 */
	public function getSession()
	{
		if (!$this->_session) {
			$this->_session = new THttpSession;
			$this->_session->init(null);
		}
		return $this->_session;
	}

	/**
	 * @param THttpSession $session the session module
	 */
	public function setSession(THttpSession $session)
	{
		$this->_session = $session;
	}

	/**
	 * @return TErrorHandler the error handler module
	 */
	public function getErrorHandler()
	{
		if (!$this->_errorHandler) {
			$this->_errorHandler = new TErrorHandler;
			$this->_errorHandler->init(null);
		}
		return $this->_errorHandler;
	}

	/**
	 * @param TErrorHandler $handler the error handler module
	 */
	public function setErrorHandler(TErrorHandler $handler)
	{
		$this->_errorHandler = $handler;
	}

	/**
	 * @return TSecurityManager the security manager module
	 */
	public function getSecurityManager()
	{
		if (!$this->_security) {
			$this->_security = new TSecurityManager;
			$this->_security->init(null);
		}
		return $this->_security;
	}

	/**
	 * @param TSecurityManager $sm the security manager module
	 */
	public function setSecurityManager(TSecurityManager $sm)
	{
		$this->_security = $sm;
	}

	/**
	 * @return TAssetManager asset manager
	 */
	public function getAssetManager()
	{
		if (!$this->_assetManager) {
			$this->_assetManager = new TAssetManager;
			$this->_assetManager->init(null);
		}
		return $this->_assetManager;
	}

	/**
	 * @param TAssetManager $value asset manager
	 */
	public function setAssetManager(TAssetManager $value)
	{
		$this->_assetManager = $value;
	}

	/**
	 * @return IStatePersister application state persister
	 */
	public function getApplicationStatePersister()
	{
		if (!$this->_statePersister) {
			$this->_statePersister = new TApplicationStatePersister;
			$this->_statePersister->init(null);
		}
		return $this->_statePersister;
	}

	/**
	 * @param IStatePersister $persister application state persister
	 */
	public function setApplicationStatePersister(IStatePersister $persister)
	{
		$this->_statePersister = $persister;
	}

	/**
	 * @return ICache the cache module, null if cache module is not installed
	 */
	public function getCache()
	{
		return $this->_cache;
	}

	/**
	 * @param \Prado\Caching\ICache $cache the cache module
	 */
	public function setCache(\Prado\Caching\ICache $cache)
	{
		$this->_cache = $cache;
	}

	/**
	 * @return IUser the application user
	 */
	public function getUser()
	{
		return $this->_user;
	}

	/**
	 * @param \Prado\Security\IUser $user the application user
	 */
	public function setUser(\Prado\Security\IUser $user)
	{
		$this->_user = $user;
	}

	/**
	 * @param bool $createIfNotExists whether to create globalization if it does not exist
	 * @return TGlobalization globalization module
	 */
	public function getGlobalization($createIfNotExists = true)
	{
		if ($this->_globalization === null && $createIfNotExists) {
			$this->_globalization = new TGlobalization;
			$this->_globalization->init(null);
		}
		return $this->_globalization;
	}

	/**
	 * @param \Prado\I18N\TGlobalization $glob globalization module
	 */
	public function setGlobalization(\Prado\I18N\TGlobalization $glob)
	{
		$this->_globalization = $glob;
	}

	/**
	 * @return TAuthorizationRuleCollection list of authorization rules for the current request
	 */
	public function getAuthorizationRules()
	{
		if ($this->_authRules === null) {
			$this->_authRules = new \Prado\Security\TAuthorizationRuleCollection;
		}
		return $this->_authRules;
	}

	protected function getApplicationConfigurationClass()
	{
		return '\Prado\TApplicationConfiguration';
	}

	protected function internalLoadModule($id, $force = false)
	{
		[$moduleClass, $initProperties, $configElement] = $this->_lazyModules[$id];
		if (isset($initProperties['lazy']) && $initProperties['lazy'] && !$force) {
			Prado::trace("Postponed loading of lazy module $id ({$moduleClass})", '\Prado\TApplication');
			$this->setModule($id, null);
			return null;
		}

		Prado::trace("Loading module $id ({$moduleClass})", '\Prado\TApplication');
		$module = Prado::createComponent($moduleClass);
		foreach ($initProperties as $name => $value) {
			if ($name === 'lazy') {
				continue;
			}
			$module->setSubProperty($name, $value);
		}
		$this->setModule($id, $module);
		// keep the key to avoid reuse of the old module id
		$this->_lazyModules[$id] = null;

		return [$module, $configElement];
	}
	/**
	 * Applies an application configuration.
	 * @param TApplicationConfiguration $config the configuration
	 * @param bool $withinService whether the configuration is specified within a service.
	 */
	public function applyConfiguration($config, $withinService = false)
	{
		if ($config->getIsEmpty()) {
			return;
		}

		// set path aliases and using namespaces
		foreach ($config->getAliases() as $alias => $path) {
			Prado::setPathOfAlias($alias, $path);
		}
		foreach ($config->getUsings() as $using) {
			Prado::using($using);
		}

		// set application properties
		if (!$withinService) {
			foreach ($config->getProperties() as $name => $value) {
				$this->setSubProperty($name, $value);
			}
		}

		if (empty($this->_services)) {
			$this->_services = [$this->getPageServiceID() => ['Prado\Web\Services\TPageService', [], null]];
		}

		// load parameters
		foreach ($config->getParameters() as $id => $parameter) {
			if (is_array($parameter)) {
				$component = Prado::createComponent($parameter[0]);
				foreach ($parameter[1] as $name => $value) {
					$component->setSubProperty($name, $value);
				}
				$this->_parameters->add($id, $component);
			} else {
				$this->_parameters->add($id, $parameter);
			}
		}

		// load and init modules specified in app config
		$modules = [];
		foreach ($config->getModules() as $id => $moduleConfig) {
			if (!is_string($id)) {
				$id = '_module' . count($this->_lazyModules);
			}
			$this->_lazyModules[$id] = $moduleConfig;
			if ($module = $this->internalLoadModule($id)) {
				$modules[] = $module;
			}
		}
		foreach ($modules as $module) {
			$module[0]->init($module[1]);
		}

		// load service
		foreach ($config->getServices() as $serviceID => $serviceConfig) {
			$this->_services[$serviceID] = $serviceConfig;
		}

		// external configurations
		foreach ($config->getExternalConfigurations() as $filePath => $condition) {
			if ($condition !== true) {
				$condition = $this->evaluateExpression($condition);
			}
			if ($condition) {
				if (($path = Prado::getPathOfNamespace($filePath, $this->getConfigurationFileExt())) === null || !is_file($path)) {
					throw new TConfigurationException('application_includefile_invalid', $filePath);
				}
				$cn = $this->getApplicationConfigurationClass();
				$c = new $cn;
				$c->loadFromFile($path);
				$this->applyConfiguration($c, $withinService);
			}
		}
	}

	/**
	 * Loads configuration and initializes application.
	 * Configuration file will be read and parsed (if a valid cached version exists,
	 * it will be used instead). Then, modules are created and initialized;
	 * Afterwards, the requested service is created and initialized.
	 * @throws TConfigurationException if module is redefined of invalid type, or service not defined or of invalid type
	 */
	protected function initApplication()
	{
		Prado::trace('Initializing application', 'Prado\TApplication');

		if ($this->_configFile !== null) {
			if ($this->_cacheFile === null || @filemtime($this->_cacheFile) < filemtime($this->_configFile)) {
				$config = new TApplicationConfiguration;
				$config->loadFromFile($this->_configFile);
				if ($this->_cacheFile !== null) {
					file_put_contents($this->_cacheFile, serialize($config), LOCK_EX);
				}
			} else {
				$config = unserialize(file_get_contents($this->_cacheFile));
			}

			$this->applyConfiguration($config, false);
		}

		if (($serviceID = $this->getRequest()->resolveRequest(array_keys($this->_services))) === null) {
			$serviceID = $this->getPageServiceID();
		}

		$this->startService($serviceID);
	}

	/**
	 * Starts the specified service.
	 * The service instance will be created. Its properties will be initialized
	 * and the configurations will be applied, if any.
	 * @param string $serviceID service ID
	 */
	public function startService($serviceID)
	{
		if (isset($this->_services[$serviceID])) {
			[$serviceClass, $initProperties, $configElement] = $this->_services[$serviceID];
			$service = Prado::createComponent($serviceClass);
			if (!($service instanceof IService)) {
				throw new THttpException(500, 'application_service_invalid', $serviceClass);
			}
			if (!$service->getEnabled()) {
				throw new THttpException(500, 'application_service_unavailable', $serviceClass);
			}
			$service->setID($serviceID);
			$this->setService($service);

			foreach ($initProperties as $name => $value) {
				$service->setSubProperty($name, $value);
			}

			if ($configElement !== null) {
				$config = new TApplicationConfiguration;
				if ($this->getConfigurationType() == self::CONFIG_TYPE_PHP) {
					$config->loadFromPhp($configElement, $this->getBasePath());
				} else {
					$config->loadFromXml($configElement, $this->getBasePath());
				}
				$this->applyConfiguration($config, true);
			}

			$service->init($configElement);
		} else {
			throw new THttpException(500, 'application_service_unknown', $serviceID);
		}
	}

	/**
	 * Raises OnError event.
	 * This method is invoked when an exception is raised during the lifecycles
	 * of the application.
	 * @param mixed $param event parameter
	 */
	public function onError($param)
	{
		Prado::log($param->getMessage(), TLogger::ERROR, 'Prado\TApplication');
		$this->raiseEvent('OnError', $this, $param);
		$this->getErrorHandler()->handleError($this, $param);
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
		$this->raiseEvent('OnBeginRequest', $this, null);
	}

	/**
	 * Raises OnAuthentication event.
	 * This method is invoked when the user request needs to be authenticated.
	 */
	public function onAuthentication()
	{
		$this->raiseEvent('OnAuthentication', $this, null);
	}

	/**
	 * Raises OnAuthenticationComplete event.
	 * This method is invoked right after the user request is authenticated.
	 */
	public function onAuthenticationComplete()
	{
		$this->raiseEvent('OnAuthenticationComplete', $this, null);
	}

	/**
	 * Raises OnAuthorization event.
	 * This method is invoked when the user request needs to be authorized.
	 */
	public function onAuthorization()
	{
		$this->raiseEvent('OnAuthorization', $this, null);
	}

	/**
	 * Raises OnAuthorizationComplete event.
	 * This method is invoked right after the user request is authorized.
	 */
	public function onAuthorizationComplete()
	{
		$this->raiseEvent('OnAuthorizationComplete', $this, null);
	}

	/**
	 * Raises OnLoadState event.
	 * This method is invoked when the application needs to load state (probably stored in session).
	 */
	public function onLoadState()
	{
		$this->loadGlobals();
		$this->raiseEvent('OnLoadState', $this, null);
	}

	/**
	 * Raises OnLoadStateComplete event.
	 * This method is invoked right after the application state has been loaded.
	 */
	public function onLoadStateComplete()
	{
		$this->raiseEvent('OnLoadStateComplete', $this, null);
	}

	/**
	 * Raises OnPreRunService event.
	 * This method is invoked right before the service is to be run.
	 */
	public function onPreRunService()
	{
		$this->raiseEvent('OnPreRunService', $this, null);
	}

	/**
	 * Runs the requested service.
	 */
	public function runService()
	{
		if ($this->_service) {
			$this->_service->run();
		}
	}

	/**
	 * Raises OnSaveState event.
	 * This method is invoked when the application needs to save state (probably stored in session).
	 */
	public function onSaveState()
	{
		$this->raiseEvent('OnSaveState', $this, null);
		$this->saveGlobals();
	}

	/**
	 * Raises OnSaveStateComplete event.
	 * This method is invoked right after the application state has been saved.
	 */
	public function onSaveStateComplete()
	{
		$this->raiseEvent('OnSaveStateComplete', $this, null);
	}

	/**
	 * Raises OnPreFlushOutput event.
	 * This method is invoked right before the application flushes output to client.
	 */
	public function onPreFlushOutput()
	{
		$this->raiseEvent('OnPreFlushOutput', $this, null);
	}

	/**
	 * Flushes output to client side.
	 * @param bool $continueBuffering whether to continue buffering after flush if buffering was active
	 */
	public function flushOutput($continueBuffering = true)
	{
		$this->getResponse()->flush($continueBuffering);
	}

	/**
	 * Raises OnEndRequest event.
	 * This method is invoked when the application completes the processing of the request.
	 */
	public function onEndRequest()
	{
		$this->flushOutput(false); // flush all remaining content in the buffer
		$this->saveGlobals();  // save global state
		$this->raiseEvent('OnEndRequest', $this, null);
	}
}
