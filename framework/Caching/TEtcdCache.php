<?php
/**
 * TEtcdCache class file
 *
 * @author LANDWEHR Computer und Software GmbH <programmierung@landwehr-software.de>
 * @link https://github.com/pradosoft/prado4
 * @license https://github.com/pradosoft/prado4/blob/master/LICENSE
 * @package Prado\Caching
 */

namespace Prado\Caching;

use Prado\TPropertyValue;
use Prado\Exceptions\TConfigurationException;

/**
 * TEtcdCache class
 *
 * TEtcdCache implements a cache application module based on the distributed,
 * consistent key-value store {@link https://github.com/coreos/etcd etcd}.
 * etcd is high performance key-value store written in Go which uses the Raft
 * consensus algorithm to manage a highly-available replicated log.
 *
 * By definition, cache does not ensure the existence of a value
 * even if it never expires. Cache is not meant to be an persistent storage.
 *
 * To use this module, an etcd instance must be running and reachable on the host
 * specified by {@link setHost} and the port specified by {@link setPort} which
 * default to 'localhost:2379'. All values are stored within a directory set by
 * {@link setDir} which defaults to 'pradocache'.
 *
 * TEtcdCache only supports etcd API v2 and uses cURL to fire the HTTP
 * GET/PUT/DELETE commands, thus the PHP cURL extension is also needed.
 *
 * Some usage examples of TEtcdCache are as follows,
 * <code>
 * $cache = new TEtcdCache(); // TEtcdCache may also be loaded as a Prado application module
 * $cache->init(null);
 * $cache->add('value', $value);
 * $value = $cache->get('value');
 * </code>
 *
 * If loaded, TEtcdCache will register itself with {@link TApplication} as the
 * cache module. It can be accessed via {@link TApplication::getCache()}.
 *
 * TEtcdCache may be configured in application configuration file as follows
 * <code>
 * <module id="cache" class="Prado\Caching\TEtcdCache" Host="localhost" Port="2379" Dir="pradocache" />
 * </code>
 *
 * @author LANDWEHR Computer und Software GmbH <programmierung@landwehr-software.de>
 * @package Prado\Caching
 * @since 4.0
 */
class TEtcdCache extends TCache
{

  /**
   * @var string the etcd host
   */
	protected $_host = 'localhost';

	/**
	 * @var int the etcd port
	 */
	protected $_port = 2379;

	/**
	 * @var string the directory to store values in
	 */
	protected $_dir = 'pradocache';

	/**
	 * Initializes this module.
	 * This method is required by the IModule interface.
	 * @param TXmlElement $config configuration for this module, can be null
	 * @throws TConfigurationException if cURL extension is not installed
	 */
	public function init($config)
	{
		if (!function_exists('curl_version')) {
			throw new TConfigurationException('curl_extension_required');
		}
		parent::init($config);
	}

	/**
	 * Gets the host the etcd instance is running on, defaults to 'localhost'.
	 * @return string the etcd host
	 */
	public function getHost()
	{
		return $this->_host;
	}

	/**
	 * Sets the host the etcd instance is running on.
	 * @param string $value the etcd host
	 */
	public function setHost($value)
	{
		$this->_host = TPropertyValue::ensureString($value);
	}

	/**
	 * Gets the port the etcd instance is running on, defaults to 2379.
	 * @return int the etcd port
	 */
	public function getPort()
	{
		return $this->_port;
	}

	/**
	 * Sets the port the etcd instance is running on.
	 * @param int $value the etcd port
	 */
	public function setPort($value)
	{
		$this->_port = TPropertyValue::ensureInteger($value);
	}

	/**
	 * Sets the directory to store values in, defaults to 'pradocache'.
	 * @return string the directory to store values in
	 */
	public function getDir()
	{
		return $this->_dir;
	}

	/**
	 * Gets the directory to store values in.
	 * @param string $value the directory to store values in
	 */
	public function setDir($value)
	{
		$this->_dir = TPropertyValue::ensureString($value);
	}

	/**
	 * Retrieves a value from cache with a specified key.
	 * This is the implementation of the method declared in the parent class.
	 * @param string $key a unique key identifying the cached value
	 * @return string the value stored in cache, false if the value is not in the cache or expired.
	 */
	protected function getValue($key)
	{
		$result = $this->request('GET', $this->_dir . '/' . $key);
		return property_exists($result, 'errorCode') ? false : unserialize($result->node->value);
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
		$value = ['value' => serialize($value)];
		if ($expire > 0) {
			$value['ttl'] = $expire;
		}
		$result = $this->request('PUT', $this->_dir . '/' . $key, $value);
		return !property_exists($result, 'errorCode');
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
		$value = ['value' => serialize($value), 'prevExist' => 'false'];
		if ($expire > 0) {
			$value['ttl'] = $expire;
		}
		$result = $this->request('PUT', $this->_dir . '/' . $key, $value);
		return !property_exists($result, 'errorCode');
	}

	/**
	 * Deletes a value with the specified key from cache
	 * This is the implementation of the method declared in the parent class.
	 * @param string $key the key of the value to be deleted
	 * @return bool if no error happens during deletion
	 */
	protected function deleteValue($key)
	{
		$this->request('DELETE', $this->_dir . '/' . $key);
		return true;
	}

	/**
	 * Deletes all values from cache.
	 * Be careful of performing this operation if the cache is shared by multiple applications.
	 */
	public function flush()
	{
		$this->request('DELETE', $this->_dir . '?recursive=true');
	}

	/**
	 * This method does the actual cURL request by generating the method specific
	 * URL, setting the cURL options and adding additional request parameters.
	 * The etcd always returns a JSON string which is decoded and returned to
	 * the calling method.
	 * @param string $method the HTTP method for the request (GET,PUT,DELETE)
	 * @param string $key the the key to perform the action on (includes the directory)
	 * @param array $value the additional post data to send with the request
	 * @return \stdClass the response from the etcd instance
	 */
	protected function request($method, $key, $value = [])
	{
		$curl = curl_init("http://{$this->_host}:{$this->_port}/v2/keys/{$key}");
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
		curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
		curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($value));
		$response = curl_exec($curl);
		curl_close($curl);
		return json_decode($response);
	}
}
