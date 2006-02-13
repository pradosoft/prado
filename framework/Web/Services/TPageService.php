<?php
/**
 * TPageService class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System.Web.Services
 */

/**
 * Include classes to be used by page service
 */
Prado::using('System.Web.UI.TPage');
Prado::using('System.Web.UI.TTemplateManager');
Prado::using('System.Web.UI.TThemeManager');
Prado::using('System.Web.UI.TAssetManager');

/**
 * TPageService class.
 *
 * TPageService implements the service for serving user page requests.
 *
 * Pages that are available to client users are stored under a directory specified by
 * {@link setBasePath BasePath}. The directory may contain subdirectories.
 * Pages serving for a similar goal are usually placed under the same directory.
 * A directory may contain a configuration file <b>config.xml</b> whose content
 * is similar to that of application configuration file.
 *
 * A page is requested via page path, which is a dot-connected directory names
 * appended by the page name. Assume '<BasePath>/Users/Admin' is the directory
 * containing the page 'Update'. Then the page can be requested via 'Users.Admin.Update'.
 * By default, the {@link setBasePath BasePath} of the page service is the "pages"
 * directory under the application base path. You may change this default
 * by setting {@link setBasePath BasePath} with a different path you prefer.
 *
 * Page name refers to the file name (without extension) of the page template.
 * In order to differentiate from the common control template files, the extension
 * name of the page template files must be '.page'. If there is a PHP file with
 * the same page name under the same directory as the template file, that file
 * will be considered as the page class file and the file name is the page class name.
 * If such a file is not found, the page class is assumed as {@link TPage}.
 *
 * Modules can be configured and loaded in page directory configurations.
 * Configuration of a module in a subdirectory will overwrite its parent
 * directory's configuration, if both configurations refer to the same module.
 *
 * By default, TPageService will automatically load three modules:
 * - {@link TTemplateManager} : manages page and control templates
 * - {@link TThemeManager} : manages themes used in a Prado application
 * - {@link TAssetManager} : manages assets used in a Prado application.
 *
 * In page directory configurations, static authorization rules can also be specified,
 * which governs who and which roles can access particular pages.
 * Refer to {@link TAuthorizationRule} for more details about authorization rules.
 * Page authorization rules can be configured within an <authorization> tag in
 * each page directory configuration as follows,
 * <authorization>
 *   <deny pages="Update" users="?" />
 *   <allow pages="Admin" roles="administrator" />
 *   <deny pages="Admin" users="*" />
 * </authorization>
 * where the 'pages' attribute may be filled with a sequence of comma-separated
 * page IDs. If 'pages' attribute does not appear in a rule, the rule will be
 * applied to all pages in this directory and all subdirectories (recursively).
 * Application of authorization rules are in a bottom-up fashion, starting from
 * the directory containing the requested page up to all parent directories.
 * The first matching rule will be used. The last rule always allows all users
 * accessing to any resources.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Services
 * @since 3.0
 */
class TPageService extends TService
{
	/**
	 * Configuration file name
	 */
	const CONFIG_FILE='config.xml';
	/**
	 * Default base path
	 */
	const DEFAULT_BASEPATH='pages';
	/**
	 * Prefix of ID used for storing parsed configuration in cache
	 */
	const CONFIG_CACHE_PREFIX='prado:pageservice:';
	/**
	 * Page template file extension
	 */
	const PAGE_FILE_EXT='.page';
	/**
	 * @var string id of this service (page)
	 */
	private $_id='page';
	/**
	 * @var string root path of pages
	 */
	private $_basePath=null;
	/**
	 * @var string default page
	 */
	private $_defaultPage='Home';
	/**
	 * @var string requested page (path)
	 */
	private $_pagePath=null;
	/**
	 * @var TPage the requested page
	 */
	private $_page=null;
	/**
	 * @var array list of initial page property values
	 */
	private $_properties;
	/**
	 * @var boolean whether service is initialized
	 */
	private $_initialized=false;
	/**
	 * @var TAssetManager asset manager
	 */
	private $_assetManager=null;
	/**
	 * @var TThemeManager theme manager
	 */
	private $_themeManager=null;
	/**
	 * @var TTemplateManager template manager
	 */
	private $_templateManager=null;

