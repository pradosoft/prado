<?php

/**
 * TRedisCache class file
 *
 * @author Jens Klaer <kj.landwehr.software@gmail.com>
 * @author LANDWEHR Computer und Software GmbH
 * @link https://github.com/pradosoft/prado4
 * @license https://github.com/pradosoft/prado4/blob/master/LICENSE
 */

namespace Prado\Caching;

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Prado;
use Prado\TPropertyValue;
use Prado\Util\Traits\TInitializedTrait;
use Prado\Xml\TXmlElement;

/**
 * TRedisCache class
 *
 * TRedisCache implements a cache application module based on {@see https://redis.io/ redis} key-value store.
 *
 * TRedisCache can be configured with the {@see setHost Host} and {@see setPort Port}
 * properties, which specify the host and port of the redis server to be used.
 * By default, they take the value 'localhost' and 6379, respectively.
 *
 * It is also possible to use a unix socket for connection, it can be set
 * using {@see setSocket}. Be sure that the socket is readable/writeable by
 * the webserver/php user. By default, this value is left empty. If both,
 * server/port and socket are set, the latter takes precedence.
 *
 * Use the {@see setIndex Index} property to change the database to the given
 * database index. Defaults to 0.
 *
 * The following basic cache operations are implemented:
 * - {@see get} : retrieve the value with a key (if any) from cache
 * - {@see set} : store the value with a key into cache
 * - {@see add} : store the value only if cache does not have this key
 * - {@see delete} : delete the value with the specified key from cache
 * - {@see flush} : delete all values from cache
 *
 * Each value is associated with an expiration time. The {@see get} operation
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
 * ```php
 * $cache=new TRedisCache;  // TRedisache may also be loaded as a Prado application module
 * $cache->init(null);
 * $cache->add('object',$object);
 * $object2=$cache->get('object');
 * ```
 *
 * If loaded as module, TRedisCache will register itself with {@see \Prado\TApplication} as the
 * default cache module. It can be accessed via {@see \Prado\TApplication::getCache()}.
 *
 * XML configuration style, TCP:
 * ```xml
 * <modules>
 *   <module id="cache" class="Prado\Caching\TRedisCache" Host="localhost" Port="6379" />
 * </modules>
 * ```
 *
 * PHP configuration style, TCP:
 * ```php
 * return [
 *     'modules' => [
 *         'cache' => [
 *             'class' => 'Prado\Caching\TRedisCache',
 *             'properties' => [
 *                 'Host' => 'localhost',
 *                 'Port' => '6379',
 *             ],
 *         ],
 *     ],
 * ];
 * ```
 *
 * XML configuration style, Unix socket:
 * ```xml
 * <modules>
 *   <module id="cache" class="Prado\Caching\TRedisCache" Socket="/var/run/redis/redis.sock" Index="2" />
 * </modules>
 * ```
 *
 * PHP configuration style, Unix socket:
 * ```php
 * return [
 *     'modules' => [
 *         'cache' => [
 *             'class' => 'Prado\Caching\TRedisCache',
 *             'properties' => [
 *                 'Socket' => '/var/run/redis/redis.sock',
 *                 'Index' => '2',
 *             ],
 *         ],
 *     ],
 * ];
 * ```
 * where {@see setHost Host} and {@see setPort Port} or {@see setSocket Socket} are configurable
 * properties of TRedisCache.
 *
 * PHP configuration style:
 * ```php
 * return [
 *     'modules' => [
 *         'cache' => [
 *             'class' => 'Prado\Caching\TRedisCache',
 *             'properties' => ['Host' => 'localhost', 'Port' => '6379'],
 *         ],
 *     ],
 * ];
 * ```
 *
 * @author Jens Klaer <kj.landwehr.software@gmail.com>
 * @author LANDWEHR Computer und Software GmbH
 * @since 4.0
 */
class TRedisCache extends TCache
{
	use TInitializedTrait;

	/**
	 * @var ?\Redis the Redis instance
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
	 * @var ?string the unix socket of the redis cache server, or null to use TCP.
	 */
	private $_socket;
	/**
	 * @var int the database index to use within the redis server.
	 */
	private $_index = 0;

