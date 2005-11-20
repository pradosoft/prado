<?php
/**
 * TThemeManager class
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System.Web.UI
 */

/**
 * TThemeManager class
 *
 * TThemeManager manages the themes used in a Prado application.
 *
 * Themes are stored in under {@link setBasePath BasePath} that can be accessed
 * via URL {@link setBaseUrl BaseUrl}. Each theme is defined by a subdirectory
 * and all the files under that directory.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI
 * @since 3.0
 */
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

		if($this->_baseUrl===null)
		{
			$appPath=dirname(Prado::getApplication()->getRequest()->getPhysicalApplicationPath());
			if(strpos($themePath,$appPath)===false)
				throw new TConfigurationException('theme_baseurl_required');
			$appUrl=dirname(Prado::getApplication()->getRequest()->getApplicationPath());
			$themeUrl=$appUrl.'/'.strtr(substr($themePath,strlen($appPath)),'\\','/');
		}
		else
			$themeUrl=$this->_baseUrl.'/'.basename($themePath);
		return new TTheme($this->_application,$themePath,$themeUrl);

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

class TTheme extends TComponent
{
	const THEME_CACHE_PREFIX='prado:theme:';
	const SKIN_FILE_EXT='.skin';
	private $_themePath;
	private $_themeUrl;
	private $_skins=null;

	public function __construct($application,$themePath,$themeUrl)
	{
		$this->_themeUrl=$themeUrl;
		if(($cache=$application->getCache())!==null)
		{
			$array=$cache->get(self::THEME_CACHE_PREFIX.$themePath);
			if(is_array($array))
			{
				list($skins,$timestamp)=$array;
				$cacheValid=true;
				if($application->getMode()!=='Performance')
				{
					if(($dir=opendir($themePath))===false)
						throw new TIOException('theme_path_inexistent',$themePath);
					while(($file=readdir($dir))!==false)
					{
						if(basename($file,self::SKIN_FILE_EXT)!==$file && filemtime($themePath.'/'.$file)>$timestamp)
						{
							$cacheValid=false;
							break;
						}
					}
					closedir($dir);
				}
				if($cacheValid)
					$this->_skins=$skins;
			}
		}
		if($this->_skins===null)
		{
			if(($dir=opendir($themePath))===false)
				throw new TIOException('theme_path_inexistent',$themePath);
			while(($file=readdir($dir))!==false)
			{
				if(basename($file,self::SKIN_FILE_EXT)!==$file)
				{
					$template=new TTemplate(file_get_contents($themePath.'/'.$file),$themePath,$themePath.'/'.$file);
					foreach($template->getItems() as $skin)
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
								throw new TConfigurationException('theme_databind_forbidden',$type,$id,$name);
						}
						$this->_skins[$type][$id]=$skin[2];
					}
				}
			}
			closedir($dir);
			if($cache!==null)
				$cache->set(self::THEME_CACHE_PREFIX.$themePath,array($this->_skins,time()));
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