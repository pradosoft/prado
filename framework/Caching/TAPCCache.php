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
 * even if it never expires. Cache is not meant to be a persistent storage.
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
 * XML configuration style:
 * ```xml
 * <modules>
 *   <module id="cache" class="Prado\Caching\TAPCCache" />
 * </modules>
 * ```
 *
 * PHP configuration style:
 * ```php
 * return [
 *     'modules' => [
 *         'cache' => [
 *             'class' => 'Prado\Caching\TAPCCache',
 *         ],
 *     ],
 * ];
 * ```
 *
 * @author Alban Hanry <compte_messagerie@hotmail.com>
 * @author Knut Urdalen <knut.urdalen@gmail.com>
 * @since 3.0b
 */
class TAPCCache extends TCache
{
	/**
	 * @return bool whether APCu is loaded, enabled, and (for CLI) `apc.enable_cli` is on.
	 */
	public static function getIsAvailable(): bool
	{
		return extension_loaded('apcu')
			&& ini_get('apc.enabled')
			&& !(substr(php_sapi_name(), 0, 3) === 'cli' && !ini_get('apc.enable_cli'));
	}

	/**
	 * Initializes this module.
	 * This method is required by the IModule interface.
	 * @param \Prado\Xml\TXmlElement $config configuration for this module, can be null
	 * @throws TConfigurationException if the APCu extension is not installed, not enabled, or not enabled for CLI
	 */
	public function init($config)
	{
		if (!static::getIsAvailable()) {
			if (!extension_loaded('apcu')) {
				throw new TConfigurationException('apccache_extension_required');
			}

			if (ini_get('apc.enabled') == false) {
				throw new TConfigurationException('apccache_extension_not_enabled');
			}

			if (substr(php_sapi_name(), 0, 3) === 'cli' && ini_get('apc.enable_cli') == false) {
				throw new TConfigurationException('apccache_extension_not_enabled_cli');
			}
		}

		parent::init($config);
	}

	/**
	 * @param string $key a unique key identifying the cached value
	 * @return mixed the stored value on a hit, or `false` on a miss or expiry.
	 */
	protected function getValue(string $key): mixed
	{
		return apcu_fetch($key);
	}

	/**
	 * @param string $key the key identifying the value to be cached
	 * @param mixed $value the value to be cached
	 * @param int $expire the number of seconds in which the cached value will expire. 0 means never expire.
	 * @return bool true if the value is successfully stored into cache, false otherwise
	 */
	protected function setValue(string $key, mixed $value, int $expire): bool
	{
		return apcu_store($key, $value, $expire);
	}

	/**
	 * @param string $key the key identifying the value to be cached
	 * @param mixed $value the value to be cached
	 * @param int $expire the number of seconds in which the cached value will expire. 0 means never expire.
	 * @return bool true if the value is successfully stored into cache, false otherwise
	 */
	protected function addValue(string $key, mixed $value, int $expire): bool
	{
		return apcu_add($key, $value, $expire);
	}

	/**
	 * @param string $key the key of the value to be deleted
	 * @return bool true if no error happens during deletion
	 */
	protected function deleteValue(string $key): bool
	{
		return apcu_delete($key);
	}

	/**
	 * Deletes all values from cache.
	 */
	public function flush(): void
	{
		apcu_clear_cache();
	}
}
