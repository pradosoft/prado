<?php

/**
 * TApplication class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado;

use Prado\Caching\ICache;
use Prado\Collections\TCollectionItemChangeParameter;
use Prado\Collections\TMap;
use Prado\Exceptions\{TConfigurationException, TExitException, THttpException};
use Prado\Exceptions\TErrorHandler;
use Prado\I18N\TGlobalization;
use Prado\Security\IUser;
use Prado\Security\TAuthorizationRuleCollection;
use Prado\Security\TSecurityManager;
use Prado\Web\{THttpRequest, THttpResponse, THttpSession};
use Prado\Web\TAssetManager;
use Prado\Util\TLogger;
use Prado\Web\Services\TPageService;
use Prado\Web\UI\TTemplateManager;
use Prado\Web\UI\TThemeManager;
use Prado\Xml\TXmlElement;

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
 * {@see IModule} interface. Each module accomplishes certain functionalities
 * that are shared by all Prado components in an application.
 * There are default modules, composer modules, and user-defined modules. The latter
 * offers extreme flexibility of extending TApplication in a plug-and-play fashion.
 * Modules cooperate with each other to serve a user request by following
 * a sequence of lifecycles predefined in TApplication.
 *
 * TApplicationConfiguration loads the composer.json for each installed composer extension
 * and checks the extra field for a "bootstrap" class for the package.
 * Packages can be specified as a configuration module (without a class) to load the
 * composer extension module.  The ID of the module is the name of the package.
 *
 * TApplication has four modes that can be changed by setting {@see setMode Mode}
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
 * {@see Shell\TShellApplication} extends TApplication for CLI use. It overrides
 * {@see initService()} to skip web service startup, overrides
 * {@see runService()} to dispatch shell commands instead of a web service, overrides
 * {@see flushOutput()} to write to a {@see Shell\TShellWriter} instead of the
 * HTTP response, and attaches argument processing to {@see onConfiguration}. The full
 * request lifecycle (all lifecycle steps) is executed for shell applications allowing
 * for authentication.
 *
 * TApplication maintains a lifecycle with the following stages:
 * - [construct] : The application instance has been constructed.
 * - [initApplication] : Configuration has been loaded; modules and the requested service have been instantiated.
 * - onConfiguration : Configuration has been fully applied. The request has not yet been resolved and no service has been started.
 * - onInitComplete : The service has been initialized. For {@see Shell\TShellApplication}, argument processing is attached to this event.
 * - [run] : The primary request lifecycle has begun.
 * - onBeginRequest : Application initialization has completed.
 * - onLoadState : State loading has begun.
 * - onLoadStateComplete : Application state has been loaded.
 * - onAuthentication : User authentication has begun, session login.
 * - onAuthenticationComplete : The request has been authenticated.
 * - onAuthorization : User authorization has begun, apply Authorization Rules.
 * - onAuthorizationComplete : The request has been authorized.
 * - onPreRunService : The application has prepared to run the requested service.
 * - runService : The requested service has run.
 * - onSaveState : State saving has begun.
 * - onSaveStateComplete : Application state has been saved.
 * - onPreFlushOutput : The application has prepared to flush output to the client.
 * - flushOutput : Output has been flushed to the client.
 * - onEndRequest : The request has been fully processed.
 * - [destruct] : The application instance has been destroyed.
 * Modules and services can attach their methods to one or several of the above
 * events and do appropriate processing when the events are raised. By this way,
 * the application is able to coordinate the activities of modules and services
 * in the above order. To terminate an application before the whole lifecycle
 * completes, call {@see completeRequest}.
 *
 * ## Module Dependency Ordering
 *
 * Each configuration batch is sorted into dependency-first initialization order
 * before initialization phases 2–4 (dyPreInit, init, dyPostInit) run.
 * Dependencies are declared via the {@see IModuleDependency} interface — either
 * directly on the module or on any attached behavior that also implements
 * {@see IModuleDependency}. Kahn's topological sort is used; a
 * {@see Exceptions\TConfigurationException} is thrown when a dependency cycle
 * is detected. See {@see TModule} for a full description of all declaration
 * mechanisms.
 *
 * Examples:
 * - Create and run a Prado application:
 * ```php
 * $application=new TApplication($configFile);
 * $application->run();
 * ```
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author Brad Anderson <belisoful@icloud.com> Self-encapsulation, module dependencies.
 * @since 3.0
 * @method array dyFilterModuleDependencies(array $moduleRecords, array $pending, bool $isPreInit) Filters the complete module dependency map before topological sorting; the (possibly modified) map is returned.
 */
class TApplication extends TComponent implements ISingleton
{
	/**
	 * Page service ID
	 */
	public const PAGE_SERVICE_ID = 'page';
	/**
	 * Application configuration file name
	 */
	public const CONFIG_FILE_XML = 'application.xml';
	/**
	 * File extension for external config files
	 */
	public const CONFIG_FILE_EXT_XML = '.xml';
	/**
	 * Configuration file type, application.xml and config.xml
	 */
	public const CONFIG_TYPE_XML = 'xml';
	/**
	 * Application configuration file name
	 */
	public const CONFIG_FILE_PHP = 'application.php';
	/**
	 * File extension for external config files
	 */
	public const CONFIG_FILE_EXT_PHP = '.php';
	/**
	 * Configuration file type, application.php and config.php
	 */
	public const CONFIG_TYPE_PHP = 'php';
	/**
	 * Runtime directory name
	 */
	public const RUNTIME_PATH = 'runtime';
	/**
	 * Config cache file
	 */
	public const CONFIGCACHE_FILE = 'config.cache';
	/**
	 * Global data file
	 */
	public const GLOBAL_FILE = 'global.cache';
	/**
	 * Default Application Mode
	 * @since 4.3.3
	 */
	public const DEFAULT_APPLICATION_MODE = TApplicationMode::Debug;
	/**
	 * Default Page Service Class
	 * @since 4.3.3
	 */
	public const DEFAULT_PAGE_SERVICE_CLASS = TPageService::class;
	/**
	 * Key used within the dependency cache array to store sort results,
	 * distinct from the integer spl_object_id keys used for per-instance data.
	 * @since 4.4.0
	 */
	public const DEP_SORT_CACHE_KEY = '_sort';

	/**
	 * Canonical IDs assigned to lazily-created default-core modules by
	 * {@see bootstrapModule()}, and the registry keys
	 * {@see bootstrapDefaultModules()} writes into `$_modules`.
	 * @since 4.4.0
	 */
	public const DEFAULT_REQUEST_ID = 'request';
	public const DEFAULT_RESPONSE_ID = 'response';
	public const DEFAULT_SESSION_ID = 'session';
	public const DEFAULT_ERROR_HANDLER_ID = 'errorHandler';
	public const DEFAULT_SECURITY_MANAGER_ID = 'securityManager';
	public const DEFAULT_ASSET_MANAGER_ID = 'assetManager';
	public const DEFAULT_GLOBALIZATION_ID = 'globalization';
	public const DEFAULT_TEMPLATE_MANAGER_ID = 'templateManager';
	public const DEFAULT_THEME_MANAGER_ID = 'themeManager';

	/**
	 * Set by {@see bootstrapDefaultModules()} on completion; while set,
	 * {@see bootstrapModule()} also registers late-arrival defaults
	 * in `$_modules`.
	 * @since 4.4.0
	 */
	public const STATE_DEFAULT_MODULES_BOOTSTRAPPED = (1 << 0);

	/**
	 * Set by {@see initApplication()} after {@see onInitComplete} returns.
	 * @since 4.4.0
	 */
	public const STATE_INITIALIZED = (1 << 1);

	/**
	 * @var string[] ordered list of lifecycle method names executed by {@see run()}.
	 *   Override {@see getSteps()} to customize the lifecycle in subclasses.
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
		'flushOutput',
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
	private $_step = 0;
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
	 * @var TMap list of application parameters
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
	 * @var string configuration file name
	 * @since 4.3.3
	 */
	private $_configFileName;
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
	 * @var TTemplateManager template manager module
	 */
	private $_templateManager;
	/**
	 * @var TThemeManager theme manager module
	 */
	private $_themeManager;
	/**
	 * @var TAuthorizationRuleCollection collection of authorization rules
	 */
	private $_authRules;
	/**
	 * @var string|TApplicationMode application mode
	 */
	private $_mode;

	/**
	 * @var string Customizable page service ID
	 */
	private $_pageServiceID;

	/**
	 * @var int Bit field of TApplication internal state flags. Set via
	 *   {@see setStateFlag()}, queried via {@see hasStateFlag()} or
	 *   {@see getStateFlags()}. See the `STATE_*` constants for valid bits.
	 * @since 4.4.0
	 */
	private int $_stateFlags = 0;

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
	 * @param ?string $configType configuration type. Defaults to null then set to static::CONFIG_TYPE_XML.
	 * @throws TConfigurationException if configuration file cannot be read or the runtime path is invalid.
	 */
	public function __construct($basePath = 'protected', $cacheConfig = true, $configType = null)
	{
		if ($configType === null) {
			$configType = static::CONFIG_TYPE_XML;
		}
		$this->setMode(static::DEFAULT_APPLICATION_MODE);
		$this->setPageServiceID(static::PAGE_SERVICE_ID);

		$this->registerApplication();
		$this->setConfigurationType($configType);
		$this->resolvePaths($basePath);

		if ($cacheConfig) {
			$this->setCacheFile($this->buildCacheFilePath($this->getRuntimePath()));
		}

		$this->setParameters($this->newParameters());

		//This can be changed through setPageServiceID
		$this->registerService($this->getPageServiceID(), static::DEFAULT_PAGE_SERVICE_CLASS);

		Prado::setPathOfAlias('Application', $this->getBasePath());
		parent::__construct();
	}

	/**
	 * Returns the current Prado application.  This enables application behaviors to
	 * be used for undefined static function calls via {@see TComponent::__callStatic}.
	 * @param bool $create This is ignored and returns Prado::getApplication().
	 * @return ?object The singleton instance of the class.
	 * @since 4.3.0
	 */
	public static function singleton(bool $create = true): ?object
	{
		return Prado::getApplication();
	}

	/**
	 * Registers this instance as the primary PRADO application via {@see Prado::setApplication()}.
	 * Called once from the constructor before any configuration is loaded.
	 * Override in a subclass to redirect or suppress registration during testing.
	 * @since 4.3.3
	 */
	protected function registerApplication()
	{
		Prado::setApplication($this);
	}

	/**
	 * Returns the ordered list of lifecycle method names executed by {@see run()}.
	 * Subclasses may override this method to add, remove, or otherwise change steps.
	 * @return string[] the lifecycle step method names.
	 * @since 4.3.3
	 */
	protected function getSteps(): array
	{
		return self::$_steps;
	}

