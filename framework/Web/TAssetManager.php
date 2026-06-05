<?php

/**
 * TAssetManager class
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web;

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Exceptions\TIOException;
use Prado\Prado;
use Prado\TApplicationMode;
use Prado\IO\TTarFileExtractor;
use Prado\TPropertyValue;
use Prado\Util\Traits\TInitializedTrait;

/**
 * TAssetManager class
 *
 * TAssetManager provides a scheme to allow web clients visiting
 * private files that are normally web-inaccessible.
 *
 * TAssetManager will copy the file to be published into a web-accessible
 * directory. The default base directory for storing the file is "assets", which
 * should be under the application directory. This can be changed by setting
 * the {@see setBasePath BasePath} property together with the
 * {@see setBaseUrl BaseUrl} property that refers to the URL for accessing the base path.
 *
 * By default, TAssetManager will not publish a file or directory if it already
 * exists in the publishing directory and has an older modification time.
 * If the application mode is set as 'Performance', the modification time check
 * will be skipped. You can explicitly require a modification time check
 * with the function {@see publishFilePath}. This is usually
 * very useful during development.
 *
 * XML configuration style:
 * ```xml
 * <modules>
 *   <module id="asset" class="Prado\Web\TAssetManager"
 *       BasePath="Application.assets" BaseUrl="/assets" />
 * </modules>
 * ```
 * where {@see getBasePath BasePath} and {@see getBaseUrl BaseUrl} are
 * configurable properties of TAssetManager. Make sure that BasePath is a namespace
 * pointing to a valid directory writable by the Web server process.
 *
 * PHP configuration style:
 * ```php
 * return [
 *     'modules' => [
 *         'asset' => [
 *             'class' => 'Prado\Web\TAssetManager',
 *             'properties' => [
 *                 'BasePath' => 'Application.assets',
 *                 'BaseUrl' => '/assets',
 *             ],
 *         ],
 *     ],
 * ];
 * ```
 *
 * ## Publishing Options
 *
 * When publishing files or directories, you can pass an options array to customize the behavior:
 *
 * - `only`: array, list of glob patterns that files must match to be copied.
 * - `except`: array, list of glob patterns that files must NOT match to be copied.
 * - `caseSensitive`: bool, whether patterns should be case sensitive (default: true).
 * - `beforeCopy`: callable, a PHP callback invoked before copying each file. Return false to skip.
 * - `afterCopy`: callable, a PHP callback invoked after each file is copied.
 * - `forceCopy`: bool, whether to copy even if the file already exists.
 *
 * ## Asset Mapping
 *
 * TAssetManager supports asset mapping via {@see setAssetMap AssetMap} property.
 * This allows resolving asset URLs from logical names to published URLs:
 *
 * ```php
 * $manager = $this->getAssetManager();
 * $manager->setAssetMap([
 *     'jquery.js' => '/assets/abc123/jquery.min.js',
 *     'bootstrap.css' => '/assets/def456/bootstrap.min.css',
 * ]);
 * $url = $manager->resolveAsset('jquery.js'); // Returns '/assets/abc123/jquery.min.js'
 * ```
 *
 * ## Pattern Matching
 *
 * The `only` and `except` options use glob patterns with fnmatch():
 *
 * ```php
 * // Publish only JavaScript files
 * $url = $manager->publishFilePath('/path/to/assets', [
 *     'only' => ['*.js'],
 * ]);
 *
 * // Exclude source maps
 * $url = $manager->publishFilePath('/path/to/assets', [
 *     'except' => ['*.map', '*.src.js'],
 * ]);
 *
 * // Case-insensitive matching
 * $url = $manager->publishFilePath('/path/to/assets', [
 *     'only' => ['APP.JS', 'MAIN.JS'],
 *     'caseSensitive' => false,
 * ]);
 * ```
 *
 * ## Callbacks
 *
 * The `beforeCopy` and `afterCopy` callbacks receive source and destination paths:
 *
 * ```php
 * $url = $manager->publishFilePath('/path/to/assets', [
 *     'beforeCopy' => function($src, $dst) {
 *         // Return false to skip copying this file
 *         return strpos($src, '.git') !== 0;
 *     },
 *     'afterCopy' => function($src, $dst) {
 *         // Log after copying
 *         error_log("Copied $src to $dst");
 *     },
 * ]);
 * ```
 *
 * ## Directory Publishing
 *
 * The {@see copyDirectory} method can be used standalone to copy directory contents
 * with pattern filtering:
 *
 * ```php
 * $manager->copyDirectory('/source/path', '/destination/path', [
 *     'only' => ['*.js', '*.css'],
 *     'except' => ['*.min.js'],
 *     'caseSensitive' => false,
 *     'forceCopy' => true,
 * ]);
 * ```
 *
 * Pattern matching follows gitignore-style rules. A pattern without a slash matches
 * the file name at any depth (`*.js` matches `js/app.js`). A pattern containing a
 * slash is anchored to the path relative to the published root, and `*` does not
 * cross directory separators (`js/*.js` matches `js/app.js` but not `js/sub/app.js`).
 *
 * ## Symlink Publishing Security
 *
 * Setting {@see setLinkAssets LinkAssets} to true publishes each asset as a symbolic
 * link to its original source file instead of a copy. This carries security
 * implications for the publishing directory.
 *
 * - A published link targets the source path relative to the link, so the link
 *   resolves after the asset tree moves to a different absolute root and does not
 *   embed the absolute source path. A client that reads the link, or a directory
 *   index that resolves it, still learns the relative location of the source.
 * - The web server must be configured to follow symbolic links to serve a linked
 *   asset (Apache `Options +FollowSymLinks`, or the narrower `+SymLinksIfOwnerMatch`).
 *   Enabling link following on the assets directory widens what the server serves
 *   from there. A process that can write into the assets directory can plant a link
 *   to any file the server may read, escaping the web root.
 * - A linked asset shares the source file's content, modification time, and
 *   permissions. A source file that is group or world readable remains so when
 *   served. {@see setAppendTimestamp AppendTimestamp} reflects the source mtime.
 * - There is no isolated copy. Editing the source changes the served asset
 *   immediately; deleting the source leaves a dangling link that fails to serve.
 * - Refreshing a stale link requires {@see setForceCopy ForceCopy}, which unlinks
 *   and recreates the link. Without it an existing link is left in place.
 * - A link whose source is deleted becomes a dangling link. {@see validateSymlinks}
 *   checks one link or sweeps the publishing directory, removing the broken links.
 *
 * Use LinkAssets only when the source tree is trusted and the web server's symlink
 * following is scoped to the assets directory. Prefer copy publishing for untrusted
 * or mixed-content source directories.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 */
