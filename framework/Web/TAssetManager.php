<?php
/**
 * TAssetManager class
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System.Web
 */

/**
 * TAssetManager class
 *
 * TAssetManager provides a scheme to allow web clients visiting
 * private files that are normally web-inaccessible.
 *
 * TAssetManager will copy the file to be published into a web-accessible
 * directory. The default base directory for storing the file is "assets", which
 * should be under the application directory. This can be changed by setting
 * the {@link setBasePath BasePath} property together with the
 * {@link setBaseUrl BaseUrl} property that refers to the URL for accessing the base path.
 *
 * By default, TAssetManager will not publish a file or directory if it already
 * exists in the publishing directory and has an older modification time.
 * If the application mode is set as 'Performance', the modification time check
 * will be skipped. You can explicitly require a modification time check
 * with the function {@link publishFilePath}. This is usually
 * very useful during development.
 *
 * TAssetManager may be configured in application configuration file as follows,
 * <code>
 * <module id="asset" BasePath="Application.assets" BaseUrl="/assets" />
 * </code>
 * where {@link getBasePath BasePath} and {@link getBaseUrl BaseUrl} are
 * configurable properties of TAssetManager. Make sure that BasePath is a namespace
 * pointing to a valid directory writable by the Web server process.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web
 * @since 3.0
 */
class TAssetManager extends TModule
{
	/**
	 * Default web accessible base path for storing private files
	 */
	const DEFAULT_BASEPATH='assets';
	/**
	 * @var string base web accessible path for storing private files
	 */
	private $_basePath=null;
	/**
	 * @var string base URL for accessing the publishing directory.
	 */
	private $_baseUrl=null;
	/**
	 * @var boolean whether to use timestamp checking to ensure files are published with up-to-date versions.
	 */
	private $_checkTimestamp=false;
	/**
	 * @var TApplication application instance
	 */
	private $_application;
	/**
	 * @var array published assets
	 */
	private $_published=array();
	/**
	 * @var boolean whether the module is initialized
	 */
	private $_initialized=false;

	/**
	 * Initializes the module.
	 * This method is required by IModule and is invoked by application.
	 * @param TXmlElement module configuration
	 */
	public function init($config)
	{
		$application=$this->getApplication();
		if($this->_basePath===null)
			$this->_basePath=dirname($application->getRequest()->getPhysicalApplicationPath()).'/'.self::DEFAULT_BASEPATH;
		if(!is_writable($this->_basePath) || !is_dir($this->_basePath))
			throw new TConfigurationException('assetmanager_basepath_invalid',$this->_basePath);
		if($this->_baseUrl===null)
			$this->_baseUrl=rtrim(dirname($application->getRequest()->getApplicationPath()),'/\\').'/'.self::DEFAULT_BASEPATH;
		$application->setAssetManager($this);
		$this->_initialized=true;
	}

	/**
	 * @return string the root directory storing published asset files
	 */
	public function getBasePath()
	{
		return $this->_basePath;
	}

	/**
	 * Sets the root directory storing published asset files.
	 * The directory must be in namespace format.
	 * @param string the root directory storing published asset files
	 * @throws TInvalidOperationException if the module is initialized already
	 */
	public function setBasePath($value)
	{
		if($this->_initialized)
			throw new TInvalidOperationException('assetmanager_basepath_unchangeable');
		else
		{
			$this->_basePath=Prado::getPathOfAlias($value);
			if($this->_basePath===null || !is_dir($this->_basePath) || !is_writable($this->_basePath))
				throw new TInvalidDataValueException('assetmanage_basepath_invalid',$value);
		}
	}

	/**
	 * @return string the base url that the published asset files can be accessed
	 */
	public function getBaseUrl()
	{
		return $this->_baseUrl;
	}

	/**
	 * @param string the base url that the published asset files can be accessed
	 * @throws TInvalidOperationException if the module is initialized already
	 */
	public function setBaseUrl($value)
	{
		if($this->_initialized)
			throw new TInvalidOperationException('assetmanager_baseurl_unchangeable');
		else
			$this->_baseUrl=rtrim($value,'/');
	}

	public function getPublishedUrl($path)
	{
		if(($fullpath=realpath($path))!==false)
		{
			$dir=$this->hash(dirname($fullpath));
			$file=$this->_basePath.'/'.$dir.'/'.basename($fullpath);
			if(is_file($file) || is_dir($file))
				return $this->_baseUrl.'/'.$dir.'/'.basename($fullpath);
		}
		return null;
	}

	public function isPublished($path)
	{
		return $this->getPublishedUrl($path) !== null;
	}

	/**
	 * Publishes a file or a directory (recursively).
	 * This method will copy the content in a directory (recursively) to
	 * a web accessible directory and returns the URL for the directory.
	 * @param string the path to be published
	 * @param boolean whether to use file modify time to ensure every published file is latest
	 * @return string an absolute URL to the published directory
	 */
	public function publishFilePath($path,$checkTimestamp=false)
	{
		if(isset($this->_published[$path]))
			return $this->_published[$path];
		else if(($fullpath=realpath($path))===false)
			return '';
		else if(is_file($fullpath))
		{
			$dir=$this->hash(dirname($fullpath));
			$file=$this->_basePath.'/'.$dir.'/'.basename($fullpath);
			if(!is_file($file) || $checkTimestamp || $this->getApplication()->getMode()!==TApplication::STATE_PERFORMANCE)
			{
				if(!is_dir($this->_basePath.'/'.$dir))
					@mkdir($this->_basePath.'/'.$dir);
				if(!is_file($file) || @filemtime($file)<@filemtime($fullpath))
				{
					Prado::trace("Publishing file $fullpath",'System.Web.UI.TAssetManager');
					@copy($fullpath,$file);
				}
			}
			$this->_published[$path]=$this->_baseUrl.'/'.$dir.'/'.basename($fullpath);
			return $this->_published[$path];
		}
		else
		{
			$dir=$this->hash($fullpath);
			if(!is_dir($this->_basePath.'/'.$dir) || $checkTimestamp || $this->getApplication()->getMode()!==TApplication::STATE_PERFORMANCE)
			{
				Prado::trace("Publishing directory $fullpath",'System.Web.UI.TAssetManager');
				$this->copyDirectory($fullpath,$this->_basePath.'/'.$dir);
			}
			$this->_published[$path]=$this->_baseUrl.'/'.$dir;
			return $this->_published[$path];
		}
	}

	/**
	 * Generate a CRC32 hash for the directory path. Collisions are higher
	 * than MD5 but generates a much smaller hash string.
	 * @param string string to be hashed.
	 * @return string hashed string.
	 */
	protected function hash($dir)
	{
		return sprintf('%x',crc32($dir));
	}

	/**
	 * Copies a directory recursively as another.
	 * If the destination directory does not exist, it will be created.
	 * File modification time is used to ensure the copied files are latest.
	 * @param string the source directory
	 * @param string the destination directory
	 */
	protected function copyDirectory($src,$dst)
	{
		if(!is_dir($dst))
			@mkdir($dst);
		$folder=@opendir($src);
		while($file=@readdir($folder))
		{
			if($file==='.' || $file==='..')
				continue;
			else if(is_file($src.'/'.$file))
			{
				if(@filemtime($dst.'/'.$file)<@filemtime($src.'/'.$file))
					@copy($src.'/'.$file,$dst.'/'.$file);
			}
			else
				$this->copyDirectory($src.'/'.$file,$dst.'/'.$file);
		}
		closedir($folder);
	}
}

?>