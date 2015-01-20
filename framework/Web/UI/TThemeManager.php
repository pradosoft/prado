<?php
/**
 * TThemeManager class
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package System.Web.UI
 */

Prado::using('System.Web.Services.TPageService');

/**
 * TThemeManager class
 *
 * TThemeManager manages the themes used in a Prado application.
 *
 * Themes are stored under the directory specified by the
 * {@link setBasePath BasePath} property. The themes can be accessed
 * via URL {@link setBaseUrl BaseUrl}. Each theme is represented by a subdirectory
 * and all the files under that directory. The name of a theme is the name
 * of the corresponding subdirectory.
 * By default, the base path of all themes is a directory named "themes"
 * under the directory containing the application entry script.
 * To get a theme (normally you do not need to), call {@link getTheme}.
 *
 * TThemeManager may be configured within page service tag in application
 * configuration file as follows,
 * <module id="themes" class="System.Web.UI.TThemeManager"
 *         BasePath="Application.themes" BaseUrl="/themes" />
 * where {@link getCacheExpire CacheExpire}, {@link getCacheControl CacheControl}
 * and {@link getBufferOutput BufferOutput} are configurable properties of THttpResponse.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package System.Web.UI
 * @since 3.0
 */
class TThemeManager extends TModule
{
	/**
	 * default themes base path
	 */
	const DEFAULT_BASEPATH='themes';

	/**
	 * default theme class
	 */
	const DEFAULT_THEMECLASS = 'TTheme';

	/**
	 * @var string
	 */
	private $_themeClass=self::DEFAULT_THEMECLASS;

	/**
	 * @var boolean whether this module has been initialized
	 */
	private $_initialized=false;
	/**
	 * @var string the directory containing all themes
	 */
	private $_basePath=null;
	/**
	 * @var string the base URL for all themes
	 */
	private $_baseUrl=null;

	/**
	 * Initializes the module.
	 * This method is required by IModule and is invoked by application.
	 * @param TXmlElement module configuration
	 */
	public function init($config)
	{
		$this->_initialized=true;
		$service=$this->getService();
		if($service instanceof TPageService)
			$service->setThemeManager($this);
		else
			throw new TConfigurationException('thememanager_service_unavailable');
	}

	/**
	 * @param string name of the theme to be retrieved
	 * @return TTheme the theme retrieved
	 */
	public function getTheme($name)
	{
		$themePath=$this->getBasePath().DIRECTORY_SEPARATOR.$name;
		$themeUrl=rtrim($this->getBaseUrl(),'/').'/'.$name;
		return Prado::createComponent($this->getThemeClass(), $themePath, $themeUrl);
	}

	/**
	 * @param string|null $class Theme class name in namespace format
	 */
	public function setThemeClass($class) {
		$this->_themeClass = $class===null ? self::DEFAULT_THEMECLASS : (string)$class;
	}

	/**
	 * @return string Theme class name in namespace format. Defaults to {@link TThemeManager::DEFAULT_THEMECLASS DEFAULT_THEMECLASS}.
	 */
	public function getThemeClass() {
		return $this->_themeClass;
	}

	/**
	 * @return array list of available theme names
	 */
	public function getAvailableThemes()
	{
		$themes=array();
		$basePath=$this->getBasePath();
		$folder=@opendir($basePath);
		while($file=@readdir($folder))
		{
			if($file!=='.' && $file!=='..' && $file!=='.svn' && is_dir($basePath.DIRECTORY_SEPARATOR.$file))
				$themes[]=$file;
		}
		closedir($folder);
		return $themes;
	}

	/**
	 * @return string the base path for all themes. It is returned as an absolute path.
	 * @throws TConfigurationException if base path is not set and "themes" directory does not exist.
	 */
	public function getBasePath()
	{
		if($this->_basePath===null)
		{
			$this->_basePath=dirname($this->getRequest()->getApplicationFilePath()).DIRECTORY_SEPARATOR.self::DEFAULT_BASEPATH;
			if(($basePath=realpath($this->_basePath))===false || !is_dir($basePath))
				throw new TConfigurationException('thememanager_basepath_invalid2',$this->_basePath);
			$this->_basePath=$basePath;
		}
		return $this->_basePath;
	}

	/**
	 * @param string the base path for all themes. It must be in the format of a namespace.
	 * @throws TInvalidDataValueException if the base path is not a proper namespace.
	 */
	public function setBasePath($value)
	{
		if($this->_initialized)
			throw new TInvalidOperationException('thememanager_basepath_unchangeable');
		else
		{
			$this->_basePath=Prado::getPathOfNamespace($value);
			if($this->_basePath===null || !is_dir($this->_basePath))
				throw new TInvalidDataValueException('thememanager_basepath_invalid',$value);
		}
	}

	/**
	 * @return string the base URL for all themes.
	 * @throws TConfigurationException If base URL is not set and a correct one cannot be determined by Prado.
	 */
	public function getBaseUrl()
	{
		if($this->_baseUrl===null)
		{
			$appPath=dirname($this->getRequest()->getApplicationFilePath());
			$basePath=$this->getBasePath();
			if(strpos($basePath,$appPath)===false)
				throw new TConfigurationException('thememanager_baseurl_required');
			$appUrl=rtrim(dirname($this->getRequest()->getApplicationUrl()),'/\\');
			$this->_baseUrl=$appUrl.strtr(substr($basePath,strlen($appPath)),'\\','/');
		}
		return $this->_baseUrl;
	}

	/**
	 * @param string the base URL for all themes.
	 */
	public function setBaseUrl($value)
	{
		$this->_baseUrl=rtrim($value,'/');
	}
}