class TAssetManager extends \Prado\TModule
{
	use TInitializedTrait;

	/** Default web accessible base path for storing private files  */
	public const DEFAULT_BASEPATH = 'assets';
	/** @var array<string> explicit files/directories to exclude. */
	public const PATH_COPY_EXCEPTIONS = ['.svn', '.git', '.github', '.claude'];

	/** @var string base web accessible path for storing private files */
	private $_basePath;
	/** @var string base URL for accessing the publishing directory. */
	private $_baseUrl;
	/** @var array published assets */
	private $_published = [];
	/** @var bool whether to use symbolic link to publish asset files. */
	private $_linkAssets = false;
	/** @var bool whether to copy asset files even if they already exist in the target directory. */
	private $_forceCopy = false;
	/** @var bool whether to append timestamp to the URL of every published asset. */
	private $_appendTimestamp = false;
	/** @var string the query parameter name to use for timestamp appending. */
	private $_timestampVar = 'v';
	/** @var null|callable a callback that will be called to produce hash for asset directory generation. */
	private $_hashCallback;
	/** @var null|callable a PHP callback that is called before copying each sub-directory or file. */
	private $_beforeCopy;
	/** @var null|callable a PHP callback that is called after a sub-directory or file is successfully copied. */
	private $_afterCopy;
	/** @var array mapping from source asset files (keys) to target asset files (values). */
	private $_assetMap = [];
	/** @var null|array list of patterns that the file paths should match if they want to be copied. */
	private $_only;
	/** @var null|array list of patterns that the files or directories should match if they want to be excluded from being copied. */
	private $_except;
	/** @var bool whether patterns specified at "only" or "except" should be case sensitive. */
	private $_caseSensitive = true;
	/** @var null|int the permission to be set for newly published asset files. */
	private $_fileMode;
	/** @var null|int the permission to be set for newly generated asset directories. */
	private $_dirMode;

	/**
	 * Initializes the module.
	 * This method is required by IModule and is invoked by application.
	 * @param null|array|\Prado\Xml\TXmlElement $config module configuration
	 */
	public function init($config)
	{
		$application = $this->getApplication();
		if ($this->_basePath === null) {
			$this->_basePath = dirname($application->getRequest()->getApplicationFilePath()) . DIRECTORY_SEPARATOR . self::DEFAULT_BASEPATH;
		}
		if (!is_writable($this->_basePath) || !is_dir($this->_basePath)) {
			throw new TConfigurationException('assetmanager_basepath_invalid', $this->_basePath);
		}
		if ($this->_baseUrl === null) {
			$this->_baseUrl = rtrim(dirname($application->getRequest()->getApplicationUrl()), '/\\') . '/' . self::DEFAULT_BASEPATH;
		}
		$this->setAppAssetManager();
		parent::init($config);
		$this->markInitialized();
	}

	/**
	 * Registers this module as the application asset manager when an application is available.
	 * Called during {@see init()}; may also be called by behaviors or subclasses.
	 * @since 4.3.3
	 */
	protected function setAppAssetManager()
	{
		$this->getApplication()?->setAssetManager($this);
	}

	/**
	 * @return string the root directory storing published asset files
	 */
	public function getBasePath()
	{
		return $this->_basePath;
	}

	/**
	 * Sets the root directory storing published asset files.
	 * The directory must be in namespace format.
	 * @param string $value the root directory storing published asset files
	 * @throws TInvalidOperationException if the module is initialized already
	 */
	public function setBasePath($value)
	{
		$this->assertUninitialized('BasePath');
		$basePath = Prado::getPathOfNamespace($value);
		if ($basePath === null || !is_dir($basePath) || !is_writable($basePath)) {
			throw new TInvalidDataValueException('assetmanager_basepath_invalid', $value);
		}
		$this->_basePath = $basePath;
	}

