<?php

// TODO: use namespace for BasePath
// add ThemesUrl


class TThemeManager extends TComponent implements IModule
{
	const THEME_CACHE_PREFIX='prado:theme:';
	const DEFAULT_BASEPATH='themes';
	/**
	 * @var string module ID
	 */
	private $_id;
	/**
	 * @var boolean whether this module has been initialized
	 */
	private $_initialized=false;
	/**
	 * @var string the directory containing all themes
	 */
	private $_basePath=null;
	private $_baseUrl=null;
	/**
	 * @var TApplication application
	 */
	private $_application;

	/**
	 * Initializes the module.
	 * This method is required by IModule and is invoked by application.
	 * @param TApplication application
	 * @param TXmlElement module configuration
	 */
	public function init($application,$config)
	{
		$this->_application=$application;
		if($this->_basePath===null)
			$this->_basePath=dirname($application->getRequest()->getPhysicalApplicationPath()).'/'.self::DEFAULT_BASEPATH;
		if(($basePath=realpath($this->_basePath))===false || !is_dir($basePath))
			throw new TConfigurationException('thememanager_basepath_invalid',$this->_basePath);
		$this->_basePath=$basePath;
		$this->_initialized=true;
		$application->getService()->setThemeManager($this);
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

	public function getTheme($name)
	{
		if(($themePath=realpath($this->_basePath.'/'.$name))===false || !is_dir($themePath))
			throw new TConfigurationException('thememanager_theme_inexistent',$name);
		if(($cache=$this->_application->getCache())!==null)
		{
			$array=$cache->get(self::THEME_CACHE_PREFIX.$themePath);
			if(is_array($array))
			{
				list($theme,$timestamp)=$array;
				$cacheValid=true;
				if($this->_application->getMode()!=='Performance')
				{
					if(($dir=opendir($themePath))===false)
						throw new TIOException('thememanager_theme_inexistent',$name);
					while(($file=readdir($dir))!==false)
					{
						if(basename($file,'.skin')!==$file && filemtime($themePath.'/'.$file)>$timestamp)
						{
							$cacheValid=false;
							break;
						}
					}
					closedir($dir);
				}
				if($cacheValid)
					return $theme;
			}
		}
		// not cached, so we collect all skin files
		$content='';
		if(($dir=opendir($themePath))===false)
			throw new TIOException('thememanager_theme_inexistent',$name);
		while(($file=readdir($dir))!==false)
		{
			if(basename($file,'.skin')!==$file)
				$content.=file_get_contents($themePath.'/'.$file);
		}
		closedir($dir);

		$theme=new TTheme($content,$themePath,$this->_baseUrl);
		if($cache!==null)
			$cache->set(self::THEME_CACHE_PREFIX.$themePath,array($theme,time()));
		return $theme;
	}

	public function getBasePath()
	{
		return $this->_basePath;
	}

	public function setBasePath($value)
	{
		if($this->_initialized)
			throw new TInvalidOperationException('thememanager_basepath_unchangeable');
		else
		{
			$this->_basePath=Prado::getPathOfAlias($value);
			if($this->_basePath===null || !is_dir($this->_basePath))
				throw new TInvalidDataValueException('thememanager_basepath_invalid',$value);
			$this->_basePath=$value;
		}
	}

	public function getBaseUrl()
	{
		return $this->_baseUrl;
	}

	public function setBaseUrl($value)
	{
		$this->_baseUrl=$value;
	}
}

class TTheme extends TTemplate
{
	private $_themePath;
	private $_themeUrl;
	private $_skins=array();

	public function __construct($content,$themePath,$baseUrl)
	{
		$this->_themePath=$themePath;
		if($baseUrl===null)
		{
			$appPath=dirname(Prado::getApplication()->getRequest()->getPhysicalApplicationPath());
			if(strpos($themePath,$appPath)===false)
				throw new TConfigurationException('theme_baseurl_required');
			$appUrl=dirname(Prado::getApplication()->getRequest()->getApplicationPath());
			$this->_themeUrl=$appUrl.'/'.strtr(substr($theme,strlen($basePath)),'\\','/');
		}
		else
			$this->_themeUrl=$baseUrl.'/'.basename($themePath);

		$theme=&$this->parse($content);
		foreach($theme as $skin)
		{
			if($skin[0]!==-1)
				throw new TConfigurationException('theme_control_nested',$skin[1]);
			else if(!isset($skin[2]))  // a text string, ignored
				continue;
			$type=$skin[1];
			$id=isset($skin[2]['skinid'])?$skin[2]['skinid']:0;
			unset($skin[2]['skinid']);
			if(isset($this->_skins[$type][$id]))
				throw new TConfigurationException('theme_skinid_duplicated',$type,$id);
			foreach($skin[2] as $name=>$value)
			{
				if(is_array($value) && $value[0]===0)
					throw new TConfigurationException('theme_databind_unsupported',$type,$id,$name);
			}
			$this->_skins[$type][$id]=$skin[2];
		}
	}

	public function applySkin($control)
	{
		$type=get_class($control);
		if(($id=$control->getSkinID())==='')
			$id=0;
		if(isset($this->_skins[$type][$id]))
		{
			foreach($this->_skins[$type][$id] as $name=>$value)
			{
				if(is_array($value))
				{
					if($value[0]===1)
						$value=$this->evaluateExpression($value[1]);
					else if($value[0]===2)
						$value=$this->_themeUrl.'/'.$value[1];
				}
				if(strpos($name,'.')===false)	// is simple property or custom attribute
				{
					if($control->hasProperty($name))
					{
						if($control->canSetProperty($name))
						{
							$setter='set'.$name;
							$control->$setter($value);
						}
						else
							throw new TConfigurationException('theme_property_readonly',get_class($control),$name);
					}
					else if($control->getAllowCustomAttributes())
						$control->getAttributes()->add($name,$value);
					else
						throw new TConfigurationException('theme_property_undefined',get_class($control),$name);
				}
				else	// complex property
					$control->setSubProperty($name,$value);
			}
			return true;
		}
		else
			return false;
	}
}


?>