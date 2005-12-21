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
Prado::using('System.Web.UI.TPageStatePersister');

/**
 * TPageService class.
 *
 * TPageService implements the service for serving user page requests.
 *
 * Pages that are available to client users are stored under a directory specified by
 * {@link setBasePath BasePath}. The directory may contain subdirectories.
 * A directory may be used to group together the pages serving for the similar goal.
 * Each directory must contain a configuration file <b>config.xml</b> that is similar to the application
 * configuration file. The only difference is that the page directory configuration
 * contains a mapping between page IDs and page types. The page IDs are visible
 * by client users while page types are used on the server side.
 * A page is requested via page path, which is a dot-connected directory names
 * appended by the page ID. Assume '<BasePath>/Users/Admin' is the directory
 * containing the page 'Update' whose type is UpdateUserPage. Then the page can
 * be requested via 'Users.Admin.Update'.
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
class TPageService extends TComponent implements IService
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
	 * @var string id of this service (page)
	 */
	private $_id;
	/**
	 * @var string root path of pages
	 */
	private $_basePath=null;
	/**
	 * @var string default page
	 */
	private $_defaultPage=null;
	/**
	 * @var string requested page (path)
	 */
	private $_pagePath;
	/**
	 * @var string requested page type
	 */
	private $_pageType;
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
	 * @var TApplication application
	 */
	private $_application;
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
	 * @var IPageStatePersister page state persister
	 */
	private $_pageStatePersister=null;

	/**
	 * Initializes the service.
	 * This method is required by IService interface and is invoked by application.
	 * @param TApplication application
	 * @param TXmlElement service configuration
	 */
	public function init($application,$config)
	{
		$this->_application=$application;

		if($this->_basePath===null)
		{
			$basePath=dirname($application->getConfigurationPath()).'/'.self::DEFAULT_BASEPATH;
			if(($this->_basePath=realpath($basePath))===false || !is_dir($this->_basePath))
				throw new TConfigurationException('pageservice_basepath_invalid',$basePath);
		}

		$this->_pagePath=$application->getRequest()->getServiceParameter();
		if(empty($this->_pagePath))
			$this->_pagePath=$this->_defaultPage;
		if(empty($this->_pagePath))
			throw new THttpException(400,'pageservice_page_required');

		if(($cache=$application->getCache())===null)
		{
			$pageConfig=new TPageConfiguration;
			$pageConfig->loadXmlElement($config,dirname($application->getConfigurationFile()),null);
			$pageConfig->loadConfigurationFiles($this->_pagePath,$this->_basePath);
		}
		else
		{
			$configCached=true;
			$arr=$cache->get(self::CONFIG_CACHE_PREFIX.$this->_pagePath);
			if(is_array($arr))
			{
				list($pageConfig,$timestamp)=$arr;
				if($application->getMode()!=='Performance')
				{
					// check to see if cache is the latest
					$paths=explode('.',$this->_pagePath);
					array_pop($paths);
					$configPath=$this->_basePath;
					foreach($paths as $path)
					{
						if(@filemtime($configPath.'/'.self::CONFIG_FILE)>$timestamp)
						{
							$configCached=false;
							break;
						}
						$configPath.='/'.$path;
					}
					if($configCached && (@filemtime($application->getConfigurationFile())>$timestamp || @filemtime($configPath.'/'.self::CONFIG_FILE)>$timestamp))
						$configCached=false;
				}
			}
			else
				$configCached=false;
			if(!$configCached)
			{
				$pageConfig=new TPageConfiguration;
				$pageConfig->loadXmlElement($config,dirname($application->getConfigurationFile()),null);
				$pageConfig->loadConfigurationFiles($this->_pagePath,$this->_basePath);
				$cache->set(self::CONFIG_CACHE_PREFIX.$this->_pagePath,array($pageConfig,time()));
			}
		}

		$this->_pageType=$pageConfig->getPageType();

		// set path aliases and using namespaces
		foreach($pageConfig->getAliases() as $alias=>$path)
			Prado::setPathAlias($alias,$path);
		foreach($pageConfig->getUsings() as $using)
			Prado::using($using);

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
			$module=Prado::createComponent($moduleConfig[0]);
			$application->setModule($id,$module);
			foreach($moduleConfig[1] as $name=>$value)
				$module->setSubProperty($name,$value);
			$module->init($this->_application,$moduleConfig[2]);
		}

		$application->getAuthorizationRules()->mergeWith($pageConfig->getRules());

		$this->_initialized=true;
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
			$this->_templateManager->init($this->_application,null);
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
			$this->_assetManager->init($this->_application,null);
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
			$this->_themeManager->init($this->_application,null);
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
	 * @return IPageStatePersister page state persister
	 */
	public function getPageStatePersister()
	{
		if(!$this->_pageStatePersister)
		{
			$this->_pageStatePersister=new TPageStatePersister;
			$this->_pageStatePersister->init($this->_application,null);
		}
		return $this->_pageStatePersister;
	}

	/**
	 * @param IPageStatePersister page state persister
	 */
	public function setPageStatePersister(IPageStatePersister $value)
	{
		$this->_pageStatePersister=$value;
	}

	/**
	 * @return string the requested page path
	 */
	public function getRequestedPagePath()
	{
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
	 * @return string default page path to be served if no explicit page is request
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
	 * @return string root directory (in namespace form) storing pages
	 */
	public function getBasePath()
	{
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
		$page=null;
		if(($pos=strpos($this->_pageType,'.'))===false)
		{
			$className=$this->_pageType;
			if(!class_exists($className,false))
			{
				$p=explode('.',$this->_pagePath);
				array_pop($p);
				array_push($p,$className);
				$path=$this->_basePath.'/'.implode('/',$p).Prado::CLASS_FILE_EXT;
				include_once($path);
			}
		}
		else
		{
			$className=substr($this->_pageType,$pos+1);
			if(($path=Prado::getPathOfNamespace($this->_pageType,Prado::CLASS_FILE_EXT))!==null)
			{
				if(!class_exists($className,false))
				{
					include_once($path);
				}
			}
		}
		if(class_exists($className,false))
			$this->_page=new $className($this->_properties);
		else
			throw new THttpException(404,'pageservice_page_unknown',$this->_pageType);
		$writer=$this->_application->getResponse()->createHtmlWriter();
		$this->_page->run($writer);
		$writer->flush();
	}

	/**
	 * Constructs a URL with specified page path and GET parameters.
	 * @param string page path
	 * @param array list of GET parameters, null if no GET parameters required
	 * @return string URL for the page and GET parameters
	 */
	public function constructUrl($pagePath,$getParams=null)
	{
		return $this->_application->getRequest()->constructUrl($this->_id,$pagePath,$getParams);
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
	 * @var string page type
	 */
	private $_pageType=null;
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
	 * @return string the requested page type
	 */
	public function getPageType()
	{
		return $this->_pageType;
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
		if(empty($fname) || !is_file($fname))
		{
			if($page===null)
				return;
		}
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
				if(($id=$properties->itemAt('id'))===null)
					throw new TConfigurationException('pageserviceconf_module_invalid',$configPath);
				if(isset($this->_modules[$id]))
				{
					if($type===null || $type===$this->_modules[$id][0])
					{
						$this->_modules[$id][1]=array_merge($this->_modules[$id][1],$properties->toArray());
						$elements=$this->_modules[$id][2]->getElements();
						foreach($node->getElements() as $element)
							$elements->add($element);
					}
					else
					{
						$node->setParent(null);
						$this->_modules[$id]=array($type,$properties->toArray(),$node);
					}
				}
				else if($type===null)
					throw new TConfigurationException('pageserviceconf_moduletype_required',$id,$configPath);
				else
				{
					$node->setParent(null);
					$this->_modules[$id]=array($type,$properties->toArray(),$node);
				}
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
					$this->_parameters[$id]=$node->getValue();
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
					$type=$properties->remove('class');
					$id=$properties->itemAt('id');
					if($id===null || $type===null)
						throw new TConfigurationException('pageserviceconf_page_invalid',$configPath);
					if($id===$page)
					{
						$this->_properties=array_merge($this->_properties,$properties->toArray());
						$this->_pageType=$type;
					}
				}
			}
		}
		if($page!==null && $this->_pageType===null)
			throw new THttpException(404,'pageservice_page_unknown',$page);
	}
}



?>