	/**
	 * @return string the base url that the published asset files can be accessed
	 */
	public function getBaseUrl()
	{
		return $this->_baseUrl;
	}

	/**
	 * @param string $value the base url that the published asset files can be accessed
	 * @throws TInvalidOperationException if the module is initialized already
	 */
	public function setBaseUrl($value)
	{
		$this->assertUninitialized('BaseUrl');
		$this->_baseUrl = rtrim($value, '/');
	}

	/**
	 * @return bool whether to use symbolic link to publish asset files.
	 * @since 4.3.3
	 */
	public function getLinkAssets()
	{
		return $this->_linkAssets;
	}

	/**
	 * @param bool $value whether to use symbolic link to publish asset files.
	 * @since 4.3.3
	 */
	public function setLinkAssets($value)
	{
		$this->_linkAssets = TPropertyValue::ensureBoolean($value);
	}

	/**
	 * @return bool whether to copy asset files even if they already exist in the target directory.
	 * @since 4.3.3
	 */
	public function getForceCopy()
	{
		return $this->_forceCopy;
	}

	/**
	 * @param bool $value whether to copy asset files even if they already exist in the target directory.
	 * @since 4.3.3
	 */
	public function setForceCopy($value)
	{
		$this->_forceCopy = TPropertyValue::ensureBoolean($value);
	}

	/**
	 * @return bool whether to append timestamp to the URL of every published asset.
	 * @since 4.3.3
	 */
	public function getAppendTimestamp()
	{
		return $this->_appendTimestamp;
	}

	/**
	 * @param bool $value whether to append timestamp to the URL of every published asset.
	 * @since 4.3.3
	 */
	public function setAppendTimestamp($value)
	{
		$this->_appendTimestamp = TPropertyValue::ensureBoolean($value);
	}

	/**
	 * @return string the query parameter name to use for timestamp appending.
	 * @since 4.3.3
	 */
	public function getTimestampVar()
	{
		return $this->_timestampVar;
	}

	/**
	 * @param string $value the query parameter name to use for timestamp appending.
	 * @since 4.3.3
	 */
	public function setTimestampVar($value)
	{
		$this->_timestampVar = TPropertyValue::ensureString($value);
	}

	/**
	 * @return null|callable a callback that will be called to produce hash for asset directory generation.
	 * @since 4.3.3
	 */
	public function getHashCallback()
	{
		return $this->_hashCallback;
	}

	/**
	 * @param null|callable $value a callback that will be called to produce hash for asset directory generation.
	 * @since 4.3.3
	 */
	public function setHashCallback($value)
	{
		$this->_hashCallback = $value !== null && is_callable($value) ? $value : null;
	}

	/**
	 * @return null|callable a PHP callback that is called before copying each sub-directory or file.
	 * @since 4.3.3
	 */
	public function getBeforeCopy()
	{
		return $this->_beforeCopy;
	}

	/**
	 * @param null|callable $value a PHP callback that is called before copying each sub-directory or file.
	 * @since 4.3.3
	 */
	public function setBeforeCopy($value)
	{
		$this->_beforeCopy = $value !== null && is_callable($value) ? $value : null;
	}

	/**
	 * @return null|callable a PHP callback that is called after a sub-directory or file is successfully copied.
	 * @since 4.3.3
	 */
	public function getAfterCopy()
	{
		return $this->_afterCopy;
	}

	/**
	 * @param null|callable $value a PHP callback that is called after a sub-directory or file is successfully copied.
	 * @since 4.3.3
	 */
	public function setAfterCopy($value)
	{
		$this->_afterCopy = $value !== null && is_callable($value) ? $value : null;
	}

	/**
	 * @return array mapping from source asset files (keys) to target asset files (values).
	 * @since 4.3.3
	 */
	public function getAssetMap()
	{
		return $this->_assetMap;
	}

	/**
	 * @param array $value mapping from source asset files (keys) to target asset files (values).
	 * @since 4.3.3
	 */
	public function setAssetMap($value)
	{
		$this->_assetMap = TPropertyValue::ensureArray($value);
	}

	/**
	 * @return null|array list of patterns that the file paths should match if they want to be copied.
	 * @since 4.3.3
	 */
	public function getOnly()
	{
		return $this->_only;
	}

	/**
	 * @param null|array $value list of patterns that the file paths should match if they want to be copied.
	 * @since 4.3.3
	 */
	public function setOnly($value)
	{
		$this->_only = $value !== null ? TPropertyValue::ensureArray($value) : null;
	}

	/**
	 * @return null|array list of patterns that the files or directories should match if they want to be excluded from being copied.
	 * @since 4.3.3
	 */
	public function getExcept()
	{
		return $this->_except;
	}

	/**
	 * @param null|array $value list of patterns that the files or directories should match if they want to be excluded from being copied.
	 * @since 4.3.3
	 */
	public function setExcept($value)
	{
		$this->_except = $value !== null ? TPropertyValue::ensureArray($value) : null;
	}

