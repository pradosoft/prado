<?php

/**
 * TMemoryCache class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Caching;

use Prado\Caching\ICache;
use Prado\Exceptions\TConfigurationException;
use Prado\IModuleDependency;
use Prado\Prado;
use Prado\TApplicationMode;
use Prado\TPropertyValue;

/**
 * TMemoryCache class.
 *
 * TMemoryCache is an in-process, in-memory {@see TCache} application module.
 * All cache entries are held in a plain PHP array for the lifetime of the current
 * process. Data is never shared across processes or requests unless it is
 * explicitly persisted through a backing store.
 *
 * ## Backing stores
 *
 * TMemoryCache supports two optional backing stores, tried in the order below:
 *
 * 1. **Backing cache module** ({@see getBackingCacheId BackingCacheId}) — any
 *    {@see TCache} module already registered with the application. The entire
 *    in-memory store is serialized and written under
 *    {@see getBackingCacheKey BackingCacheKey}.
 *
 * 2. **Backing file** ({@see getBackingFile BackingFile}) — a file path;
 *    namespace-style paths (e.g. `Application.runtime.memory`) are resolved via
 *    {@see \Prado\Prado::getPathOfNamespace()}. The store is serialized to disk
 *    with exclusive locking.
 *
 * When both are configured the backing cache module takes precedence. When
 * neither is configured, {@see load()} and {@see save()} are no-ops and the
 * module behaves as a pure process-scoped cache with no persistence.
 *
 * ## Lifecycle
 *
 * On {@see init()}, the in-memory store is populated from the backing store.
 * On every `OnSaveState` application event, {@see save()} is invoked
 * automatically so that the store is persisted at the end of each request.
 *
 * ## Merge policy
 *
 * When {@see load()} imports data into a non-empty in-memory store, the
 * {@see getMergePolicy MergePolicy} property controls the outcome:
 *
 * | Policy  | Behavior |
 * |---------|----------|
 * | `Merge` | *(default)* Keys already in memory are preserved; missing keys are imported from the backing store. |
 * | `Replace` | The entire in-memory store is replaced by the backing store contents. |
 *
 * ## TTL semantics
 *
 * TTL is enforced in-memory on every {@see get()} call. Expired entries are
 * removed from the array on access. A `$expire` of `0` means the entry never
 * expires.
 *
 * ## Cache dependencies
 *
 * {@see ICacheDependency} objects are supported and serialized together with
 * each value, consistent with the behavior of every other {@see TCache} subclass.
 *
 * ## Key hashing
 *
 * By default ({@see getHashKeys HashKeys}`= null`), cache keys are hashed with
 * MD5 in production modes ({@see TApplicationMode::Normal},
 * {@see TApplicationMode::Performance}) and left readable in
 * {@see TApplicationMode::Debug} mode, matching the typical developer workflow.
 * Set `HashKeys="true"` to force hashing in all modes, or `HashKeys="false"` to
 * disable it entirely (e.g. for explicit debugging of in-memory store contents).
 *
 * ## Maximum size
 *
 * When {@see getMaximumSize MaximumSize} is set to a value greater than `0`, the
 * module enforces a total serialized-byte limit on the in-memory store. The size of
 * each entry is measured as the `strlen()` of the serialized payload produced by the
 * {@see TCache} base class. After every {@see set()} call the total is checked, and
 * the least recently accessed entries are evicted one at a time until the store fits
 * within the limit, following the algorithm used by Apple Foundation's `NSCache`.
 * A value of `0` (the default) means no size limit is enforced. When a single
 * serialized entry exceeds the configured limit, {@see set()} throws
 * {@see \Prado\Exceptions\TInvalidDataValueException} rather than writing the
 * entry and evicting everything else.
 *
 * **XML configuration** (`application.xml`):
 * ```xml
 * <module id="cache" class="Prado\Caching\TMemoryCache"
 *         BackingCacheId="fileCache"
 *         MaximumSize="4194304"
 *         MergePolicy="Replace" />
 *
 * <module id="fileCache"
 *         class="Prado\Caching\TFileCache"
 *         Directory="Application.runtime.cache"
 *         PrimaryCache="false" />
 * ```
 *
 * **PHP configuration** (`application.php`):
 * ```php
 * return [
 *     'modules' => [
 *         'cache' => [
 *             'class' => 'Prado\Caching\TMemoryCache',
 *             'properties' => [
 *                 'BackingCacheId' => 'fileCache',
 *                 'MaximumSize'    => '4194304',
 *                 'MergePolicy'    => 'Replace',
 *             ],
 *         ],
 *         'fileCache' => [
 *             'class' => 'Prado\Caching\TFileCache',
 *             'properties' => [
 *                 'Directory'    => 'Application.runtime.cache',
 *                 'PrimaryCache' => 'false',
 *             ],
 *         ],
 *     ],
 * ];
 * ```
 *
 * Or with a file backing and no external module:
 *
 * XML:
 * ```xml
 * <module id="cache" class="Prado\Caching\TMemoryCache"
 *         BackingFile="Application.runtime.memory-cache" />
 * ```
 *
 * PHP:
 * ```php
 * return [
 *     'modules' => [
 *         'cache' => [
 *             'class' => 'Prado\Caching\TMemoryCache',
 *             'properties' => [
 *                 'BackingFile' => 'Application.runtime.memory-cache',
 *             ],
 *         ],
 *     ],
 * ];
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TMemoryCache extends TCache implements IModuleDependency, ICacheSize
{
	use TCacheSizeTrait;

	/** Merge policy: backing fills in only the keys absent from memory. */
	public const MERGE = 'Merge';

	/** Merge policy: the in-memory store is completely replaced by the backing store. */
	public const REPLACE = 'Replace';

	/** In-store array key for the cached payload (value + dependency). */
	protected const STORE_DATA = 'data';

	/** In-store array key for the absolute expiry timestamp (Unix seconds; 0 = never). */
	protected const STORE_EXPIRE = 'expire';

	/**
	 * Default base key used to store the serialized in-memory store in the backing
	 * cache module. Subclasses may override this constant to avoid key collisions
	 * when multiple TMemoryCache subclasses share the same backing cache.
	 * {@see init()} appends a dot-separated module ID when one is available.
	 */
	public const DEFAULT_BACKING_CACHE_KEY = 'prado.memory-cache';

	/**
	 * Default merge policy applied by {@see load()} when the backing store is imported
	 * into a non-empty in-memory store. Subclasses may override this constant to change
	 * the default without overriding {@see __construct()} or {@see setMergePolicy()}.
	 */
	public const DEFAULT_MERGE_POLICY = self::MERGE;

	// ------------------------------------------------------------------ fields

	/**
	 * @var array<string, array{data: mixed, expire: int}> In-memory cache store,
	 *   keyed by unique cache key. Always access through {@see getStoreDirect()} /
	 *   {@see setStoreDirect()} so that subclasses may substitute alternative storage.
	 */
	private array $_store = [];

	/** @var string Module ID of the backing cache; empty when not configured. */
	private string $_backingCacheId = '';

	/** @var string File path for file-based backing; empty when not configured. */
	private string $_backingFile = '';

	/**
	 * @var string Key used to read/write the serialized store in the backing cache.
	 *   Initialized to {@see DEFAULT_BACKING_CACHE_KEY} in {@see __construct()};
	 *   {@see init()} appends `'.<moduleId>'` when a module ID is available.
	 */
	private string $_backingCacheKey = '';

	/**
	 * @var string Merge policy: one of {@see MERGE} or {@see REPLACE}. Initialized
	 *   to {@see DEFAULT_MERGE_POLICY} in {@see __construct()} via late static binding
	 *   so that subclasses may override the constant to change the default.
	 */
	private string $_mergePolicy = '';

	/**
	 * @var bool Dirty flag: true when the in-memory store has been modified since
	 *   the last {@see load()} or successful {@see save()}. {@see save()} skips
	 *   the backing store write when this flag is false.
	 */
	private bool $_changed = false;

	/**
	 * @var ?bool Whether cache keys are hashed by the TCache parent before use.
	 *   - `null` *(default)* — automatic: hashing is **off** in {@see TApplicationMode::Debug}
	 *     mode and **on** in {@see TApplicationMode::Normal} and
	 *     {@see TApplicationMode::Performance} mode.
	 *   - `true` — always hash; delegates to {@see TCache::generateUniqueKey()}.
	 *   - `false` — never hash; the raw key (with prefix) is used as-is,
	 *     making entries human-readable during development.
	 */
	private ?bool $_hashKeys = null;

	/**
	 * @var array<string, int> Per-key byte size of the serialized STORE_DATA payload,
	 *   maintained incrementally by {@see setValue()} and {@see deleteValue()}.
	 *   Rebuilt from scratch by {@see computeCurrentSize()} on a fingerprint mismatch.
	 */
	private array $_entrySizes = [];

	/**
	 * @var array<string, float> Per-key last-access timestamp from `microtime(true)`,
	 *   used to determine eviction order in {@see evictToFitMaximumSize()}.
	 *   Entries loaded without an explicit access record are assigned `0.0` (oldest
	 *   possible) by {@see computeCurrentSize()} so they are evicted first.
	 *   Only populated when {@see getMaximumSize MaximumSize} is greater than `0`.
	 */
	private array $_accessTimes = [];

	// --------------------------------------------------------------- lifecycle

	/**
	 * Declares the backing cache module as a required dependency so that
	 * {@see \Prado\TApplication} initializes it before this module, guaranteeing
	 * that {@see load()} can resolve the backing module during {@see init()}.
	 * Returns `null` when no {@see getBackingCacheId BackingCacheId} has been set.
	 *
	 * @param bool $isPreInit `true` when collecting for the dyPreInit pass,
	 *   `false` when collecting for the init() pass (default).
	 *   TMemoryCache needs its backing cache in all phases.
	 * @return null|array|string dependency list; an empty string when no
	 *   {@see getBackingCacheId BackingCacheId} has been configured
	 */
	public function getModuleDependencies(bool $isPreInit = false): null|string|array
	{
		return $this->getBackingCacheId();
	}

	/**
	 * Seeds {@see getBackingCacheKey BackingCacheKey} from {@see DEFAULT_BACKING_CACHE_KEY}
	 * and {@see getMergePolicy MergePolicy} from {@see DEFAULT_MERGE_POLICY} via late
	 * static binding so that subclasses may override either constant to change the
	 * defaults without overriding this method or {@see init()}.
	 */
	public function __construct()
	{
		$this->setBackingCacheKeyDirect(static::DEFAULT_BACKING_CACHE_KEY);
		$this->setMergePolicyDirect(static::DEFAULT_MERGE_POLICY);
		parent::__construct();
	}

	/**
	 * @return bool always true; the in-memory store has no external dependency.
	 * @since 4.4.0
	 */
	public static function getIsAvailable(): bool
	{
		return true;
	}

	/**
	 * Initializes the module. Refines the {@see getBackingCacheKey BackingCacheKey}
	 * set by {@see __construct()} by appending `'.<moduleId>'` when the key is still
	 * at its {@see DEFAULT_BACKING_CACHE_KEY default} and a module ID is available,
	 * then loads the in-memory store from the backing store and registers
	 * {@see handleSaveState()} as a handler for the application's `OnSaveState`
	 * event so that the store is persisted automatically at the end of each request.
	 *
	 * @param null|\Prado\Xml\TXmlElement $config module configuration
	 */
	public function init($config)
	{
		if ($this->getBackingCacheKeyDirect() === static::DEFAULT_BACKING_CACHE_KEY) {
			$id = $this->getID() ?? '';
			if ($id !== '') {
				$this->setBackingCacheKeyDirect(static::DEFAULT_BACKING_CACHE_KEY . '.' . $id);
			}
		}
		$this->load();
		$this->getApplication()->attachEventHandler('OnSaveState', [$this, 'handleSaveState'], 5);
		parent::init($config);
	}

	/**
	 * Persists the in-memory store when the application raises its `OnSaveState`
	 * event. This handler is registered automatically during {@see init()}.
	 *
	 * @param mixed $sender the event sender (the application instance)
	 * @param mixed $param the event parameter
	 */
	public function handleSaveState(mixed $sender, mixed $param): void
	{
		$this->save();
	}

	// --------------------------------------------------------------- accessors (store)

	/**
	 * Replaces the entire in-memory store.
	 * Override in a subclass alongside {@see getStoreDirect()} to substitute a
	 * different storage backend.
	 *
	 * @param array<string, array{data: mixed, expire: int}> $value the new store
	 */
	protected function setStoreDirect(array $value): void
	{
		$this->_store = $value;
	}

	/**
	 * Returns true when an entry exists for the given internal key, regardless of
	 * its {@see STORE_DATA} value or expiry timestamp.
	 *
	 * @param string $key the internal (hashed) key
	 * @return bool true when the key is present in the store
	 */
	protected function hasStoreEntry(string $key): bool
	{
		return array_key_exists($key, $this->_store);
	}

	/**
	 * Returns a reference to the entire in-memory store.
	 * Override in a subclass to substitute a different storage backend
	 * while retaining all other TMemoryCache behavior.
	 *
	 * @return array<string, array{data: mixed, expire: int}> the current store, by reference
	 */
	protected function &getStoreDirect(): array
	{
		return $this->_store;
	}

	/**
	 * Returns the raw store entry for a single internal key, or null when the
	 * key is absent. Use {@see hasStoreEntry()} to distinguish between a missing
	 * key and an entry whose {@see STORE_DATA} payload is null.
	 *
	 * @param string $key the internal (hashed) key
	 * @return null|array{data: mixed, expire: int} the entry, or null when absent
	 */
	protected function getStoreEntry(string $key): ?array
	{
		return $this->_store[$key] ?? null;
	}

	/**
	 * Writes a single entry into the store, overwriting any existing entry for
	 * the same key.
	 *
	 * @param string $key the internal (hashed) key
	 * @param array{data: mixed, expire: int} $entry the entry to store
	 */
	protected function setStoreEntry(string $key, array $entry): void
	{
		$this->_store[$key] = $entry;
	}

	/**
	 * Removes a single entry from the store. This is the correct way to delete
	 * an entry when {@see STORE_DATA} may legitimately be null — calling
	 * {@see setStoreEntry()} with null data would keep the key present.
	 *
	 * @param string $key the internal (hashed) key to remove
	 */
	protected function clearStoreEntry(string $key): void
	{
		unset($this->_store[$key]);
	}

	// --------------------------------------------------------------- load / save

	/**
	 * Loads the in-memory store from the backing store and applies
	 * {@see getMergePolicy MergePolicy}:
	 *
	 * - `Merge` — keys already in memory are preserved; only keys absent from
	 *   memory are imported.
	 * - `Replace` — the entire in-memory store is replaced by the backing data.
	 *
	 * After a successful load the dirty flag is cleared because the in-memory
	 * store is in sync with the backing store. When no backing store is configured,
	 * this method is a no-op. Invalid or unserializable backing data is silently
	 * ignored.
	 *
	 * @return bool true when data was successfully loaded from a backing store;
	 *   false when no backing is configured or the backing contains no data
	 */
	public function load(): bool
	{
		$data = $this->loadFromBacking();
		if ($data === null) {
			return false;
		}
		if ($this->getMergePolicy() === self::REPLACE) {
			$this->setStoreDirect($data);
		} else {
			foreach ($data as $key => $entry) {
				if (!$this->hasStoreEntry($key)) {
					$this->setStoreEntry($key, $entry);
				}
			}
		}
		$this->setChangedDirect(false);
		return true;
	}

	/**
	 * Persists the entire in-memory store to the backing store, but only when
	 * the dirty flag ({@see getChanged Changed}) is set. When the store has not
	 * been modified since the last load or save, this method returns `true`
	 * without writing to the backing store. When no backing store is configured,
	 * this method is a no-op.
	 *
	 * On a successful write the dirty flag is cleared.
	 *
	 * @return bool true when the backing write succeeded or no write was needed;
	 *   false when a backing is configured but the write operation failed
	 */
	public function save(): bool
	{
		if (!$this->getChangedDirect()) {
			return true;
		}
		$result = $this->saveToBacking($this->getStoreDirect());
		if ($result) {
			$this->setChangedDirect(false);
		}
		return $result;
	}

	/**
	 * Reads the serialized store from the backing store and returns it as a
	 * PHP array, or `null` when no backing is configured or the data cannot be
	 * read. Backing cache module is preferred over the backing file.
	 *
	 * @return ?array<string, array{data: mixed, expire: int}> the deserialized
	 *   store, or null on failure
	 */
	protected function loadFromBacking(): ?array
	{
		$cacheId = $this->getBackingCacheId();
		if ($cacheId !== '') {
			$cache = $this->getApplication()->getModule($cacheId);
			if ($cache instanceof ICache) {
				$raw = $cache->get($this->getBackingCacheKey());
				if ($raw !== false && is_array($raw)) {
					return $raw;
				}
			}
			return null;
		}
		$file = $this->getBackingFileDirect();
		if ($file !== '') {
			$content = $this->getContents($file);
			if ($content !== false && $content !== '') {
				$data = $this->unserialize($content);
				if (is_array($data)) {
					return $data;
				}
			}
		}
		return null;
	}

	/**
	 * Writes the serialized store to the backing store. Backing cache module is
	 * preferred over the backing file.
	 *
	 * @param array<string, array{data: mixed, expire: int}> $store the in-memory
	 *   store to persist
	 * @return bool true on success; false when no backing is configured or the
	 *   write failed
	 */
	protected function saveToBacking(array $store): bool
	{
		$cacheId = $this->getBackingCacheId();
		if ($cacheId !== '') {
			$cache = $this->getApplication()->getModule($cacheId);
			if ($cache instanceof ICache) {
				return $cache->set($this->getBackingCacheKey(), $store, 0);
			}
			return false;
		}
		$file = $this->getBackingFileDirect();
		if ($file !== '') {
			return $this->putContents($file, $this->serialize($store)) !== false;
		}
		return false;
	}

	// -------------------------------------------------- generateUniqueKey override

	/**
	 * Converts a raw application cache key into the internal storage key.
	 *
	 * When hashing is enabled (per {@see getHashKeys HashKeys}), delegates to
	 * {@see TCache::generateUniqueKey()} which returns `md5(prefix . key)`.
	 * When disabled, returns `prefix . key` verbatim for human-readable store keys.
	 *
	 * @param string $key the raw application key
	 * @return string the internal storage key
	 */
	protected function generateUniqueKey($key): string
	{
		$hash = $this->getHashKeysDirect() ?? ($this->getApplication()->getMode() !== TApplicationMode::Debug);
		if ($hash) {
			return parent::generateUniqueKey($key);
		}
		return $this->getKeyPrefix() . $key;
	}

	// --------------------------------------------------------------- ICache impl

	/**
	 * Retrieves a value from the in-memory store by its unique key. Expired entries
	 * are removed from the store on access; when {@see getMaximumSize MaximumSize}
	 * is active the running size total is decremented accordingly. Access time is
	 * updated on every live hit so that LRU eviction remains accurate.
	 *
	 * @param string $key the unique key produced by {@see \Prado\Caching\TCache::generateUniqueKey()}
	 * @return false|mixed the stored payload, or false when the key is absent or expired
	 */
	protected function getValue($key)
	{
		if (!$this->hasStoreEntry($key)) {
			return false;
		}
		$entry = $this->getStoreEntry($key);
		$expire = (int) $entry[static::STORE_EXPIRE];
		if ($expire > 0 && $expire <= $this->now()) {
			if ($this->getMaximumSizeDirect() > 0) {
				$size = $this->_entrySizes[$key] ?? 0;
				$current = $this->getCurrentSizeDirect();
				if ($current >= 0) {
					$this->setCurrentSizeDirect(max(0, $current - $size));
				}
				unset($this->_entrySizes[$key], $this->_accessTimes[$key]);
			}
			$this->clearStoreEntry($key);
			if ($this->getMaximumSizeDirect() > 0 && $this->getCurrentSizeDirect() >= 0) {
				$this->setSizeFingerprintDirect($this->computeSizeFingerprint());
			}
			return false;
		}
		if ($this->getMaximumSizeDirect() > 0) {
			$this->_accessTimes[$key] = microtime(true);
		}
		return $entry[static::STORE_DATA];
	}

	/**
	 * Stores a value under the given unique key, overwriting any existing entry.
	 * When {@see getMaximumSize MaximumSize} is active, the serialized size of the
	 * entry is checked before the write — an oversized item is rejected rather than
	 * written and immediately evicted. After the write the running size total is
	 * updated incrementally and {@see enforceMaximumSize()} is called to evict LRU
	 * entries when the cache has exceeded its limit.
	 *
	 * @param string $key the unique key
	 * @param mixed $value the payload to store (contains the value and its dependency)
	 * @param int $expire TTL in seconds; 0 means never expire
	 * @throws \Prado\Exceptions\TInvalidDataValueException when MaximumSize is active
	 *   and the serialized entry exceeds it
	 * @return true
	 */
	protected function setValue($key, $value, $expire)
	{
		$newSize = strlen(is_string($value) ? $value : serialize($value));
		$this->assertItemFitsMaximumSize($newSize);
		$oldSize = $this->_entrySizes[$key] ?? 0;
		$this->_entrySizes[$key] = $newSize;

		$this->setStoreEntry($key, [
			static::STORE_DATA => $value,
			static::STORE_EXPIRE => (int) $expire > 0 ? $this->now() + (int) $expire : 0,
		]);
		$this->setChangedDirect(true);

		if ($this->getMaximumSizeDirect() > 0) {
			$this->_accessTimes[$key] = microtime(true);
			$current = $this->getCurrentSizeDirect();
			if ($current >= 0) {
				$this->setCurrentSizeDirect($current - $oldSize + $newSize);
				$this->setSizeFingerprintDirect($this->computeSizeFingerprint());
			}
			$this->enforceMaximumSize();
		}

		return true;
	}

	/**
	 * Stores a value only when no live entry already exists under the given key.
	 *
	 * @param string $key the unique key
	 * @param mixed $value the payload to store
	 * @param int $expire TTL in seconds; 0 means never expire
	 * @return bool true when the entry was stored; false when a live entry already existed
	 */
	protected function addValue($key, $value, $expire)
	{
		if ($this->getValue($key) !== false) {
			return false;
		}
		return $this->setValue($key, $value, $expire);
	}

	/**
	 * Removes an entry from the in-memory store. When {@see getMaximumSize MaximumSize}
	 * is active, the running size total is decremented and the size fingerprint is
	 * updated so that subsequent {@see enforceMaximumSize()} calls remain accurate.
	 *
	 * @param string $key the unique key to remove
	 * @return true
	 */
	protected function deleteValue($key)
	{
		if ($this->getMaximumSizeDirect() > 0) {
			$size = $this->_entrySizes[$key] ?? 0;
			unset($this->_entrySizes[$key], $this->_accessTimes[$key]);
			$current = $this->getCurrentSizeDirect();
			if ($current >= 0) {
				$this->setCurrentSizeDirect(max(0, $current - $size));
			}
		}
		$this->clearStoreEntry($key);
		if ($this->getMaximumSizeDirect() > 0 && $this->getCurrentSizeDirect() >= 0) {
			$this->setSizeFingerprintDirect($this->computeSizeFingerprint());
		}
		$this->setChangedDirect(true);
		return true;
	}

	/**
	 * Removes all entries from the in-memory store, resets size tracking state, and
	 * marks the store as changed so that {@see save()} propagates the empty state
	 * to the backing store.
	 *
	 * @return true
	 */
	public function flush()
	{
		$this->setStoreDirect([]);
		$this->_entrySizes = [];
		$this->_accessTimes = [];
		$this->setCurrentSizeDirect(0);
		$this->setSizeFingerprintDirect($this->computeSizeFingerprint());
		$this->setChangedDirect(true);
		return true;
	}

	// --------------------------------------------------------------- TCacheSizeTrait impl

	/**
	 * Computes a fingerprint of the current in-memory key set.
	 *
	 * The fingerprint is an MD5 hash of the null-separated sorted internal keys.
	 * It changes whenever a key is added or removed, allowing {@see validateSizeCache()}
	 * to detect backing-store reloads and other out-of-band modifications.
	 *
	 * @return string the current fingerprint
	 */
	protected function computeSizeFingerprint(): string
	{
		$keys = array_keys($this->getStoreDirect());
		sort($keys);
		return md5(implode("\0", $keys));
	}

	/**
	 * Performs a full recompute of the total serialized-byte size of all in-memory
	 * entries. Also rebuilds `$_entrySizes` and seeds `$_accessTimes` for
	 * any key that has no recorded access time (entries imported via
	 * {@see load()} bypass the normal {@see setValue()} path).
	 *
	 * @return int the total byte size of all entries
	 */
	protected function computeCurrentSize(): int
	{
		$size = 0;
		$this->_entrySizes = [];
		foreach ($this->getStoreDirect() as $key => $entry) {
			$data = $entry[static::STORE_DATA] ?? '';
			$bytes = strlen(is_string($data) ? $data : serialize($data));
			$this->_entrySizes[$key] = $bytes;
			if (!array_key_exists($key, $this->_accessTimes)) {
				// Entries with no recorded access are treated as oldest for LRU.
				$this->_accessTimes[$key] = 0.0;
			}
			$size += $bytes;
		}
		// Prune stale access-time entries for keys no longer in the store.
		$this->_accessTimes = array_intersect_key($this->_accessTimes, $this->_entrySizes);
		return $size;
	}

	/**
	 * Evicts the least recently accessed entries, one at a time, until the total
	 * size of the in-memory store is at or below {@see getMaximumSize MaximumSize}.
	 * After all evictions the running size total and fingerprint are updated.
	 */
	protected function evictToFitMaximumSize(): void
	{
		$max = $this->getMaximumSizeDirect();
		// asort preserves keys and sorts by value ascending (oldest first).
		asort($this->_accessTimes);
		$current = $this->getCurrentSizeDirect();
		foreach (array_keys($this->_accessTimes) as $key) {
			if ($current <= $max) {
				break;
			}
			$size = $this->_entrySizes[$key] ?? 0;
			$this->clearStoreEntry($key);
			unset($this->_accessTimes[$key], $this->_entrySizes[$key]);
			$current -= $size;
			$this->setChangedDirect(true);
		}
		$this->setCurrentSizeDirect(max(0, $current));
		$this->setSizeFingerprintDirect($this->computeSizeFingerprint());
	}

	// --------------------------------------------------------------- accessors

	/**
	 * @return string the raw BackingCacheId field value; empty when not configured
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
	 * @return string the module ID of the backing cache; empty when not configured
	 */
	public function getBackingCacheId(): string
	{
		return $this->getBackingCacheIdDirect();
	}

	/**
	 * Sets the module ID of the backing {@see TCache} module used by
	 * {@see load()} and {@see save()}.  When both {@see getBackingCacheId
	 * BackingCacheId} and {@see getBackingFile BackingFile} are configured, the
	 * cache module takes precedence.
	 *
	 * @param string $value the module ID of a registered {@see TCache} module
	 */
	public function setBackingCacheId($value): void
	{
		$this->setBackingCacheIdDirect(TPropertyValue::ensureString($value));
	}

	/**
	 * @return string the raw BackingFile field value; empty when not configured
	 */
	protected function getBackingFileDirect(): string
	{
		return $this->_backingFile;
	}

	/**
	 * @param string $value the resolved absolute path to store directly
	 */
	protected function setBackingFileDirect(string $value): void
	{
		$this->_backingFile = $value;
	}

	/**
	 * @return string the absolute path of the backing file; empty when not configured
	 */
	public function getBackingFile(): string
	{
		return $this->getBackingFileDirect();
	}

	/**
	 * Sets the file path used by {@see load()} and {@see save()} when no
	 * {@see getBackingCacheId BackingCacheId} is configured. The directory
	 * containing the file must already exist. Namespace-style paths
	 * (e.g. `Application.runtime.memory`) are resolved via
	 * {@see \Prado\Prado::getPathOfNamespace()}.
	 *
	 * @param string $value the file path for the backing store
	 * @throws TConfigurationException when the parent directory does not exist
	 */
	public function setBackingFile($value): void
	{
		$value = TPropertyValue::ensureString($value);
		if ($value === '') {
			$this->setBackingFileDirect('');
			return;
		}
		if (($path = Prado::getPathOfNamespace($value)) !== null) {
			$value = $path;
		}
		$dir = dirname($value);
		if (!is_dir($dir)) {
			throw new TConfigurationException('memorycache_backing_file_directory_not_found', $value);
		}
		$this->setBackingFileDirect($value);
	}

	/**
	 * @return string the raw BackingCacheKey field value
	 */
	protected function getBackingCacheKeyDirect(): string
	{
		return $this->_backingCacheKey;
	}

	/**
	 * @param string $value the backing cache key to store directly
	 */
	protected function setBackingCacheKeyDirect(string $value): void
	{
		$this->_backingCacheKey = $value;
	}

	/**
	 * Returns the key used to store the serialized cache store in the backing
	 * cache module. Defaults to {@see DEFAULT_BACKING_CACHE_KEY}, optionally
	 * suffixed with `'.<moduleId>'` by {@see init()} when a module ID is available.
	 *
	 * @return string the backing cache key
	 */
	public function getBackingCacheKey(): string
	{
		return $this->getBackingCacheKeyDirect();
	}

	/**
	 * Sets the key used to read and write the entire serialized in-memory store
	 * within the backing cache module. Set this to avoid collisions when multiple
	 * TMemoryCache modules share the same backing cache.
	 *
	 * @param string $value the cache key string
	 */
	public function setBackingCacheKey($value): void
	{
		$this->setBackingCacheKeyDirect(TPropertyValue::ensureString($value));
	}

	/**
	 * @return string the raw MergePolicy field value; one of {@see MERGE} or {@see REPLACE}
	 */
	protected function getMergePolicyDirect(): string
	{
		return $this->_mergePolicy;
	}

	/**
	 * @param string $value the merge policy to store directly
	 */
	protected function setMergePolicyDirect(string $value): void
	{
		$this->_mergePolicy = $value;
	}

	/**
	 * @return string the active merge policy; one of {@see MERGE} or {@see REPLACE}.
	 *   Defaults to {@see DEFAULT_MERGE_POLICY}.
	 */
	public function getMergePolicy(): string
	{
		return $this->getMergePolicyDirect();
	}

	/**
	 * Sets the merge policy applied by {@see load()} when importing data into a
	 * non-empty in-memory store.
	 *
	 * - `Merge` — keys already present in memory are preserved; only absent
	 *   keys are imported from the backing store.
	 * - `Replace` — the in-memory store is completely replaced by the backing data.
	 *
	 * @param string $value one of {@see MERGE} (`'Merge'`) or {@see REPLACE} (`'Replace'`)
	 * @throws \Prado\Exceptions\TInvalidDataValueException when the value is not a valid policy name
	 */
	public function setMergePolicy($value): void
	{
		$this->setMergePolicyDirect(TPropertyValue::ensureEnum($value, [self::MERGE, self::REPLACE]));
	}

	// --------------------------------------------------------------- accessors (HashKeys)

	/**
	 * @return ?bool the raw HashKeys field value
	 */
	protected function getHashKeysDirect(): ?bool
	{
		return $this->_hashKeys;
	}

	/**
	 * @param ?bool $value the value to store directly
	 */
	protected function setHashKeysDirect(?bool $value): void
	{
		$this->_hashKeys = $value;
	}

	/**
	 * Returns whether cache keys are hashed before use.
	 *
	 * - `null` *(default)* — automatic: off in {@see TApplicationMode::Debug},
	 *   on in {@see TApplicationMode::Normal} and {@see TApplicationMode::Performance}.
	 * - `true` — always hash (MD5 via {@see TCache::generateUniqueKey()}).
	 * - `false` — never hash; the raw key (with prefix) is used as-is.
	 *
	 * @return ?bool the current HashKeys setting
	 */
	public function getHashKeys(): ?bool
	{
		return $this->getHashKeysDirect();
	}

	/**
	 * Sets whether cache keys are hashed before use.
	 *
	 * Accepts `true`, `false`, or `null` directly, as well as the strings
	 * `'true'`, `'false'`, `'null'`, and `''` for XML configuration.
	 *
	 * @param mixed $value `true`, `false`, or `null` (also accepts string equivalents)
	 */
	public function setHashKeys($value): void
	{
		if (is_string($value)) {
			if ($value === '' || strcasecmp($value, 'null') === 0) {
				$value = null;
			} else {
				$value = TPropertyValue::ensureBoolean($value);
			}
		} elseif ($value !== null) {
			$value = (bool) $value;
		}
		$this->setHashKeysDirect($value);
	}

	// --------------------------------------------------------------- accessors (Changed)

	/**
	 * @return bool the raw dirty-flag value
	 */
	protected function getChangedDirect(): bool
	{
		return $this->_changed;
	}

	/**
	 * @param bool $value the dirty-flag value to store directly
	 */
	protected function setChangedDirect(bool $value): void
	{
		$this->_changed = $value;
	}

	/**
	 * @return bool true when the in-memory store has been modified since the last
	 *   {@see load()} or successful {@see save()}; false when the store is in sync
	 *   with the backing store. {@see save()} skips the backing write when this
	 *   returns false.
	 */
	public function getChanged(): bool
	{
		return $this->getChangedDirect();
	}

	// --------------------------------------------------------------- helpers

	/**
	 * Returns the current Unix timestamp. Extracted to allow subclasses and
	 * test doubles to control clock behavior without modifying real system time.
	 *
	 * @return int the current Unix timestamp in seconds
	 */
	protected function now(): int
	{
		return time();
	}

	/**
	 * Serializes a store snapshot to a string for file-based persistence.
	 * Override to substitute a different serialization format.
	 *
	 * @param mixed $value the value to serialize
	 * @return string the serialized representation
	 */
	protected function serialize(mixed $value): string
	{
		return serialize($value);
	}

	/**
	 * Unserializes a string produced by {@see serialize()}.
	 * Returns false when the string is not valid serialized data.
	 *
	 * @param string $data the serialized string to decode
	 * @return mixed the unserialized value, or false on failure
	 */
	protected function unserialize(string $data): mixed
	{
		return @unserialize($data);
	}

	/**
	 * Reads and returns the entire contents of a file.
	 * Returns false when the file cannot be read.
	 *
	 * @param string $filePath the path of the file to read
	 * @return false|string the file contents, or false on failure
	 */
	protected function getContents(string $filePath): string|false
	{
		return @file_get_contents($filePath);
	}

	/**
	 * Writes data to a file with exclusive locking, replacing its current contents.
	 * Returns false when the file cannot be written.
	 *
	 * @param string $filePath the path of the file to write
	 * @param string $data the data to write
	 * @return false|int the number of bytes written, or false on failure
	 */
	protected function putContents(string $filePath, string $data): int|false
	{
		return @file_put_contents($filePath, $data, LOCK_EX);
	}

	// --------------------------------------- serialization / cloning

	/**
	 * Excludes transient and default-valued fields from serialization so that a
	 * serialized TMemoryCache does not carry process-scoped state across process
	 * boundaries. The in-memory store, dirty flag, LRU access times, per-key size
	 * bookkeeping, and the size-cache state are always excluded — they are all
	 * rebuilt during {@see init()} or on the first {@see enforceMaximumSize()} call.
	 *
	 * @param array $exprops excluded-properties list, passed by reference
	 */
	protected function _getZappableSleepProps(&$exprops)
	{
		parent::_getZappableSleepProps($exprops);
		$exprops[] = "\0" . __CLASS__ . "\0_store";
		$exprops[] = "\0" . __CLASS__ . "\0_changed";
		$exprops[] = "\0" . __CLASS__ . "\0_entrySizes";
		$exprops[] = "\0" . __CLASS__ . "\0_accessTimes";
		// TCacheSizeTrait fields — private props scoped to this class.
		$exprops[] = "\0" . __CLASS__ . "\0_currentSize";
		$exprops[] = "\0" . __CLASS__ . "\0_sizeFingerprint";
		if ($this->getMaximumSizeDirect() === 0) {
			$exprops[] = "\0" . __CLASS__ . "\0_maximumSize";
		}
		if ($this->getBackingCacheIdDirect() === '') {
			$exprops[] = "\0" . __CLASS__ . "\0_backingCacheId";
		}
		if ($this->getBackingFileDirect() === '') {
			$exprops[] = "\0" . __CLASS__ . "\0_backingFile";
		}
		if ($this->getBackingCacheKeyDirect() === static::DEFAULT_BACKING_CACHE_KEY) {
			$exprops[] = "\0" . __CLASS__ . "\0_backingCacheKey";
		}
		if ($this->getMergePolicyDirect() === static::DEFAULT_MERGE_POLICY) {
			$exprops[] = "\0" . __CLASS__ . "\0_mergePolicy";
		}
		if ($this->getHashKeysDirect() === null) {
			$exprops[] = "\0" . __CLASS__ . "\0_hashKeys";
		}
	}
}
