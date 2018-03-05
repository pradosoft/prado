<?php
/**
 * TMemCache class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author Carl G. Mathisen <carlgmathisen@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @copyright Copyright &copy; 2005-2016 The PRADO Group
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Caching
 */

namespace Prado\Caching;

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Prado;
use Prado\TPropertyValue;
use Prado\Xml\TXmlElement;

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
 * You can configure TMemCache two different ways. If you only need one memcache server
 * you may use the method as follows.
 * <code>
 * <module id="cache" class="System.Caching.TMemCache" Host="localhost" Port="11211" />
 * </code>
 *
 * If you want a more complex configuration, you may use the method as follows.
 * <code>
 * <module id="cache" classs="System.Caching.TMemCache">
 *     <server Host="localhost" Port="11211" Weight="1" Timeout="300" RetryInterval="15" />
 *     <server Host="anotherhost" Port="11211" Weight="1" Timeout="300" RetryInterval="15" />
 * </module>
 * </code>
 *
 * If loaded, TMemCache will register itself with {@link TApplication} as the
 * cache module. It can be accessed via {@link TApplication::getCache()}.
 *
 * TMemCache may be configured in application configuration file as follows
 * <code>
 * <module id="cache" class="System.Caching.TMemCache" Host="localhost" Port="11211" />
 * </code>
 * where {@link getHost Host} and {@link getPort Port} are configurable properties
 * of TMemCache.
 *
 * Automatic compression of values may be used (using zlib extension) by setting {@link getThreshold Threshold} and {@link getMinSavings MinSavings} properties.
 * NB : MemCache server(s) must be restarted to apply settings. Require (PECL memcache >= 2.0.0) or memcached if {@link useMemcached} is true.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Caching
 * @since 3.0
 */
class TMemCache extends TCache
{
	/**
	 * @var boolean if the module is initialized
	 */
	private $_initialized = false;
	/**
	 * @var Memcache the Memcache instance
	 */
	private $_cache;
	/**
	 * @var string host name of the memcache server
	 */
	private $_host = 'localhost';
	/**
	 * @var integer the port number of the memcache server
	 */
	private $_port = 11211;

	private $_timeout = 360;

	/**
	 * @var integer Controls the minimum value length before attempting to compress automatically.
	 */
	private $_threshold = 0;

	/**
	 * @var float Specifies the minimum amount of savings to actually store the value compressed. The supplied value must be between 0 and 1. Default value is 0.2 giving a minimum 20% compression savings.
	 */
	private $_minSavings = 0.0;

	/**
	 * @var boolean whether to use memcached or memcache as the underlying caching extension.
	 * If true {@link http://pecl.php.net/package/memcached memcached} will be used.
	 * If false {@link http://pecl.php.net/package/memcache memcache}. will be used.
	 * Defaults to false.
	 */
	private $_useMemcached = false;

	/**
	 * @var array list of servers available
	 */
	private $_servers = [];

	/**
	 * Destructor.
	 * Disconnect the memcache server.
	 */
	public function __destruct()
	{
		if ($this->_cache !== null && !$this->_useMemcached) {
			$this->_cache->close();
		}
	}

	/**
	 * Initializes this module.
	 * This method is required by the IModule interface. It makes sure that
	 * UniquePrefix has been set, creates a Memcache instance and connects
	 * to the memcache server.
	 * @param TApplication Prado application, can be null
	 * @param TXmlElement $config configuration for this module, can be null
	 * @throws TConfigurationException if memcache extension is not installed or memcache sever connection fails
	 */
	public function init($config)
	{
		if (!extension_loaded('memcache') && !$this->_useMemcached) {
			throw new TConfigurationException('memcache_extension_required');
		}
		if (!extension_loaded('memcached') && $this->_useMemcached) {
			throw new TConfigurationException('memcached_extension_required');
		}

		$this->_cache = $this->_useMemcached ? new Memcached : new Memcache;
		$this->loadConfig($config);
		if (count($this->_servers)) {
			foreach ($this->_servers as $server) {
				Prado::trace('Adding server ' . $server['Host'] . ' from serverlist', '\Prado\Caching\TMemCache');
				if ($this->_cache->addServer(
					$server['Host'],
					$server['Port'],
					$server['Persistent'],
					$server['Weight'],
					$server['Timeout'],
					$server['RetryInterval']
				) === false) {
					throw new TConfigurationException('memcache_connection_failed', $server['Host'], $server['Port']);
				}
			}
		} else {
			Prado::trace('Adding server ' . $this->_host, '\Prado\Caching\TMemCache');
			if ($this->_cache->addServer($this->_host, $this->_port) === false) {
				throw new TConfigurationException('memcache_connection_failed', $this->_host, $this->_port);
			}
		}
		if ($this->_threshold !== 0) {
			$this->_cache->setCompressThreshold($this->_threshold, $this->_minSavings);
		}
		$this->_initialized = true;
		parent::init($config);
	}

