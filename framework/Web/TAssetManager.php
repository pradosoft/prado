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
 * the BasePath property together with the BaseUrl property that refers to
 * the URL for accessing the base path.
 *
 * By default, TAssetManager will not publish a file or directory if it already
 * exists in the publishing directory. You may require a timestamp checking by
 * setting CheckTimestamp to true (which is false by default). You may also require
 * so when calling {@link publishFilePath}. This is usually
 * very useful during development. In production sites, the timestamp checking
 * should be turned off to improve performance.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web
 * @since 3.0
 */
class TAssetManager extends TComponent implements IModule
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
	 * @var string module ID
	 */
	private $_id;
	/**
	 * @var boolean whether to use timestamp checking to ensure files are published with up-to-date versions.
	 */
	private $_checkTimestamp=false;

	/**
	 * Initializes the module.
	 * This method is required by IModule and is invoked by application.
	 * @param TApplication application
	 * @param TXmlElement module configuration
	 */
	public function init($application,$config)
	{
		if($this->_basePath===null)
			$this->_basePath=dirname($application->getRequest()->getPhysicalApplicationPath()).'/'.self::DEFAULT_BASEPATH;
		if(!is_writable($this->_basePath) || !is_dir($this->_basePath))
			throw new TConfigurationException('assetmanager_basepath_invalid',$this->_basePath);
		if($this->_baseUrl===null)
			$this->_baseUrl=dirname($application->getRequest()->getApplicationPath()).'/'.self::DEFAULT_BASEPATH;
		$application->getService()->setAssetManager($this);
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
	 * @return string the root directory storing published asset files
	 */
	public function getBasePath()
	{
		return $this->_basePath;
	}

	/**
	 * @param string the root directory storing published asset files
	 * @throws TInvalidOperationException if the service is initialized already
	 */
	public function setBasePath($value)
	{
		if($this->_initialized)
			throw new TInvalidOperationException('assetmanager_basepath_unchangeable');
		else if(is_dir($value))
			$this->_basePath=realpath($value);
		else
			throw new TInvalidDataValueException('assetmanage_basepath_invalid',$value);
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
	 * @throws TInvalidOperationException if the service is initialized already
	 */
	public function setBaseUrl($value)
	{
		if($this->_initialized)
			throw new TInvalidOperationException('pageservice_baseurl_unchangeable');
		else
			$this->_baseUrl=$value;
	}

	/**
	 * @return boolean whether file modify time should be used to ensure a published file is latest. Defaults to false.
	 */
	public function getCheckTimestamp()
	{
		return $this->_checkTimestamp;
	}

	/**
	 * @param boolean whether file modify time should be used to ensure a published file is latest. Defaults to false.
	 */
	public function setCheckTimestamp($value)
	{
		if($this->_initialized)
			throw new TInvalidOperationException('pageservice_checktimestamp_unchangeable');
		else
			$this->_checkTimestamp=TPropertyValue::ensureBoolean($value);
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
		if(($fullpath=realpath($path))===false)
			return '';
		else if(is_file($fullpath))
		{
			$dir=md5(dirname($fullpath));
			$file=$this->_basePath.'/'.$dir.'/'.basename($fullpath);

			if(!is_file($file) || (($checkTimestamp || $this->_checkTimestamp) && filemtime($file)<filemtime($path)))
			{
				@mkdir($this->_basePath.'/'.$dir);
				@copy($fullpath,$file);
			}
			return $this->_baseUrl.'/'.$dir.'/'.basename($fullpath);
		}
		else
		{
			$dir=md5($fullpath);
			if(!is_dir($this->_basePath.'/'.$dir) || $checkTimestamp || $this->_checkTimestamp)
				$this->copyDirectory($fullpath,$this->_basePath.'/'.$dir);
			return $this->_baseUrl.'/'.$dir;
		}
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