	/**
	 * Initializes the service.
	 * This method is required by IService interface and is invoked by application.
	 * @param TXmlElement service configuration
	 */
	public function init($config)
	{
		Prado::trace("Initializing TPageService",'System.Web.Services.TPageService');

		$this->getApplication()->setPageService($this);

		$pageConfig=$this->loadPageConfig($this->getRequestedPagePath(),$config);

		$this->initPageContext($pageConfig);

		$this->_initialized=true;
	}

	/**
	 * Initializes page context.
	 * Page context includes path alias settings, namespace usages,
	 * parameter initialization, module loadings, page initial properties
	 * and authorization rules.
	 * @param TPageConfiguration
	 */
	protected function initPageContext($pageConfig)
	{
		$application=$this->getApplication();

		// set path aliases and using namespaces
		foreach($pageConfig->getAliases() as $alias=>$path)
			Prado::setPathOfAlias($alias,$path);
		foreach($pageConfig->getUsings() as $using)
			Prado::using($using);

		// initial page properties (to be set when page runs)
		$this->_properties=$pageConfig->getProperties();

		// load parameters
		$parameters=$application->getParameters();
		foreach($pageConfig->getParameters() as $id=>$parameter)
		{
			if(is_string($parameter))
				$parameters->add($id,$parameter);
			else
			{
				$component=Prado::createComponent($parameter[0]);
				foreach($parameter[1] as $name=>$value)
					$component->setSubProperty($name,$value);
				$parameters->add($id,$component);
			}
		}

		// load modules specified in page directory config
		foreach($pageConfig->getModules() as $id=>$moduleConfig)
		{
			Prado::trace("Loading module $id ({$moduleConfig[0]})",'System.Web.Services.TPageService');
			$module=Prado::createComponent($moduleConfig[0]);
			if(is_string($id))
				$application->setModule($id,$module);
			foreach($moduleConfig[1] as $name=>$value)
				$module->setSubProperty($name,$value);
			$module->init($moduleConfig[2]);
		}

		$application->getAuthorizationRules()->mergeWith($pageConfig->getRules());
	}

	/**
	 * Determines the requested page path.
	 * @return string page path requested
	 */
	protected function determineRequestedPagePath()
	{
		$pagePath=$this->getRequest()->getServiceParameter();
		if(empty($pagePath))
			$pagePath=$this->getDefaultPage();
		return $pagePath;
	}