	/**
	 * Returns the index of the current lifecycle step being executed by {@see run()}.
	 * @return int the index of the current lifecycle step being executed.
	 * @since 4.3.3
	 */
	protected function getStep(): int
	{
		return $this->_step;
	}

	/**
	 * Sets the lifecycle step index; used by {@see run()} to track progress through {@see getSteps()}.
	 * @param int $value the lifecycle step index to set.
	 * @since 4.3.3
	 */
	private function setStep(int $value): void
	{
		$this->_step = $value;
	}

	/**
	 * Returns the raw `$_stateFlags` bit field. See the `STATE_*` constants
	 * for which bits are defined.
	 * @return int the raw bit field of internal state flags.
	 * @since 4.4.0
	 */
	public function getStateFlags(): int
	{
		return $this->_stateFlags;
	}

	/**
	 * Returns true when every bit in `$flag` is set on `$_stateFlags`.
	 * @param int $flag one or more `STATE_*` bits to test for.
	 * @return bool true when all of `$flag`'s bits are currently set.
	 * @since 4.4.0
	 */
	public function hasStateFlag(int $flag): bool
	{
		return ($this->_stateFlags & $flag) === $flag;
	}

	/**
	 * Sets (`$on = true`) or clears (`$on = false`) every bit in `$flag` on
	 * `$_stateFlags`.
	 * @param int  $flag one or more `STATE_*` bits to mutate.
	 * @param bool $on   true to set the bits, false to clear them.
	 * @since 4.4.0
	 */
	protected function setStateFlag(int $flag, bool $on = true): void
	{
		if ($on) {
			$this->_stateFlags |= $flag;
		} else {
			$this->_stateFlags &= ~$flag;
		}
	}

	/**
	 * Resolves application-relevant paths.
	 * This method is invoked by the application constructor
	 * to determine the application configuration file,
	 * application root path and the runtime path.
	 * The runtime path is resolved by {@see resolveRuntimePath()}, which
	 * subclasses may override to provide alternative runtime-path strategies.
	 * @param string $basePath the application root path or the application configuration file
	 * @see setBasePath
	 * @see setRuntimePath
	 * @see setConfigurationFile
	 * @see resolveRuntimePath
	 */
	protected function resolvePaths($basePath)
	{
		// determine configuration path and file
		if (empty($errValue = $basePath) || ($basePath = realpath($basePath)) === false) {
			throw new TConfigurationException('application_basepath_invalid', $errValue);
		}
		if (is_dir($basePath) && is_file($cf = $basePath . DIRECTORY_SEPARATOR . $this->getConfigurationFileName())) {
			$configFile = $cf;
		} elseif (is_file($basePath)) {
			$configFile = $basePath;
			$basePath = dirname($configFile);
		} else {
			$configFile = null;
		}

		$runtimePath = $this->resolveRuntimePath($basePath, $configFile);

		if ($configFile !== null) {
			$this->setConfigurationFile($configFile);
		}
		$this->setBasePath($basePath);
		$this->setRuntimePath($runtimePath);
	}