	/**
	 * @todo
	 */
	public static function getIsAvailable(): bool
	{
		return extension_loaded('redis') && class_exists('\Redis', false);
	}

	/**
	 * Destructor.
	 * Disconnect the redis cache server.
	 */
	public function __destruct()
	{
		$cacheObject = $this->getCacheDirect();
		if ($cacheObject instanceof \Redis) {
			$cacheObject->close();
		}
		parent::__destruct();
	}

	/**
	 * Initializes this module.
	 * This method is required by the IModule interface. It creates a Redis instance and connects to the redis server.
	 * @param \Prado\Xml\TXmlElement $config configuration for this module, can be null
	 * @throws TConfigurationException if php-redis extension is not installed or redis cache sever connection fails
	 */
	public function init($config)
	{
		if (!static::getIsAvailable()) {
			throw new TConfigurationException('rediscache_extension_required');
		}
		$this->setCacheDirect($this->newRedis());
		$cacheObject = $this->getCacheDirect();
		if ($this->_socket !== null) {
			$cacheObject->connect($this->getSocket());
		} else {
			$cacheObject->connect($this->getHost(), $this->getPort());
		}
		$cacheObject->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_PHP);
		$cacheObject->select($this->getIndex());
		parent::init($config);
		$this->markInitialized();
	}

	/**
	 * Creates the Redis instance.
	 * Override in a subclass to substitute a mock or alternative implementation.
	 * @return \Redis the new Redis instance
	 * @since 4.3.3
	 */
	protected function newRedis(): object
	{
		return new \Redis();
	}

	/**
	 * @return ?\Redis the underlying Redis instance, or null before initialization
	 * @since 4.3.3
	 */
	protected function getCacheDirect(): ?object
	{
		return $this->_cache;
	}

	/**
	 * @param ?\Redis $value the underlying Redis instance
	 * @since 4.3.3
	 */
	protected function setCacheDirect(?object $value): void
	{
		$this->_cache = $value;
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
		$this->assertUninitialized('Host');
		$this->_host = $value;
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
		$this->assertUninitialized('Port');
		$this->_port = TPropertyValue::ensureInteger($value);
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
		$this->assertUninitialized('Socket');
		$this->_socket = TPropertyValue::ensureString($value);
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
		$this->assertUninitialized('Index');
		$this->_index = TPropertyValue::ensureInteger($value);
	}

	/**
	 * @param string $key a unique key identifying the cached value
	 * @return mixed the stored value on a hit, or `false` on a miss or expiry.
	 */
	protected function getValue($key)
	{
		return $this->getCacheDirect()->get($key);
	}

	/**
	 * @param string $key the key identifying the value to be cached
	 * @param mixed $value the value to be cached
	 * @param int $expire the number of seconds in which the cached value will expire. 0 means never expire.
	 * @return bool true if the value is successfully stored into cache, false otherwise
	 */
	protected function setValue($key, $value, $expire)
	{
		$options = $expire === 0 ? [] : ['ex' => $expire];
		return $this->getCacheDirect()->set($key, $value, $options);
	}

	/**
	 * @param string $key the key identifying the value to be cached
	 * @param mixed $value the value to be cached
	 * @param int $expire the number of seconds in which the cached value will expire. 0 means never expire.
	 * @return bool true if the value is successfully stored into cache, false otherwise
	 */
	protected function addValue($key, $value, $expire)
	{
		$options = $expire === 0 ? ['nx'] : ['nx', 'ex' => $expire];
		return $this->getCacheDirect()->set($key, $value, $options);
	}

	/**
	 * @param string $key the key of the value to be deleted
	 * @return bool true if no error happens during deletion
	 */
	protected function deleteValue($key)
	{
		$this->getCacheDirect()->delete($key);
		return true;
	}

	/**
	 * Flushes the currently selected Redis database only.
	 */
	public function flush()
	{
		return $this->getCacheDirect()->flushDB();
	}
}
