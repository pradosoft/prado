<?php

/**
 * TEtcdCache class file
 *
 * @author LANDWEHR Computer und Software GmbH <programmierung@landwehr-software.de>
 * @link https://github.com/pradosoft/prado4
 * @license https://github.com/pradosoft/prado4/blob/master/LICENSE
 */

namespace Prado\Caching;

use Prado\TPropertyValue;
use Prado\Exceptions\TConfigurationException;

/**
 * TEtcdCache class
 *
 * TEtcdCache implements a cache application module based on the distributed,
 * consistent key-value store {@see https://github.com/coreos/etcd etcd}.
 * etcd is high performance key-value store written in Go which uses the Raft
 * consensus algorithm to manage a highly-available replicated log.
 *
 * By definition, cache does not ensure the existence of a value
 * even if it never expires. Cache is not meant to be a persistent storage.
 *
 * To use this module, an etcd instance must be running and reachable on the host
 * specified by {@see setHost} and the port specified by {@see setPort} which
 * default to 'localhost:2379'. All values are stored within a directory set by
 * {@see setDir} which defaults to 'pradocache'.
 *
 * TEtcdCache only supports etcd API v2 and uses cURL to fire the HTTP
 * GET/PUT/DELETE commands, thus the PHP cURL extension is also needed.
 *
 * Some usage examples of TEtcdCache are as follows,
 * ```php
 * $cache = new TEtcdCache(); // TEtcdCache may also be loaded as a Prado application module
 * $cache->init(null);
 * $cache->add('value', $value);
 * $value = $cache->get('value');
 * ```
 *
 * If loaded, TEtcdCache will register itself with {@see \Prado\TApplication} as the
 * cache module. It can be accessed via {@see \Prado\TApplication::getCache()}.
 *
 * XML configuration style:
 * ```xml
 * <modules>
 *   <module id="cache" class="Prado\Caching\TEtcdCache" Host="localhost" Port="2379" Dir="pradocache" />
 * </modules>
 * ```
 *
 * PHP configuration style:
 * ```php
 * return [
 *     'modules' => [
 *         'cache' => [
 *             'class' => 'Prado\Caching\TEtcdCache',
 *             'properties' => [
 *                 'Host' => 'localhost',
 *                 'Port' => '2379',
 *                 'Dir' => 'pradocache',
 *             ],
 *         ],
 *     ],
 * ];
 * ```
 *
 * @author LANDWEHR Computer und Software GmbH <programmierung@landwehr-software.de>
 * @since 4.0
 */
class TEtcdCache extends TSerializingCache
{
	/**
	 * @var string the etcd host
	 */
	private $_host = 'localhost';

	/**
	 * @var int the etcd port
	 */
	private $_port = 2379;

	/**
	 * @var string the directory to store values in
	 */
	private $_dir = 'pradocache';

	/**
	 * @return bool whether the cURL extension is loaded.
	 */
	public static function getIsAvailable(): bool
	{
		return function_exists('curl_version');
	}

	/**
	 * Initializes this module.
	 * This method is required by the IModule interface.
	 * @param \Prado\Xml\TXmlElement $config configuration for this module, can be null
	 * @throws TConfigurationException if cURL extension is not installed
	 */
	public function init($config)
	{
		if (!static::getIsAvailable()) {
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
		$this->assertUninitialized('Host');
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
		$this->assertUninitialized('Port');
		$this->_port = TPropertyValue::ensureInteger($value);
	}

	/**
	 * @return string the directory to store values in. Defaults to 'pradocache'.
	 */
	public function getDir()
	{
		return $this->_dir;
	}

	/**
	 * @param string $value the directory to store values in
	 */
	public function setDir($value)
	{
		$this->assertUninitialized('Dir');
		$this->_dir = TPropertyValue::ensureString($value);
	}

	/**
	 * @param string $key a unique key identifying the cached value
	 * @return false|string the serialized value on a hit, or `false` on a miss or expiry.
	 */
	protected function getSerializedValue(string $key): false|string
	{
		$result = $this->request('GET', $this->getDir() . '/' . $key);
		return property_exists($result, 'errorCode') ? false : $result->node->value;
	}

	/**
	 * @param string $key the key identifying the value to be cached
	 * @param string $value the serialized value to store
	 * @param int $expire the number of seconds in which the cached value will expire. 0 means never expire.
	 * @return bool true if the value is successfully stored into cache, false otherwise
	 */
	protected function setSerializedValue(string $key, string $value, int $expire): bool
	{
		$params = ['value' => $value];
		if ($expire > 0) {
			$params['ttl'] = $expire;
		}
		$result = $this->request('PUT', $this->getDir() . '/' . $key, $params);
		return !property_exists($result, 'errorCode');
	}

	/**
	 * @param string $key the key identifying the value to be cached
	 * @param string $value the serialized value to store
	 * @param int $expire the number of seconds in which the cached value will expire. 0 means never expire.
	 * @return bool true if the value is successfully stored into cache, false otherwise
	 */
	protected function addSerializedValue(string $key, string $value, int $expire): bool
	{
		$params = ['value' => $value, 'prevExist' => 'false'];
		if ($expire > 0) {
			$params['ttl'] = $expire;
		}
		$result = $this->request('PUT', $this->getDir() . '/' . $key, $params);
		return !property_exists($result, 'errorCode');
	}

	/**
	 * @param string $key the key of the value to be deleted
	 * @return bool true if no error happens during deletion
	 */
	protected function deleteValue($key)
	{
		$this->request('DELETE', $this->getDir() . '/' . $key);
		return true;
	}

	/**
	 * Deletes all values from cache.
	 */
	public function flush()
	{
		$this->request('DELETE', $this->getDir() . '?recursive=true');
		return true;
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
		$curl = curl_init("http://{$this->getHost()}:{$this->getPort()}/v2/keys/{$key}");
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
