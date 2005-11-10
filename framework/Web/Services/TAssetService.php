<?php

/**
<module id="asset" PrivateLocation="xxx" PublicLocation="xxx" BaseUrl="xxx" />
 */
class TAssetManager extends TComponent implements IModule
{
	private $_pubDir=null;
	private $_pubUrl=null;

	public function init($context)
	{
		if(is_null($this->_pubDir))
			throw new TCongiruationException('cache_public_location_required');
		if(is_null($this->_pubUrl))
			throw new TCongiruationException('cache_public_url_required');
	}

	public function getPublicLocation()
	{
		return $this->_pubDir;
	}

	public function setPublicLocation($value)
	{
		if(is_dir($value))
			$this->_pubDir=realpath($value);
		else
			throw new TInvalidDataValueException('cache_public_location_invalid');
	}

	public function getPublicUrl()
	{
		return $this->_pubUrl;
	}

	public function setPublicUrl($value)
	{
		$this->_pubUrl=rtrim($value,'/');
	}

	public function publishLocation($path,$forceOverwrite=false)
	{
		$name=basename($path);
		$prefix=md5(dirname($path));
	}

	public function publishFile($path,$forceOverwrite=false)
	{
		if(($fullpath=realpath($path))!==false)
		{
			return $this->_pubUrl.'/'.$fullpath;
		}
		else
			throw new TInvalidDataValueException('cachemanager_path_unpublishable');
	}

	public function unpublishPath($path)
	{
	}
}

class TMemcache extends TComponent
{
}

class TSqliteCache extends TComponent
{
}
?>