	/**
	 * Resolves and returns the runtime directory path.
	 * Called by {@see resolvePaths()} after the base path and configuration
	 * file have been determined. Subclasses may override this method to provide
	 * alternative runtime-path strategies without having to duplicate the base
	 * path and config-file logic in {@see resolvePaths()}.
	 * @param string $basePath the real, validated application base path.
	 * @param null|string $configFile the resolved configuration file path, or
	 *   `null` when no configuration file was found.
	 * @throws TConfigurationException if the runtime directory is not writable
	 *   or a versioned sub-directory cannot be created.
	 * @return string absolute path to the runtime directory, ready for use.
	 * @see resolvePaths
	 * @since 4.3.3
	 */
	protected function resolveRuntimePath(string $basePath, ?string $configFile): string
	{
		$runtimePath = $basePath . DIRECTORY_SEPARATOR . static::RUNTIME_PATH;
		if (!is_writable($runtimePath)) {
			throw new TConfigurationException('application_runtimepath_invalid', $runtimePath);
		}
		if ($configFile !== null) {
			$runtimePath .= DIRECTORY_SEPARATOR . basename($configFile) . '-' . Prado::getVersion();
			if (!is_dir($runtimePath)) {
				if (@mkdir($runtimePath) === false) {
					throw new TConfigurationException('application_runtimepath_failed', $runtimePath);
				}
				@chmod($runtimePath, Prado::getDefaultDirPermissions()); //make it deletable
			}
		}
		return $runtimePath;
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
			$steps = $this->getSteps();
			$n = count($steps);
			$this->setStep(0);
			$this->setRequestCompleted(false);
			while (($i = $this->getStep()) < $n) {
				if ($this->getMode() === TApplicationMode::Off) {
					throw new THttpException(503, 'application_unavailable');
				}
				if ($this->getRequestCompleted()) {
					break;
				}
				$method = $steps[$i];
				Prado::trace("Executing $method()", TApplication::class);
				$this->$method();
				$this->setStep(++$i);
			}
		} catch (TExitException $e) {
			$this->onEndRequest();
			$this->exit($e->getExitCode());
			return;
		} catch (\Exception $e) {
			$this->onError($e);
		}
		$this->onEndRequest();
	}

	/**
	 * Calls the PHP `exit` construct with `$exitCode`. Override to intercept
	 * process termination (e.g. capture the code in tests instead of exiting).
	 * @param int $exitCode exit status passed to the OS.
	 * @since 4.3.3
	 */
	protected function exit(int $exitCode): void
	{
		exit($exitCode);
	}

	/**
	 * Completes current request processing.
	 * This method can be used to exit the application lifecycles after finishing
	 * the current cycle.
	 */
	public function completeRequest()
	{
		$this->setRequestCompleted(true);
	}

	/**
	 * Returns whether the current request has been flagged as completed via {@see completeRequest()}.
	 * @return bool whether the current request is processed.
	 */
	public function getRequestCompleted()
	{
		return $this->_requestCompleted;
	}

	/**
	 * Flags the request as completed or pending; called internally by {@see completeRequest()}.
	 * @param bool $value whether the current request is processed.
	 * @since 4.3.3
	 */
	protected function setRequestCompleted($value)
	{
		$this->_requestCompleted = $value;
	}

	// =========================================================================
	// Global State API
	// =========================================================================

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
		return array_key_exists($key, $this->_globals) ? $this->_globals[$key] : $defaultValue;
	}

	/**
	 * Sets a global value.
	 *
	 * A global value is one that is persistent across users sessions and requests.
	 * Make sure that the value is serializable and unserializable.
	 *
	 * Raises {@see onGlobalStateChange} when the stored value actually changes.
	 * The event parameter is a read-only {@see TCollectionItemChangeParameter};
	 * `IS_UNSET` is never set, so `->getIsUnset()` is always `false` and all
	 * set-operation getters are meaningful:
	 *
	 * | Getter              | Key           | Returns  | Notes                                             |
	 * |---------------------|---------------|----------|---------------------------------------------------|
	 * | `->getKey()`        | `'key'`       | `string` | The global-state key that was modified.           |
	 * | `->getValue()`      | `'value'`     | `mixed`  | The new value (equal to `$value`).                |
	 * | `->getIsDefault()`  | `'isDefault'` | `bool`   | `true` when `$value === $defaultValue`.           |
	 * | `->getIsNew()`      | `'isNew'`     | `bool`   | `true` when the key did not previously exist.     |
	 * | `->getOldValue()`   | `'oldValue'`  | `mixed`  | Previous value; `null` placeholder when `isNew`.  |
	 *
	 * @param string $key the name of the value to be set
	 * @param mixed $value the global value to be set
	 * @param ?mixed $defaultValue the identity value: when `$value === $defaultValue`, the key is cleared rather than stored.
	 * @param bool $forceSave whether to force an immediate GlobalState save. Defaults to false.
	 */
	public function setGlobalState($key, $value, $defaultValue = null, $forceSave = false)
	{
		$isDefault = ($value === $defaultValue);
		$isset = array_key_exists($key, $this->_globals);
		$changed = false;

		if ($isDefault) {
			if ($isset) {
				$oldValue = $this->_globals[$key];
				unset($this->_globals[$key]);
				$this->_stateChanged = true;
				$changed = true;
			}
		} else {
			if (!$isset || $this->_globals[$key] !== $value) {
				$oldValue = $isset ? $this->_globals[$key] : null;
				$this->_globals[$key] = $value;
				$this->_stateChanged = true;
				$changed = true;
			}
		}

		if ($changed) {
			$flags = ($isDefault ? TCollectionItemChangeParameter::IS_DEFAULT : 0)
				| (!$isset ? TCollectionItemChangeParameter::IS_NEW : 0);
			$this->onGlobalStateChange(new TCollectionItemChangeParameter(
				$key,
				$value,
				$isset ? $oldValue : null,
				$flags,
				true
			));
		}

		if ($forceSave) {
			$this->saveGlobals();
		}
	}

	/**
	 * Clears a global value.
	 *
	 * The value cleared will no longer be available in this request and the following requests.
	 *
	 * Raises {@see onGlobalStateChange} when the key was present. The event parameter
	 * is a read-only {@see TCollectionItemChangeParameter} with `IS_UNSET` set and
	 * `IS_NEW` never set, so the removal getters are:
	 *
	 * | Getter             | Key          | Returns  | Notes                                         |
	 * |--------------------|--------------|----------|-----------------------------------------------|
	 * | `->getKey()`       | `'key'`      | `string` | The global-state key that was removed.        |
	 * | `->getIsUnset()`   | `'isUnset'`  | `bool`   | Always `true`; signals explicit removal.      |
	 * | `->getOldValue()`  | `'oldValue'` | `mixed`  | The value previously stored under `$key`.     |
	 *
	 * @param string $key the name of the value to be cleared
	 */
	public function clearGlobalState($key)
	{
		if (array_key_exists($key, $this->_globals)) {
			$oldValue = $this->_globals[$key];
			unset($this->_globals[$key]);
			$this->_stateChanged = true;
			$this->onGlobalStateChange(new TCollectionItemChangeParameter(
				$key,
				null,
				$oldValue,
				TCollectionItemChangeParameter::IS_UNSET,
				true
			));
		}
	}

	/**
	 * Raises the {@see onGlobalStateChange OnGlobalStateChange} event.
	 *
	 * This event is raised by {@see setGlobalState} and {@see clearGlobalState}
	 * whenever a global state value is added, changed, or cleared. The event fires
	 * after the mutation, so handlers observe the new state via {@see getGlobalState}.
	 * The parameter is a read-only {@see TCollectionItemChangeParameter}; use
	 * `->getIsUnset()` to distinguish a remove (`true`) from a set (`false`):
	 *
	 * | Getter              | Key           | Type     | `offsetExists` true when        | Notes                                           |
	 * |---------------------|---------------|----------|---------------------------------|-------------------------------------------------|
	 * | `->getKey()`        | `'key'`       | `string` | always                          | The global-state key that was modified.         |
	 * | `->getValue()`      | `'value'`     | `mixed`  | `!isUnset` (set operations)     | The new value; stored as `null` when `isUnset`. |
	 * | `->getIsDefault()`  | `'isDefault'` | `bool`   | `!isUnset` (set operations)     | `true` when `$value === $defaultValue`.         |
	 * | `->getIsNew()`      | `'isNew'`     | `bool`   | `!isUnset` (set operations)     | `true` when the key did not previously exist.   |
	 * | `->getIsUnset()`    | `'isUnset'`   | `bool`   | `isUnset` (remove operations)   | `true`; signals the key was removed entirely.   |
	 * | `->getOldValue()`   | `'oldValue'`  | `mixed`  | `!isNew` (key existed before)   | Previous value; stored as `null` when `isNew`.  |
	 * | `->getFlags()`      | `'flags'`     | `int`    | always                          | Raw bitmask of the `IS_*` flag constants.       |
	 *
	 * @param TCollectionItemChangeParameter $param the read-only event parameter
	 * @since 4.3.3
	 */
	public function onGlobalStateChange($param)
	{
		$this->raiseEvent('onGlobalStateChange', $this, $param);
	}

	/**
	 * Loads global values from persistent storage.
	 * This method is invoked when {@see onLoadState OnLoadState} event is raised.
	 * After this method, values that are stored in previous requests become
	 * available to the current request via {@see getGlobalState}.
	 */
	protected function loadGlobals()
	{
		$this->_globals = $this->getApplicationStatePersister()->load() ?? [];
	}

	/**
	 * Saves global values into persistent storage.
	 * This method is invoked when {@see onSaveState OnSaveState} event is raised.
	 */
	protected function saveGlobals()
	{
		if ($this->_stateChanged) {
			$this->_stateChanged = false;
			$this->getApplicationStatePersister()->save($this->_globals);
		}
	}

	// =========================================================================
	// Property Getters and Setters
	// =========================================================================

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
	 * Returns the service ID used as the fallback page service when no services are explicitly
	 * configured. The default value is {@see static::PAGE_SERVICE_ID} (`'page'`).
	 * @return string the page service ID.
	 */
	public function getPageServiceID()
	{
		return $this->_pageServiceID;
	}

	/**
	 * Sets the fallback page service ID.
	 *
	 * If the old ID is registered as a service and the new ID is not already taken,
	 * the registry entry is moved from the old ID to the new ID.
	 *
	 * @param string $value the new fallback page service ID.
	 */
	public function setPageServiceID($value)
	{
		$old = $this->_pageServiceID;
		$this->_pageServiceID = $value;
		if ($old !== $value && $this->hasRegisteredService($old) && !$this->hasRegisteredService($value)) {
			$this->registerService($value, ...$this->getRegisteredService($old));
			$this->unregisterService($old);
		}
	}

	/**
	 * Returns an ID that uniquely identifies this Prado application from the others.
	 * When no explicit ID has been set via {@see setUniqueID()}, the ID is computed
	 * on demand by passing {@see getRuntimePath()} to {@see generateAppUniqueId()},
	 * ensuring it reflects the current runtime path even when {@see setRuntimePath()}
	 * is called after construction.
	 * @return string an ID that uniquely identifies this Prado application.
	 * @see generateAppUniqueId
	 */
	public function getUniqueID()
	{
		return $this->_uniqueID ?? $this->generateAppUniqueId($this->getRuntimePath());
	}

	/**
	 * Sets an explicit unique ID for this application instance, overriding the value
	 * that would otherwise be derived on demand by {@see getUniqueID()} via
	 * {@see generateAppUniqueId()}. Called internally by {@see setRuntimePath()}
	 * whenever the runtime path changes.
	 * @param string $value a unique ID for this application instance.
	 * @since 4.3.3
	 */
	protected function setUniqueID($value)
	{
		$this->_uniqueID = $value;
	}

	/**
	 * @return string|TApplicationMode application mode. Defaults to static::DEFAULT_APPLICATION_MODE - TApplicationMode::Debug.
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
		$this->_mode = TPropertyValue::ensureEnum($value, TApplicationMode::class);
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
	 * @return ?string the application configuration file (absolute path)
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
	 * @return string the configuration type ('xml' or 'php')
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
	 * Returns the config file extension for the current type ('.xml' or '.php'). Result is cached on first access.
	 * @return string the configuration file extension for the current configuration type ('.xml' or '.php').
	 */
	public function getConfigurationFileExt()
	{
		if ($this->_configFileExt === null) {
			switch ($this->getConfigurationType()) {
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
	 * Returns the default config file name for the current type ('application.xml' or 'application.php'). Result is cached on first access.
	 * @return string the default configuration file name
	 */
	public function getConfigurationFileName()
	{
		if ($this->_configFileName === null) {
			switch ($this->getConfigurationType()) {
				case TApplication::CONFIG_TYPE_PHP:
					$this->_configFileName = TApplication::CONFIG_FILE_PHP;
					break;
				default:
					$this->_configFileName = TApplication::CONFIG_FILE_XML;
			}
		}
		return $this->_configFileName;
	}

	/**
	 * @return string the directory storing cache data and application-level persistent data. (absolute path)
	 */
	public function getRuntimePath()
	{
		return $this->_runtimePath;
	}

	/**
	 * Sets the runtime path field directly without rebuilding the cache file path or unique ID.
	 * Intended for subclasses (e.g. test environments) that manage those derived values themselves.
	 * Use {@see setRuntimePath()} for the full update.
	 * @param string $value the directory storing cache data and application-level persistent data. (absolute path)
	 * @since 4.3.3
	 */
	protected function setRuntimePathDirect($value)
	{
		$this->_runtimePath = $value;
	}

	/**
	 * Sets the runtime path and synchronizes two derived values: rebuilds the config
	 * cache file path (if caching is enabled) and regenerates the unique application
	 * ID via {@see generateAppUniqueId()}. Use {@see setRuntimePathDirect()} to update
	 * the path alone without these side effects.
	 * @param string $value the directory storing cache data and application-level persistent data. (absolute path)
	 * @see generateAppUniqueId
	 */
	public function setRuntimePath($value)
	{
		$this->setRuntimePathDirect($value);
		$value = $this->getRuntimePath();
		if ($this->getCacheFile()) {
			$this->setCacheFile($this->buildCacheFilePath($value));
		}
		$this->setUniqueID($this->generateAppUniqueId($value));
	}

	/**
	 * Derives a unique application ID from `$token` (typically the runtime path).
	 * Subclasses may override this method to substitute a different hashing algorithm.
	 * Called by {@see setRuntimePath()} and, on demand, by {@see getUniqueID()}.
	 * @param string $token the value to hash; normally the absolute runtime path.
	 * @return string a unique ID derived from the token.
	 * @since 4.3.3
	 * @todo v4.4 change md5 to sha1
	 */
	protected function generateAppUniqueId(string $token): string
	{
		return md5($token);
	}

	/**
	 * @return ?string the config cache file path (absolute path), or null if caching is disabled
	 * @since 4.3.3
	 */
	protected function getCacheFile(): ?string
	{
		return $this->_cacheFile;
	}

	/**
	 * @param ?string $value the config cache file path (absolute path)
	 * @since 4.3.3
	 */
	protected function setCacheFile(?string $value): void
	{
		$this->_cacheFile = $value;
	}

	/**
	 * Builds the config cache file path for the given runtime directory.
	 * @param string $runtimePath absolute path to the runtime directory
	 * @return string absolute path to the config cache file
	 * @since 4.3.3
	 */
	protected function buildCacheFilePath(string $runtimePath): string
	{
		return $runtimePath . DIRECTORY_SEPARATOR . static::CONFIGCACHE_FILE;
	}

	// =========================================================================
	// Parameters API
	// =========================================================================

	/**
	 * @return TMap the list of application parameters
	 * @since 4.3.3
	 */
	protected function newParameters()
	{
		return new TMap();
	}

	/**
	 * Returns the list of application parameters.
	 * Since the parameters are returned as a {@see TMap} object, you may use
	 * the returned result to access, add or remove individual parameters.
	 * @return TMap the list of application parameters
	 */
	public function getParameters()
	{
		return $this->_parameters;
	}

	/**
	 * @param TMap $parameters the application parameter map.
	 * @since 4.3.3
	 */
	protected function setParameters($parameters)
	{
		$this->_parameters = $parameters;
	}

	// =========================================================================
	// Cache API
	// =========================================================================

	/**
	 * @return ?ICache the cache module, null if cache module is not installed
	 */
	public function getCache()
	{
		return $this->_cache;
	}

	/**
	 * @param ICache $cache the cache module
	 */
	public function setCache(ICache $cache)
	{
		$this->_cache = $cache;
	}

	// =========================================================================
	// User API
	// =========================================================================

	/**
	 * @return IUser the application user
	 */
	public function getUser()
	{
		return $this->_user;
	}

	/**
	 * This sets the application user and raises the onSetUser event.
	 * @param IUser $user the application user
	 */
	public function setUser(IUser $user)
	{
		$this->_user = $user;
		$this->onSetUser($user);
	}

	/**
	 * Raises onSetUser event.
	 * Allows modules/components to run handlers when the Application User is set.
	 * e.g. A user module could set the $_SERVER['HTTP_ACCEPT_LANGUAGE'] and
	 * $_SERVER['HTTP_ACCEPT_CHARSET'] in a cli environment to the user's last
	 * web Language and Charset so Emails (and other templates) get language
	 * customized.
	 * @param IUser $user
	 * @since 4.2.2
	 */
	public function onSetUser(IUser $user)
	{
		$this->raiseEvent('onSetUser', $this, $user);
	}

	// =========================================================================
	// Services API
	// =========================================================================

	/**
	 * @return ?IService the currently active service, or null if none has been started yet.
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
	 * Registers a service in the application's service registry.
	 * If a service is already registered under the same ID it is replaced.
	 * The service will be available for request routing via
	 * {@see TApplication::startService()} and discoverable by
	 * {@see getRegisteredServiceByClass()} / {@see getRegisteredServicesByClass()}.
	 *
	 * ```php
	 * $app->registerService('csp-reporter', TCspReporterService::class);
	 * $app->registerService('csp-reporter', TCspReporterService::class, ['AutoRegistered' => true]);
	 * ```
	 *
	 * @param string $id service ID to register; any existing entry under the same ID is overwritten.
	 * @param ?string $class fully-qualified class name of the service. Must be non-empty and must
	 *   implement {@see IService}; a null or empty value throws {@see TConfigurationException}.
	 * @param array $properties name → value map of initial properties applied to the service instance
	 *   via {@see TComponent::setSubProperty()} before {@see TService::init()} is called.
	 * @param null|array|TXmlElement $config optional extra configuration element passed to
	 *   {@see TService::init()}. Pass `null` (the default) for no extra configuration.
	 * @throws TConfigurationException if `$class` is empty, does not exist, or does not implement {@see IService}.
	 * @since 4.3.3
	 */
	public function registerService(string $id, ?string $class = null, array $properties = [], $config = null): void
	{
		if ($class === null || $class === '') {
			throw new TConfigurationException('application_service_class_required', $id);
		}
		$originalClass = $class;
		$class = Prado::usingClass($class);
		if (!is_string($class) || !class_exists($class, true)) {
			throw new TConfigurationException('application_service_class_not_found', $class ?? $originalClass);
		}
		if (!is_a($class, IService::class, true)) {
			throw new TConfigurationException('application_service_class_not_service', $class);
		}
		$this->_services[$id] = [$class, $properties, $config];
	}

	/**
	 * Removes a service from the application's service registry.
	 *
	 * If the ID is not registered this method is a no-op. Removing a service that has
	 * already been started via {@see startService()} does not stop the active instance.
	 * The default page service ID (see {@see getPageServiceID()}) cannot be unregistered.
	 *
	 * @param string $id service ID to remove.
	 * @throws TConfigurationException if `$id` is the default page service ID.
	 * @since 4.3.3
	 */
	public function unregisterService(string $id): void
	{
		if ($id === $this->getPageServiceID()) {
			throw new TConfigurationException('application_service_default_unregister', $id);
		}
		unset($this->_services[$id]);
	}

	/**
	 * Returns whether any services are registered, or whether a specific service ID is registered.
	 *
	 * When called with no argument (or `null`), returns `true` if at least one service is
	 * registered. When called with a string ID, returns `true` only if that specific service
	 * ID exists in the registry.
	 *
	 * @param ?string $id service ID to look for, or `null` to test whether any services exist.
	 * @return bool `true` when at least one service is registered (no argument), or when the
	 *   given ID is present in the service registry (string argument).
	 * @since 4.3.3
	 */
	public function hasRegisteredService(?string $id = null): bool
	{
		if ($id === null) {
			return !empty($this->_services);
		} else {
			return isset($this->_services[$id]);
		}
	}

	/**
	 * Returns the registry entry for the given service ID, or `null` if not registered.
	 *
	 * The returned array is a three-element tuple `[$class, $properties, $config]`:
	 * - `$class` (`string`) — fully-qualified class name of the service.
	 * - `$properties` (`array`) — name → value map of initial properties applied before `init()`.
	 * - `$config` (`null|array|TXmlElement`) — optional extra configuration element.
	 *
	 * @param string $id service ID to look for.
	 * @return ?array the three-element registry tuple, or `null` if the ID is not registered.
	 * @since 4.3.3
	 */
	public function getRegisteredService(string $id): ?array
	{
		return $this->_services[$id] ?? null;
	}

	/**
	 * Returns the full service registry as an associative array keyed by service ID.
	 *
	 * Each value is a three-element tuple `[$class, $properties, $config]` as described
	 * in {@see getRegisteredService()}. Entries appear in registration order.
	 * Use {@see hasRegisteredService()} to test for a specific ID without fetching the
	 * full map, or {@see getRegisteredServiceByClass()} /
	 * {@see getRegisteredServicesByClass()} to search by class name.
	 *
	 * @return array<string, array> map of service ID → registry tuple for every registered service.
	 * @since 4.3.3
	 */
	public function getRegisteredServices(): array
	{
		return $this->_services ?? [];
	}

	/**
	 * Returns the service ID of the first registered service whose class is or extends
	 * `$class`, or `null` if none matches. Subclass matching is always active; use
	 * {@see getRegisteredServicesByClass()} with `$strict = true` if you need an
	 * exact-class-only search across multiple registrations.
	 *
	 * ```php
	 * $id = $this->getApplication()->getRegisteredServiceByClass(TPageService::class);
	 * ```
	 *
	 * @param string $class fully-qualified class name to search for.
	 * @return ?string the ID of the first matching service in registration order, or `null`.
	 * @since 4.3.3
	 */
	public function getRegisteredServiceByClass(string $class): ?string
	{
		$class = Prado::usingClass($class);
		if (!is_string($class)) {
			return null;
		}
		foreach ($this->getRegisteredServices() as $id => $serviceConfig) {
			$serviceClass = $serviceConfig[0];
			if ($serviceClass === $class || is_a($serviceClass, $class, true)) {
				return (string) $id;
			}
		}
		return null;
	}

	/**
	 * Returns the IDs of all registered services whose class is or extends
	 * `$class`. Pass `$strict = true` to exclude subclasses.
	 * ```php
	 * $ids = $this->getApplication()->getRegisteredServicesByClass(TJsonService::class);
	 * [$firstId] = $this->getApplication()->getRegisteredServicesByClass(TPageService::class);
	 * ```
	 * @param string $class fully-qualified class name to search for.
	 * @param bool $strict when `true`, only exact class matches are returned. Default `false`.
	 * @return string[] the IDs of all matching services, or an empty array.
	 * @since 4.3.3
	 */
	public function getRegisteredServicesByClass(string $class, bool $strict = false): array
	{
		$class = Prado::usingClass($class);
		if (!is_string($class)) {
			return [];
		}
		$ids = [];
		foreach ($this->getRegisteredServices() as $id => $serviceConfig) {
			$serviceClass = $serviceConfig[0];
			if ($strict ? ($serviceClass === $class) : ($serviceClass === $class || is_a($serviceClass, $class, true))) {
				$ids[] = (string) $id;
			}
		}
		return $ids;
	}

	// =========================================================================
	// Modules API
	// =========================================================================

	/**
	 * @param string $id module ID to look up.
	 * @return bool whether the given ID has a pending lazy-module entry.
	 * @since 4.3.3
	 */
	public function hasLazyModule(string $id): bool
	{
		return isset($this->_lazyModules[$id]);
	}

	/**
	 * Returns the total number of lazy-module slots (including consumed ones).
	 * Used to generate unique auto-IDs for anonymous modules.
	 * @return int the total number of registered lazy-module slots.
	 * @since 4.3.3
	 */
	public function getLazyModuleCount(): int
	{
		return count($this->_lazyModules);
	}

	/**
	 * Returns the lazy-module registration tuple for the given ID, or `null` if
	 * the ID is not registered or has already been loaded (nulled out).
	 * The tuple is `[$class, $properties, $configElement]`.
	 * @param string $id module ID.
	 * @return ?array the registration tuple, or `null`.
	 * @since 4.3.3
	 */
	protected function getLazyModule(string $id): ?array
	{
		return $this->_lazyModules[$id] ?? null;
	}

	/**
	 * Registers or nullifies a lazy-module entry. Pass `null` to mark the slot
	 * as consumed without removing the key (preserving the ID against reuse).
	 * When a config tuple is provided, the class name is normalized to a PHP FQN
	 * via {@see Prado::usingClass()} when possible. If the class cannot be resolved at
	 * registration time, the original name is kept and resolution is deferred to
	 * {@see getModulesByType()} (lazy path) or {@see internalLoadModule()}.
	 * @param string $id module ID.
	 * @param ?array $config the registration tuple `[$class, $properties, $configElement]`,
	 *   or `null` to mark the slot as consumed.
	 * @since 4.3.3
	 */
	protected function setLazyModule(string $id, ?array $config): void
	{
		if ($config !== null && isset($config[0])) {
			$resolved = Prado::usingClass($config[0]);
			if (is_string($resolved)) {
				$config[0] = $resolved;
			}
		}
		$this->setLazyModuleDirect($id, $config);
	}

	/**
	 * Writes a lazy-module entry without class-name resolution.
	 * Prefer {@see setLazyModule()} for new registrations; use this only when
	 * the class name is already a PHP FQN (e.g. a normalization write-back).
	 * @param string $id module ID.
	 * @param ?array $config `[$class, $properties, $configElement]`, or `null` to consume the slot.
	 * @since 4.3.3
	 */
	protected function setLazyModuleDirect(string $id, ?array $config): void
	{
		$this->_lazyModules[$id] = $config;
	}

	/**
	 * Adds a module to application.
	 * Note, this method does not do module initialization.
	 * @param string $id ID of the module
	 * @param ?IModule $module module object or null if the module has not been loaded yet
	 */
	public function setModule($id, ?IModule $module = null)
	{
		if (isset($this->_modules[$id])) {
			throw new TConfigurationException('application_moduleid_duplicated', $id);
		} else {
			$this->_modules[$id] = $module;
		}
	}

	/**
	 * Returns the module registered under `$id`, or `null` if no module with that ID
	 * exists.
	 *
	 * Lazy modules are deferred at startup and loaded on first access. When a lazy
	 * module is loaded, its full four-phase sequence is run inline:
	 * {@see TModule::dyPreInit()}, dependency initialization (each declared dependency
	 * is loaded recursively via a nested `getModule()` call before `init()` runs),
	 * {@see IModule::init()}, and {@see TModule::dyPostInit()}.
	 *
	 * @param string $id module ID
	 * @return ?TModule the module with the specified ID, null if not found
	 */
	public function getModule($id)
	{
		if (!array_key_exists($id, $this->_modules)) {
			return null;
		}

		// force loading of a lazy module
		if ($this->_modules[$id] === null) {
			$module = $this->internalLoadModule($id, true);
			if ($module) {
				$this->runModuleLifecycle($module[0], $module[1]);
			}
		}

		return $this->_modules[$id];
	}

	/**
	 * Returns a list of application modules indexed by module IDs.
	 * Modules that have not been loaded yet are returned as null objects.
	 * @return array<TModule> list of loaded application modules, indexed by module IDs
	 */
	public function getModules()
	{
		return $this->_modules;
	}

	/**
	 * Returns a list of application modules of a specific class.
	 * Lazy Loading Modules are not loaded, and are null but have an ID Key.
	 * When null modules are found, load them with {@see getModule}. eg.
	 * ```php
	 *	foreach (Prado::getApplication()->getModulesByType(ICache::class) as $id => $module) {
	 *		$module = (!$module) ? $app->getModule($id) : $module;
	 *		...
	 *	}
	 * ```
	 * @template T of IModule
	 * @param class-string<T> $type class name of the modules to look for.
	 * @param bool $strict should the module be the class or can the module be a subclass
	 * @return array<string, null|T> keys are the ids of the module and values are module of a specific class
	 * @since 4.2.0
	 */
	public function getModulesByType($type, $strict = false)
	{
		$type = Prado::usingClass($type);
		if (!is_string($type)) {
			return [];
		}
		$m = [];
		foreach ($this->_modules as $id => $module) {
			if ($module === null && $this->hasLazyModule($id)) {
				$config = $this->getLazyModule($id);
				$moduleClass = $config[0];
				// If the class was unresolvable at registration time, try again now
				// (e.g. the relevant using() may have been called since then).
				$resolved = Prado::usingClass($moduleClass);
				if (!is_string($resolved)) {
					continue;
				}
				if ($resolved !== $moduleClass) {
					$config[0] = $resolved;
					$this->setLazyModuleDirect($id, $config);
					$moduleClass = $resolved;
				}
				if ($strict ? ($moduleClass === $type) : is_a($moduleClass, $type, true)) {
					$m[$id] = null;
				}
			} elseif ($module !== null && ($strict ? ($module::class === $type) : $module->isa($type))) {
				$m[$id] = $module;
			}
		}
		return $m;
	}

	/**
	 * Runs the four post-construction lifecycle phases on `$module`:
	 *  - `dyPreInit($config)`
	 *  - dependency resolution — force-loads lazy deps via
	 *    {@see getModule()}; missing required deps throw
	 *    {@see TConfigurationException}
	 *  - `init($config)`
	 *  - `dyPostInit($config)`
	 *
	 * Single source of truth for both the lazy-load path in
	 * {@see getModule()} and {@see bootstrapModule()}.
	 * @param IModule $module the module to bring online.
	 * @param mixed   $config configuration forwarded to every phase; `null`
	 *   for default-bootstrapped modules.
	 * @throws TConfigurationException when a required dependency is missing.
	 * @since 4.4.0
	 */
	protected function runModuleLifecycle(IModule $module, mixed $config): void
	{
		$isComponent = $module instanceof TComponent;
		if ($isComponent) {
			$module->dyPreInit($config);
		}
		foreach ($this->collectModuleDependencies($module, false)['deps'] as $dep) {
			if (!array_key_exists($dep['id'], $this->_modules)) {
				if ($dep['required']) {
					throw new TConfigurationException(
						'application_module_required_dep_missing',
						$module->getID(),
						$dep['id']
					);
				}
				continue; // advisory dep absent — skip silently
			}
			$this->getModule($dep['id']); // force-load if lazy
		}
		$module->init($config);
		if ($isComponent) {
			$module->dyPostInit($config);
		}
	}

	/**
	 * Assigns the module's ID, runs its lifecycle via
	 * {@see runModuleLifecycle()}, and — when `$register` is true —
	 * enrolls it in `$_modules` under `$module->getID()`.
	 * @param IModule $module   freshly-constructed module instance.
	 * @param string  $id       ID to assign.
	 * @param bool    $register write to `$_modules` after the lifecycle; `true` by default.
	 * @throws TConfigurationException when a required dependency is missing.
	 * @since 4.4.0
	 */
	protected function bootstrapModule(IModule $module, string $id, bool $register = true): void
	{
		$module->setID($id);
		$this->runModuleLifecycle($module, null);
		if ($register) {
			$this->_modules[$module->getID()] = $module;
		}
	}

	/**
	 * Returns true when {@see bootstrapModule()} should register a
	 * default-core module of `$type`: the
	 * {@see STATE_DEFAULT_MODULES_BOOTSTRAPPED} flag is set AND no
	 * same-type module is already in `$_modules`.
	 * @param string $type FQN of the default's canonical class.
	 * @since 4.4.0
	 */
	protected function shouldRegisterDefault(string $type): bool
	{
		return $this->hasStateFlag(static::STATE_DEFAULT_MODULES_BOOTSTRAPPED)
			&& empty($this->getModulesByType($type));
	}

	/**
	 * Enrols each instanced default-core module in `$_modules` unless a
	 * same-type module is already registered; never instantiates. Sets
	 * {@see STATE_DEFAULT_MODULES_BOOTSTRAPPED} on completion so later
	 * defaults register immediately via {@see bootstrapModule()}.
	 * @since 4.4.0
	 */
	protected function bootstrapDefaultModules(): void
	{
		$defaults = [
			static::DEFAULT_REQUEST_ID => [THttpRequest::class,    $this->getRequestDirect()],
			static::DEFAULT_RESPONSE_ID => [THttpResponse::class,   $this->getResponseDirect()],
			static::DEFAULT_SESSION_ID => [THttpSession::class,    $this->getSessionDirect()],
			static::DEFAULT_ERROR_HANDLER_ID => [TErrorHandler::class,   $this->getErrorHandlerDirect()],
			static::DEFAULT_SECURITY_MANAGER_ID => [TSecurityManager::class, $this->getSecurityManagerDirect()],
			static::DEFAULT_ASSET_MANAGER_ID => [TAssetManager::class,   $this->getAssetManagerDirect()],
			static::DEFAULT_GLOBALIZATION_ID => [TGlobalization::class,  $this->getGlobalizationDirect()],
			static::DEFAULT_TEMPLATE_MANAGER_ID => [TTemplateManager::class, $this->getTemplateManagerDirect()],
			static::DEFAULT_THEME_MANAGER_ID => [TThemeManager::class,   $this->getThemeManagerDirect()],
		];
		foreach ($defaults as $id => [$type, $module]) {
			if ($module === null) {
				continue;
			}
			if (!empty($this->getModulesByType($type))) {
				continue;
			}
			$this->_modules[$module->getID()] = $module;
		}
		$this->setStateFlag(static::STATE_DEFAULT_MODULES_BOOTSTRAPPED);
	}

	// =========================================================================
	// Request API
	// =========================================================================

	/**
	 * @return THttpRequest A new request module.
	 * @since 4.3.3
	 */
	protected function newRequest()
	{
		return new THttpRequest();
	}

	/**
	 * @return THttpRequest the request module
	 * @since 4.3.3
	 */
	protected function getRequestDirect()
	{
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
	 * Returns the request module, lazily bootstrapping a default via
	 * {@see newRequest()} + {@see bootstrapModule()} when not yet set.
	 * @return THttpRequest the request module
	 */
	public function getRequest()
	{
		$request = $this->getRequestDirect();
		if (!$request && ($request = $this->newRequest())) {
			$this->setRequest($request);
			$this->bootstrapModule(
				$request,
				static::DEFAULT_REQUEST_ID,
				$this->shouldRegisterDefault(THttpRequest::class)
			);
			$request = $this->getRequestDirect();
		}
		return $request;
	}

	// =========================================================================
	// Response API
	// =========================================================================

	/**
	 * @return THttpResponse a new response module.
	 * @since 4.3.3
	 */
	protected function newResponse()
	{
		return new THttpResponse();
	}

	/**
	 * @return THttpResponse the response module
	 * @since 4.3.3
	 */
	protected function getResponseDirect()
	{
		return $this->_response;
	}

	/**
	 * Returns the response module, lazily bootstrapping a default via
	 * {@see newResponse()} + {@see bootstrapModule()} when not yet set.
	 * @return THttpResponse the response module
	 */
	public function getResponse()
	{
		$response = $this->getResponseDirect();
		if (!$response && ($response = $this->newResponse())) {
			$this->setResponse($response);
			$this->bootstrapModule(
				$response,
				static::DEFAULT_RESPONSE_ID,
				$this->shouldRegisterDefault(THttpResponse::class)
			);
			$response = $this->getResponseDirect();
		}
		return $response;
	}

	/**
	 * @param THttpResponse $response the response module
	 */
	public function setResponse(THttpResponse $response)
	{
		$this->_response = $response;
	}

	// =========================================================================
	// Session API
	// =========================================================================

	/**
	 * @return THttpSession a new session module.
	 * @since 4.3.3
	 */
	protected function newSession()
	{
		return new THttpSession();
	}

	/**
	 * @return THttpSession the session module, null if session module is not installed
	 * @since 4.3.3
	 */
	protected function getSessionDirect()
	{
		return $this->_session;
	}

	/**
	 * Returns the session module, lazily bootstrapping a default via
	 * {@see newSession()} + {@see bootstrapModule()} when not yet set.
	 * @return THttpSession the session module, null if session module is not installed
	 */
	public function getSession()
	{
		$session = $this->getSessionDirect();
		if (!$session && ($session = $this->newSession())) {
			$this->setSession($session);
			$this->bootstrapModule(
				$session,
				static::DEFAULT_SESSION_ID,
				$this->shouldRegisterDefault(THttpSession::class)
			);
			$session = $this->getSessionDirect();
		}
		return $session;
	}

	/**
	 * @param THttpSession $session the session module
	 */
	public function setSession(THttpSession $session)
	{
		$this->_session = $session;
	}

	// =========================================================================
	// Error Handler API
	// =========================================================================

	/**
	 * @return TErrorHandler a new error handler module.
	 * @since 4.3.3
	 */
	protected function newErrorHandler()
	{
		return new TErrorHandler();
	}

	/**
	 * @return TErrorHandler the error handler module
	 * @since 4.3.3
	 */
	protected function getErrorHandlerDirect()
	{
		return $this->_errorHandler;
	}

	/**
	 * Returns the error handler module, lazily bootstrapping a default via
	 * {@see newErrorHandler()} + {@see bootstrapModule()} when not yet set.
	 * @return TErrorHandler the error handler module
	 */
	public function getErrorHandler()
	{
		$errorHandler = $this->getErrorHandlerDirect();
		if (!$errorHandler && ($errorHandler = $this->newErrorHandler())) {
			$this->setErrorHandler($errorHandler);
			$this->bootstrapModule(
				$errorHandler,
				static::DEFAULT_ERROR_HANDLER_ID,
				$this->shouldRegisterDefault(TErrorHandler::class)
			);
			$errorHandler = $this->getErrorHandlerDirect();
		}
		return $errorHandler;
	}

	/**
	 * @param TErrorHandler $handler the error handler module
	 */
	public function setErrorHandler(TErrorHandler $handler)
	{
		$this->_errorHandler = $handler;
	}

	// =========================================================================
	// Security Manager API
	// =========================================================================

	/**
	 * @return TSecurityManager a new security manager module.
	 * @since 4.3.3
	 */
	protected function newSecurityManager()
	{
		return new TSecurityManager();
	}

	/**
	 * @return TSecurityManager the security manager module
	 * @since 4.3.3
	 */
	protected function getSecurityManagerDirect()
	{
		return $this->_security;
	}

	/**
	 * Returns the security manager module, lazily bootstrapping a default via
	 * {@see newSecurityManager()} + {@see bootstrapModule()} when not yet set.
	 * @return TSecurityManager the security manager module
	 */
	public function getSecurityManager()
	{
		$securityManager = $this->getSecurityManagerDirect();
		if (!$securityManager && ($securityManager = $this->newSecurityManager())) {
			$this->setSecurityManager($securityManager);
			$this->bootstrapModule(
				$securityManager,
				static::DEFAULT_SECURITY_MANAGER_ID,
				$this->shouldRegisterDefault(TSecurityManager::class)
			);
			$securityManager = $this->getSecurityManagerDirect();
		}
		return $securityManager;
	}

	/**
	 * @param TSecurityManager $sm the security manager module
	 */
	public function setSecurityManager(TSecurityManager $sm)
	{
		$this->_security = $sm;
	}

	// =========================================================================
	// Asset Manager API
	// =========================================================================

	/**
	 * @return TAssetManager a new asset manager module.
	 * @since 4.3.3
	 */
	protected function newAssetManager()
	{
		return new TAssetManager();
	}

	/**
	 * @return TAssetManager asset manager
	 * @since 4.3.3
	 */
	protected function getAssetManagerDirect()
	{
		return $this->_assetManager;
	}

	/**
	 * Returns the asset manager module, lazily bootstrapping a default via
	 * {@see newAssetManager()} + {@see bootstrapModule()} when not yet set.
	 * @return TAssetManager asset manager
	 */
	public function getAssetManager()
	{
		$assetManager = $this->getAssetManagerDirect();
		if (!$assetManager && ($assetManager = $this->newAssetManager())) {
			$this->setAssetManager($assetManager);
			$this->bootstrapModule(
				$assetManager,
				static::DEFAULT_ASSET_MANAGER_ID,
				$this->shouldRegisterDefault(TAssetManager::class)
			);
			$assetManager = $this->getAssetManagerDirect();
		}
		return $assetManager;
	}

	/**
	 * @param TAssetManager $value asset manager
	 */
	public function setAssetManager(TAssetManager $value)
	{
		$this->_assetManager = $value;
	}

	// =========================================================================
	// Template Manager API
	// =========================================================================

	/**
	 * @return TTemplateManager a new template manager module.
	 * @since 4.3.3
	 */
	protected function newTemplateManager()
	{
		return new TTemplateManager();
	}

	/**
	 * @return TTemplateManager template manager
	 * @since 4.3.3
	 */
	protected function getTemplateManagerDirect()
	{
		return $this->_templateManager;
	}

	/**
	 * Returns the template manager module, lazily bootstrapping a default via
	 * {@see newTemplateManager()} + {@see bootstrapModule()} when not yet set.
	 * @return TTemplateManager template manager
	 */
	public function getTemplateManager()
	{
		$templateManager = $this->getTemplateManagerDirect();
		if (!$templateManager && ($templateManager = $this->newTemplateManager())) {
			$this->setTemplateManager($templateManager);
			$this->bootstrapModule(
				$templateManager,
				static::DEFAULT_TEMPLATE_MANAGER_ID,
				$this->shouldRegisterDefault(TTemplateManager::class)
			);
			$templateManager = $this->getTemplateManagerDirect();
		}
		return $templateManager;
	}

	/**
	 * @param TTemplateManager $value template manager
	 */
	public function setTemplateManager(TTemplateManager $value)
	{
		$this->_templateManager = $value;
	}

	// =========================================================================
	// Theme Manager API
	// =========================================================================

	/**
	 * @return TThemeManager a new theme manager module.
	 * @since 4.3.3
	 */
	protected function newThemeManager()
	{
		return new TThemeManager();
	}

	/**
	 * @return TThemeManager theme manager
	 * @since 4.3.3
	 */
	protected function getThemeManagerDirect()
	{
		return $this->_themeManager;
	}

	/**
	 * Returns the theme manager module, lazily bootstrapping a default via
	 * {@see newThemeManager()} + {@see bootstrapModule()} when not yet set.
	 * @return TThemeManager theme manager
	 */
	public function getThemeManager()
	{
		$themeManager = $this->getThemeManagerDirect();
		if (!$themeManager && ($themeManager = $this->newThemeManager())) {
			$this->setThemeManager($themeManager);
			$this->bootstrapModule(
				$themeManager,
				static::DEFAULT_THEME_MANAGER_ID,
				$this->shouldRegisterDefault(TThemeManager::class)
			);
			$themeManager = $this->getThemeManagerDirect();
		}
		return $themeManager;
	}

	/**
	 * @param TThemeManager $value theme manager
	 */
	public function setThemeManager(TThemeManager $value)
	{
		$this->_themeManager = $value;
	}

	// =========================================================================
	// Application State Persister API
	// =========================================================================

	/**
	 * @return IStatePersister a new application state persister.
	 * @since 4.3.3
	 */
	protected function newApplicationStatePersister()
	{
		return new TApplicationStatePersister();
	}

	/**
	 * @return IStatePersister application state persister
	 * @since 4.3.3
	 */
	protected function getApplicationStatePersisterDirect()
	{
		return $this->_statePersister;
	}

	/**
	 * Returns the application state persister, auto-creating it via
	 * {@see newApplicationStatePersister()} when not yet set. The persister
	 * is an `IStatePersister` rather than an application module, so it is
	 * not routed through {@see bootstrapModule()}; a direct
	 * `init(null)` is invoked when the implementation also happens to be an
	 * {@see IModule} (the default `TApplicationStatePersister` is).
	 * @return IStatePersister application state persister
	 */
	public function getApplicationStatePersister()
	{
		$statePersister = $this->getApplicationStatePersisterDirect();
		if (!$statePersister && ($statePersister = $this->newApplicationStatePersister())) {
			$this->setApplicationStatePersister($statePersister);
			if ($statePersister instanceof IModule) {
				$statePersister->init(null);
			}
			$statePersister = $this->getApplicationStatePersisterDirect();
		}
		return $statePersister;
	}

	/**
	 * @param IStatePersister $persister application state persister
	 */
	public function setApplicationStatePersister(IStatePersister $persister)
	{
		$this->_statePersister = $persister;
	}

	// =========================================================================
	// Globalization API
	// =========================================================================

	/**
	 * @return ?TGlobalization globalization module
	 * @since 4.3.3
	 */
	protected function newGlobalization()
	{
		return new TGlobalization();
	}

	/**
	 * @return ?TGlobalization globalization module
	 * @since 4.3.3
	 */
	protected function getGlobalizationDirect()
	{
		return $this->_globalization;
	}

	/**
	 * Returns the globalization module, lazily bootstrapping a default via
	 * {@see newGlobalization()} + {@see bootstrapModule()} when not yet set
	 * and `$createIfNotExists` is true; otherwise returns `null`.
	 * @param bool $createIfNotExists whether to create on first access.
	 * @return ?TGlobalization globalization module, or null when none exists and creation is disabled.
	 */
	public function getGlobalization($createIfNotExists = true)
	{
		$globalization = $this->getGlobalizationDirect();
		if (!$globalization && $createIfNotExists && ($globalization = $this->newGlobalization())) {
			$this->setGlobalization($globalization);
			$this->bootstrapModule(
				$globalization,
				static::DEFAULT_GLOBALIZATION_ID,
				$this->shouldRegisterDefault(TGlobalization::class)
			);
			$globalization = $this->getGlobalizationDirect();
		}
		return $globalization;
	}

	/**
	 * @param TGlobalization $glob globalization module
	 */
	public function setGlobalization(TGlobalization $glob)
	{
		$this->_globalization = $glob;
	}

	// =========================================================================
	// Module Loading & Configuration
	// =========================================================================

	/**
	 * Returns the authorization rule collection for the current request, creating an empty
	 * {@see TAuthorizationRuleCollection} on first access.
	 * @return TAuthorizationRuleCollection list of authorization rules for the current request
	 */
	public function getAuthorizationRules()
	{
		if ($this->_authRules === null) {
			$this->_authRules = new TAuthorizationRuleCollection();
		}
		return $this->_authRules;
	}

	/**
	 * Returns the fully-qualified class name used to parse application configuration files.
	 * Subclasses may override this method to substitute a custom configuration parser.
	 * @return string the application configuration class name.
	 * @since 4.3.3
	 */
	protected function getApplicationConfigurationClass()
	{
		return TApplicationConfiguration::class;
	}

	/**
	 * Instantiates a single module from its lazy-module registration tuple and
	 * applies its configuration properties (Phase 1 of module initialization).
	 *
	 * If the module's `lazy` property is `true` and `$force` is `false`, the module
	 * is registered as a null placeholder in `$_modules` and this method returns `null`.
	 * Otherwise the module is instantiated, its properties are applied via setXxx()
	 * calls, and it is stored in `$_modules`. The lazy-module slot is nullified to
	 * prevent ID reuse.
	 *
	 * @param string $id module ID registered in `$_lazyModules`.
	 * @param bool $force when `true`, forces loading even if the `lazy` property is set. Defaults to `false`.
	 * @return null|array{0: IModule, 1: mixed}|false a two-element array `[$module, $configElement]`
	 *   ready for `dyPreInit($configElement)` and `$module->init($configElement)`,
	 *   `null` if the module was deferred, or `false` if `$id` is not registered in `$_lazyModules`.
	 */
	protected function internalLoadModule($id, $force = false)
	{
		if (($lazy = $this->getLazyModule($id)) === null) {
			return false;
		}
		[$moduleClass, $initProperties, $configElement] = $lazy;
		if (isset($initProperties['lazy']) && $initProperties['lazy'] && !$force) {
			Prado::trace("Postponed loading of lazy module $id ({$moduleClass})", TApplication::class);
			$this->setModule($id, null);
			return null;
		}

		Prado::trace("Loading module $id ({$moduleClass})", TApplication::class);
		$module = Prado::createComponent($moduleClass);
		foreach ($initProperties as $name => $value) {
			if ($name === 'lazy') {
				continue;
			}
			$module->setSubProperty($name, $value);
		}
		$this->setModule($id, $module);
		// keep the key to avoid reuse of the old module id
		$this->setLazyModule($id, null);

		return [$module, $configElement];
	}

	/**
	 * Sorts a set of pending module entries into initialization order using
	 * Kahn's topological sort algorithm, respecting declared module dependencies.
	 *
	 * Both `$pending` and the return value are arrays of entries with the same
	 * structure — the output is the input reordered so each module appears after
	 * all of its in-batch dependencies:
	 * ```php
	 * [
	 *     'id'     => 'moduleA',   // registration ID
	 *     'module' => $moduleA,    // IModule instance
	 *     'config' => $config,     // configuration element passed to init()
	 * ]
	 * ```
	 *
	 * Dependencies that reference module IDs outside of `$pending` (already
	 * initialized or not present in this configuration batch) are silently
	 * ignored for ordering purposes. The caller is responsible for ensuring
	 * those modules are already live.
	 *
	 * `$cache` is a sort-result cache passed by reference, keyed by dependency-graph
	 * fingerprint under {@see DEP_SORT_CACHE_KEY}. Passing the same array across both
	 * phase-sort calls within a single configuration run avoids redundant sorts when
	 * the dependency graph is unchanged between phases.
	 *
	 * `$isPreInit` is forwarded to {@see collectModuleDependencies()} and from there
	 * to each module's {@see IModuleDependency::getModuleDependencies()}, allowing
	 * modules to declare different dependencies for the dyPreInit and init() passes.
	 *
	 * @param array  $pending list of pending module entries.
	 * @param array &$cache  sort-result cache, keyed by graph fingerprint.
	 * @param bool $isPreInit `true` when sorting the dyPreInit pass,
	 *   `false` for the init() pass (default).
	 * @throws \Prado\Exceptions\TConfigurationException if a dependency cycle is detected.
	 * @return array the entries of `$pending` in dependency-first order.
	 * @since 4.4.0
	 */
	protected function sortModulesByDependency(array $pending, array &$cache = [], bool $isPreInit = false): array
	{
		if (count($pending) <= 1) {
			return $pending;
		}

		// Build index: id → entry.
		$byId = [];
		foreach ($pending as $entry) {
			$byId[$entry['id']] = $entry;
		}

		// Collect all dependencies for this phase — fully re-evaluated on every call (no caching).
		$moduleRecords = [];
		foreach ($byId as $id => $entry) {
			// $moduleRecords[$id] = ['id' => string, 'module' => IModule, 'deps' => array<depId, array{id:string,required:bool}>].
			$moduleRecords[$id] = $this->collectModuleDependencies($entry['module'], $isPreInit);
		}

		$moduleRecords = $this->filterModuleDependencies($moduleRecords, $pending, $isPreInit);

		// Build a fingerprint of the in-batch dependency graph.
		// Only intra-batch edges influence the sort, so out-of-batch deps are excluded.
		// The fingerprint is stored under DEP_SORT_CACHE_KEY in $cache.
		$depGraph = [];
		foreach ($moduleRecords as $id => $record) {
			$inBatch = array_values(array_filter(
				array_keys($record['deps']),
				fn (string $depId) => isset($moduleRecords[$depId])
			));
			sort($inBatch);
			$depGraph[$id] = $inBatch;
		}
		ksort($depGraph);
		$fingerprint = sha1(serialize($depGraph));

		if (isset($cache[self::DEP_SORT_CACHE_KEY][$fingerprint])) {
			return array_map(fn (string $id) => $byId[$id], $cache[self::DEP_SORT_CACHE_KEY][$fingerprint]);
		}

		// Build in-degree table and successor adjacency list (intra-batch edges only).
		// Both required and advisory deps influence ordering when they reference an
		// in-batch module; the required flag only governs error handling, not sort order.
		$depMap = [];   // id → [dep_id, ...]  (used for cycle reporting)
		$inDegree = [];   // id → int
		$successors = [];   // dep_id → [dependent_id, ...]
		foreach ($moduleRecords as $id => $_) {
			$inDegree[$id] = 0;
			$successors[$id] = [];
			$depMap[$id] = [];
		}
		foreach ($moduleRecords as $id => $record) {
			foreach ($record['deps'] as $depId => $_dep) {
				if (!isset($moduleRecords[$depId])) {
					continue; // dep is outside this batch — already satisfied or truly external
				}
				$depMap[$id][] = $depId;
				$inDegree[$id]++;
				$successors[$depId][] = $id;
			}
		}

		// Kahn's algorithm: seed the queue with all zero-in-degree nodes.
		$queue = [];
		foreach ($inDegree as $id => $deg) {
			if ($deg === 0) {
				$queue[] = $id;
			}
		}

		$sorted = [];
		while ($queue !== []) {
			$id = array_shift($queue);
			$sorted[] = $id;
			foreach ($successors[$id] as $succId) {
				if (--$inDegree[$succId] === 0) {
					$queue[] = $succId;
				}
			}
		}

		if (count($sorted) < count($moduleRecords)) {
			$cycleIds = array_diff(array_keys($moduleRecords), $sorted);
			$cyclePath = $this->findCyclePath($cycleIds, $depMap);
			throw new TConfigurationException(
				'application_module_dependency_cycle',
				implode(' -> ', $cyclePath)
			);
		}

		$cache[self::DEP_SORT_CACHE_KEY][$fingerprint] = $sorted;
		return array_map(fn (string $id) => $byId[$id], $sorted);
	}

	/**
	 * Collects all dependencies that `$module` declares for the given lifecycle
	 * `$isPreInit`, merging two sources in priority order — module first, then
	 * behaviors — and deduplicating by module ID. When the same ID appears in
	 * both sources, the module-level declaration wins.
	 *
	 * Sources:
	 * 1. {@see IModuleDependency::getModuleDependencies()} on the module itself —
	 *    fully dynamic, re-evaluated on every call. The return value is cast to
	 *    an array, so implementations may return a single string as shorthand.
	 * 2. Any behavior attached to the module that also implements
	 *    {@see IModuleDependency} — fully dynamic, the behavior list is re-scanned
	 *    and each behavior's `getModuleDependencies()` is re-evaluated on every call,
	 *    because behaviors may be attached or detached between initialization phases.
	 *    Behavior return values are cast to array by the same rule.
	 *
	 * {@see IModuleDependency::getModuleDependencies()} may return dependencies in
	 * any of four forms, all of which are normalized to the `deps` map format:
	 *
	 * - **String return** — a bare `$depId` string: cast to an indexed array before
	 *   before processing; the dependency ID is the string value; `required`
	 *   defaults to `true`.
	 * - **Indexed array** — `[$depId]`: a non-empty string value with an integer key;
	 *   the dependency ID is the value; `required` defaults to `true`.
	 * - **Key-value** — `[$depId => $required]`: a non-empty string key is the
	 *   dependency ID; the value is cast to `bool` via
	 *   {@see TPropertyValue::ensureBoolean()} as the `required` flag.
	 * - **Verbose array** — `[['id' => $depId, 'required' => $bool]]`: `'id'` may
	 *   be any value, but entries where `'id'` is `null`, `''`, or not a string are
	 *   silently skipped; `'required'` is optional and defaults to `true`.
	 *
	 * Entries that resolve to a `null`, `false`, `0`, or empty-string dependency ID
	 * are silently skipped. This lets implementations return a fixed-shape array
	 * where a dep ID that is not set or known is represented as `null`.
	 *
	 * Both sources are re-evaluated on every call with no caching.
	 *
	 * Example return value:
	 * ```php
	 * [
	 *     'id'     => 'moduleA',             // the module's registration ID
	 *     'module' => $moduleA,              // the IModule instance
	 *     'deps'   => [                      // keyed by dependency module ID
	 *         'moduleB' => ['id' => 'moduleB', 'required' => true],
	 *         'moduleC' => ['id' => 'moduleC', 'required' => false],
	 *     ],
	 * ]
	 * ```
	 *
	 * Each returned dependency (`deps`) is an associative array with keys:
	 * - `'id'`       (string) — the dependency module ID
	 * - `'required'` (bool)   — `true` if the dependency is mandatory (default),
	 *                           `false` if advisory (influences order when present
	 *                           but raises no error when absent).
	 *
	 * @param IModule $module the module to inspect.
	 * @param bool $isPreInit `true` when collecting for the dyPreInit pass,
	 *   `false` when collecting for the init() pass (default). Passed verbatim to
	 *   {@see IModuleDependency::getModuleDependencies()}.
	 * @return array{id:string,module:IModule,deps:array<string,array{id:string,required:bool}>} record containing the module ID, the module instance, and its dependency map keyed by dep ID.
	 * @since 4.4.0
	 * @see IModuleDependency
	 */
	protected function collectModuleDependencies(IModule $module, bool $isPreInit = false): array
	{
		// Keyed by dep ID so that module-level declarations take priority over
		// behavior-level ones when the same dep ID appears in both sources.
		$ownId = $module->getID();
		$deps = [];

		// Source 1: IModuleDependency on the module itself.
		// Processed first so module-level declarations take priority over behaviors.
		if ($module instanceof IModuleDependency) {
			foreach ((array) ($module->getModuleDependencies($isPreInit) ?? []) as $key => $dep) {
				$required = true;
				if (is_string($key) && $key !== '') {
					$required = TPropertyValue::ensureBoolean($dep);
					$dep = $key;
				} elseif (is_string($dep) && $dep !== '') {
					$key = $dep;
				} elseif (is_array($dep) && is_string($dep['id'] ?? null) && $dep['id'] !== '') {
					$required = TPropertyValue::ensureBoolean($dep['required'] ?? true);
					$key = $dep = $dep['id'];
				} else {
					continue; // no valid dep ID resolved — silently skip
				}
				if ($dep === $ownId) {
					throw new TConfigurationException('application_module_dependency_self_reference', $ownId);
				}
				$deps[$key] = ['id' => $dep, 'required' => $required];
			}
		}

		// Source 2: IModuleDependency behaviors attached to the module.
		// Re-scanned every call — behaviors may be attached or detached between phases.
		// Only adds IDs not already contributed by the module (Source 1 takes priority).
		if ($module instanceof TComponent) {
			foreach ($module->getBehaviors(IModuleDependency::class) as $behavior) {
				foreach ((array) ($behavior->getModuleDependencies($isPreInit) ?? []) as $key => $dep) {
					$required = true;
					if (is_string($key) && $key !== '') {
						$required = TPropertyValue::ensureBoolean($dep);
						$dep = $key;
					} elseif (is_string($dep) && $dep !== '') {
						$key = $dep;
					} elseif (is_array($dep) && is_string($dep['id'] ?? null) && $dep['id'] !== '') {
						$required = TPropertyValue::ensureBoolean($dep['required'] ?? true);
						$key = $dep = $dep['id'];
					} else {
						continue; // no valid dep ID resolved — silently skip
					}
					if ($dep === $ownId) {
						throw new TConfigurationException('application_module_dependency_self_reference', $ownId);
					}
					if (!isset($deps[$key])) {
						$deps[$key] = ['id' => $dep, 'required' => $required];
					}
				}
			}
			$deps = $module->dyFilterDependencies($deps);
		}

		return ['id' => $ownId, 'module' => $module, 'deps' => $deps];
	}

	/**
	 * Filters the complete dependency map for all pending modules after every
	 * module's own sources have been collected. Called once per sort pass, so
	 * implementations can see — and cross-reference — the full set of edges at
	 * once. Subclasses may override this method to inject configuration-sourced
	 * edges or apply global ordering policies. Attached behaviors may implement
	 * {@see dyFilterModuleDependencies()} to participate without subclassing.
	 *
	 * This is the application-level counterpart to
	 * {@see \Prado\TModule::dyFilterDependencies()}, which lets each module's own
	 * behaviors filter its individual dep list before this method is reached.
	 *
	 * The `$moduleRecords` map has the following structure:
	 * ```php
	 * [
	 *     'moduleA' => [
	 *         'id'     => 'moduleA',         // the module's registration ID
	 *         'module' => $moduleA,          // the IModule instance
	 *         'deps'   => [                  // keyed by dependency module ID
	 *             'moduleB' => ['id' => 'moduleB', 'required' => true],
	 *             'moduleC' => ['id' => 'moduleC', 'required' => false],
	 *         ],
	 *     ],
	 *     'moduleB' => [
	 *         'id'     => 'moduleB',
	 *         'module' => $moduleB,
	 *         'deps'   => [],
	 *     ],
	 * ]
	 * ```
	 *
	 * @param array<string,array{id:string,module:IModule,deps:array<string,array{id:string,required:bool}>}> $moduleRecords map of module ID → record from {@see collectModuleDependencies()}
	 * @param array $pending the pending module entries being sorted, each with keys
	 *   `'id'` (string), `'module'` (IModule), and `'config'` (mixed)
	 * @param bool $isPreInit `true` when sorting the dyPreInit pass,
	 *   `false` for the init() pass
	 * @return array<string,array{id:string,module:IModule,deps:array<string,array{id:string,required:bool}>}> the filtered map, keyed by module ID
	 * @since 4.4.0
	 */
	protected function filterModuleDependencies(array $moduleRecords, array $pending, bool $isPreInit = false): array
	{
		return $this->dyFilterModuleDependencies($moduleRecords, $pending, $isPreInit);
	}

	/**
	 * Finds and returns a representative cycle path within a known set of
	 * cycle node IDs by following dependency edges via DFS until a node is
	 * revisited.
	 *
	 * Returns a closed-loop array where the first and last elements are the
	 * same module ID (e.g. `['a', 'b', 'c', 'a']`). Self-cycles — where a
	 * module lists itself as a dependency — produce a two-element path
	 * (e.g. `['a', 'a']`) and are detected the same way as multi-node cycles.
	 *
	 * @param array $cycleIds IDs known to participate in a cycle.
	 * @param array $depMap   dependency map: id → [dep_id, ...].
	 * @return array the cycle path with the entry node repeated at the end.
	 * @since 4.4.0
	 */
	private function findCyclePath(array $cycleIds, array $depMap): array
	{
		$inCycle = array_flip($cycleIds);
		$start = (string) array_key_first($inCycle);
		$visited = [];
		$path = [];

		$current = $start;
		while (!isset($visited[$current])) {
			$visited[$current] = count($path);
			$path[] = $current;
			$next = null;
			foreach ($depMap[$current] ?? [] as $depId) {
				if (isset($inCycle[$depId])) {
					$next = $depId;
					break;
				}
			}
			if ($next === null) {
				break;
			}
			$current = $next;
		}

		$cycleStart = $visited[$current] ?? 0;
		$loop = array_slice($path, $cycleStart);
		$loop[] = $current; // close the loop
		return $loop;
	}

	/**
	 * Applies a parsed application configuration in the following order:
	 * 1. Path aliases and usings
	 * 2. Application properties (skipped when `$withinService` is true)
	 * 3. Services
	 * 4. Parameters
	 * 5. Modules — four phases:
	 *    a. Instantiate all modules and apply configuration properties via {@see setSubProperty()}
	 *    b. Sort by dependency (pre-init pass), then raise {@see TModule::dyPreInit()} in order
	 *    c. Sort by dependency (init pass), then call {@see IModule::init()} in order
	 *    d. Raise {@see TModule::dyPostInit()} in the same order as step c — no re-sort
	 *    Steps b–c share a sort-result cache ({@see DEP_SORT_CACHE_KEY}) if they remain the same.
	 * 6. External configuration files (evaluated conditionally and applied recursively)
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

		// load services, provide for modules
		foreach ($config->getServices() as $serviceID => $serviceConfig) {
			$this->registerService($serviceID, ...$serviceConfig);
		}

		// Default Page Service is registered at construct,
		//		setPageServiceId changes the registered service within _services

		// load parameters
		$appParams = $this->getParameters();
		foreach ($config->getParameters() as $id => $parameter) {
			if (is_array($parameter)) {
				$component = Prado::createComponent($parameter[0]);
				foreach ($parameter[1] as $name => $value) {
					$component->setSubProperty($name, $value);
				}
				$component->dyInit($parameter[2]);
				$appParams->add($id, $component);
			} else {
				$appParams->add($id, $parameter);
			}
		}

		// Phase 1: Instantiate all modules and apply configuration properties via setXxx().
		$pending = [];
		foreach ($config->getModules() as $id => $moduleConfig) {
			if (!is_string($id)) {
				$id = '_module' . $this->getLazyModuleCount();
			}
			$this->setLazyModule($id, $moduleConfig);
			if ($entry = $this->internalLoadModule($id)) {
				$pending[] = ['id' => $id, 'module' => $entry[0], 'config' => $entry[1]];
			}
		}

		if ($pending !== []) {
			// Sort-result cache shared across both sort passes in this config run.
			// All dependency sources are re-evaluated on every pass.
			$depCache = [];

			// Phase 2: sort → dyPreInit in dependency order.
			foreach ($this->sortModulesByDependency($pending, $depCache, true) as $entry) {
				$entry['module']->dyPreInit($entry['config']);
			}

			// Phase 3: sort → init() in dependency order; save result for post-init.
			$initOrder = $this->sortModulesByDependency($pending, $depCache, false);
			foreach ($initOrder as $entry) {
				$entry['module']->init($entry['config']);
			}

			// Phase 4: dyPostInit in the same order as init() — no re-sort.
			foreach ($initOrder as $entry) {
				$entry['module']->dyPostInit($entry['config']);
			}
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
				$c = new $cn();
				$c->loadFromFile($path);
				$this->applyConfiguration($c, $withinService);
			}
		}
	}

	// =========================================================================
	// Life Cycle Methods and Events
	// =========================================================================

	/**
	 * Loads configuration and initializes the application. Reads the
	 * configuration file (or its cached form when current), applies it via
	 * {@see applyConfiguration()} so configured modules are created and
	 * initialized, raises {@see onConfiguration} for late-stage service
	 * registration, runs {@see bootstrapDefaultModules()} to enroll any
	 * lazily-created default-core modules in `$_modules`, runs
	 * {@see initService()} to resolve and start the requested service,
	 * and finally raises {@see onInitComplete}.
	 * @throws TConfigurationException when a module is redefined or
	 *   invalid, or when the requested service is undefined or invalid.
	 * @see onConfiguration
	 * @see onInitComplete
	 */
	protected function initApplication()
	{
		Prado::trace('Initializing application', TApplication::class);

		$configFile = $this->getConfigurationFile();
		if ($configFile !== null) {
			$cacheFile = $this->getCacheFile();
			if ($cacheFile === null || @filemtime($cacheFile) < filemtime($configFile)) {
				$cn = $this->getApplicationConfigurationClass();
				$config = new $cn();
				$config->loadFromFile($configFile);
				if ($cacheFile !== null) {
					file_put_contents($cacheFile, serialize($config), LOCK_EX);
				}
			} else {
				$config = unserialize(file_get_contents($cacheFile));
			}

			$this->applyConfiguration($config, false);
		}

		$this->bootstrapDefaultModules();
		$this->onConfiguration();
		$this->initService();
		$this->onInitComplete();
		$this->setStateFlag(static::STATE_INITIALIZED);

		Prado::trace('Initializing application complete', TApplication::class);
	}

	/**
	 * Resolves which service to run from the current request and starts it.
	 *
	 * Called by {@see initApplication()} after configuration has been applied and
	 * {@see onConfiguration} has fired.  Subclasses that do not use a web service
	 * (e.g. {@see Shell\TShellApplication}) can override this method to do nothing,
	 * leaving service startup entirely to their own {@see runService()} implementation.
	 *
	 * @since 4.3.3
	 */
	protected function initService(): void
	{
		if (($serviceID = $this->getRequest()->resolveRequest(array_keys($this->getRegisteredServices()))) === null) {
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
		if (!$this->hasRegisteredService($serviceID)) {
			throw new THttpException(500, 'application_service_unknown', $serviceID);
		}

		[$serviceClass, $initProperties, $configElement] = $this->getRegisteredService($serviceID);
		$service = Prado::createComponent($serviceClass);
		if (!($service instanceof IService && $service instanceof TComponent)) {
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
			$cn = $this->getApplicationConfigurationClass();
			$config = new $cn();
			if ($this->getConfigurationType() == static::CONFIG_TYPE_PHP) {
				$config->loadFromPhp($configElement, $this->getBasePath());
			} else {
				$config->loadFromXml($configElement, $this->getBasePath());
			}
			$this->applyConfiguration($config, true);
		}

		$service->init($configElement);
	}

	/**
	 * Raises OnError event.
	 * This method is invoked when an exception is raised during the lifecycles
	 * of the application.
	 * @param mixed $param event parameter
	 */
	public function onError($param)
	{
		Prado::log($param->getMessage(), TLogger::ERROR, TApplication::class);
		$this->raiseEvent('OnError', $this, $param);
		$this->getErrorHandler()->handleError($this, $param);
	}

	/**
	 * Raises onConfiguration event.
	 * Configuration is fully applied and modules are loaded, but the request
	 * has not yet been resolved and no service has been started. Use this event
	 * to register additional services before the request routing.
	 * @since 4.3.3
	 */
	public function onConfiguration()
	{
		$this->raiseEvent('onConfiguration', $this, null);
	}

	/**
	 * Raises onInitComplete event.
	 * At the time when this method is invoked, application modules are loaded,
	 * the request has been resolved, and the corresponding service has been loaded
	 * and initialized. The application is about to begin the request lifecycle.
	 * {@see Shell\TShellApplication} attaches CLI argument processing to
	 * this event, making it the effective entry point for shell command dispatch.
	 * @since 4.2.0
	 */
	public function onInitComplete()
	{
		$this->raiseEvent('onInitComplete', $this, null);
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
	 * Runs the currently active service by calling its `run()` method.
	 * Called as the `runService` lifecycle step in {@see run()}.
	 */
	public function runService()
	{
		if ($service = $this->getService()) {
			$service->run();
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
	 * Flushes any remaining buffered output, raises the event, then saves global state.
	 * This method is invoked when the application completes the processing of the request.
	 */
	public function onEndRequest()
	{
		$this->flushOutput(false); // flush all remaining content in the buffer
		$this->raiseEvent('OnEndRequest', $this, null);
		$this->saveGlobals();  // save global state
	}
}
