<?php
/**
 * TRedisCache class file
 *
 * @author Jens Klaer <kj.landwehr.software@gmail.com>
 * @author LANDWEHR Computer und Software GmbH
 * @link https://github.com/pradosoft/prado4
 * @license https://github.com/pradosoft/prado4/blob/master/LICENSE
 * @package Prado\Caching
 */

namespace Prado\Caching;

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Prado;
use Prado\TPropertyValue;
use Prado\Xml\TXmlElement;

/**
 * TRedisCache class
 *
 * TRedisCache implements a cache application module based on {@link https://redis.io/ redis} key-value store.
 *
 * TRedisCache can be configured with the {@link setHost Host} and {@link setPort Port}
 * properties, which specify the host and port of the redis server to be used.
 * By default, they take the value 'localhost' and 6379, respectively.
 *
 * It is also possible to use a unix socket for connection, it can be set
 * using {@link setSocket}. Be sure that the socket is readable/writeable by
 * the webserver/php user. By default, this value is left empty. If both,
 * server/port and socket are set, the latter takes precedence.
 *
 * Use the {@link setIndex Index} property to change the database to the given
 * database index. Defaults to 0.
 *
 * The following basic cache operations are implemented:
 * - {@link get} : retrieve the value with a key (if any) from cache
 * - {@link set} : store the value with a key into cache
 * - {@link add} : store the value only if cache does not have this key
 * - {@link delete} : delete the value with the specified key from cache
 * - {@link flush} : delete all values from cache
 *
 * Each value is associated with an expiration time. The {@link get} operation
 * ensures that any expired value will not be returned. The expiration time can
 * be specified by the number of seconds. A expiration time 0 represents never expire.
 *
 * By definition, cache does not ensure the existence of a value
 * even if it never expires. Cache is not meant to be an persistent storage.
 *
 * Also note, there is no security measure to protected data in redis cache.
 * All data in redis cache can be accessed by any process running in the system.
 *
 * To use this module, the php-redis extension must be loaded.
 *
 * Some usage examples of TRedisCache are as follows,
 * <code>
 * $cache=new TRedisCache;  // TRedisache may also be loaded as a Prado application module
 * $cache->init(null);
 * $cache->add('object',$object);
 * $object2=$cache->get('object');
 * </code>
 *
 * If loaded as module, TRedisCache will register itself with {@link TApplication} as the
 * default cache module. It can be accessed via {@link TApplication::getCache()}.
 *
 * TRedisCache may be configured in application configuration file as follows
 * <code>
 * <module id="cache" class="Prado\Caching\TRedisCache" Host="localhost" Port="6379" />
 * </code>
 * or
 * <code>
 * <module id="cache" class="Prado\Caching\TRedisCache" Socket="var/run/redis/redis.sock" Index="2" />
 * </code>
 * where {@link setHost Host} and {@link setPort Port} or {@link setSocket Socket} are configurable properties
 * of TRedisCache.
 *
 * @author Jens Klaer <kj.landwehr.software@gmail.com>
 * @author LANDWEHR Computer und Software GmbH
 * @since 4.0
 * @package Prado\Caching
 */

class TRedisCache extends TCache
{
	/**
	 * @var bool if the module is initialized
	 */
	private $_initialized = false;
	/**
	 * @var \Redis the Redis instance
	 */
	private $_cache;
	/**
	 * @var string host name of the redis cache server
	 */
	private $_host = 'localhost';
	/**
	 * @var int the port number of the redis cache server
	 */
	private $_port = 6379;
	/**
	 * @var string the unix socket of the redis cache server.
	 */
	private $_socket;
	/**
	 * @var int the database index to use within the redis server.
	 */
	private $_index = 0;

	/**
	 * Destructor.
	 * Disconnect the redis cache server.
	 */
	public function __destruct()
	{
		if ($this->_cache instanceof \Redis) {
			$this->_cache->close();
		}
	}