	/**
	 * @return bool whether patterns specified at "only" or "except" should be case sensitive.
	 * @since 4.3.3
	 */
	public function getCaseSensitive()
	{
		return $this->_caseSensitive;
	}

	/**
	 * @param bool $value whether patterns specified at "only" or "except" should be case sensitive.
	 * @since 4.3.3
	 */
	public function setCaseSensitive($value)
	{
		$this->_caseSensitive = TPropertyValue::ensureBoolean($value);
	}

	/**
	 * @return null|int the permission to be set for newly published asset files.
	 * @since 4.3.3
	 */
	public function getFileMode()
	{
		return $this->_fileMode;
	}

	/**
	 * @param null|int $value the permission to be set for newly published asset files.
	 * @since 4.3.3
	 */
	public function setFileMode($value)
	{
		$this->_fileMode = $value !== null ? TPropertyValue::ensureInteger($value) : null;
	}

	/**
	 * @return int the permission to be set for newly generated asset directories.
	 * @since 4.3.3
	 */
	public function getDirMode()
	{
		return $this->_dirMode ?? Prado::getDefaultDirPermissions();
	}

	/**
	 * @param int $value the permission to be set for newly generated asset directories.
	 * @since 4.3.3
	 */
	public function setDirMode($value)
	{
		$this->_dirMode = $value !== null ? TPropertyValue::ensureInteger($value) : null;
	}

	/**
	 * Publishes a file or a directory (recursively).
	 * This method will copy the content in a directory (recursively) to
	 * a web accessible directory and returns the URL for the directory.
	 * If the application is not in performance mode, the file modification
	 * time will be used to make sure the published file is latest or not.
	 * If not, a file copy will be performed.
	 * @param string $path the path to be published
	 * @param array|bool $checkTimestamp If true, file modification time will be checked even if the application
	 * is in performance mode. If an array, it is treated as options with the following keys:
	 * - only: array, list of patterns that the file paths should match if they want to be copied.
	 * - except: array, list of patterns that the files or directories should match if they want to be excluded from being copied.
	 * - caseSensitive: bool, whether patterns should be case sensitive. Defaults to true.
	 * - beforeCopy: callable, a PHP callback that is called before copying each sub-directory or file.
	 * - afterCopy: callable, a PHP callback that is called after a sub-directory or file is successfully copied.
	 * - forceCopy: bool, whether to copy even if the file already exists.
	 * @throws TInvalidDataValueException if the file path to be published is
	 * invalid
	 * @return string the absolute URL to the published file or directory. A file
	 * URL carries the appended timestamp query when {@see setAppendTimestamp
	 * AppendTimestamp} is enabled.
	 */
	public function publishFilePath($path, $checkTimestamp = false)
	{
		$options = [];
		if (is_array($checkTimestamp)) {
			$options = $checkTimestamp;
			$checkTimestamp = $options['forceCopy'] ?? false;
		}

		$cacheKey = $this->publishCacheKey($path, $options);
		if (isset($this->_published[$cacheKey])) {
			return $this->_published[$cacheKey];
		} elseif (empty($path) || ($fullpath = realpath($path)) === false) {
			throw new TInvalidDataValueException('assetmanager_filepath_invalid', $path);
		} elseif (is_file($fullpath)) {
			$dir = $this->hash(dirname($fullpath));
			$fileName = basename($fullpath);
			$dst = $this->_basePath . DIRECTORY_SEPARATOR . $dir;
			$forceCopy = $options['forceCopy'] ?? $this->getForceCopy();
			if (!is_file($dst . DIRECTORY_SEPARATOR . $fileName) || $checkTimestamp || $forceCopy || $this->getApplication()->getMode() !== TApplicationMode::Performance) {
				$this->copyFile($fullpath, $dst, $options);
			}
			$publishedUrl = $this->_baseUrl . '/' . $dir . '/' . $fileName;
			if ($this->getAppendTimestamp()) {
				$dstFile = $dst . DIRECTORY_SEPARATOR . $fileName;
				if (($timestamp = @filemtime($dstFile)) > 0) {
					$publishedUrl .= '?' . $this->getTimestampVar() . '=' . $timestamp;
				}
			}
			return $this->_published[$cacheKey] = $publishedUrl;
		} else {
			$dir = $this->hash($fullpath);
			$forceCopy = $options['forceCopy'] ?? $this->getForceCopy();
			if (!is_dir($this->_basePath . DIRECTORY_SEPARATOR . $dir) || $checkTimestamp || $forceCopy || $this->getApplication()->getMode() !== TApplicationMode::Performance) {
				Prado::trace("Publishing directory $fullpath", TAssetManager::class);
				$this->copyDirectory($fullpath, $this->_basePath . DIRECTORY_SEPARATOR . $dir, $options);
			}
			return $this->_published[$cacheKey] = $this->_baseUrl . '/' . $dir;
		}
	}