	/**
	 * Loads configuration from an XML element
	 * @param TXmlElement $xml configuration node
	 * @throws TConfigurationException if log route class or type is not specified
	 */
	private function loadConfig($xml)
	{
		if ($xml instanceof TXmlElement) {
			foreach ($xml->getElementsByTagName('server') as $serverConfig) {
				$properties = $serverConfig->getAttributes();
				if (($host = $properties->remove('Host')) === null) {
					throw new TConfigurationException('memcache_serverhost_required');
				}
				if (($port = $properties->remove('Port')) === null) {
					throw new TConfigurationException('memcache_serverport_required');
				}
				if (!is_numeric($port)) {
					throw new TConfigurationException('memcache_serverport_invalid');
				}
				$server = ['Host' => $host, 'Port' => $port, 'Weight' => 1, 'Timeout' => 1800, 'RetryInterval' => 15, 'Persistent' => true];
				$checks = [
					'Weight' => 'memcache_serverweight_invalid',
					'Timeout' => 'memcache_servertimeout_invalid',
					'RetryInterval' => 'memcach_serverretryinterval_invalid'
				];
				foreach ($checks as $property => $exception) {
					$value = $properties->remove($property);
					if ($value !== null && is_numeric($value)) {
						$server[$property] = $value;
					} elseif ($value !== null) {
						throw new TConfigurationException($exception);
					}
				}
				$server['Persistent'] = TPropertyValue::ensureBoolean($properties->remove('Persistent'));
				$this->_servers[] = $server;
			}
		}
	}

	/**
	 * @return string host name of the memcache server
	 */
	public function getHost()
	{
		return $this->_host;
	}

	/**
	 * @param string $value host name of the memcache server
	 * @throws TInvalidOperationException if the module is already initialized
	 */
	public function setHost($value)
	{
		if ($this->_initialized) {
			throw new TInvalidOperationException('memcache_host_unchangeable');
		} else {
			$this->_host = $value;
		}
	}

	/**
	 * @return integer port number of the memcache server
	 */
	public function getPort()
	{
		return $this->_port;
	}

	/**
	 * @param integer $value port number of the memcache server
	 * @throws TInvalidOperationException if the module is already initialized
	 */
	public function setPort($value)
	{
		if ($this->_initialized) {
			throw new TInvalidOperationException('memcache_port_unchangeable');
		} else {
			$this->_port = TPropertyValue::ensureInteger($value);
		}
	}

	/**
	 * @return boolean if memcached instead memcache
	 */
	public function getUseMemcached()
	{
		return $this->_useMemcached;
	}

	/**
	 * @param string $value if memcached instead memcache
	 * @throws TInvalidOperationException if the module is already initialized
	 */
	public function setUseMemcached($value)
	{
		if ($this->_initialized) {
			throw new TInvalidOperationException('memcache_host_unchangeable');
		} else {
			$this->_useMemcached = $value;
		}
	}

	/**
	 * @return integer minimum value length before attempting to compress
	 */
	public function getThreshold()
	{
		return $this->_threshold;
	}

	/**
	 * @param integer $value minimum value length before attempting to compress
	 * @throws TInvalidOperationException if the module is already initialized
	 */
	public function setThreshold($value)
	{
		if ($this->_initialized) {
			throw new TInvalidOperationException('memcache_threshold_unchangeable');
		} else {
			$this->_threshold = TPropertyValue::ensureInteger($value);
		}
	}

	/**
	 * @return float minimum amount of savings to actually store the value compressed
	 */
	public function getMinSavings()
	{
		return $this->_minSavings;
	}

	/**
	 * @param float $value minimum amount of savings to actually store the value compressed
	 * @throws TInvalidOperationException if the module is already initialized
	 */
	public function setMinSavings($value)
	{
		if ($this->_initialized) {
			throw new TInvalidOperationException('memcache_min_savings_unchangeable');
		} else {
			$this->_minSavings = TPropertyValue::ensureFloat($value);
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
	 * @param integer $expire the number of seconds in which the cached value will expire. 0 means never expire.
	 * @return boolean true if the value is successfully stored into cache, false otherwise
	 */
	protected function setValue($key, $value, $expire)
	{
		if ($this->_useMemcached) {
			return $this->_cache->set($key, $value, $expire);
		} else {
			return $this->_cache->set($key, $value, 0, $expire);
		}
	}

	/**
	 * Stores a value identified by a key into cache if the cache does not contain this key.
	 * This is the implementation of the method declared in the parent class.
	 *
	 * @param string $key the key identifying the value to be cached
	 * @param string $value the value to be cached
	 * @param integer $expire the number of seconds in which the cached value will expire. 0 means never expire.
	 * @return boolean true if the value is successfully stored into cache, false otherwise
	 */
	protected function addValue($key, $value, $expire)
	{
		if ($this->_useMemcached) {
			$this->_cache->add($key, $value, $expire);
		} else {
			return $this->_cache->add($key, $value, 0, $expire);
		}
	}

	/**
	 * Deletes a value with the specified key from cache
	 * This is the implementation of the method declared in the parent class.
	 * @param string $key the key of the value to be deleted
	 * @return boolean if no error happens during deletion
	 */
	protected function deleteValue($key)
	{
		return $this->_cache->delete($key);
	}

	/**
	 * Deletes all values from cache.
	 * Be careful of performing this operation if the cache is shared by multiple applications.
	 */
	public function flush()
	{
		return $this->_cache->flush();
	}
}