	/**
	 * Collects configuration for a page.
	 * @param string page path in the format of Path.To.Page
	 * @param TXmlElement additional configuration
	 * @return TPageConfiguration
	 */
	protected function loadPageConfig($pagePath,$config=null)
	{
		$application=$this->getApplication();
		if(($cache=$application->getCache())===null)
		{
			$pageConfig=new TPageConfiguration;
			if($config!==null)
				$pageConfig->loadXmlElement($config,$application->getBasePath(),null);
			$pageConfig->loadConfigurationFiles($pagePath,$this->getBasePath());
		}
		else
		{
			$configCached=true;
			$currentTimestamp=array();
			$arr=$cache->get(self::CONFIG_CACHE_PREFIX.$pagePath);
			if(is_array($arr))
			{
				list($pageConfig,$timestamps)=$arr;
				if($application->getMode()!==TApplication::STATE_PERFORMANCE)
				{
					foreach($timestamps as $fileName=>$timestamp)
					{
						if($fileName===0) // application config file
						{
							$appConfigFile=$application->getConfigurationFile();
							$currentTimestamp[0]=$appConfigFile===null?0:@filemtime($appConfigFile);
							if($currentTimestamp[0]>$timestamp || ($timestamp>0 && !$currentTimestamp[0]))
								$configCached=false;
						}
						else
						{
							$currentTimestamp[$fileName]=@filemtime($fileName);
							if($currentTimestamp[$fileName]>$timestamp || ($timestamp>0 && !$currentTimestamp[$fileName]))
								$configCached=false;
						}
					}
				}
			}
			else
			{
				$configCached=false;
				$paths=explode('.',$pagePath);
				array_pop($paths);
				$configPath=$this->getBasePath();
				foreach($paths as $path)
				{
					$configFile=$configPath.'/'.self::CONFIG_FILE;
					$currentTimestamp[$configFile]=@filemtime($configFile);
					$configPath.='/'.$path;
				}
				$appConfigFile=$application->getConfigurationFile();
				$currentTimestamp[0]=$appConfigFile===null?0:@filemtime($appConfigFile);
			}
			if(!$configCached)
			{
				$pageConfig=new TPageConfiguration;
				if($config!==null)
					$pageConfig->loadXmlElement($config,$application->getBasePath(),null);
				$pageConfig->loadConfigurationFiles($pagePath,$this->getBasePath());
				$cache->set(self::CONFIG_CACHE_PREFIX.$pagePath,array($pageConfig,$currentTimestamp));
			}
		}
		return $pageConfig;
	}

	/**
	 * @return string id of this module
	 */
	public function getID()
	{
		return $this->_id;
	}

	/**
	 * @param string id of this module
	 */
	public function setID($value)
	{
		$this->_id=$value;
	}

	/**
	 * @return TTemplateManager template manager
	 */
	public function getTemplateManager()
	{
		if(!$this->_templateManager)
		{
			$this->_templateManager=new TTemplateManager;
			$this->_templateManager->init(null);
		}
		return $this->_templateManager;
	}

	/**
	 * @param TTemplateManager template manager
	 */
	public function setTemplateManager(TTemplateManager $value)
	{
		$this->_templateManager=$value;
	}

	/**
	 * @return TAssetManager asset manager
	 */
	public function getAssetManager()
	{
		if(!$this->_assetManager)
		{
			$this->_assetManager=new TAssetManager;
			$this->_assetManager->init(null);
		}
		return $this->_assetManager;
	}

	/**
	 * @param TAssetManager asset manager
	 */
	public function setAssetManager(TAssetManager $value)
	{
		$this->_assetManager=$value;
	}

	/**
	 * @return TThemeManager theme manager
	 */
	public function getThemeManager()
	{
		if(!$this->_themeManager)
		{
			$this->_themeManager=new TThemeManager;
			$this->_themeManager->init(null);
		}
		return $this->_themeManager;
	}

	/**
	 * @param TThemeManager theme manager
	 */
	public function setThemeManager(TThemeManager $value)
	{
		$this->_themeManager=$value;
	}

	/**
	 * @return string the requested page path
	 */
	public function getRequestedPagePath()
	{
		if($this->_pagePath===null)
		{
			$this->_pagePath=$this->determineRequestedPagePath();
			if(empty($this->_pagePath))
				throw new THttpException(404,'pageservice_page_required');
		}
		return $this->_pagePath;
	}

	/**
	 * @return TPage the requested page
	 */
	public function getRequestedPage()
	{
		return $this->_page;
	}

	/**
	 * @return string default page path to be served if no explicit page is request. Defaults to 'Home'.
	 */
	public function getDefaultPage()
	{
		return $this->_defaultPage;
	}

	/**
	 * @param string default page path to be served if no explicit page is request
	 * @throws TInvalidOperationException if the page service is initialized
	 */
	public function setDefaultPage($value)
	{
		if($this->_initialized)
			throw new TInvalidOperationException('pageservice_defaultpage_unchangeable');
		else
			$this->_defaultPage=$value;
	}