	/**
	 * Builds the {@see $_published} cache key for a path and its publishing options.
	 * With no options the key is the path itself, preserving prior cache behavior.
	 * Distinct option sets receive distinct keys so that publishing the same path
	 * with different options re-publishes instead of returning a stale URL. Object
	 * options (closures, invokables) are keyed by object id within the request.
	 * @param string $path the path being published
	 * @param array $options publishing options
	 * @return string the cache key
	 * @since 4.3.3
	 */
	protected function publishCacheKey($path, $options)
	{
		if (!$options) {
			return $path;
		}
		$normalized = [];
		foreach ($options as $key => $value) {
			$normalized[$key] = is_object($value) ? 'object#' . spl_object_id($value) : $value;
		}
		ksort($normalized);
		return $path . '#' . md5(serialize($normalized));
	}

	/**
	 * @return array List of published assets
	 * @since 3.1.6
	 */
	public function getPublished()
	{
		return $this->_published;
	}

	/**
	 * @param array $values List of published assets
	 * @since 3.1.6
	 */
	protected function setPublished($values = [])
	{
		$this->_published = $values;
	}

	/**
	 * Direct, unvalidated read accessor of the base publishing path for subclasses.
	 * This bypasses the public {@see setBasePath()} validation and lets a subclass
	 * reach the private state without re-declaring it. Self-Encapsulation per UAP.
	 * @return string the base publishing path
	 * @since 4.3.3
	 */
	protected function getBasePathDirect()
	{
		return $this->_basePath;
	}

	/**
	 * Direct, unvalidated write accessor of the base publishing path for subclasses.
	 * @param string $value the base publishing path
	 * @since 4.3.3
	 */
	protected function setBasePathDirect($value): void
	{
		$this->_basePath = $value;
	}

	/**
	 * Direct, unvalidated read accessor of the base publishing URL for subclasses.
	 * @return string the base publishing URL
	 * @since 4.3.3
	 */
	protected function getBaseUrlDirect()
	{
		return $this->_baseUrl;
	}

	/**
	 * Direct, unvalidated write accessor of the base publishing URL for subclasses.
	 * @param string $value the base publishing URL
	 * @since 4.3.3
	 */
	protected function setBaseUrlDirect($value): void
	{
		$this->_baseUrl = $value;
	}

	/**
	 * Direct read accessor of the published-asset map for subclasses. A subclass may
	 * store a richer value shape here than the URL strings the base class stores.
	 * @return array the published-asset map
	 * @since 4.3.3
	 */
	protected function getPublishedDirect(): array
	{
		return $this->_published;
	}

	/**
	 * Direct write accessor of the published-asset map for subclasses.
	 * @param array $value the published-asset map
	 * @since 4.3.3
	 */
	protected function setPublishedDirect(array $value): void
	{
		$this->_published = $value;
	}

	/**
	 * Returns the published path of a file path.
	 * This method does not perform any publishing. It merely tells you
	 * if the file path is published, where it will go.
	 * @param string $path directory or file path being published
	 * @return string the published file path
	 */
	public function getPublishedPath($path)
	{
		$path = realpath($path);
		if (is_file($path)) {
			return $this->_basePath . DIRECTORY_SEPARATOR . $this->hash(dirname($path)) . DIRECTORY_SEPARATOR . basename($path);
		} else {
			return $this->_basePath . DIRECTORY_SEPARATOR . $this->hash($path);
		}
	}

	/**
	 * Returns the URL of a published file path.
	 * This method does not perform any publishing. It merely tells you
	 * if the file path is published, what the URL will be to access it.
	 * @param string $path directory or file path being published
	 * @return string the published URL for the file path
	 */
	public function getPublishedUrl($path)
	{
		$path = realpath($path);
		if (is_file($path)) {
			return $this->_baseUrl . '/' . $this->hash(dirname($path)) . '/' . basename($path);
		} else {
			return $this->_baseUrl . '/' . $this->hash($path);
		}
	}

	/**
	 * Generates the asset sub-directory name for a path. CRC32 produces a much
	 * smaller string than MD5 at the cost of a higher collision rate.
	 * When a {@see setHashCallback HashCallback} is set, it produces the name
	 * instead. Otherwise a file path is reduced to its directory, the framework
	 * version is mixed in so an upgrade republishes, and {@see setLinkAssets
	 * LinkAssets} contributes a discriminator so linked and copied assets occupy
	 * distinct directories.
	 * @param string $path string to be hashed (file or directory path).
	 * @return string hashed string.
	 */
	protected function hash($path)
	{
		if (is_callable($this->getHashCallback())) {
			return call_user_func($this->getHashCallback(), $path);
		}
		$pathToHash = is_file($path) ? dirname($path) : $path;
		$hashComponents = $pathToHash . Prado::getVersion();
		if ($this->getLinkAssets()) {
			$hashComponents .= '|' . ($this->getLinkAssets() ? '1' : '0');
		}
		return sprintf('%x', crc32($hashComponents));
	}

