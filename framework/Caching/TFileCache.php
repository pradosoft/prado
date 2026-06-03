<?php

/**
 * TFileCache class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Caching;

use Prado\Exceptions\TConfigurationException;
use Prado\Prado;
use Prado\TPropertyValue;
use Prado\Util\Cron\TCronTaskInfo;

/**
 * TFileCache class.
 *
 * TFileCache implements a file-based {@see TSerializingCache} application module.
 * Each cache entry is stored as a single file under the configured
 * {@see getDirectory Directory}, named by a SHA-1 hash of the internal key. The
 * file contains the absolute expiry timestamp on the first line followed by the
 * serialized payload produced by {@see TSerializingCache} (which may also be
 * {@see getEncrypt encrypted} and {@see getEncoding encoded}).
 *
 * TFileCache requires no external extensions — it works on any PHP host with a
 * writable filesystem. For high-throughput deployments, prefer a shared-memory
 * cache such as {@see \Prado\Caching\TAPCCache},
 * {@see \Prado\Caching\TMemCache}, or {@see \Prado\Caching\TRedisCache}.
 *
 * **Concurrency**: writes use `tempnam()` + atomic `rename()` so that concurrent
 * readers never see a partially-written cache file.
 *
 * **TTL semantics**: a `$expire` of `0` passed to {@see TCache::set} or
 * {@see TCache::add} falls back to the {@see getDefaultTtl DefaultTtl} property.
 * If `DefaultTtl` is also `0` the entry never expires.
 *
 * **Cache dependencies** ({@see ICacheDependency}) are honored: the dependency is
 * serialized alongside the value and re-validated on every {@see TCache::get} by
 * the {@see TCache} base class.
 *
 * **Maximum size**: when {@see getMaximumSize MaximumSize} is greater than `0`, the
 * module enforces a total on-disk byte limit measured by `filesize()` across all
 * `.cache` files in the directory. After every write the total is checked and the
 * files closest to expiry are deleted one at a time until the directory fits within the
 * limit (never-expiring entries are evicted last). A value of `0` (the default) means no
 * size limit is enforced.
 *
 * **Expiry in metadata**: each file's modification time (`mtime`) mirrors the entry's
 * absolute expiry (never-expiring entries use the {@see NEVER_EXPIRES_MTIME} sentinel), so
 * {@see flushCacheExpired()} and the size eviction read the expiry from `filemtime()`
 * without opening any file. The file content still carries the expiry on its first line as
 * the authoritative source for {@see TCache::get}.
 *
 * **Expired-file sweeps**: on every application `OnSaveState`, {@see flushCacheExpired()}
 * deletes files whose TTL has passed, at most once per {@see getFlushInterval FlushInterval}
 * seconds (default 60; set to `0` to disable the automatic sweep). The module also
 * registers a cron task (`filecacheflush`) via the global `fxGetCronTaskInfos`
 * event so the sweep can be scheduled externally — mirroring {@see \Prado\Caching\TDbCache}.
 *
 * If loaded, TFileCache will register itself with {@see \Prado\TApplication} as the
 * cache module. It can be accessed via {@see \Prado\TApplication::getCache()}.
 *
 * Some usage examples of TFileCache are as follows,
 * ```php
 * $cache = new TFileCache;  // TFileCache may also be loaded as a Prado application module
 * $cache->init(null);
 * $cache->add('object', $object);
 * $object2 = $cache->get('object');
 * ```
 *
 * **XML configuration** (`application.xml`):
 * ```xml
 * <module id="cache" class="Prado\Caching\TFileCache"
 *         Directory="Application.runtime.cache"
 *         DefaultTtl="3600"
 *         MaximumSize="104857600" />
 * ```
 *
 * **PHP configuration** (`application.php`):
 * ```php
 * return [
 *     'modules' => [
 *         'cache' => [
 *             'class' => 'Prado\Caching\TFileCache',
 *             'properties' => [
 *                 'Directory'  => 'Application.runtime.cache',
 *                 'DefaultTtl' => '3600',
 *                 'MaximumSize' => '104857600',
 *             ],
 *         ],
 *     ],
 * ];
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TFileCache extends TSerializingCache implements ICacheSize
{
	use TCacheSizeTrait;
	use TCacheFileTrait;

	/** Default filename prefix for the atomic temporary write files. */
	public const CACHE_FILE_PREFIX = '.prado-cache-';

	/** Sentinel `mtime` marking a never-expiring entry; non-zero and `>= 2` to avoid stray `0`/`1` mtimes. */
	public const NEVER_EXPIRES_MTIME = 3;

	/** Default interval in seconds between automatic expired-file sweeps. */
	public const DEFAULT_FLUSH_INTERVAL = 60;

	/** @var string Absolute path to the cache directory; empty until configured. */
	private string $_dir = '';

	/** @var int Default TTL in seconds; 0 means never expire. */
	private int $_defaultTtl = 0;

	/** @var string Filename prefix used when creating atomic temporary write files. */
	private string $_tempFilePrefix = '';

	/** @var int Interval in seconds between automatic expired-file sweeps; 0 disables them. */
	private int $_flushInterval = self::DEFAULT_FLUSH_INTERVAL;

	// ---------------------------------------------------------------- lifecycle

	/**
	 * Creates a new TFileCache instance, optionally pre-configuring the cache
	 * directory and default TTL, and seeding {@see getTempFilePrefix TempFilePrefix}
	 * from {@see CACHE_FILE_PREFIX}.
	 *
	 * @param string $directory the cache directory; created if it does not exist.
	 *   Pass an empty string (default) to use the application runtime path.
	 * @param int $defaultTtl the default TTL in seconds (0 = never expire)
	 */
	public function __construct(string $directory = '', int $defaultTtl = 0)
	{
		parent::__construct();
		if ($directory !== '') {
			$this->setDirectory($directory);
		}
		$this->setDefaultTtl($defaultTtl);
		$this->setTempFilePrefix(static::CACHE_FILE_PREFIX);
		$this->setFlushInterval(static::DEFAULT_FLUSH_INTERVAL);
	}

	/**
	 * @return bool always true; file-based caching has no external dependency.
	 */
	public static function getIsAvailable(): bool
	{
		return true;
	}

	/**
	 * Initializes the cache module. When no {@see getDirectory Directory} has been
	 * set, a `filecache/` subdirectory under the application runtime path is used
	 * and created if necessary. Throws when the directory is not writable.
	 *
	 * @param null|array|\Prado\Xml\TXmlElement $config module configuration
	 * @throws TConfigurationException when the directory cannot be created or is
	 *   not writable, or when the {@see hashToken} implementation does not properly
	 *   hash its input (returns the token unchanged, or returns an unsafe value
	 *   containing a path separator)
	 */
	public function init($config)
	{
		$directory = $this->getDirectory();
		if ($directory === '') {
			$directory = $this->getApplication()->getRuntimePath() . DIRECTORY_SEPARATOR . 'filecache';
			$this->setDirectory($directory);
			$directory = $this->getDirectory();
		}
		if (!is_writable($directory)) {
			throw new TConfigurationException('filecache_directory_not_writable', $directory);
		}
		$sentinel = 'prado-filecache/hash-check';
		$hash = $this->hashToken($sentinel);
		if ($hash === $sentinel) {
			throw new TConfigurationException('filecache_hash_token_identity');
		}
		if (strpbrk($hash, '/\\') !== false) {
			throw new TConfigurationException('filecache_hash_token_path_separator');
		}
		$this->getApplication()?->attachEventHandler('OnSaveState', [$this, 'doFlushCacheExpired']);
		parent::init($config);
	}

	// ---------------------------------------------------------------- accessors

	/**
	 * @return string the raw Directory field value
	 */
	protected function getDirectoryDirect(): string
	{
		return $this->_dir;
	}

	/**
	 * @param string $value the resolved directory path to store directly
	 */
	protected function setDirectoryDirect(string $value): void
	{
		$this->_dir = $value;
	}

	/**
	 * @return string the absolute path to the cache directory
	 */
	public function getDirectory()
	{
		return $this->getDirectoryDirect();
	}

	/**
	 * Sets the cache directory, creating it (recursively) when it does not exist.
	 *
	 * @param string $value the directory path; namespace-style paths
	 *   (e.g. `Application.runtime.cache`) are resolved via
	 *   {@see \Prado\Prado::getPathOfNamespace()}
	 * @throws TConfigurationException when the value is empty, or when the
	 *   directory does not exist and cannot be created
	 */
	public function setDirectory($value)
	{
		$this->assertUninitialized('Directory');
		$value = TPropertyValue::ensureString($value);
		if ($value === '') {
			throw new TConfigurationException('filecache_directory_required');
		}
		// Resolve namespace-style paths (e.g. "Application.runtime.cache").
		if (($path = Prado::getPathOfNamespace($value)) !== null) {
			$value = $path;
		}
		if (!is_dir($value) && !@mkdir($value, 0o755, true) && !is_dir($value)) {
			throw new TConfigurationException('filecache_directory_create_failed', $value);
		}
		$this->setDirectoryDirect(rtrim(realpath($value) ?: $value, '/\\'));
	}

	/**
	 * @return int the raw DefaultTtl field value; 0 means entries never expire
	 */
	protected function getDefaultTtlDirect(): int
	{
		return $this->_defaultTtl;
	}

	/**
	 * @param int $value the default TTL in seconds
	 */
	protected function setDefaultTtlDirect(int $value): void
	{
		$this->_defaultTtl = $value;
	}

	/**
	 * @return int the default TTL in seconds; 0 means entries never expire
	 */
	public function getDefaultTtl()
	{
		return $this->getDefaultTtlDirect();
	}

	/**
	 * @param int $value the default TTL in seconds; values below zero are clamped to 0
	 */
	public function setDefaultTtl($value)
	{
		$this->setDefaultTtlDirect(max(0, TPropertyValue::ensureInteger($value)));
	}

	/**
	 * @return string the raw TempFilePrefix field value
	 */
	protected function getTempFilePrefixDirect(): string
	{
		return $this->_tempFilePrefix;
	}

	/**
	 * @param string $value the filename prefix to store directly
	 */
	protected function setTempFilePrefixDirect(string $value): void
	{
		$this->_tempFilePrefix = $value;
	}

	/**
	 * @return string the filename prefix used when creating atomic temporary write files
	 */
	public function getTempFilePrefix()
	{
		return $this->getTempFilePrefixDirect();
	}

	/**
	 * Sets the filename prefix applied to the atomic temporary files created
	 * during cache writes. The prefix is passed directly to {@see tempnam} and
	 * therefore follows the same length constraints as that function.
	 *
	 * @param string $value the filename prefix (e.g. `.my-cache-`)
	 */
	public function setTempFilePrefix($value)
	{
		$this->setTempFilePrefixDirect(TPropertyValue::ensureString($value));
	}

	// ---------------------------------------------------------------- ICache impl


	/**
	 * Retrieves the stored serialized payload for a unique key, using the file's
	 * first-line expiry header as the authoritative liveness check; an expired entry is
	 * deleted and reported as a miss.
	 *
	 * @param string $key the unique key
	 * @return false|string the stored serialized payload, or false if the entry is
	 *   missing, malformed, or expired
	 */
	protected function getSerializedValue(string $key): false|string
	{
		$file = $this->pathFor($key);
		if (!$this->isFile($file)) {
			return false;
		}
		$raw = $this->getContents($file);
		if ($raw === false || $raw === '') {
			return false;
		}
		$pos = strpos($raw, "\n");
		if ($pos === false) {
			return false;
		}
		$expire = (int) substr($raw, 0, $pos);
		if ($expire > 0 && $expire <= $this->time()) {
			$this->unlink($file);
			return false;
		}
		return substr($raw, $pos + 1);
	}

	/**
	 * Stores a serialized payload under the given unique key, overwriting any existing entry.
	 *
	 * @param string $key the unique key
	 * @param string $value the serialized payload to store
	 * @param int $expire TTL in seconds; 0 falls back to {@see getDefaultTtl}
	 * @return bool true on success
	 */
	protected function setSerializedValue(string $key, string $value, int $expire): bool
	{
		return $this->writeEntry($key, $value, $expire, false);
	}

	/**
	 * Stores a serialized payload only when no live entry already exists under the key.
	 *
	 * @param string $key the unique key
	 * @param string $value the serialized payload to store
	 * @param int $expire TTL in seconds; 0 falls back to {@see getDefaultTtl}
	 * @return bool true when the entry was stored; false when a live entry
	 *   already existed
	 */
	protected function addSerializedValue(string $key, string $value, int $expire): bool
	{
		$file = $this->pathFor($key);
		if ($this->isFile($file) && $this->getSerializedValue($key) !== false) {
			return false;
		}
		return $this->writeEntry($key, $value, $expire, true);
	}

	/**
	 * Deletes an entry by its unique key.
	 *
	 * @param string $key the unique key
	 * @return bool true on success; also true when the entry was not present
	 */
	protected function deleteValue($key)
	{
		$file = $this->pathFor($key);
		if ($this->isFile($file)) {
			return $this->unlink($file);
		}
		return true;
	}

	/**
	 * Deletes all `*.cache` files in the configured directory and, on full success,
	 * resets the size tracking state to reflect the now-empty directory.
	 *
	 * @return bool true when all files are removed successfully; false when at
	 *   least one file could not be removed
	 */
	public function flush()
	{
		$dir = $this->getDirectory();
		if (!is_dir($dir)) {
			$this->setCurrentSizeDirect(0);
			$this->setSizeFingerprintDirect($this->computeSizeFingerprint());
			return true;
		}
		$ok = true;
		foreach (glob($dir . DIRECTORY_SEPARATOR . '*.cache') ?: [] as $f) {
			if (!$this->unlink($f)) {
				$ok = false;
			}
		}
		if ($ok) {
			$this->setCurrentSizeDirect(0);
			$this->setSizeFingerprintDirect($this->computeSizeFingerprint());
		}
		return $ok;
	}

	/**
	 * Writes a cache entry atomically using a temp file + rename. When
	 * {@see getMaximumSize MaximumSize} is active, the serialized size of the entry
	 * is checked before any file I/O — an oversized item is rejected immediately
	 * rather than written and then evicted. After a successful write the size
	 * fingerprint is invalidated so that the next {@see enforceMaximumSize()} call
	 * triggers a full recompute of on-disk totals — which handles both new files and
	 * overwrites of existing files whose size may have changed.
	 *
	 * @param string $key the unique key
	 * @param string $value the serialized payload to store
	 * @param int $expire TTL in seconds; 0 falls back to {@see getDefaultTtl}
	 * @param bool $exclusive when true, aborts if the final file already exists
	 *   (used by {@see addSerializedValue} to prevent overwriting a live entry)
	 * @throws \Prado\Exceptions\TInvalidDataValueException when MaximumSize is active
	 *   and the entry exceeds it
	 * @return bool true on success
	 */
	protected function writeEntry(string $key, string $value, int $expire, bool $exclusive): bool
	{
		$ttl = $expire > 0 ? $expire : $this->getDefaultTtl();
		$expireAt = $ttl > 0 ? $this->time() + $ttl : 0;
		$serialized = $expireAt . "\n" . $value;
		$this->assertItemFitsMaximumSize(strlen($serialized));
		$file = $this->pathFor($key);
		$tmpFile = $this->tempnam($this->getDirectory(), $this->getTempFilePrefix());
		if ($tmpFile === false) {
			return false;
		}
		if ($this->putContents($tmpFile, $serialized) === false) {
			$this->unlink($tmpFile);
			return false;
		}
		$this->chmod($tmpFile, 0o644);
		if ($exclusive && $this->isFile($file)) {
			$this->unlink($tmpFile);
			return false;
		}
		if (!$this->rename($tmpFile, $file)) {
			$this->unlink($tmpFile);
			return false;
		}
		// Mirror the expiry into the file's mtime so the sweep and eviction can read it from
		// filemtime() without opening the file. Never-expiring entries (header expiry 0) use
		// the NEVER_EXPIRES_MTIME sentinel instead.
		$this->touch($file, $expireAt > 0 ? $expireAt : static::NEVER_EXPIRES_MTIME);
		if ($this->getMaximumSizeDirect() > 0) {
			// Invalidate fingerprint so validateSizeCache() triggers a full recompute.
			$this->setSizeFingerprintDirect('');
			$this->enforceMaximumSize();
		}
		return true;
	}

	// ------------------------------------------------------------------ expired-file flushing

	/**
	 * @return int interval in seconds between automatic expired-file sweeps. `0` disables
	 *   the automatic sweep (e.g. when flushing externally via the cron task). Defaults to 60.
	 */
	public function getFlushInterval()
	{
		return $this->getFlushIntervalDirect();
	}

	/**
	 * Sets the interval between automatic expired-file sweeps. Set to `0` to disable the
	 * automatic sweep and rely on the {@see fxGetCronTaskInfos cron task} instead.
	 * @param int $value the interval in seconds; values below zero are clamped to 0.
	 */
	public function setFlushInterval($value)
	{
		$this->setFlushIntervalDirect(max(0, TPropertyValue::ensureInteger($value)));
	}

	/**
	 * @return int the raw FlushInterval field value
	 */
	protected function getFlushIntervalDirect(): int
	{
		return $this->_flushInterval;
	}

	/**
	 * @param int $value the interval in seconds to store directly
	 */
	protected function setFlushIntervalDirect(int $value): void
	{
		$this->_flushInterval = $value;
	}

	/**
	 * Event listener for the application `OnSaveState` event; sweeps expired files subject
	 * to {@see getFlushInterval FlushInterval}.
	 */
	public function doFlushCacheExpired(): void
	{
		$this->flushCacheExpired(false);
	}

	/**
	 * Deletes expired cache files from the directory. When {@see getFlushInterval
	 * FlushInterval} is `0` and `$force` is false, the sweep is skipped; otherwise it runs
	 * at most once per interval, tracked through application global state.
	 * @param bool $force when true, ignores the interval and sweeps immediately (the cron
	 *   task uses this).
	 */
	public function flushCacheExpired($force = false): void
	{
		$interval = $this->getFlushInterval();
		if (!$force && $interval === 0) {
			return;
		}
		$key = 'TFileCache:' . $this->getDirectory() . ':flushed';
		$now = $this->time();
		$next = $interval + (int) $this->getApplication()->getGlobalState($key, 0);
		if ($force || $next <= $now) {
			Prado::trace(($force ? 'Force flush of expired files: ' : 'Flush expired files: ') . $this->getDirectory(), TFileCache::class);
			$this->deleteExpiredFiles($now);
			$this->getApplication()->setGlobalState($key, $now);
		}
	}

	/**
	 * Scans the cache directory and deletes every `.cache` file whose stored expiry has
	 * passed. When {@see getMaximumSize MaximumSize} is active, the size fingerprint is
	 * invalidated so the running total is recomputed on the next access.
	 * @param int $now the reference timestamp for expiry comparison.
	 */
	protected function deleteExpiredFiles(int $now): void
	{
		$dir = $this->getDirectory();
		if ($dir === '' || !is_dir($dir)) {
			return;
		}
		clearstatcache();
		$deleted = false;
		foreach (glob($dir . DIRECTORY_SEPARATOR . '*.cache') ?: [] as $file) {
			// The mtime mirrors the entry's absolute expiry, so the sweep reads it from
			// filesystem metadata without opening the file. The NEVER_EXPIRES_MTIME sentinel
			// is skipped; any other past mtime (including stray 0/1) is swept.
			$expire = @filemtime($file);
			if ($expire !== false && $expire !== static::NEVER_EXPIRES_MTIME && $expire <= $now && $this->unlink($file)) {
				$deleted = true;
			}
		}
		if ($deleted && $this->getMaximumSizeDirect() > 0) {
			// Invalidate the fingerprint so the running size total recomputes on next use.
			$this->setSizeFingerprintDirect('');
		}
	}

	/**
	 * Provides the cron task that clears out expired cache files, raised via the global
	 * `fxGetCronTaskInfos` event.
	 * @param object $sender the object raising fxGetCronTaskInfos.
	 * @param mixed $param the parameter.
	 */
	public function fxGetCronTaskInfos($sender, $param)
	{
		return Prado::createComponent(TCronTaskInfo::class, 'filecacheflush', $this->getId() . '->flushCacheExpired(true)', $this, Prado::localize('FileCache Flush Expired Files'), Prado::localize('This manually clears out the expired files of TFileCache.'));
	}

	// ------------------------------------------------------------------ TCacheSizeTrait impl

	/**
	 * Computes a fingerprint of the current `.cache` file set in the cache directory.
	 *
	 * The fingerprint is an MD5 hash of the null-separated sorted basenames of all
	 * `.cache` files. It changes when any file is added or removed, allowing
	 * {@see validateSizeCache()} to trigger a full recompute when the file set has
	 * changed since the last check.
	 *
	 * @return string the current fingerprint
	 */
	protected function computeSizeFingerprint(): string
	{
		$dir = $this->getDirectory();
		if ($dir === '' || !is_dir($dir)) {
			return sha1('');
		}
		$files = glob($dir . DIRECTORY_SEPARATOR . '*.cache') ?: [];
		sort($files);
		return sha1(implode("\0", array_map('basename', $files)));
	}

	/**
	 * Performs a full recompute of the total on-disk byte size of all `.cache` files
	 * in the cache directory, using `filesize()`.
	 *
	 * @return int the total byte size of all cache files
	 */
	protected function computeCurrentSize(): int
	{
		$dir = $this->getDirectory();
		if ($dir === '' || !is_dir($dir)) {
			return 0;
		}
		$size = 0;
		foreach (glob($dir . DIRECTORY_SEPARATOR . '*.cache') ?: [] as $f) {
			$s = @filesize($f);
			if ($s !== false) {
				$size += $s;
			}
		}
		return $size;
	}

	/**
	 * Evicts cache files in soonest-to-expire order, read from each file's `mtime`
	 * (which mirrors the absolute expiry), one at a time until the total on-disk size is
	 * at or below {@see getMaximumSize MaximumSize}. Never-expiring entries
	 * ({@see NEVER_EXPIRES_MTIME}) sort last and are evicted only when nothing else
	 * remains. After all evictions the running size total and fingerprint are updated.
	 */
	protected function evictToFitMaximumSize(): void
	{
		$dir = $this->getDirectory();
		if ($dir === '' || !is_dir($dir)) {
			return;
		}
		$max = $this->getMaximumSizeDirect();
		$files = [];
		foreach (glob($dir . DIRECTORY_SEPARATOR . '*.cache') ?: [] as $f) {
			$mtime = @filemtime($f);
			$fsize = @filesize($f);
			if ($mtime !== false && $fsize !== false) {
				// mtime mirrors the absolute expiry; the never-expire sentinel sorts last.
				$files[$f] = ['expire' => $mtime === static::NEVER_EXPIRES_MTIME ? PHP_INT_MAX : $mtime, 'size' => $fsize];
			}
		}
		// Sort by expiry ascending so the soonest-to-expire file is evicted first.
		uasort($files, static fn ($a, $b): int => $a['expire'] <=> $b['expire']);
		$current = $this->getCurrentSizeDirect();
		foreach ($files as $f => $info) {
			if ($current <= $max) {
				break;
			}
			if ($this->unlink($f)) {
				$current -= $info['size'];
			}
		}
		$this->setCurrentSizeDirect(max(0, $current));
		$this->setSizeFingerprintDirect($this->computeSizeFingerprint());
	}

	// ------------------------------------------------------------------ helpers

	/**
	 * Returns the absolute filesystem path for a given cache key.
	 * The key has already been hashed by {@see generateUniqueKey} so it is used
	 * directly as the filename without further transformation.
	 *
	 * @param string $key the unique key produced by {@see generateUniqueKey}
	 * @return string the absolute path to the cache file
	 */
	protected function pathFor(string $key): string
	{
		return $this->getDirectory() . DIRECTORY_SEPARATOR . $key . '.cache';
	}

	// ------------------------------------------------------------------ encapsulation

	/**
	 * Produces a filesystem-safe unique key via {@see hashToken} and validates the
	 * result: the hash must differ from the raw token and must contain no path
	 * separators, preventing a broken or malicious override from escaping the cache
	 * directory.
	 * @param string $key a key identifying a value to be cached
	 * @throws \Prado\Exceptions\TConfigurationException when {@see hashToken} does
	 *   not properly hash its input
	 * @return string a key generated from the provided key which ensures uniqueness across applications
	 */
	protected function generateUniqueKey($key)
	{
		$token = $this->getKeyPrefix() . $key;
		$hash = $this->hashToken($token);
		if ($hash === $token) {
			throw new TConfigurationException('filecache_hash_token_identity');
		}
		if (strpbrk($hash, '/\\') !== false) {
			throw new TConfigurationException('filecache_hash_token_path_separator');
		}
		return $hash;
	}

	/**
	 * Hashes a token for use as a cache filename. Override to substitute a
	 * different algorithm. The result must transform the input (not return it
	 * unchanged) and must contain no `"/"` or `"\"` path separators; both
	 * constraints are enforced by {@see init()} and {@see generateUniqueKey()}.
	 * @param string $token the raw token to hash
	 * @return string the hashed token
	 */
	protected function hashToken(string $token): string
	{
		return sha1($token);
	}

	/**
	 * Returns whether the given path refers to an existing regular file.
	 * Extracted to allow subclasses and test doubles to intercept filesystem
	 * existence checks.
	 *
	 * @param string $path the filesystem path to test
	 * @return bool true when the path exists and is a regular file
	 */
	protected function isFile(string $path): bool
	{
		return is_file($path);
	}

	/**
	 * Creates a uniquely named temporary file in the given directory and returns
	 * its path. Returns false when the file cannot be created.
	 *
	 * @param string $dir the directory in which to create the temporary file
	 * @param string $prefix the filename prefix for the temporary file
	 * @return false|string the path of the created temporary file, or false on failure
	 */
	protected function tempnam(string $dir, string $prefix): string|false
	{
		return @tempnam($dir, $prefix);
	}

	/**
	 * Deletes a file from the filesystem.
	 *
	 * @param string $filePath the path of the file to delete
	 * @return bool true on success, false on failure
	 */
	protected function unlink(string $filePath): bool
	{
		return @unlink($filePath);
	}

	/**
	 * Renames (moves) a file from one path to another.
	 *
	 * @param string $srcFilePath the source path
	 * @param string $destFilePath the destination path
	 * @return bool true on success, false on failure
	 */
	protected function rename(string $srcFilePath, string $destFilePath): bool
	{
		return @rename($srcFilePath, $destFilePath);
	}

	/**
	 * Sets the permissions on a file.
	 *
	 * @param string $filePath the path of the file
	 * @param int $mode the permissions bitmask (e.g. `0o644`)
	 * @return bool true on success, false on failure
	 */
	protected function chmod(string $filePath, int $mode): bool
	{
		return @chmod($filePath, $mode);
	}

	/**
	 * Sets a file's modification time. {@see writeEntry()} sets it to the entry's absolute
	 * expiry (`0` for never-expire) so that {@see deleteExpiredFiles()} and
	 * {@see evictToFitMaximumSize()} can read the expiry from `filemtime()` without opening
	 * the file. The PHP stat cache for the path is cleared so the new mtime is seen
	 * immediately within the same request.
	 *
	 * @param string $filePath the path of the file to touch
	 * @param ?int $mtime the modification time to set; `null` uses the current time
	 * @return bool true on success, false on failure
	 */
	protected function touch(string $filePath, ?int $mtime = null): bool
	{
		$ok = $mtime === null ? @touch($filePath) : @touch($filePath, $mtime);
		clearstatcache(true, $filePath);
		return $ok;
	}

	// --------------------------------------- serialization / cloning

	/**
	 * Excludes transient and default-valued fields from serialization. The size-cache
	 * state (`$_currentSize` and `$_sizeFingerprint`) is always excluded because it is
	 * recomputed lazily on demand. `$_maximumSize` is excluded when it holds its
	 * default value of `0`.
	 *
	 * @param array $exprops excluded-properties list, passed by reference
	 */
	protected function _getZappableSleepProps(&$exprops)
	{
		parent::_getZappableSleepProps($exprops);
		// TCacheSizeTrait fields — private props scoped to this class.
		$exprops[] = "\0" . __CLASS__ . "\0_currentSize";
		$exprops[] = "\0" . __CLASS__ . "\0_sizeFingerprint";
		if ($this->getMaximumSizeDirect() === 0) {
			$exprops[] = "\0" . __CLASS__ . "\0_maximumSize";
		}
		if ($this->getDefaultTtlDirect() === 0) {
			$exprops[] = "\0" . __CLASS__ . "\0_defaultTtl";
		}
		if ($this->getTempFilePrefixDirect() === static::CACHE_FILE_PREFIX) {
			$exprops[] = "\0" . __CLASS__ . "\0_tempFilePrefix";
		}
		if ($this->getFlushIntervalDirect() === static::DEFAULT_FLUSH_INTERVAL) {
			$exprops[] = "\0" . __CLASS__ . "\0_flushInterval";
		}
	}
}