	/**
	 * @return string root directory (in namespace form) storing pages. Defaults to 'pages' directory under application base path.
	 */
	public function getBasePath()
	{
		if($this->_basePath===null)
		{
			$basePath=$this->getApplication()->getBasePath().'/'.self::DEFAULT_BASEPATH;
			if(($this->_basePath=realpath($basePath))===false || !is_dir($this->_basePath))
				throw new TConfigurationException('pageservice_basepath_invalid',$basePath);
		}
		return $this->_basePath;
	}

	/**
	 * @param string root directory (in namespace form) storing pages
	 * @throws TInvalidOperationException if the service is initialized already or basepath is invalid
	 */
	public function setBasePath($value)
	{
		if($this->_initialized)
			throw new TInvalidOperationException('pageservice_basepath_unchangeable');
		else if(($this->_basePath=realpath(Prado::getPathOfNamespace($value)))===false || !is_dir($this->_basePath))
			throw new TConfigurationException('pageservice_basepath_invalid',$value);
	}

	/**
	 * Runs the service.
	 * This will create the requested page, initializes it with the property values
	 * specified in the configuration, and executes the page.
	 */
	public function run()
	{
		Prado::trace("Running page service",'System.Web.Services.TPageService');
		$page=null;
		$path=$this->getBasePath().'/'.strtr($this->getRequestedPagePath(),'.','/');
		if(is_file($path.self::PAGE_FILE_EXT))
		{
			if(is_file($path.Prado::CLASS_FILE_EXT))
			{
				$className=basename($path);
				if(!class_exists($className,false))
					include_once($path.Prado::CLASS_FILE_EXT);
				if(!class_exists($className,false))
					throw new TConfigurationException('pageservice_pageclass_unknown',$className);
			}
			else
				$className='TPage';

			$this->_page=new $className();

			// initialize page properties with those set in configurations
			foreach($this->_properties as $name=>$value)
				$this->_page->setSubProperty($name,$value);

			// set page template
			$this->_page->setTemplate($this->getTemplateManager()->getTemplateByFileName($path.self::PAGE_FILE_EXT));
		}
		else
			throw new THttpException(404,'pageservice_page_unknown',$this->getRequestedPagePath());

		$this->_page->run($this->getResponse()->createHtmlWriter());
	}

	/**
	 * Constructs a URL with specified page path and GET parameters.
	 * @param string page path
	 * @param array list of GET parameters, null if no GET parameters required
	 * @param boolean whether to encode the ampersand in URL, defaults to false.
	 * @return string URL for the page and GET parameters
	 */
	public function constructUrl($pagePath,$getParams=null,$encodeAmpersand=false)
	{
		return $this->getRequest()->constructUrl($this->_id,$pagePath,$getParams,$encodeAmpersand);
	}

	/**
	 * Publishes a private asset and returns its URL.
	 * This method will publish a private asset (file or directory)
	 * and returns the URL to the asset. Note, if the asset refers to
	 * a directory, all contents under that directory will be published.
	 * @param string path of the asset that is either absolute or relative to the directory containing the current running script.
	 * @return string URL to the asset path.
	 */
	public function getAsset($path)
	{
		return $this->getAssetManager()->publishFilePath($path);
	}
}


/**
 * TPageConfiguration class
 *
 * TPageConfiguration represents the configuration for a page.
 * The page is specified by a dot-connected path.
 * Configurations along this path are merged together to be provided for the page.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Services
 * @since 3.0
 */
class TPageConfiguration extends TComponent
{
	/**
	 * @var array list of page initial property values
	 */
	private $_properties=array();
	/**
	 * @var array list of namespaces to be used
	 */
	private $_usings=array();
	/**
	 * @var array list of path aliases
	 */
	private $_aliases=array();
	/**
	 * @var array list of module configurations
	 */
	private $_modules=array();
	/**
	 * @var array list of parameters
	 */
	private $_parameters=array();
	/**
	 * @var TAuthorizationRuleCollection list of authorization rules
	 */
	private $_rules=array();

