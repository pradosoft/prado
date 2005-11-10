<?php
/**
 * TMemCache class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System.Data
 */

/**
 * TMemCache class
 *
 * TMemCache implements a cache application module based on {@link http://www.danga.com/memcached/ memcached}.
 *
 * TMemCache can be configured with the Host and Port properties, which
 * specify the host and port of the memcache server to be used.
 * By default, they take the value 'localhost' and 11211, respectively.
 * These properties must be set before {@link init} is invoked.
 *
 * The following basic cache operations are implemented:
 * - {@link get} : retrieve the value with a key (if any) from cache
 * - {@link set} : store the value with a key into cache
 * - {@link add} : store the value only if cache does not have this key
 * - {@link replace} : store the value only if cache has this key
 * - {@link delete} : delete the value with the specified key from cache
 * - {@link flush} : delete all values from cache
 *
 * Each value is associated with an expiration time. The {@link get} operation
 * ensures that any expired value will not be returned. The expiration time can
 * be specified by the number of seconds (maximum 60*60*24*30)
 * or a UNIX timestamp. A expiration time 0 represents never expire.
 *
 * By definition, cache does not ensure the existence of a value
 * even if it never expires. Cache is not meant to be an persistent storage.
 *
 * Also note, there is no security measure to protected data in memcache.
 * All data in memcache can be accessed by any process running in the system.
 *
 * To use this module, the memcache PHP extension must be loaded.
 *
 * Some usage examples of TMemCache are as follows,
 * <code>
 * $cache=new TMemCache;  // TMemCache may also be loaded as a Prado application module
 * $cache->init(null);
 * $cache->add('object',$object);
 * $object2=$cache->get('object');
 * </code>
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Data
 * @since 3.0
 */
class TMemCache extends TComponent implements IModule, ICache
{
	/**
	 * @var boolean if the module is initialized
	 */
	private $_initialized=false;
	/**
	 * @var Memcache the Memcache instance
	 */
	private $_cache=null;
	/**
	 * @var string a unique prefix used to identify this cache instance from the others
	 */
	private $_prefix=null;
	/**
	 * @var string host name of the memcache server
	 */
	private $_host='localhost';
	/**
	 * @var integer the port number of the memcache server
	 */
	private $_port=11211;
	/**
	 * @var string ID of this module
	 */
	private $_id='';

	/**
	 * Destructor.
	 * Disconnect the memcache server.
	 */
	public function __destruct()
	{
		if($this->_cache!==null)
			$this->_cache->close();
		parent::__destruct();
	}

	/**
	 * Initializes this module.
	 * This method is required by the IModule interface. It makes sure that
	 * UniquePrefix has been set, creates a Memcache instance and connects
	 * to the memcache server.
	 * @param IApplication Prado application, can be null
	 * @param TXmlElement configuration for this module, can be null
	 * @throws TConfigurationException if memcache extension is not installed or memcache sever connection fails
	 */
	public function init($application,$config)
	{
		if(!extension_loaded('memcache'))
			throw new TConfigurationException('memcache_extension_required');
		$this->_cache=new Memcache;
		if($this->_cache->connect($this->_host,$this->_port)===false)
			throw new TInvalidConfigurationException('memcache_connection_failed');
		if($application instanceof IApplication)
			$this->_prefix=$application->getUniqueID();
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
	 * Retrieves a value from cache with a specified key.
	 * @return mixed the value stored in cache, false if the value is not in the cache or expired.
	 */
	public function get($key)
	{
		return $this->_cache->get($this->generateUniqueKey($key));
	}

	/**
	 * Stores a value identified by a key into cache.
	 * If the cache already contains such a key, the existing value and
	 * expiration time will be replaced with the new ones.
	 *
	 * Note, avoid using this method whenever possible. Database insertion is
	 * very expensive. Try using {@link add} instead, which will not store the value
	 * if the key is already in cache.
	 *
	 * @param string the key identifying the value to be cached
	 * @param mixed the value to be cached
	 * @param integer the expiration time of the value,
	 *        0 means never expire,
	 *        a number less or equal than 60*60*24*30 means the number of seconds that the value will remain valid.
	 *        a number greater than 60 means a UNIX timestamp after which the value will expire.
	 * @return boolean true if the value is successfully stored into cache, false otherwise
	 */
	public function set($key,$value,$expire=0)
	{
		return $this->_cache->set($this->generateUniqueKey($key),$value,0,$expire);
	}

	/**
	 * Stores a value identified by a key into cache if the cache does not contain this key.
	 * Nothing will be done if the cache already contains the key.
	 * @param string the key identifying the value to be cached
	 * @param mixed the value to be cached
	 * @param integer the expiration time of the value,
	 *        0 means never expire,
	 *        a number less or equal than 60*60*24*30 means the number of seconds that the value will remain valid.
	 *        a number greater than 60 means a UNIX timestamp after which the value will expire.
	 * @return boolean true if the value is successfully stored into cache, false otherwise
	 */
	public function add($key,$value,$expiry=0)
	{
		return $this->_cache->add($this->generateUniqueKey($key),$value,0,$expire);
	}

	/**
	 * Stores a value identified by a key into cache only if the cache contains this key.
	 * The existing value and expiration time will be overwritten with the new ones.
	 * @param string the key identifying the value to be cached
	 * @param mixed the value to be cached
	 * @param integer the expiration time of the value,
	 *        0 means never expire,
	 *        a number less or equal than 60*60*24*30 means the number of seconds that the value will remain valid.
	 *        a number greater than 60 means a UNIX timestamp after which the value will expire.
	 * @return boolean true if the value is successfully stored into cache, false otherwise
	 */
	public function replace($key,$value,$expiry=0)
	{
		return $this->_cache->replace($this->generateUniqueKey($key),$value,0,$expire);
	}

	/**
	 * Deletes a value with the specified key from cache
	 * @param string the key of the value to be deleted
	 * @return boolean if no error happens during deletion
	 */
	public function delete($key)
	{
		return $this->_cache->delete($this->generateUniqueKey($key));
	}

	/**
	 * Deletes all values from cache.
	 * Be careful of performing this operation if the cache is shared by multiple applications.
	 */
	public function flush()
	{
		return $this->_cache->flush();
	}

	/**
	 * Generates a unique key based on a given user key.
	 * This method generates a unique key with the memcache.
	 * The key is made unique by prefixing with a unique string that is supposed
	 * to be unique among applications using the same memcache.
	 * @param string user key
	 * @param string a unique key
	 */
	protected function generateUniqueKey($key)
	{
		return md5($this->_prefix.$key);
	}
}

?>