<?php

class TAssetManager extends TComponent implements IModule
{
	const DEFAULT_BASEPATH='assets';
	private $_basePath=null;
	private $_baseUrl=null;
	/**
	 * @var string module ID
	 */
	private $_id;

	/**
	 * Initializes the module.
	 * This method is required by IModule and is invoked by application.
	 * @param IApplication application
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

	public function publishDirectory($path,$forceOverwrite=false)
	{
		if(($fullpath=realpath($path))!==false && is_dir($fullpath))
		{
			$dir=md5($fullpath);
			if(!is_dir($this->_basePath.'/'.$dir))
				$this->copyDirectory($this->_basePath.'/'.$dir);
			return $this->_baseUrl.'/'.$dir;
		}
		else
			throw new TInvalidDataValueException('assetmanager_directory_invalid',$path);
	}

	protected function copyDirectory($src,$dst)
	{
		mkdir($dst);
		$folder=opendir($src);
		while($file=readdir($folder))
		{
			if($file==='.' || $file==='..')
				continue;
			else if(is_file($src.'/'.$file))
				copy($src.'/'.$file,$dst.'/'.$file);
			else
				$this->copyDirectory($src.'/'.$file,$dst.'/'.$file);
		}
		closedir($folder);
	}

	public function publishFile($path,$forceOverwrite=false)
	{
		if(($fullpath=realpath($path))!==false && is_file($fullpath))
		{
			$dir=md5(dirname($fullpath));
			$file=$this->_basePath.'/'.$dir.'/'.basename($fullpath);
			if(!is_file($file))
			{
				@mkdir($this->_basePath.'/'.$dir);
				copy($fullpath,$file);
			}
			return $this->_baseUrl.'/'.$dir.'/'.basename($fullpath);
		}
		else
			throw new TInvalidDataValueException('assetmanager_file_invalid',$path);
	}
}

?>