	/**
	 * Returns list of page initial property values.
	 * Each array element represents a single property with the key
	 * being the property name and the value the initial property value.
	 * @return array list of page initial property values
	 */
	public function getProperties()
	{
		return $this->_properties;
	}

	/**
	 * Returns list of path alias definitions.
	 * The definitions are aggregated (top-down) from configuration files along the path
	 * to the specified page. Each array element represents a single alias definition,
	 * with the key being the alias name and the value the absolute path.
	 * @return array list of path alias definitions
	 */
	public function getAliases()
	{
		return $this->_aliases;
	}

	/**
	 * Returns list of namespaces to be used.
	 * The namespaces are aggregated (top-down) from configuration files along the path
	 * to the specified page. Each array element represents a single namespace usage,
	 * with the value being the namespace to be used.
	 * @return array list of namespaces to be used
	 */
	public function getUsings()
	{
		return $this->_usings;
	}

	/**
	 * Returns list of module configurations.
	 * The module configurations are aggregated (top-down) from configuration files
	 * along the path to the specified page. Each array element represents
	 * a single module configuration, with the key being the module ID and
	 * the value the module configuration. Each module configuration is
	 * stored in terms of an array with the following content
	 * ([0]=>module type, [1]=>module properties, [2]=>complete module configuration)
	 * The module properties are an array of property values indexed by property names.
	 * The complete module configuration is a TXmlElement object representing
	 * the raw module configuration which may contain contents enclosed within
	 * module tags.
	 * @return array list of module configurations to be used
	 */
	public function getModules()
	{
		return $this->_modules;
	}

	/**
	 * Returns list of parameter definitions.
	 * The parameter definitions are aggregated (top-down) from configuration files
	 * along the path to the specified page. Each array element represents
	 * a single parameter definition, with the key being the parameter ID and
	 * the value the parameter definition. A parameter definition can be either
	 * a string representing a string-typed parameter, or an array.
	 * The latter defines a component-typed parameter whose format is as follows,
	 * ([0]=>component type, [1]=>component properties)
	 * The component properties are an array of property values indexed by property names.
	 * @return array list of parameter definitions to be used
	 */
	public function getParameters()
	{
		return $this->_parameters;
	}

	/**
	 * Returns list of authorization rules.
	 * The authorization rules are aggregated (bottom-up) from configuration files
	 * along the path to the specified page.
	 * @return TAuthorizationRuleCollection collection of authorization rules
	 */
	public function getRules()
	{
		return $this->_rules;
	}

	/**
	 * Loads configuration for a page specified in a path format.
	 * @param string path to the page (dot-connected format)
	 * @param string root path for pages
	 */
	public function loadConfigurationFiles($pagePath,$basePath)
	{
		$paths=explode('.',$pagePath);
		$page=array_pop($paths);
		$path=$basePath;
		foreach($paths as $p)
		{
			$this->loadFromFile($path.'/'.TPageService::CONFIG_FILE,null);
			$path.='/'.$p;
		}
		$this->loadFromFile($path.'/'.TPageService::CONFIG_FILE,$page);
		$this->_rules=new TAuthorizationRuleCollection($this->_rules);
	}

	/**
	 * Loads a specific config file.
	 * @param string config file name
	 * @param string page name, null if page is not required
	 */
	private function loadFromFile($fname,$page)
	{
		Prado::trace("Loading $page with file $fname",'System.Web.Services.TPageService');
		if(empty($fname) || !is_file($fname))
			return;
		$dom=new TXmlDocument;
		if($dom->loadFromFile($fname))
			$this->loadXmlElement($dom,dirname($fname),$page);
		else
			throw new TConfigurationException('pageserviceconf_file_invalid',$fname);
	}

