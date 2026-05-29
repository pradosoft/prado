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
use Prado\TPropertyValue;
use Prado\Util\TLogger;

/**
 * TCacheProxy class.
 *
 * TCacheProxy is a transparent proxy that delegates every {@see ICache}
 * operation to another {@see TCache} module already registered with the
 * application. This lets a single logical "cache slot" (e.g. the primary
 * application cache) be hot-swapped at configuration time without changing
 * the consumers that depend on it.
 *
 * **Configuration** — set {@see getBackingCacheId BackingCacheId} to the module ID of the
 * backing cache. TCacheProxy declares that module as a required dependency via
 * {@see \Prado\IModuleDependency} so the framework initializes it first.
 *
 * **Transparency** — `get`, `set`, `add`, `delete`, and `flush` are forwarded
 * verbatim to the backing cache's public interface, preserving its key prefix,
 * TTL semantics, dependency handling, and flush behavior exactly.
 *
 * **Change logging** — calling {@see setBackingCacheId} after an id has already been
 * set logs a {@see \Prado\Util\TLogger::WARNING} message via
 * {@see \Prado\Prado::log()} so unexpected runtime swaps are visible in the
 * application log.
 *
 * Configure in application.xml:
 * ```xml
 * <module id="cache"
 *         class="Prado\Caching\TCacheProxy"
 *         BackingCacheId="fileCache"
 *         PrimaryCache="true" />
 *
 * <module id="fileCache"
 *         class="Prado\Caching\TFileCache"
 *         Directory="Application.runtime.cache"
 *         PrimaryCache="false" />
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

	/** @var string Module ID of the backing cache; empty until configured. */
	private string $_backingCacheId = '';

	// ----------------------------------------------------------------- lifecycle

	/**
	 * Declares a required dependency on the backing cache module so that
	 * {@see \Prado\TApplication} initializes it before this proxy.
	 *
	 * @param bool $isPreInit `true` when collecting for the dyPreInit pass,
	 *   `false` when collecting for the init() pass (default).
	 *   TCacheProxy requires its backing cache in all phases, so `$isPreInit` is not used.
	 * @return ?array<int, array{id: ?string, required: bool}> dependency list,
	 *   or null when no {@see getBackingCacheId BackingCacheId} has been set yet
	 */
	public function getModuleDependencies(bool $isPreInit = false): ?array
	{
		$id = $this->getBackingCacheId();
		if ($id === '') {
			return null;
		}
		return [['id' => $id, 'required' => true]];
	}

	/**
	 * Initializes the proxy cache module. Throws when no
	 * {@see getBackingCacheId BackingCacheId} has been configured.
	 *
	 * @param ?\Prado\Xml\TXmlElement $config module configuration
	 * @throws TConfigurationException when {@see getBackingCacheId} is empty
	 */
	public function init($config)
	{
		if ($this->getBackingCacheId() === '') {
			throw new TConfigurationException('cacheproxy_backing_cache_id_required');
		}
		parent::init($config);
	}

	// ----------------------------------------------------------------- TComponentProxyTrait implementation

	/**
	 * Returns the resolved backing cache, using the same lazy-resolution path
	 * as {@see getCache()}.
	 *
	 * @throws TConfigurationException when {@see getBackingCacheId} is empty
	 * @throws TConfigurationException when the referenced module does not exist
	 * @throws TConfigurationException when the referenced module is not a {@see TCache}
	 * @return ?TComponent the resolved backing cache
	 */
	public function getProxyBacking(): ?TComponent
	{
		return $this->getCache();
	}

	/**
	 * Returns `true` when a {@see getBackingCacheId BackingCacheId} has been
	 * configured, enabling lazy resolution of the backing from the application
	 * module registry.
	 *
	 * @return bool whether lazy resolution is possible
	 */
	protected function canResolveProxyBacking(): bool
	{
		return $this->getBackingCacheId() !== '';
	}

	// --------------------------------------------------------------- accessors

	/**
	 * @return string the module ID of the backing cache
	 */
	protected function getBackingCacheIdDirect(): string
	{
		return $this->_backingCacheId;
	}

	/**
	 * @param string $value the module ID to store directly
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
	 * Sets the module ID of the backing cache. When a non-empty id was already
	 * set and the new value differs, the change is logged at
	 * {@see \Prado\Util\TLogger::WARNING} level and the resolved cache
	 * reference is invalidated so the next operation re-resolves the module.
	 *
	 * @param string $value the module ID of the cache to proxy
	 */
	public function setBackingCacheId(string $value): void
	{
		$value = TPropertyValue::ensureString($value);
		$current = $this->getBackingCacheIdDirect();
		if ($value === $current) {
			return;
		}
		if ($current !== '') {
			$this->detachProxy();
			Prado::log(
				sprintf(
					"TCacheProxy.BackingCacheId changed from '%s' to '%s'.",
					$current,
					$value
				),
				TLogger::WARNING,
				'prado.caching'
			);
		}
		$this->setBackingCacheIdDirect($value);
		$this->setCacheDirect(null);
	}

	/**
	 * Returns the lazily resolved backing cache reference, or null when not yet
	 * resolved. Narrows the trait's `?TComponent` storage to `?TCache`.
	 *
	 * @return ?TCache the backing cache, or null when not yet resolved
	 */
	protected function getCacheDirect(): ?TCache
	{
		$b = $this->getProxyBackingDirect();
		return $b instanceof TCache ? $b : null;
	}

	/**
	 * Stores the backing cache reference directly via the trait's backing field.
	 *
	 * @param ?TCache $cache the backing cache reference to store directly
	 */
	protected function setCacheDirect(?TCache $cache): void
	{
		$this->setProxyBackingDirect($cache);
	}

	/**
	 * Returns the resolved backing {@see TCache} instance, resolving it lazily
	 * on first call via {@see \Prado\TApplication::getModule()}.
	 *
	 * @throws TConfigurationException when {@see getBackingCacheId} is empty
	 * @throws TConfigurationException when the referenced module does not exist
	 * @throws TConfigurationException when the referenced module is not a {@see TCache}
	 * @return TCache the backing cache module
	 */
	public function getCache(): TCache
	{
		$cacheModule = $this->getCacheDirect();
		if ($cacheModule === null) {
			$id = $this->getBackingCacheId();
			if ($id === '') {
				throw new TConfigurationException('cacheproxy_backing_cache_id_required');
			}
			$cacheModule = $this->getApplication()->getModule($id);
			if ($cacheModule === null) {
				throw new TConfigurationException('cacheproxy_cache_not_found', $id);
			}
			if (!($cacheModule instanceof TCache)) {
				throw new TConfigurationException('cacheproxy_invalid_cache_type', $id);
			}
			$this->setCacheDirect($cacheModule);
			$this->attachProxy();
			$cacheModule = $this->getCacheDirect();
		}
		return $cacheModule;
	}

	// ----------------------------------------------------------------- ICache

	/**
	 * Retrieves a value from the backing cache with the specified key.
	 *
	 * @param string $id a key identifying the cached value
	 * @return false|mixed the value stored in cache, or false on miss / expiry
	 */
	public function get($id)
	{
		return $this->getCache()->get($id);
	}

	/**
	 * Stores a value in the backing cache under the specified key.
	 *
	 * @param string $id the key identifying the value to be cached
	 * @param mixed $value the value to be cached
	 * @param int $expire TTL in seconds; 0 means never expire
	 * @param ?ICacheDependency $dependency optional invalidation dependency
	 * @return bool true on success
	 */
	public function set($id, $value, $expire = 0, $dependency = null)
	{
		return $this->getCache()->set($id, $value, $expire, $dependency);
	}

	/**
	 * Stores a value in the backing cache only when no live entry exists.
	 *
	 * @param string $id the key identifying the value to be cached
	 * @param mixed $value the value to be cached
	 * @param int $expire TTL in seconds; 0 means never expire
	 * @param ?ICacheDependency $dependency optional invalidation dependency
	 * @return bool true when the entry was stored; false when it already existed
	 */
	public function add($id, $value, $expire = 0, $dependency = null)
	{
		return $this->getCache()->add($id, $value, $expire, $dependency);
	}

	/**
	 * Deletes a value from the backing cache.
	 *
	 * @param string $id the key of the value to delete
	 * @return bool true on success
	 */
	public function delete($id)
	{
		return $this->getCache()->delete($id);
	}

	/**
	 * Deletes all values from the backing cache.
	 *
	 * @return bool true on success
	 */
	public function flush()
	{
		return $this->getCache()->flush();
	}

	// --------------------------------------------------------------- internals

	/**
	 * Satisfies the abstract contract of {@see TCache}; never invoked because
	 * the public interface delegates directly to the backing cache.
	 *
	 * @param string $key the unique key
	 * @return false
	 */
	protected function getValue($key)
	{
		return false; // @codeCoverageIgnore
	}

	/**
	 * Satisfies the abstract contract of {@see TCache}; never invoked because
	 * the public interface delegates directly to the backing cache.
	 *
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
	 * Satisfies the abstract contract of {@see TCache}; never invoked because
	 * the public interface delegates directly to the backing cache.
	 *
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
	 * Satisfies the abstract contract of {@see TCache}; never invoked because
	 * the public interface delegates directly to the backing cache.
	 *
	 * @param string $key the unique key
	 * @return false
	 */
	protected function deleteValue($key)
	{
		return false; // @codeCoverageIgnore
	}

	// -------------------------------------------------- serialization

	/**
	 * Excludes transient and default-valued fields from serialization. The
	 * resolved backing cache reference and the proxy event names are always
	 * excluded. The `_backingCacheId` is excluded only when empty.
	 *
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
