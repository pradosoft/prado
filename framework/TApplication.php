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
 * TApplication adopts a modular structure. A TApplication instance is a composition
 * of multiple modules. A module is an instance of class implementing
 * {@link IModule} interface. Each module accomplishes certain functionalities
 * that are shared by all Prado components in an application.
 * There are default modules and user-defined modules. The latter offers extreme
 * flexibility of extending TApplication in a plug-and-play fashion.
 * Modules cooperate with each other to serve a user request by following
 * a sequence of lifecycles predefined in TApplication.
 *
 * TApplication dispatches each user request to a particular service which
 * finishes the actual work for the request with the aid from the application
 * modules.
 *
 * TApplication uses a configuration file to specify the settings of
 * the application, the modules, the services, the parameters, and so on.
 *
 * Examples:
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
	 * Default service ID
	 */
	const DEFAULT_SERVICE='page';
	/**
	 * @var array list of events that define application lifecycles
	 */
	private static $_steps=array(
		'BeginRequest',
		'Authentication',
		'PostAuthentication',
		'Authorization',
		'PostAuthorization',
		'LoadState',
		'PostLoadState',
		'PreRunService',
		'RunService',
		'PostRunService',
		'SaveState',
		'PostSaveState',
		'EndRequest'
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
	private $_service;
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
	 * @var IUser user instance, could be null
	 */
	private $_user=null;
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
	 * Initializes the application singleton. This method ensures that users can
	 * only create one application instance.
	 * @param string configuration file path (absolute or relative to current running script)
	 * @param string cache file path. This is optional. If it is present, it will
	 *               be used to store and load parsed application configuration (to improve performance).
	 */
	public function __construct($configFile,$cacheFile=null)
	{
		parent::__construct();
		Prado::setApplication($this);
		if(($this->_configFile=realpath($configFile))===false || !is_file($this->_configFile))
			throw new TIOException('application_configfile_inexistent',$configFile);
		$this->_cacheFile=$cacheFile;
		// generates unique ID by hashing the configuration file path
		$this->_uniqueID=md5($this->_configFile);
		$this->_errorHandler=new TErrorHandler;
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
			$this->initApplication($this->_configFile,$this->_cacheFile);
			$n=count(self::$_steps);
			$this->_step=0;
			$this->_requestCompleted=false;
			while($this->_step<$n)
			{
				$method='on'.self::$_steps[$this->_step];
				$this->$method($this);
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
		$this->_mode=TPropertyValue::ensureEnum($value,array('Off','Debug','Normal','Performance'));
	}

	/**
	 * @return string configuration file path
	 */
	public function getConfigurationFile()
	{
		return $this->_configFile;
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
	 * @return THttpRequest the request module
	 */
	public function getRequest()
	{
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
	 * @return TErrorHandler the error hanlder module
	 */
	public function getErrorHandler()
	{
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
	 * @return THttpSession the session module, null if session module is not installed
	 */
	public function getSession()
	{
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
	protected function initApplication($configFile,$cacheFile)
	{
		if($cacheFile===null || @filemtime($cacheFile)<filemtime($configFile))
		{
			$config=new TApplicationConfiguration;
			$config->loadFromFile($configFile);
			if($cacheFile!==null)
			{
				if(($fp=fopen($cacheFile,'wb'))!==false)
				{
					fputs($fp,Prado::serialize($config));
					fclose($fp);
				}
				else
					syslog(LOG_WARNING,'Prado application config cache file "'.$cacheFile.'" cannot be created.');
			}
		}
		else
		{
			$config=Prado::unserialize(file_get_contents($cacheFile));
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
			if(is_string($parameter))
				$this->_parameters->add($id,$parameter);
			else
			{
				$component=Prado::createComponent($parameter[0]);
				foreach($parameter[1] as $name=>$value)
					$component->setSubProperty($name,$value);
				$this->_parameters->add($id,$component);
			}
		}

		// load and init modules specified in app config
		$this->_modules=array();
		foreach($config->getModules() as $id=>$moduleConfig)
		{
			$module=Prado::createComponent($moduleConfig[0]);
			$this->_modules[$id]=$module;
			foreach($moduleConfig[1] as $name=>$value)
				$module->setSubProperty($name,$value);
			$module->init($this,$moduleConfig[2]);
		}

		if(($serviceID=$this->getRequest()->getServiceID())===null)
			$serviceID=self::DEFAULT_SERVICE;
		if(($serviceConfig=$config->getService($serviceID))!==null)
		{
			$service=Prado::createComponent($serviceConfig[0]);
			if(!($service instanceof IService))
				throw new TConfigurationException('application_service_invalid',$serviceID);
			$this->_service=$service;
			foreach($serviceConfig[1] as $name=>$value)
				$service->setSubProperty($name,$value);
			$service->init($this,$serviceConfig[2]);
			$this->attachEventHandler('RunService',array($service,'run'));
		}
		else
			throw new TConfigurationException('application_service_unknown',$serviceID);
	}

	/**
	 * Raises Error event.
	 * This method is invoked when an exception is raised during the lifecycles
	 * of the application.
	 * @param mixed event parameter
	 */
	public function onError($param)
	{
		if($this->hasEventHandler('Error'))
			$this->raiseEvent('Error',$this,$param);
		else
			$this->_errorHandler->handleError($this,$param);
	}

	/**
	 * Raises BeginRequest event.
	 * At the time when this method is invoked, application modules are loaded
	 * and initialized, user request is resolved and the corresponding service
	 * is loaded and initialized. The application is about to start processing
	 * the user request.
	 * @param mixed event parameter
	 */
	public function onBeginRequest($param)
	{
		$this->raiseEvent('BeginRequest',$this,$param);
	}

	/**
	 * Raises Authentication event.
	 * This method is invoked when the user request needs to be authenticated.
	 * @param mixed event parameter
	 */
	public function onAuthentication($param)
	{
		$this->raiseEvent('Authentication',$this,$param);
	}

	/**
	 * Raises PostAuthentication event.
	 * This method is invoked right after the user request is authenticated.
	 * @param mixed event parameter
	 */
	public function onPostAuthentication($param)
	{
		$this->raiseEvent('PostAuthentication',$this,$param);
	}

	/**
	 * Raises Authorization event.
	 * This method is invoked when the user request needs to be authorized.
	 * @param mixed event parameter
	 */
	public function onAuthorization($param)
	{
		$this->raiseEvent('Authorization',$this,$param);
	}

	/**
	 * Raises PostAuthorization event.
	 * This method is invoked right after the user request is authorized.
	 * @param mixed event parameter
	 */
	public function onPostAuthorization($param)
	{
		$this->raiseEvent('PostAuthorization',$this,$param);
	}

	/**
	 * Raises LoadState event.
	 * This method is invoked when the application needs to load state (probably stored in session).
	 * @param mixed event parameter
	 */
	public function onLoadState($param)
	{
		$this->raiseEvent('LoadState',$this,$param);
	}

	/**
	 * Raises PostLoadState event.
	 * This method is invoked right after the application state has been loaded.
	 * @param mixed event parameter
	 */
	public function onPostLoadState($param)
	{
		$this->raiseEvent('PostLoadState',$this,$param);
	}

	/**
	 * Raises PreRunService event.
	 * This method is invoked right before the service is to be run.
	 * @param mixed event parameter
	 */
	public function onPreRunService($param)
	{
		$this->raiseEvent('PreRunService',$this,$param);
	}

	/**
	 * Raises RunService event.
	 * This method is invoked when the service runs.
	 * @param mixed event parameter
	 */
	public function onRunService($param)
	{
		$this->raiseEvent('RunService',$this,$param);
	}

	/**
	 * Raises PostRunService event.
	 * This method is invoked right after the servie is run.
	 * @param mixed event parameter
	 */
	public function onPostRunService($param)
	{
		$this->raiseEvent('PostRunService',$this,$param);
	}

	/**
	 * Raises SaveState event.
	 * This method is invoked when the application needs to save state (probably stored in session).
	 * @param mixed event parameter
	 */
	public function onSaveState($param)
	{
		$this->raiseEvent('SaveState',$this,$param);
	}

	/**
	 * Raises PostSaveState event.
	 * This method is invoked right after the application state has been saved.
	 * @param mixed event parameter
	 */
	public function onPostSaveState($param)
	{
		$this->raiseEvent('PostSaveState',$this,$param);
	}

	/**
	 * Raises EndRequest event.
	 * This method is invoked when the application completes the processing of the request.
	 * @param mixed event parameter
	 */
	public function onEndRequest($param)
	{
		$this->raiseEvent('EndRequest',$this,$param);
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
	private $_modules=array(
			'request'=>array('THttpRequest',array(),null),
			'response'=>array('THttpResponse',array(),null)
		);
	/**
	 * @var array list of service configurations
	 */
	private $_services=array(
			'page'=>array('TPageService',array(),null)
		);
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
					$this->_aliases[$id]=$p;
				}
				else
					throw new TConfigurationException('appconfig_aliasid_required');
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
				if(($type=$properties->remove('type'))===null && isset($this->_modules[$id]) && $this->_modules[$id][2]===null)
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
				if(($type=$properties->remove('type'))===null && isset($this->_services[$id]) && $this->_services[$id][2]===null)
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
				if(($type=$properties->remove('type'))===null)
					$this->_parameters[$id]=$node->getValue();
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
	public function getService($id)
	{
		return isset($this->_services[$id])?$this->_services[$id]:null;
	}

	/**
	 * @return array list of parameters
	 */
	public function getParameters()
	{
		return $this->_parameters;
	}
}

?>