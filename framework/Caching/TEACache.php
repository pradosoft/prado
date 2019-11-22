<?php
/**
 * TEACache class file
 *
 * @author Dario rigolin <drigolin@e-portaltech.it>
 * @link http://www.pradosoft.com/
 * @license http://www.pradosoft.com/license/
 * @package Prado\Caching
 */

namespace Prado\Caching;

use Prado\Exceptions\TConfigurationException;

/**
 * TEACache class
 *
 * TEACache implements a cache application module based on {@link http://eaccelerator.net/ eAccelerator}.
 *
 * By definition, cache does not ensure the existence of a value
 * even if it never expires. Cache is not meant to be an persistent storage.
 *
 * To use this module, the eAccelerator PHP extension must be loaded and enabled
 *
 * Please note that as of v0.9.6, eAccelerator no longer supports data caching.
 * This means if you still want to use this component, your eAccelerator should be of 0.9.5.x or lower version.
 *
 * Some usage examples of TEACache are as follows,
 * <code>
 * $cache=new TEACache;  // TEACache may also be loaded as a Prado application module
 * $cache->init(null);
 * $cache->add('object',$object);
 * $object2=$cache->get('object');
 * </code>
 *
 * If loaded, TEACache will register itself with {@link TApplication} as the
 * cache module. It can be accessed via {@link TApplication::getCache()}.
 *
 * TEACache may be configured in application configuration file as follows
 * <code>
 * <module id="cache" class="Prado\Caching\TEACache" />
 * </code>
 *
 * @author Dario Rigolin <drigolin@e-portaltech.it>
 * @package Prado\Caching
 * @since 3.2.2
 */
class TEACache extends TCache
{
	/**
	 * Initializes this module.
	 * This method is required by the IModule interface.
	 * @param TXmlElement $config configuration for this module, can be null
	 * @throws TConfigurationException if eaccelerator extension is not installed or not started, check your php.ini
	 */
	public function init($config)
	{
		if (!function_exists('eaccelerator_get')) {
			throw new TConfigurationException('eacceleratorcache_extension_required');
		}
		parent::init($config);
	}

	/**
	 * Retrieves a value from cache with a specified key.
	 * This is the implementation of the method declared in the parent class.
	 * @param string $key a unique key identifying the cached value
	 * @return string the value stored in cache, false if the value is not in the cache or expired.
	 */
	protected function getValue($key)
	{
		$value = eaccelerator_get($key);
		return ($value === null) ? false : $value;
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
		return eaccelerator_put($key, $value, $expire);
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
		return (null === eaccelerator_get($key)) ? $this->setValue($key, $value, $expire) : false;
	}

	/**
	 * Deletes a value with the specified key from cache
	 * This is the implementation of the method declared in the parent class.
	 * @param string $key the key of the value to be deleted
	 * @return bool if no error happens during deletion
	 */
	protected function deleteValue($key)
	{
		return eaccelerator_rm($key);
	}

	/**
	 * Deletes all values from cache.
	 * Be careful of performing this operation if the cache is shared by multiple applications.
	 */
	public function flush()
	{
		// first, remove expired content from cache
		eaccelerator_gc();
		// now, remove leftover cache-keys
		$keys = eaccelerator_list_keys();
		foreach ($keys as $key) {
			$this->deleteValue(substr($key['name'], 1));
		}
		return true;
	}
}