	/**
	 * Initializes this module.
	 * This method is required by the IModule interface. It creates a Redis instance and connects to the redis server.
	 * @param TXmlElement $config configuration for this module, can be null
	 * @throws TConfigurationException if php-redis extension is not installed or redis cache sever connection fails
	 */
	public function init($config)
	{
		if (!extension_loaded('redis') || !class_exists('\Redis', false)) {
			throw new TConfigurationException('rediscache_extension_required');
		}
		$this->_cache = new \Redis();
		if ($this->_socket !== null) {
			$this->_cache->connect($this->_socket);
		} else {
			$this->_cache->connect($this->_host, $this->_port);
		}
		$this->_cache->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_PHP);
		$this->_cache->select($this->_index);
		parent::init($config);
		$this->_initialized = true;
	}

	public function valid($key)
	{
		return true;
	}

	/**
	 * @return string the host name of the redis cache server
	 */
	public function getHost()
	{
		return $this->_host;
	}

	/**
	 * @param string $value the host name of the redis cache server
	 * @throws TInvalidOperationException if the module is already initialized
	 */
	public function setHost($value)
	{
		if ($this->_initialized) {
			throw new TInvalidOperationException('rediscache_host_unchangeable');
		} else {
			$this->_host = $value;
		}
	}

	/**
	 * @return int the port number of the redis cache server
	 */
	public function getPort()
	{
		return $this->_port;
	}

	/**
	 * @param int $value the port number of the redis cache server
	 * @throws TInvalidOperationException if the module is already initialized
	 */
	public function setPort($value)
	{
		if ($this->_initialized) {
			throw new TInvalidOperationException('rediscache_port_unchangeable');
		} else {
			$this->_port = TPropertyValue::ensureInteger($value);
		}
	}

	/**
	 * @return string the unix socket of the redis cache server
	 */
	public function getSocket()
	{
		return $this->_socket;
	}

	/**
	 * @param string $value the unix socket of the redis cache server
	 * @throws TInvalidOperationException if the module is already initialized
	 */
	public function setSocket($value)
	{
		if ($this->_initialized) {
			throw new TInvalidOperationException('rediscache_socket_unchangeable');
		} else {
			$this->_socket = TPropertyValue::ensureString($value);
		}
	}

	/**
	 * @return int the database index to use. Defaults to 0.
	 */
	public function getIndex()
	{
		return $this->_index;
	}

	/**
	 * @param int $value the database index to use.
	 * @throws TInvalidOperationException if the module is already initialized
	 */
	public function setIndex($value)
	{
		if ($this->_initialized) {
			throw new TInvalidOperationException('rediscache_index_unchangeable');
		} else {
			$this->_index = TPropertyValue::ensureInteger($value);
		}
	}

	/**
	 * Retrieves a value from cache with a specified key.
	 * This is the implementation of the method declared in the parent class.
	 * @param string $key a unique key identifying the cached value
	 * @return string the value stored in cache, false if the value is not in the cache or expired.
	 */
	protected function getValue($key)
	{
		return $this->_cache->get($key);
	}

	/**
	 * Stores a value identified by a key in cache.
	 * This is the implementation of the method declared in the parent class.
	 *
	 * @param string $key the key identifying the value to be cached
	 * @param string $value the value to be cached
	 * @param int $expire the number of seconds in which the cached value will expire. 0 means never expire.
	 * @return bool true if the value is successfully stored into cache, false otherwise
	 */
	protected function setValue($key, $value, $expire)
	{
		$options = $expire === 0 ? [] : ['ex' => $expire];
		return $this->_cache->set($key, $value, $options);
	}

	/**
	 * Stores a value identified by a key into cache if the cache does not contain this key.
	 * This is the implementation of the method declared in the parent class.
	 *
	 * @param string $key the key identifying the value to be cached
	 * @param string $value the value to be cached
	 * @param int $expire the number of seconds in which the cached value will expire. 0 means never expire.
	 * @return bool true if the value is successfully stored into cache, false otherwise
	 */
	protected function addValue($key, $value, $expire)
	{
		$options = $expire === 0 ? ['nx'] : ['nx', 'ex' => $expire];
		return $this->_cache->set($key, $value, $options);
	}

	/**
	 * Deletes a value with the specified key from cache
	 * This is the implementation of the method declared in the parent class.
	 * @param string $key the key of the value to be deleted
	 * @return bool if no error happens during deletion
	 */
	protected function deleteValue($key)
	{
		$this->_cache->delete($key);
		return true;
	}

	/**
	 * Deletes all values from cache, only clearing the currently selected database.
	 */
	public function flush()
	{
		return $this->_cache->flushDB();
	}
}