	/**
	 * Copies a file to a directory.
	 * Copying is done only when the destination file does not exist
	 * or has an older file modification time.
	 * @param string $src source file path
	 * @param string $dst destination directory (if not exists, it will be created)
	 * @param array $options publishing options
	 */
	protected function copyFile($src, $dst, $options = [])
	{
		$only = array_key_exists('only', $options) ? $options['only'] : $this->getOnly();
		$except = array_key_exists('except', $options) ? $options['except'] : $this->getExcept();
		$caseSensitive = $options['caseSensitive'] ?? $this->getCaseSensitive();

		if (!$this->matchFilePattern(basename($src), $only, $except, $caseSensitive)) {
			return;
		}

		$dstFile = $dst . DIRECTORY_SEPARATOR . basename($src);

		$beforeCopy = $options['beforeCopy'] ?? $this->getBeforeCopy();
		if ($beforeCopy !== null && !call_user_func($beforeCopy, $src, $dstFile)) {
			return;
		}

		if (!is_dir($dst)) {
			$dirMode = $this->getDirMode();
			@mkdir($dst, $dirMode);
			@chmod($dst, $dirMode);	// override umask on mkdir dirMode
		}

		$forceCopy = $options['forceCopy'] ?? $this->getForceCopy();

		if ($this->getLinkAssets()) {
			if ($forceCopy && (is_file($dstFile) || is_link($dstFile))) {
				@unlink($dstFile);
			}
			if (!is_file($dstFile) && !is_link($dstFile)) {
				try {
					$this->symlink($this->relativeSymlinkTarget($src, $dstFile), $dstFile);
				} catch (\Throwable $e) {
					if (!is_file($dstFile) && !is_link($dstFile)) {
						throw $e;
					}
				}
			}
		} elseif ($forceCopy || @filemtime($dstFile) < @filemtime($src)) {
			Prado::trace("Publishing file $src to $dstFile", TAssetManager::class);
			@copy($src, $dstFile);
			$fileMode = $this->getFileMode();
			if ($fileMode !== null) {
				@chmod($dstFile, $fileMode);
			}
		}

		$afterCopy = $options['afterCopy'] ?? $this->getAfterCopy();
		if ($afterCopy !== null) {
			call_user_func($afterCopy, $src, $dstFile);
		}
	}

	/**
	 * Creates a symbolic link from a target to a link path. Warnings are suppressed;
	 * the caller checks the resulting path and rethrows on a genuine failure. This
	 * isolates the {@see symlink()} call so the link-failure recovery is testable.
	 * @param string $target the source path the link points at, relative to the link
	 * @param string $link the link path to create
	 * @return bool whether the link was created
	 * @since 4.3.3
	 */
	protected function symlink($target, $link)
	{
		return @symlink($target, $link);
	}

	/**
	 * Computes the link target relative to the directory containing the link.
	 * Published links are relative so the asset tree resolves after it moves to a
	 * different absolute root and the link target does not embed the absolute source
	 * path. When the two paths share no common root, such as different Windows drive
	 * letters, the absolute target is returned because no relative link exists.
	 * @param string $target the absolute source path the link points at
	 * @param string $link the absolute link path being created
	 * @return string the target to store in the link
	 * @since 4.3.3
	 */
	protected function relativeSymlinkTarget($target, $link)
	{
		$sep = DIRECTORY_SEPARATOR;
		$from = explode($sep, dirname($link));
		$to = explode($sep, $target);
		if (($from[0] ?? null) !== ($to[0] ?? null)) {
			return $target;
		}
		$i = 0;
		$max = min(count($from), count($to));
		while ($i < $max && $from[$i] === $to[$i]) {
			$i++;
		}
		$rel = array_merge(array_fill(0, count($from) - $i, '..'), array_slice($to, $i));
		return $rel === [] ? '.' : implode($sep, $rel);
	}

	/**
	 * Validates symlinks, optionally removing the broken ones. The result depends on
	 * what the path is:
	 * - symlink → bool: true when its target exists, false when broken.
	 * - directory → int: the number of broken links found in the hierarchy.
	 * - neither → null.
	 *
	 * A directory is scanned recursively. Directory symlinks are validated but not
	 * descended into, so the walk stays within the asset tree and cannot loop.
	 * Defaults to {@see getBasePath BasePath}.
	 * @param ?string $path the link or directory to validate. Defaults to the
	 * publishing root.
	 * @param bool $remove whether to delete broken links. Defaults to true.
	 * @return null|bool|int the validation result, by path kind described above
	 * @since 4.3.3
	 */
	public function validateSymlinks($path = null, $remove = true)
	{
		$path ??= $this->_basePath;
		if (is_link($path)) {
			if (@file_exists($path)) {
				return true;
			}
			if ($remove) {
				@unlink($path);
			}
			return false;
		}
		if (!is_dir($path) || !($folder = @opendir($path))) {
			return null;
		}
		$broken = 0;
		while (($file = @readdir($folder)) !== false) {
			if ($file === '.' || $file === '..') {
				continue;
			}
			$full = $path . DIRECTORY_SEPARATOR . $file;
			if (is_link($full)) {
				if ($this->validateSymlinks($full, $remove) === false) {
					$broken++;
				}
			} elseif (is_dir($full)) {
				$broken += $this->validateSymlinks($full, $remove);
			}
		}
		closedir($folder);
		return $broken;
	}

