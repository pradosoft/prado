<?php

class TThemeManager extends TComponent implements IModule
{
	const THEME_CACHE_PREFIX='prado:theme:';
	const DEFAULT_THEME_PATH='themes';
	const THEME_FILE='theme.tpl';
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
	private $_themePath=null;
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
		if($this->_themePath===null)
			$this->_themePath=dirname($application->getConfigurationFile()).'/'.self::DEFAULT_THEME_PATH;

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

	public function getTheme($name)
	{
		$themePath=realpath($this->_themePath.'/'.$name);
		if($themePath===false || !is_dir($this->_themePath))
			throw new TConfigurationException('thememanager_themepath_invalid',$themePath);
		if(($cache=$this->_application->getCache())===null)
			return new TTheme($themePath.'/'.self::THEME_FILE);
		else
		{
			$themeFile=$themePath.'/'.self::THEME_FILE;
			$array=$cache->get(self::THEME_CACHE_PREFIX.$themePath);
			if(is_array($array))
			{
				list($theme,$timestamp)=$array;
				if(filemtime($themeFile)<$timestamp)
					return $theme;
			}
			$theme=new TTheme($themeFile);
			$cache->set(self::THEME_CACHE_PREFIX.$themePath,array($theme,time()));
			return $theme;
		}
	}

	public function getThemePath()
	{
		return $this->_themePath;
	}

	public function setThemePath($value)
	{
		if($this->_initialized)
			throw new TInvalidOperationException('thememanager_themepath_unchangeable');
		else
			$this->_themePath=$value;
	}
}

class TTheme extends TTemplate
{
	private $_themePath;
	private $_themeFile;
	private $_skins=array();

	public function __construct($themeFile)
	{
		$this->_themeFile=$themeFile;
		$this->_themePath=dirname($themeFile);
		if(is_file($themeFile) && is_readable($themeFile))
		{
			$theme=&$this->parse(file_get_contents($themeFile));
			foreach($theme as $skin)
			{
				if($skin[0]!==0)
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
		else
			throw new TIOException('theme_themefile_invalid',$themeFile);
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
					$value=$this->evaluateExpression($value);
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