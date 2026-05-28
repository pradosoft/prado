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

/**
 * TFileCache class.
 *
 * TFileCache implements a file-based {@see TCache} application module. Each cache
 * entry is stored as a single file under the configured
 * {@see getDirectory Directory}, named by a SHA-1 hash of the internal key. The
 * file contains a serialized array with the absolute expiry timestamp and the
 * serialized payload.
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
 * least recently used files (by `mtime`, updated on each {@see TCache::get}) are
 * deleted one at a time until the directory fits within the limit, following the
 * algorithm used by Apple Foundation's `NSCache`. A value of `0` (the default)
 * means no size limit is enforced.
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
class TFileCache extends TCache implements ICacheSize
{
	use TCacheSizeTrait;

	/** Default filename prefix for the atomic temporary write files. */
	public const CACHE_FILE_PREFIX = '.prado-cache-';

	/** Payload array key for the cached value. */
	protected const CACHE_VALUE = 'value';

	/** Payload array key for the absolute expiry timestamp (Unix seconds; 0 = never expires). */
	protected const CACHE_EXPIRED = 'expired';

	/** @var string Absolute path to the cache directory; empty until configured. */
	private string $_dir = '';

	/** @var int Default TTL in seconds; 0 means never expire. */
	private int $_defaultTtl = 0;

	/** @var string Filename prefix used when creating atomic temporary write files. */
	private string $_tempFilePrefix = '';

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
	}

	/**
	 * Initializes the cache module. When no {@see getDirectory Directory} has been
	 * set, a `filecache/` subdirectory under the application runtime path is used
	 * and created if necessary. Throws when the directory is not writable.
	 *
	 * @param null|\Prado\Xml\TXmlElement $config module configuration
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
	public function getDirectory(): string
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
	public function setDirectory($value): void
	{
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
	public function getDefaultTtl(): int
	{
		return $this->getDefaultTtlDirect();
	}

	/**
	 * @param int $value the default TTL in seconds; values below zero are clamped to 0
	 */
	public function setDefaultTtl($value): void
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
	public function getTempFilePrefix(): string
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
	public function setTempFilePrefix($value): void
	{
		$this->setTempFilePrefixDirect(TPropertyValue::ensureString($value));
	}

	// ---------------------------------------------------------------- ICache impl


	/**
	 * Retrieves a stored entry by its TCache-generated unique key. When
	 * {@see getMaximumSize MaximumSize} is active, a successful read calls
	 * {@see touch()} on the cache file to update its `mtime`, which is used by
	 * {@see evictToFitMaximumSize()} to order files for LRU eviction.
	 *
	 * @param string $key the unique key
	 * @return false|mixed the stored value, or false if the entry is missing,
	 *   malformed, or expired
	 */
	protected function getValue($key)
	{
		$file = $this->pathFor($key);
		if (!$this->isFile($file)) {
			return false;
		}
		$raw = $this->getContents($file);
		if ($raw === false || $raw === '') {
			return false;
		}
		$decoded = $this->unserialize($raw);
		if (!is_array($decoded) || !array_key_exists(static::CACHE_EXPIRED, $decoded) || !array_key_exists(static::CACHE_VALUE, $decoded)) {
			return false;
		}
		$expire = (int) $decoded[static::CACHE_EXPIRED];
		if ($expire > 0 && $expire <= $this->now()) {
			$this->unlink($file);
			return false;
		}
		if ($this->getMaximumSizeDirect() > 0) {
			$this->touch($file);
		}
		return $decoded[static::CACHE_VALUE];
	}

	/**
	 * Stores a value under the given unique key, overwriting any existing entry.
	 *
	 * @param string $key the unique key
	 * @param mixed $value the value to store
	 * @param int $expire TTL in seconds; 0 falls back to {@see getDefaultTtl}
	 * @return bool true on success
	 */
	protected function setValue($key, $value, $expire)
	{
		return $this->writeEntry($key, $value, (int) $expire, false);
	}

	/**
	 * Stores a value only when no live entry already exists under the key.
	 *
	 * @param string $key the unique key
	 * @param mixed $value the value to store
	 * @param int $expire TTL in seconds; 0 falls back to {@see getDefaultTtl}
	 * @return bool true when the entry was stored; false when a live entry
	 *   already existed
	 */
	protected function addValue($key, $value, $expire)
	{
		$file = $this->pathFor($key);
		if ($this->isFile($file) && $this->getValue($key) !== false) {
			return false;
		}
		return $this->writeEntry($key, $value, (int) $expire, true);
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
	 * @param mixed $value the value to store
	 * @param int $expire TTL in seconds; 0 falls back to {@see getDefaultTtl}
	 * @param bool $exclusive when true, aborts if the final file already exists
	 *   (used by {@see addValue} to prevent overwriting a live entry)
	 * @throws \Prado\Exceptions\TInvalidDataValueException when MaximumSize is active
	 *   and the serialized entry exceeds it
	 * @return bool true on success
	 */
	protected function writeEntry(string $key, mixed $value, int $expire, bool $exclusive): bool
	{
		$ttl = $expire > 0 ? $expire : $this->getDefaultTtl();
		$entry = [
			static::CACHE_VALUE => $value,
			static::CACHE_EXPIRED => $ttl > 0 ? $this->now() + $ttl : 0,
		];
		$serialized = $this->serialize($entry);
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
		if ($this->getMaximumSizeDirect() > 0) {
			// Invalidate fingerprint so validateSizeCache() triggers a full recompute.
			$this->setSizeFingerprintDirect('');
			$this->enforceMaximumSize();
		}
		return true;
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
	 * Evicts the least recently used cache files — determined by file `mtime`,
	 * which is updated on every successful {@see getValue} via {@see touch()} — one
	 * at a time until the total on-disk size is at or below
	 * {@see getMaximumSize MaximumSize}. After all evictions the running size total
	 * and fingerprint are updated.
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
				$files[$f] = ['mtime' => $mtime, 'size' => $fsize];
			}
		}
		// Sort by mtime ascending so that the least recently accessed file comes first.
		uasort($files, static fn ($a, $b): int => $a['mtime'] <=> $b['mtime']);
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
	 * Serializes a value to a string for storage in a cache file.
	 *
	 * @param mixed $value the value to serialize
	 * @return string the serialized representation
	 */
	protected function serialize(mixed $value): string
	{
		return serialize($value);
	}

	/**
	 * Unserializes a string produced by {@see serialize}.
	 * Returns false when the string is not valid serialized data.
	 *
	 * @param string $value the serialized string to decode
	 * @return mixed the unserialized value, or false on failure
	 */
	protected function unserialize(string $value): mixed
	{
		return @unserialize($value);
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
	 * Writes data to a file, replacing its current contents.
	 * Returns false when the file cannot be written.
	 *
	 * Note: this method does **not** use `LOCK_EX`. Write atomicity is instead
	 * guaranteed by the `tempnam()` + `rename()` pattern in {@see writeEntry()},
	 * so no exclusive lock is needed here.
	 *
	 * @param string $filePath the path of the file to write
	 * @param string $data the data to write
	 * @return false|int the number of bytes written, or false on failure
	 */
	protected function putContents(string $filePath, string $data): int|false
	{
		return @file_put_contents($filePath, $data);
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
	 * Updates the access and modification time of a file to the current time.
	 * Used by {@see getValue()} to refresh the `mtime` of cache files on every
	 * read so that {@see evictToFitMaximumSize()} can order files by recency.
	 *
	 * @param string $filePath the path of the file to touch
	 * @return bool true on success, false on failure
	 */
	protected function touch(string $filePath): bool
	{
		return @touch($filePath);
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
	}
}