	/**
	 * Copies a directory recursively as another.
	 * If the destination directory does not exist, it will be created.
	 * File modification time is used to ensure the copied files are latest.
	 * @param string $src the source directory
	 * @param string $dst the destination directory
	 * @param array $options publishing options:
	 * - only: array, list of patterns that the file paths should match if they want to be copied.
	 * - except: array, list of patterns that the files or directories should match if they want to be excluded from being copied.
	 * - caseSensitive: bool, whether patterns should be case sensitive. Defaults to true.
	 * - beforeCopy: callable, a PHP callback that is called before copying each sub-directory or file.
	 * - afterCopy: callable, a PHP callback that is called after a sub-directory or file is successfully copied.
	 * - forceCopy: bool, whether to copy even if the file already exists.
	 *
	 * Patterns are matched against the path relative to the published root using
	 * gitignore-style rules. The "except" patterns also prune whole sub-directories;
	 * "only" filters files and never prunes a directory, so nested matching files
	 * remain reachable. A sub-directory whose entire contents are filtered out is
	 * removed rather than left empty, while a directory that is empty in the source
	 * is preserved. Directory symlinks are followed once; a realpath already visited
	 * is skipped so a symlink cycle cannot loop forever.
	 * @param ?string $basePath @internal the root source directory against which
	 * relative paths are computed for pattern matching. Defaults to $src on the
	 * top-level call and is preserved across recursion.
	 * @param array $visited @internal realpaths already entered, keyed by realpath,
	 * used to break symlink cycles across recursion.
	 */
	public function copyDirectory($src, $dst, $options = [], $basePath = null, &$visited = [])
	{
		$isRoot = $basePath === null;
		if ($basePath === null) {
			$basePath = $src;
		}
		$real = realpath($src);
		if ($real !== false) {
			if (isset($visited[$real])) {
				return;
			}
			$visited[$real] = true;
		}
		$only = array_key_exists('only', $options) ? $options['only'] : $this->getOnly();
		$except = array_key_exists('except', $options) ? $options['except'] : $this->getExcept();
		$caseSensitive = $options['caseSensitive'] ?? $this->getCaseSensitive();
		$beforeCopy = $options['beforeCopy'] ?? $this->getBeforeCopy();
		$afterCopy = $options['afterCopy'] ?? $this->getAfterCopy();
		$forceCopy = $options['forceCopy'] ?? $this->getForceCopy();
		$linkAssets = $this->getLinkAssets();
		$fileMode = $this->getFileMode();
		$dirMode = $this->getDirMode();

		if (!is_dir($dst)) {
			@mkdir($dst, $dirMode);
			@chmod($dst, $dirMode);	// override umask on mkdir dirMode
		}
		$srcHadEntries = false;
		if ($folder = @opendir($src)) {
			while (($file = @readdir($folder)) !== false) {
				if ($file === '.' || $file === '..') {
					continue;
				}
				$srcHadEntries = true;
				if (in_array($file, self::PATH_COPY_EXCEPTIONS)) {
					continue;
				}
				$srcPath = $src . DIRECTORY_SEPARATOR . $file;
				$dstPath = $dst . DIRECTORY_SEPARATOR . $file;
				$relativePath = str_replace(DIRECTORY_SEPARATOR, '/', substr($srcPath, strlen($basePath) + 1));

				if (is_file($srcPath)) {
					if (!$this->matchFilePattern($relativePath, $only, $except, $caseSensitive)) {
						continue;
					}

					if ($beforeCopy !== null && !call_user_func($beforeCopy, $srcPath, $dstPath)) {
						continue;
					}

					$shouldCopy = $forceCopy || !is_file($dstPath) || @filemtime($dstPath) < @filemtime($srcPath);
					if ($shouldCopy) {
						if ($linkAssets) {
							if ($forceCopy && (is_file($dstPath) || is_link($dstPath))) {
								@unlink($dstPath);
							}
							if (!is_link($dstPath)) {
								try {
									$this->symlink($this->relativeSymlinkTarget($srcPath, $dstPath), $dstPath);
								} catch (\Throwable $e) {
									if (!is_file($dstPath) && !is_link($dstPath)) {
										throw $e;
									}
								}
							}
						} else {
							Prado::trace("Publishing file $srcPath to $dstPath", TAssetManager::class);
							@copy($srcPath, $dstPath);
							if ($fileMode !== null) {
								@chmod($dstPath, $fileMode);
							}
						}
					}
					if ($afterCopy !== null) {
						call_user_func($afterCopy, $srcPath, $dstPath);
					}
				} else {
					if ($except !== null && $this->matchesAnyPattern($relativePath, $except, $caseSensitive)) {
						continue;
					}
					$this->copyDirectory($srcPath, $dstPath, $options, $basePath, $visited);
				}
			}
			closedir($folder);
			// A non-root directory whose source had entries but ended up empty has
			// had all of its contents filtered out, so it is pruned. A directory
			// that is genuinely empty in the source is kept.
			if (!$isRoot && $srcHadEntries && is_dir($dst) && count(scandir($dst)) === 2) {
				@rmdir($dst);
			}
		} else {
			throw new TInvalidDataValueException('assetmanager_source_directory_invalid', $src);
		}
	}

