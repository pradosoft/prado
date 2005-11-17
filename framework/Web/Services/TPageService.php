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

Prado::using('System.Web.UI.TPage');
/**
 * TPageService class.
 *
 * TPageService implements a service that can serve user requested pages.
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
	private $_basePath;
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
	 * Initializes the service.
	 * This method is required by IService interface and is invoked by application.
	 * @param TApplication application
	 * @param TXmlElement service configuration
	 */
	public function init($application,$config)
	{
		$this->_application=$application;

		if(($basePath=Prado::getPathOfNamespace($this->_basePath))===null || !is_dir($basePath))
			throw new TConfigurationException('pageservice_basepath_invalid',$this->_basePath);

		$this->_pagePath=$application->getRequest()->getServiceParameter();
		if(empty($this->_pagePath))
			$this->_pagePath=$this->_defaultPage;
		if(empty($this->_pagePath))
			throw new THttpException('pageservice_page_required');

		if(($cache=$application->getCache())===null)
		{
			$pageConfig=new TPageConfiguration;
			$pageConfig->loadXmlElement($config,dirname($application->getConfigurationFile()),null);
			$pageConfig->loadConfigurationFiles($this->_pagePath,$basePath);
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
					$configPath=$basePath;
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
				$pageConfig->loadConfigurationFiles($this->_pagePath,$basePath);
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
		return $this->_application->getModule('template');
	}

	/**
	 * @return TAssetManager asset manager
	 */
	public function getAssetManager()
	{
		return $this->_application->getModule('asset');
	}

	/**
	 * @return TThemeManager theme manager
	 */
	public function getThemeManager()
	{
		return $this->_application->getModule('theme');
	}

	/**
	 * @return boolean true if the pagepath is currently being requested, false otherwise
	 */
	public function isRequestingPage($pagePath)
	{
		return $this->_pagePath===$pagePath;
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
	 * @throws TInvalidOperationException if the service is initialized already
	 */
	public function setBasePath($value)
	{
		if($this->_initialized)
			throw new TInvalidOperationException('pageservice_basepath_unchangeable');
		else
			$this->_basePath=$value;
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
				$path=Prado::getPathOfNamespace($this->_basePath).'/'.implode('/',$p).Prado::CLASS_FILE_EXT;
				require_once($path);
			}
		}
		else
		{
			$className=substr($this->_pageType,$pos+1);
			if(($path=Prado::getPathOfNamespace($this->_pageType,Prado::CLASS_FILE_EXT))!==null)
			{
				if(!class_exists($className,false))
				{
					require_once($path);
				}
			}
		}
		if(class_exists($className,false))
			$page=new $className($this->_properties);
		else
			throw new THttpException('pageservice_page_unknown',$this->_pageType);
		$writer=new THtmlTextWriter($this->_application->getResponse());
		$page->run($writer);
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
	private $_modules=array(
		'template'=>array('System.Web.UI.TTemplateManager',array(),null),
		'asset'=>array('System.Web.TAssetManager',array(),null),
		'theme'=>array('System.Web.UI.TThemeManager',array(),null)
	);
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
			throw new TConfigurationException('pageservice_configfile_invalid',$fname);
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
						throw new TConfigurationException('pageservice_alias_path_invalid',$configPath,$id,$p);
					if(isset($this->_aliases[$id]))
						throw new TConfigurationException('pageservice_alias_redefined',$configPath,$id);
					$this->_aliases[$id]=$path;
				}
				else
					throw new TConfigurationException('pageservice_alias_element_invalid',$configPath);
			}
			foreach($pathsNode->getElementsByTagName('using') as $usingNode)
			{
				if(($namespace=$usingNode->getAttribute('namespace'))!==null)
					$this->_usings[]=$namespace;
				else
					throw new TConfigurationException('pageservice_using_element_invalid',$configPath);
			}
		}

		// modules
		if(($modulesNode=$dom->getElementByTagName('modules'))!==null)
		{
			foreach($modulesNode->getElementsByTagName('module') as $node)
			{
				$properties=$node->getAttributes();
				$type=$properties->remove('type');
				if(($id=$properties->itemAt('id'))===null)
					throw new TConfigurationException('pageservice_module_element_invalid',$configPath);
				if(isset($this->_modules[$id]))
				{
					if($type===null)
					{
						$this->_modules[$id][1]=array_merge($this->_modules[$id][1],$properties->toArray());
						$elements=$this->_modules[$id][2]->getElements();
						foreach($node->getElements() as $element)
							$elements->add($element);
					}
					else
						throw new TConfigurationException('pageservice_module_redefined',$configPath,$id);
				}
				else if($type===null)
					throw new TConfigurationException('pageservice_module_element_invalid',$configPath);
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
					throw new TConfigurationException('pageservice_parameter_element_invalid');
				if(($type=$properties->remove('type'))===null)
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
					$type=$properties->remove('type');
					$id=$properties->itemAt('id');
					if($id===null || $type===null)
						throw new TConfigurationException('pageservice_page_element_invalid',$configPath);
					if($id===$page)
					{
						$this->_properties=array_merge($this->_properties,$properties->toArray());
						$this->_pageType=$type;
					}
				}
			}
		}
		if($page!==null && $this->_pageType===null)
			throw new THttpException('pageservice_page_inexistent',$page);
	}
}



?>