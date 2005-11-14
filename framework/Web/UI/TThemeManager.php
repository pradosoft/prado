<?php

class TThemeManager extends TComponent implements IModule
{
	const THEME_CACHE_PREFIX='prado:theme:';
	const DEFAULT_THEME_PATH='themes';
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
	 * @var IApplication application
	 */
	private $_application;

	/**
	 * Initializes the module.
	 * This method is required by IModule and is invoked by application.
	 * @param IApplication application
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
		if(($cache=$this->_application->getCache())!==null)
		{
			$array=$cache->get(self::THEME_CACHE_PREFIX.$themePath);
			if(is_array($array))
			{
				list($theme,$timestamp)=$array;
				$cacheValid=true;
				if(($dir=opendir($themePath))===false)
					throw new TIOException('thememanager_themepath_invalid',$themePath);
				while(($file=readdir($dir))!==false)
				{
					if(basename($file,'.skin')!==$file && filemtime($themePath.'/'.$file)>$timestamp)
					{
						$cacheValid=false;
						break;
					}
				}
				closedir($dir);
				if($cacheValid)
					return $theme;
			}
		}
		// not cached, so we parse all skin files
		$content='';
		if(($dir=opendir($themePath))===false)
			throw new TIOException('thememanager_themepath_invalid',$themePath);
		while(($file=readdir($dir))!==false)
		{
			if(basename($file,'.skin')!==$file)
				$content.=file_get_contents($themePath.'/'.$file);
		}
		closedir($dir);
		$theme=new TTheme($content,$themePath);
		if($cache!==null)
			$cache->set(self::THEME_CACHE_PREFIX.$themePath,array($theme,time()));
		return $theme;
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
	const ASSET_PATH='assets';
	private $_themePath;
	private $_themeFile;
	private $_skins=array();

	public function __construct($content,$themePath)
	{
		$this->_themePath=$themePath;
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
					$value=$this->evaluateExpression($value[1]);
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

	public function publishFile($file)
	{
		return Prado::getApplication()->getService()->getAssetManager()->publishFile($file);
	}

	public function publishDirectory($directory)
	{
		return Prado::getApplication()->getService()->getAssetManager()->publishDirectory($directory);
	}

	public function getAsset($assetName)
	{
		$assetFile=$this->_themePath.'/'.self::ASSET_PATH.'/'.$assetName;
		if(is_file($assetFile))
			return $this->publishFile($assetFile);
		else if(is_dir($assetFile))
			return $this->publishDirectory($assetFile);
		else
			return '';
	}

}


?>