	/**
	 * Checks if a relative path matches the "only" or "except" patterns.
	 * "except" is tested first; a match excludes the path. "only", when set,
	 * requires a match for the path to be copied.
	 * @param string $path the path relative to the published root, using "/" separators
	 * @param null|array $only list of patterns to match if wanting to be copied
	 * @param null|array $except list of patterns to match if wanting to be excluded
	 * @param bool $caseSensitive whether the patterns are case sensitive
	 * @return bool true if the file should be copied
	 * @since 4.3.3
	 */
	protected function matchFilePattern($path, $only, $except, $caseSensitive)
	{
		if ($except !== null && $this->matchesAnyPattern($path, $except, $caseSensitive)) {
			return false;
		}
		if ($only !== null && !$this->matchesAnyPattern($path, $only, $caseSensitive)) {
			return false;
		}
		return true;
	}

	/**
	 * Tests a relative path against a list of gitignore-style glob patterns.
	 * A pattern without a slash matches the file name at any depth. A pattern
	 * containing a slash is anchored to the relative path, and `*` does not cross
	 * directory separators. A leading slash on a pattern is ignored.
	 * @param string $path the path relative to the published root, using "/" separators
	 * @param array $patterns list of glob patterns
	 * @param bool $caseSensitive whether the patterns are case sensitive
	 * @return bool true if any pattern matches
	 * @since 4.3.3
	 */
	protected function matchesAnyPattern($path, $patterns, $caseSensitive)
	{
		$fnFlags = $caseSensitive ? 0 : FNM_CASEFOLD;
		$basename = basename($path);
		foreach ($patterns as $pattern) {
			if (strpos($pattern, '/') === false) {
				if (fnmatch($pattern, $basename, $fnFlags)) {
					return true;
				}
			} elseif (fnmatch(ltrim($pattern, '/'), $path, $fnFlags | FNM_PATHNAME)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Returns the actual URL for the specified asset.
	 * An exact match on the asset key is returned first. Otherwise each map key is
	 * tested as a path suffix of the source-qualified asset, where the match must
	 * begin at a path boundary. The key "app.js" matches "lib/app.js" but not
	 * "myapp.js".
	 * @param string $asset the asset path.
	 * @param ?string $sourcePath the source path of the asset bundle
	 * @return ?string the actual URL for the asset, or null when no mapping matches.
	 * @since 4.3.3
	 */
	public function resolveAsset($asset, $sourcePath = null)
	{
		if (isset($this->_assetMap[$asset])) {
			return $this->_assetMap[$asset];
		}
		$assetWithSource = $sourcePath !== null ? $sourcePath . '/' . $asset : $asset;

		$n = strlen($assetWithSource);
		foreach ($this->_assetMap as $from => $to) {
			$n2 = strlen($from);
			if ($n2 <= $n && substr_compare($assetWithSource, $from, -$n2, $n2) === 0
				&& ($n2 === $n || $assetWithSource[$n - $n2 - 1] === '/')) {
				return $to;
			}
		}

		return null;
	}

	/**
	 * Publish a tar file by extracting its contents to the assets directory.
	 * Each tar file must be accompanied with its own MD5 check sum file.
	 * The MD5 file is published when the tar contents are successfully
	 * extracted to the assets directory. The presence of the MD5 file
	 * as published asset assumes that the tar file has already been extracted.
	 * @param string $tarfile tar filename
	 * @param string $md5sum MD5 checksum for the corresponding tar file.
	 * @param bool $checkTimestamp Wether or not to check the time stamp of the file for publishing. Defaults to false.
	 * @return string URL path to the directory where the tar file was extracted.
	 */
	public function publishTarFile($tarfile, $md5sum, $checkTimestamp = false)
	{
		if (isset($this->_published[$md5sum])) {
			return $this->_published[$md5sum];
		} elseif (($fullpath = realpath($md5sum)) === false || !is_file($fullpath)) {
			throw new TInvalidDataValueException('assetmanager_tarchecksum_invalid', $md5sum);
		} else {
			$dir = $this->hash(dirname($fullpath));
			$fileName = basename($fullpath);
			$dst = $this->_basePath . DIRECTORY_SEPARATOR . $dir;
			if (!is_file($dst . DIRECTORY_SEPARATOR . $fileName) || $checkTimestamp || $this->getApplication()->getMode() !== TApplicationMode::Performance) {
				if (@filemtime($dst . DIRECTORY_SEPARATOR . $fileName) < @filemtime($fullpath)) {
					// The checksum file is always published; instance only/except filters do not apply.
					$this->copyFile($fullpath, $dst, ['only' => null, 'except' => null]);
					$this->deployTarFile($tarfile, $dst);
				}
			}
			return $this->_published[$md5sum] = $this->_baseUrl . '/' . $dir;
		}
	}

	/**
	 * Extracts the tar file to the destination directory.
	 * N.B Tar file must not be compressed.
	 * @param string $path tar file
	 * @param string $destination path where the contents of tar file are to be extracted
	 * @return bool true if extract successful, false otherwise.
	 */
	protected function deployTarFile($path, $destination)
	{
		if (($fullpath = realpath($path)) === false || !is_file($fullpath)) {
			throw new TIOException('assetmanager_tarfile_invalid', $path);
		} else {
			$tar = new TTarFileExtractor($fullpath);
			return $tar->extract($destination);
		}
	}
}