	/**
	 * Loads a specific configuration xml element.
	 * @param TXmlElement config xml element
	 * @param string base path corresponding to this xml element
	 * @param string page name, null if page is not required
	 */
	public function loadXmlElement($dom,$configPath,$page)
	{
		// paths
		if(($pathsNode=$dom->getElementByTagName('paths'))!==null)
		{
			foreach($pathsNode->getElementsByTagName('alias') as $aliasNode)
			{
				if(($id=$aliasNode->getAttribute('id'))!==null && ($p=$aliasNode->getAttribute('path'))!==null)
				{
					$p=str_replace('\\','/',$p);
					$path=realpath(preg_match('/^\\/|.:\\//',$p)?$p:$configPath.'/'.$p);
					if($path===false || !is_dir($path))
						throw new TConfigurationException('pageserviceconf_aliaspath_invalid',$id,$p,$configPath);
					if(isset($this->_aliases[$id]))
						throw new TConfigurationException('pageserviceconf_alias_redefined',$id,$configPath);
					$this->_aliases[$id]=$path;
				}
				else
					throw new TConfigurationException('pageserviceconf_alias_invalid',$configPath);
			}
			foreach($pathsNode->getElementsByTagName('using') as $usingNode)
			{
				if(($namespace=$usingNode->getAttribute('namespace'))!==null)
					$this->_usings[]=$namespace;
				else
					throw new TConfigurationException('pageserviceconf_using_invalid',$configPath);
			}
		}

		// modules
		if(($modulesNode=$dom->getElementByTagName('modules'))!==null)
		{
			foreach($modulesNode->getElementsByTagName('module') as $node)
			{
				$properties=$node->getAttributes();
				$type=$properties->remove('class');
				$id=$properties->itemAt('id');
				if($type===null)
					throw new TConfigurationException('pageserviceconf_moduletype_required',$id,$configPath);
				$node->setParent(null);
				if($id===null)
					$this->_modules[]=array($type,$properties->toArray(),$node);
				else
					$this->_modules[$id]=array($type,$properties->toArray(),$node);
			}
		}

		// parameters
		if(($parametersNode=$dom->getElementByTagName('parameters'))!==null)
		{
			foreach($parametersNode->getElementsByTagName('parameter') as $node)
			{
				$properties=$node->getAttributes();
				if(($id=$properties->remove('id'))===null)
					throw new TConfigurationException('pageserviceconf_parameter_invalid',$configPath);
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

		// authorization
		if(($authorizationNode=$dom->getElementByTagName('authorization'))!==null)
		{
			$rules=array();
			foreach($authorizationNode->getElements() as $node)
			{
				$pages=$node->getAttribute('pages');
				$ruleApplies=false;
				if(empty($pages))
					$ruleApplies=true;
				else if($page!==null)
				{
					$ps=explode(',',$pages);
					foreach($ps as $p)
					{
						if($page===trim($p))
						{
							$ruleApplies=true;
							break;
						}
					}
				}
				if($ruleApplies)
					$rules[]=new TAuthorizationRule($node->getTagName(),$node->getAttribute('users'),$node->getAttribute('roles'),$node->getAttribute('verb'));
			}
			$this->_rules=array_merge($rules,$this->_rules);
		}

		// pages
		if(($pagesNode=$dom->getElementByTagName('pages'))!==null)
		{
			$this->_properties=array_merge($this->_properties,$pagesNode->getAttributes()->toArray());
			if($page!==null)   // at the page folder
			{
				foreach($pagesNode->getElementsByTagName('page') as $node)
				{
					$properties=$node->getAttributes();
					if(($id=$properties->itemAt('id'))===null)
						throw new TConfigurationException('pageserviceconf_page_invalid',$configPath);
					if($id===$page)
						$this->_properties=array_merge($this->_properties,$properties->toArray());
				}
			}
		}
	}
}

?>