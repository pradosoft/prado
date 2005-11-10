<?php
/**
<configuration>
  <modules>
	<module id="cache" Expiry="xxx" CacheStorage="file"
			Location="xxx"
	        MemcacheServer="localhost" MemcachePort="1111" />
	<module id="security" MachineKey="xxx" .... />
	<module id="authenticator" ... />
  </modules>
  <services>
    <service id="page" Location="xxx">
	  <module id="asset" UseService="false" Location="xxx" Url="xxx" />
	  <module id="authorizer" />
	</service>
  </services>
</configuration>

<module id="cache" Storage="sqlite,memcache" UniquePrefix="xxx"
        SqliteFile="xxx" MemcacheServer="localhost" MemcachePort="1111"/>
<module id="asset" UseService="true" Location="xxx" Url="xxx" />
<module id="authenticator" LoginUrl="xxxx" />
<module id="authorizer" /> // need to investigate the security of memory cache
 */

/**
<module id="cache" type="System.Modules.TMemCache" Server="localhost" Port="1FileName="xxx" UniquePrefix="" />
*/

/**
<module id="cache" type="System.Modules.TSqliteCache" DbFile="xxx" />
*/


class TAuthencator extends TComponent
{
}

class TAuthorizer extends TComponent
{
}

$cm->generateUniqueID('button:',$id)
$cm->saveValue('template:'.$tmpFile,$template);
$cm->saveValue('application:ID',$appID);
$cm->saveValue('application:hashkey',$key);

class TTemplateManager extends TComponent implements IModule
{
}

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