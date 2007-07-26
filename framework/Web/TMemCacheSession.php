<?php

/**
 * TMemCacheSession class
 *
 * @author Carl G. Mathisen <carlgmathisen@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2007 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id: TMemCacheSession.php $
 * @package System.Web
 */

/**
 * TMemCacheSession class
 *
 * TMemCacheSession provides access for storing sessions in memcache.
 * Beware, session data will be stored in memory. Old data will be flushed
 * if you run out of cache memory.  Configure your memcache carefully.
 * Keep in mind that memcached is a cache not a database. It is fast,
 * but not reliable storage.
 *
 * <module id="session" class="TMemCacheSession" SessionName="SSID"
 *         CookieMode="Allow" AutoStart="true" GCProbability="1"
 *         UseTransparentSessionID="true" TimeOut="3600" />
 *     <server Host="hostname1" Port="11211" />
 *     <server Host="hostname2" Port="11211" Weight="1" Timeout="1" RetryInterval="15" Persistent="true" />
 * </module>
 * 
 * @author Carl G. Mathisen <carlgmathisen@gmail.com>
 * @version $Id: TMemCacheSession.php $
 * @package System.Web
 * @since 3.1.0
 */
class TMemCacheSession extends THttpSession
{		
	/**
	 * @var MemCache
	 */
	private $_memCache=null;
	
	/**
	 * @var boolean if the module is initialized
	 */
	private $_initialized=false;
	
	/**
	 * File extension of external configuration file
	 */
	const CONFIG_FILE_EXT='.xml';
	
	/**
	 * @var array list of servers available
	 */
	private $_servers=array();
	
	/**
	 * @var string external configuration file
	 */
	private $_configFile=null;
	
	/**
	 * @var string 
	 */
	private $_prefix = 'PRADO';
	
	/**
	 * @var string
	 */
	private $_host = 'localhost';
	
	/**
	 * @var integer
	 */
	private $_port = 11211;

    /**
     * Connecting to memcached. If no servers are defined in config, we will use
   	 *the default server {@see Host} and {@see Port}, localhost and 11211 respectively.
     */
    public function init($config)
    {
        $this->setAutoStart(true);
        $this->setUseCustomStorage(true);
        if(!extension_loaded('memcache'))
			throw new TConfigurationException('memcache_extension_required');
        if($this->_configFile!==null)
		{
 			if(is_file($this->_configFile))
 			{
				$dom=new TXmlDocument;
				$dom->loadFromFile($this->_configFile);
				$this->loadConfig($dom);
			}
			else
				throw new TConfigurationException('memcachesession_configfile_invalid',$this->_configFile);
		}
		$this->loadConfig($config);
        $this->_memCache = new MemCache;
        if(count($this->_servers))
        {
            foreach($this->_servers as $server)
            {
                Prado::trace('Adding server '.$server['Host'].' from serverlist', 'System.Web.TMemCacheSession');
                if($this->_memCache->addServer($server['Host'],$server['Port'],$server['Persistent'],
                    $server['Weight'],$server['Timeout'],$server['RetryInterval'])===false)
                    throw new TConfigurationException('memcache_connection_failed',$server['Host'],$server['Port']);
            }
        }
        else
        {
            Prado::trace('Adding server '.$this->_host.' from serverlist', 'System.Web.TMemCacheSession');
            if($this->_memCache->addServer($this->_host,$this->_port)===false)
                throw new TConfigurationException('memcache_connection_failed',$this->_host,$this->_port);
        }
        $this->_initialized=true;
    }
    
