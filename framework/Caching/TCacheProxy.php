<?php

/**
 * TCacheProxy class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Caching;

use Prado\Exceptions\TConfigurationException;
use Prado\IModuleDependency;
use Prado\IProxy;
use Prado\Prado;
use Prado\TComponent;
use Prado\TComponentProxyTrait;
use Prado\Util\Log\TLogger;

/**
 * TCacheProxy class.
 *
 * TCacheProxy is a transparent cache module that delegates every {@see ICache}
 * operation to another {@see TCache} module registered with the application.
 * One logical cache slot, such as the primary application cache, can be
 * swapped at configuration time without changing the consumers that depend on it.
 *
 * ## Configuration
 *
 * {@see getBackingCacheId BackingCacheId} names the backing cache module.
 * TCacheProxy declares that module as a required {@see IModuleDependency}, so
 * the application initializes it first. Only one of the two modules may be the
 * {@see TCache::setPrimaryCache PrimaryCache}.
 *
 * ## Transparency
 *
 * {@see get()}, {@see set()}, {@see add()}, {@see delete()}, and {@see flush()}
 * call the backing cache's public methods, so the backing's key prefix, TTL
 * handling, dependencies, and flush behavior apply unchanged; the proxy's own
 * {@see TCache::getKeyPrefix KeyPrefix} is not applied. Other property reads and
 * writes, method calls, and events reach the backing through
 * {@see TComponentProxyTrait}, which wires the backing's public `on` events on
 * first resolution.
 *
 * Replacing a {@see setBackingCacheId BackingCacheId} logs a {@see TLogger::WARNING}
 * so a runtime swap is visible in the application log.
 *
 * Configure in `application.xml`:
 * ```xml
 * <module id="cache" class="Prado\Caching\TCacheProxy" BackingCacheId="fileCache" PrimaryCache="true" />
 * <module id="fileCache" class="Prado\Caching\TFileCache" Directory="Application.runtime.cache" PrimaryCache="false" />
 * ```
 *
 * Or instantiate directly:
 * ```php
 * $proxy = new TCacheProxy();
 * $proxy->setBackingCacheId('fileCache');
 * $proxy->setPrimaryCache(true);
 * $proxy->init(null);
 * // All operations now delegate to the 'fileCache' module.
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TCacheProxy extends TCache implements IModuleDependency, IProxy
{
	use TComponentProxyTrait;

	/** @var string module ID of the backing cache; empty until configured */
	private string $_backingCacheId = '';

	// ----------------------------------------------------------------- lifecycle

	/**
	 * Declares the backing cache module as a required dependency so that
	 * {@see \Prado\TApplication} initializes it before this proxy, in every pass.
	 * @param bool $isPreInit `true` for the dyPreInit pass, `false` for init(); not used
	 * @return ?string the backing cache module ID, or null when none is configured
	 */
	public function getModuleDependencies(bool $isPreInit = false): ?string
	{
		$id = $this->getBackingCacheId();
		return $id === '' ? null : $id;
	}

	/**
	 * Initializes the proxy cache module.
	 * @param null|array|\Prado\Xml\TXmlElement $config module configuration
	 * @throws TConfigurationException when {@see getBackingCacheId BackingCacheId} is empty
	 */
	public function init($config)
	{
		if ($this->getBackingCacheId() === '') {
			throw new TConfigurationException('cacheproxy_backing_cache_id_required');
		}
		parent::init($config);
	}

	/**
	 * A proxy has no backend prerequisites of its own; the backing cache reports its own.
	 * @return bool `true`
	 */
	public static function getIsAvailable(): bool
	{
		return true;
	}

	// ----------------------------------------------------------------- TComponentProxyTrait implementation

	/**
	 * Returns the backing cache through {@see getCache()}.
	 * @throws TConfigurationException when {@see getBackingCacheId BackingCacheId} is empty
	 * @throws TConfigurationException when the referenced module does not exist
	 * @throws TConfigurationException when the referenced module is not a {@see TCache}
	 * @return ?TComponent the backing cache
	 */
	public function getProxyBacking(): ?TComponent
	{
		return $this->getCache();
	}

	/**
	 * Returns whether a {@see getBackingCacheId BackingCacheId} is configured,
	 * which enables lazy resolution from the module registry.
	 * @return bool whether lazy resolution is possible
	 */
	protected function canResolveProxyBacking(): bool
	{
		return $this->getBackingCacheId() !== '';
	}

	// --------------------------------------------------------------- accessors

	/**
	 * @return string the stored module ID of the backing cache
	 */
	protected function getBackingCacheIdDirect(): string
	{
		return $this->_backingCacheId;
	}

	/**
	 * @param string $value the module ID to store
	 */
	protected function setBackingCacheIdDirect(string $value): void
	{
		$this->_backingCacheId = $value;
	}

	/**
	 * @return string the module ID of the backing cache
	 */
	public function getBackingCacheId(): string
	{
		return $this->getBackingCacheIdDirect();
	}

	/**
	 * Sets the module ID of the backing cache. Replacing a non-empty ID detaches
	 * the event forwarders, logs a {@see TLogger::WARNING}, and drops the resolved
	 * cache so the next operation resolves the new module.
	 * @param string $value the module ID of the cache to proxy
	 */
	public function setBackingCacheId(string $value): void
	{
		$current = $this->getBackingCacheIdDirect();
		if ($value === $current) {
			return;
		}
		if ($current !== '') {
			$this->detachProxy();
			Prado::log(
				sprintf("TCacheProxy.BackingCacheId changed from '%s' to '%s'.", $current, $value),
				TLogger::WARNING,
				'prado.caching'
			);
		}
		$this->setBackingCacheIdDirect($value);
		$this->setCacheDirect(null);
	}

	/**
	 * Returns the stored backing cache, narrowing the trait's `?TComponent` to `?TCache`.
	 * @return ?TCache the backing cache, or null when not yet resolved
	 */
	protected function getCacheDirect(): ?TCache
	{
		$backing = $this->getProxyBackingDirect();
		return $backing instanceof TCache ? $backing : null;
	}

	/**
	 * @param ?TCache $cache the backing cache to store
	 */
	protected function setCacheDirect(?TCache $cache): void
	{
		$this->setProxyBackingDirect($cache);
	}

	/**
	 * Returns the backing {@see TCache}, resolving it through
	 * {@see \Prado\TApplication::getModule()} on first call. The first resolution
	 * calls {@see attachProxy()}.
	 * @throws TConfigurationException when {@see getBackingCacheId BackingCacheId} is empty
	 * @throws TConfigurationException when the referenced module does not exist
	 * @throws TConfigurationException when the referenced module is not a {@see TCache}
	 * @return TCache the backing cache module
	 */
	public function getCache(): TCache
	{
		$cache = $this->getCacheDirect();
		if ($cache === null) {
			$id = $this->getBackingCacheId();
			if ($id === '') {
				throw new TConfigurationException('cacheproxy_backing_cache_id_required');
			}
			$cache = $this->getApplication()->getModule($id);
			if ($cache === null) {
				throw new TConfigurationException('cacheproxy_cache_not_found', $id);
			}
			if (!($cache instanceof TCache)) {
				throw new TConfigurationException('cacheproxy_invalid_cache_type', $id);
			}
			$this->setCacheDirect($cache);
			$this->attachProxy();
		}
		return $cache;
	}

	// ----------------------------------------------------------------- ICache

	/**
	 * Retrieves a value from the backing cache.
	 * @param string $id a key identifying the cached value
	 * @return false|mixed the cached value, or false on a miss or expiry
	 */
	public function get($id)
	{
		return $this->getCache()->get($id);
	}

	/**
	 * Stores a value in the backing cache.
	 * @param string $id the key identifying the value to be cached
	 * @param mixed $value the value to be cached
	 * @param int $expire TTL in seconds; 0 means never expire
	 * @param ?ICacheDependency $dependency invalidation dependency
	 * @return bool true on success
	 */
	public function set($id, $value, $expire = 0, $dependency = null)
	{
		return $this->getCache()->set($id, $value, $expire, $dependency);
	}

	/**
	 * Stores a value in the backing cache when the key is absent.
	 * @param string $id the key identifying the value to be cached
	 * @param mixed $value the value to be cached
	 * @param int $expire TTL in seconds; 0 means never expire
	 * @param ?ICacheDependency $dependency invalidation dependency
	 * @return bool true when the entry was stored; false when it already existed
	 */
	public function add($id, $value, $expire = 0, $dependency = null)
	{
		return $this->getCache()->add($id, $value, $expire, $dependency);
	}

	/**
	 * Deletes a value from the backing cache.
	 * @param string $id the key of the value to delete
	 * @return bool true on success
	 */
	public function delete($id)
	{
		return $this->getCache()->delete($id);
	}

	/**
	 * Deletes all values from the backing cache.
	 * @return bool true on success
	 */
	public function flush()
	{
		return $this->getCache()->flush();
	}

	// --------------------------------------------------------------- internals

	/**
	 * Satisfies the {@see TCache} contract; never invoked because the public
	 * interface delegates to the backing cache.
	 * @param string $key the unique key
	 * @return false
	 */
	protected function getValue($key)
	{
		return false; // @codeCoverageIgnore
	}

	/**
	 * Satisfies the {@see TCache} contract; never invoked because the public
	 * interface delegates to the backing cache.
	 * @param string $key the unique key
	 * @param mixed $value the value to store
	 * @param int $expire TTL in seconds
	 * @return false
	 */
	protected function setValue($key, $value, $expire)
	{
		return false; // @codeCoverageIgnore
	}

	/**
	 * Satisfies the {@see TCache} contract; never invoked because the public
	 * interface delegates to the backing cache.
	 * @param string $key the unique key
	 * @param mixed $value the value to store
	 * @param int $expire TTL in seconds
	 * @return false
	 */
	protected function addValue($key, $value, $expire)
	{
		return false; // @codeCoverageIgnore
	}

	/**
	 * Satisfies the {@see TCache} contract; never invoked because the public
	 * interface delegates to the backing cache.
	 * @param string $key the unique key
	 * @return false
	 */
	protected function deleteValue($key)
	{
		return false; // @codeCoverageIgnore
	}

	// -------------------------------------------------- serialization

	/**
	 * Excludes the transient forwarder list, the resolved backing cache
	 * (re-resolved from the module registry after unserialization), and an empty
	 * backing ID from serialization.
	 * @param array $exprops excluded-properties list, passed by reference
	 */
	protected function _getZappableSleepProps(&$exprops)
	{
		parent::_getZappableSleepProps($exprops);
		$this->_addProxyEventNamesZappable($exprops);
		$this->_addProxyBackingZappable($exprops);
		if ($this->getBackingCacheIdDirect() === '') {
			$exprops[] = "\0" . __CLASS__ . "\0_backingCacheId";
		}
	}
}
