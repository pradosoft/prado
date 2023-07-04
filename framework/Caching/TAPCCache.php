<?php
/**
 * TAPCCache class file
 *
 * @author Alban Hanry <compte_messagerie@hotmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Caching;

use Prado\Exceptions\TConfigurationException;

/**
 * TAPCCache class
 *
 * TAPCCache implements a cache application module based on {@see http://www.php.net/apcu APCu}.
 *
 * By definition, cache does not ensure the existence of a value
 * even if it never expires. Cache is not meant to be an persistent storage.
 *
 * To use this module, the APCu PHP extension must be loaded and set in the php.ini file.
 *
 * Some usage examples of TAPCCache are as follows,
 * ```php
 * $cache=new TAPCCache;  // TAPCCache may also be loaded as a Prado application module
 * $cache->init(null);
 * $cache->add('object',$object);
 * $object2=$cache->get('object');
 * ```
 *
 * If loaded, TAPCCache will register itself with {@see \Prado\TApplication} as the
 * cache module. It can be accessed via {@see \Prado\TApplication::getCache()}.
 *
 * TAPCCache may be configured in application configuration file as follows
 * ```php
 * <module id="cache" class="Prado\Caching\TAPCCache" />
 * ```
 *
 * @author Alban Hanry <compte_messagerie@hotmail.com>
 * @author Knut Urdalen <knut.urdalen@gmail.com>
 * @since 3.0b
 */
class TAPCCache extends TCache
{
	/**
	 * Initializes this module.
	 * This method is required by the IModule interface.
	 * @param \Prado\Xml\TXmlElement $config configuration for this module, can be null
	 * @throws TConfigurationException if apc extension is not installed or not started, check your php.ini
	 */
	public function init($config)
	{
		if (!extension_loaded('apcu')) {
			throw new TConfigurationException('apccache_extension_required');
		}

		if (ini_get('apc.enabled') == false) {
			throw new TConfigurationException('apccache_extension_not_enabled');
		}

		if (substr(php_sapi_name(), 0, 3) === 'cli' && ini_get('apc.enable_cli') == false) {
			throw new TConfigurationException('apccache_extension_not_enabled_cli');
		}

		parent::init($config);
	}

	/**
	 * Retrieves a value from cache with a specified key.
	 * This is the implementation of the method declared in the parent class.
	 * @param string $key a unique key identifying the cached value
	 * @return false|string the value stored in cache, false if the value is not in the cache or expired.
	 */
	protected function getValue($key)
	{
		return apcu_fetch($key);
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
		return apcu_store($key, $value, $expire);
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
		return apcu_add($key, $value, $expire);
	}

	/**
	 * Deletes a value with the specified key from cache
	 * This is the implementation of the method declared in the parent class.
	 * @param string $key the key of the value to be deleted
	 * @return bool if no error happens during deletion
	 */
	protected function deleteValue($key)
	{
		return apcu_delete($key);
	}

	/**
	 * Deletes all values from cache.
	 * Be careful of performing this operation if the cache is shared by multiple applications.
	 * @return bool if no error happens during flush
	 */
	public function flush()
	{
		return apcu_clear_cache();
	}
}