    /**
	 * Loads configuration from an XML element
	 * @param TXmlElement configuration node
	 * @throws TConfigurationException if log route class or type is not specified
	 */
	private function loadConfig($xml)
	{
	    if($xml instanceof TXmlElement)
	    {
	    	foreach($xml->getElementsByTagName('server') as $serverConfig)
    		{
    			$properties=$serverConfig->getAttributes();
    			if(($host=$properties->remove('Host'))===null)
    				throw new TConfigurationException('memcachesession_serverhost_required');
    			if(($port=$properties->remove('Port'))===null)
        			throw new TConfigurationException('memcachesession_serverport_required');
        		if(!is_numeric($port))
        		    throw new TConfigurationException('memcachesession_serverport_invalid');
        		$server = array('Host'=>$host,'Port'=>$port,'Weight'=>1,'Timeout'=>1800,'RetryInterval'=>15,'Persistent'=>true);
        		$checks = array(
        		    'Weight'=>'memcachesession_serverweight_invalid',
        		    'Timeout'=>'memcachesession_servertimeout_invalid',
        		    'RetryInterval'=>'memcachesession_serverretryinterval_invalid'
        		);
        		foreach($checks as $property=>$exception)
        		{
        		    $value=$properties->remove($property); 
        		    if($value!==null && is_numeric($value))
        		        $server[$property]=$value;
        		    else if($value!==null)
        		        throw new TConfigurationException($exception);
        		}
        		$server['Persistent']= TPropertyValue::ensureBoolean($properties->remove('Persistent'));
    			$this->_servers[]=$server;
    		}
	    }
	}

    /**
	 * Session open handler.
	 * @param string session save path
	 * @param string session name
	 * @return boolean whether session is opened successfully
	 */
	public function _open($savePath,$sessionName)
	{
        return true;
	}

	/**
	 * Session close handler.
	 * @return boolean whether session is closed successfully
	 */
	public function _close()
	{
		return true;
	}

	/**
	 * Session read handler.
	 * @param string session ID
	 * @return string the session data
	 */
	public function _read($id)
	{
	    $key = $this->calculateKey($id);
	    return $this->_memCache->get($key);
	}

	/**
	 * Session write handler.
	 * @param string session ID
	 * @param string session data
	 * @return boolean whether session write is successful
	 */
	public function _write($id,$data)
	{
		$key = $this->calculateKey($id);
		$res = $this->_memCache->set($key,$data,MEMCACHE_COMPRESSED,$this->_timeOut);
	    return $res;
	}

	/**
	 * Session destroy handler.
	 * This method should be overriden if {@link setUseCustomStorage UseCustomStorage} is set true.
	 * @param string session ID
	 * @return boolean whether session is destroyed successfully
	 */
	public function _destroy($id)
	{
	    $key = $this->calculateKey($id);
	    return $this->_memCache->delete($key);
	}

	/**
	 * Session GC (garbage collection) handler.
	 * Memcache has it's own garbage collection
	 * @param integer the number of seconds after which data will be seen as 'garbage' and cleaned up.
	 * @return boolean whether session is GCed successfully
	 */
	public function _gc($maxLifetime)
	{
		return true;
	}
	
	/**
	 * @return string host name of the memcache server
	 */
	public function getHost()
	{
		return $this->_host;
	}

	/**
	 * @param string host name of the memcache server
	 * @throws TInvalidOperationException if the module is already initialized
	 */
	public function setHost($value)
	{
		if($this->_initialized)
			throw new TInvalidOperationException('memcache_host_unchangeable');
		else
			$this->_host=$value;
	}

	/**
	 * @return integer port number of the memcache server
	 */
	public function getPort()
	{
		return $this->_port;
	}

	/**
	 * @param integer port number of the memcache server
	 * @throws TInvalidOperationException if the module is already initialized
	 */
	public function setPort($value)
	{
		if($this->_initialized)
			throw new TInvalidOperationException('memcache_port_unchangeable');
		else
			$this->_port=TPropertyValue::ensureInteger($value);
	}

    /**
     * @param string prefix of our memcache data key
     */
	public function setPrefix($value)
	{
	    if($this->_initialized)
			throw new TInvalidOperationException('memcache_prefix_unchangeable');
		else
	    $this->_prefix = $value;
	}
	
	/**
	 * @return string prefix of our memcache data key
	 */
	public function getPrefix()
	{
	    return $this->_prefix;
	}
	
	/**
	 * @param string memcache data key
	 * @return safe memcache key within 256 characters
	 */
	private function calculateKey($key)
	{
	    return md5($this->_prefix.$key);
	}
}

?>