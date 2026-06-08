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
 * - `atomic`: bool, whether to write a file via a temporary file and rename, overriding
 *   the {@see setAtomic Atomic} property for the call. Atomicity costs an extra file
 *   write and rename per file; disable it for the fastest direct writes.
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
	/** @var array<string> explicit files/directories to exclude. @since 4.3.3 */
	public const PATH_COPY_EXCEPTIONS = ['.svn', '.git', '.github', '.claude'];
	/** Prefix of the per-source completion marker written into a published directory. @since 4.4.0 */
	public const DIRECTORY_COMPLETE_MARKER_PREFIX = '.copied-';

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
	/** @var bool whether to publish a file by writing to a temporary file then renaming it into place. @since 4.4.0 */
	private $_atomic = true;
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
	 * Whether a published file is written to a temporary file then renamed into place, so
	 * a half-written file never appears at the destination. The temp file and rename add a
	 * per-file cost at publish time, paid once per asset since published files are cached.
	 * Disable it for the fastest direct writes. Default true.
	 * @return bool whether files publish through a temporary file and rename.
	 * @since 4.4.0
	 */
	public function getAtomic()
	{
		return $this->_atomic;
	}

	/**
	 * @param bool $value whether files publish through a temporary file and rename.
	 * @since 4.4.0
	 */
	public function setAtomic($value)
	{
		$this->_atomic = TPropertyValue::ensureBoolean($value);
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
	 * Publishes a file, a directory (recursively), or a virtual asset to a web accessible
	 * directory and returns the URL to it. This is the primary entry point for publishing
	 * an asset by path.
	 *
	 * The second argument is either a boolean timestamp flag (true forces a
	 * modification-time check even in performance mode) or an options array:
	 *
	 * | key           | type     | effect                                                        |
	 * |---------------|----------|---------------------------------------------------------------|
	 * | only          | string[] | glob patterns a file must match to be copied                  |
	 * | except        | string[] | glob patterns that exclude a matching file or directory       |
	 * | caseSensitive | bool     | whether the patterns are case sensitive; default true         |
	 * | beforeCopy    | callable | `fn($src, $dst): bool` called per file; return false to skip  |
	 * | afterCopy     | callable | `fn($src, $dst): void` called after a file is copied          |
	 * | forceCopy     | bool     | copy even when the destination already exists                 |
	 * | atomic        | bool     | write via a temp file then rename; defaults to {@see setAtomic Atomic} |
	 *
	 * See {@see publish} for worked examples, the gitignore-style pattern rules, and the
	 * virtual-asset and directory-completion details; this method forwards to it.
	 *
	 * @param IPublishable|string $path the file or directory path, or a virtual asset.
	 * @param array|bool $checkTimestamp the modification-time flag, or the options array above.
	 * @throws TInvalidDataValueException if the file path to be published is invalid.
	 * @return string the absolute URL to the published file, directory, or asset.
	 */
	public function publishFilePath($path, $checkTimestamp = false)
	{
		return $this->publish($path, $checkTimestamp);
	}

	/**
	 * Publishes a file, a directory (recursively), or a virtual {@see IPublishable}
	 * asset, returning the URL to the published result.
	 *
	 * A string path is copied to a web accessible directory and its URL returned. When
	 * the application is not in performance mode, the file modification time decides
	 * whether the published copy is current; otherwise a copy is performed. An
	 * {@see IPublishable} generates its own content under a translated file name rather
	 * than being copied, the slim path for a control that is its own file (e.g. an
	 * on-the-fly SVG with a unique name). An {@see IPublishedCapture} asset receives its
	 * published path and URL after writing.
	 *
	 * The second argument is either a boolean timestamp flag or an options array. As a
	 * boolean, true forces a modification-time check even in performance mode:
	 * ```php
	 * $url = $manager->publish('Application.assets/app.js');         // default publish
	 * $url = $manager->publish('Application.assets/app.js', true);   // force a freshness check
	 * ```
	 *
	 * As an array it carries publishing options:
	 *
	 * | key           | type     | effect                                                        |
	 * |---------------|----------|---------------------------------------------------------------|
	 * | only          | string[] | glob patterns a file must match to be copied                  |
	 * | except        | string[] | glob patterns that exclude a matching file or directory       |
	 * | caseSensitive | bool     | whether patterns are case sensitive; default true             |
	 * | beforeCopy    | callable | `fn($src, $dst): bool` called per file; return false to skip  |
	 * | afterCopy     | callable | `fn($src, $dst): void` called after a file is copied          |
	 * | forceCopy     | bool     | copy even when the destination already exists                 |
	 * | atomic        | bool     | write to a temp file then rename into place; defaults to the {@see setAtomic Atomic} property |
	 *
	 * Patterns are gitignore-style: a pattern without a slash matches the file name at
	 * any depth (`*.js`), while a pattern with a slash is anchored to the published root
	 * and `*` does not cross separators (see {@see copyDirectory}).
	 *
	 * ```php
	 * // Publish only JavaScript and CSS from a directory.
	 * $url = $manager->publish('Application.assets', ['only' => ['*.js', '*.css']]);
	 *
	 * // Exclude source maps and minified sources.
	 * $url = $manager->publish('Application.assets', ['except' => ['*.map', '*.min.js']]);
	 *
	 * // Skip dotfiles and log each published file.
	 * $url = $manager->publish('Application.assets', [
	 *     'beforeCopy' => fn($src, $dst) => $src[0] !== '.',
	 *     'afterCopy' => fn($src, $dst) => Prado::trace("published $dst", 'asset'),
	 * ]);
	 *
	 * // Republish even if the destination exists (e.g. after a content change).
	 * $url = $manager->publish('Application.assets/logo.png', ['forceCopy' => true]);
	 * ```
	 *
	 * @param IPublishable|string $path the path to publish, or a virtual asset.
	 * @param array|bool $checkTimestamp the modification-time flag, or an options array
	 *   with the keys above. An options array implies the timestamp flag from its
	 *   forceCopy key.
	 * @throws TInvalidDataValueException if the file path to be published is invalid.
	 * @return string the absolute URL to the published file, directory, or asset; '' when
	 * a virtual asset cancels publishing (a null virtual file path). A file URL carries
	 * the appended timestamp query when {@see setAppendTimestamp AppendTimestamp} is enabled.
	 * @since 4.4.0
	 */
	public function publish($path, $checkTimestamp = false)
	{
		$options = [];
		if (is_array($checkTimestamp)) {
			$options = $checkTimestamp;
			$checkTimestamp = $options['forceCopy'] ?? false;
		}
		if ($path instanceof IPublishable) {
			return $this->publishVirtual($path, $checkTimestamp, $options);
		}

		$cacheKey = $this->publishCacheKey($path, $options);
		if (isset($this->_published[$cacheKey])) {
			return $this->_published[$cacheKey];
		} elseif (empty($path) || ($fullpath = realpath($path)) === false) {
			throw new TInvalidDataValueException('assetmanager_filepath_invalid', $path);
		} elseif (is_file($fullpath)) {
			$loc = $this->publishedLocation($fullpath, false, basename($fullpath));
			$forceCopy = $options['forceCopy'] ?? $this->getForceCopy();
			if (!is_file($loc['dst']) || $checkTimestamp || $forceCopy || $this->getApplication()->getMode() !== TApplicationMode::Performance) {
				$this->copyFile($fullpath, $loc['dstDir'], $options);
			}
			$publishedUrl = $loc['url'];
			if ($this->getAppendTimestamp() && ($timestamp = @filemtime($loc['dst'])) > 0) {
				$publishedUrl .= '?' . $this->getTimestampVar() . '=' . $timestamp;
			}
			return $this->_published[$cacheKey] = $publishedUrl;
		} else {
			$loc = $this->publishedLocation($fullpath, true, '');
			$forceCopy = $options['forceCopy'] ?? $this->getForceCopy();
			$atomic = $options['atomic'] ?? $this->getAtomic();
			$marker = $loc['dstDir'] . DIRECTORY_SEPARATOR . $this->directoryCompleteMarker($fullpath);
			$published = $atomic ? is_file($marker) : is_dir($loc['dstDir']);
			if (!$published || $checkTimestamp || $forceCopy || $this->getApplication()->getMode() !== TApplicationMode::Performance) {
				Prado::trace("Publishing directory $fullpath", TAssetManager::class);
				$this->copyDirectory($fullpath, $loc['dstDir'], $options);
				if ($atomic && is_dir($loc['dstDir'])) {
					@touch($marker);
				}
			}
			return $this->_published[$cacheKey] = $loc['url'];
		}
	}

	/**
	 * Publishes a virtual {@see IPublishable} asset that generates its own content under a
	 * translated path, rather than copying a source file. The content is written by
	 * {@see IPublishable::publish()}: a file is written atomically (temp file then rename)
	 * when {@see getAtomic Atomic}; a directory (a virtual path ending in a separator) is
	 * populated by the asset and gated by a completion marker, so an interrupted population
	 * re-runs rather than being trusted. This is the slim path for a control that is its
	 * own file (e.g. an on-the-fly SVG with a unique name). An {@see IPublishedCapture}
	 * asset receives its published path and URL. {@see publish} dispatches here; callers
	 * normally use {@see publish} so that string paths and virtual assets share one entry
	 * point.
	 *
	 * Only these options are consulted, each falling back to the matching property:
	 *
	 * | key       | type | effect                                                              |
	 * |-----------|------|---------------------------------------------------------------------|
	 * | atomic    | bool | write via a temp file then rename; defaults to {@see setAtomic Atomic} |
	 * | forceCopy | bool | write even when the destination already exists; defaults to {@see setForceCopy ForceCopy} |
	 *
	 * @param IPublishable $asset the virtual asset to publish.
	 * @param bool $checkTimestamp true to check the modification time even in performance
	 *   mode.
	 * @param array $options the publishing options listed above.
	 * @throws TInvalidDataValueException when the virtual file path is empty or invalid.
	 * @return string the absolute URL to the published asset, or '' when publishing is
	 *   cancelled (a null virtual file path).
	 * @since 4.4.0
	 */
	protected function publishVirtual($asset, $checkTimestamp = false, array $options = [])
	{
		$target = $this->virtualAssetTarget($asset);
		if ($target === null) {
			return '';
		}
		$cacheKey = $this->publishCacheKey($target['vpath'], $options);
		if (isset($this->_published[$cacheKey])) {
			return $this->_published[$cacheKey];
		}
		$isDir = $target['isDir'];
		$dst = $target['dst'];
		$atomic = $options['atomic'] ?? $this->getAtomic();
		$forceCopy = $options['forceCopy'] ?? $this->getForceCopy();
		// A file is atomic via temp-then-rename; a directory (which the asset populates
		// itself) is gated by a per-source completion marker, so an interrupted population
		// re-runs instead of leaving a partial directory that looks finished.
		$marker = ($isDir && $atomic) ? $dst . DIRECTORY_SEPARATOR . $this->directoryCompleteMarker($target['vpath']) : null;
		$exists = $isDir ? ($marker !== null ? is_file($marker) : is_dir($dst)) : is_file($dst);
		if (!$exists || $checkTimestamp || $forceCopy || @filemtime($dst) < $asset->getAssetModificationDate() || $this->getApplication()->getMode() !== TApplicationMode::Performance) {
			$parent = $isDir ? $dst : dirname($dst);
			if (!is_dir($parent)) {
				$dirMode = $this->getDirMode();
				@mkdir($parent, $dirMode, true);
				@chmod($parent, $dirMode);
			}
			Prado::trace("Publishing virtual asset {$target['vpath']} to $dst", TAssetManager::class);
			if (!$isDir && $atomic) {
				$this->writeAtomic($dst, fn ($tmp) => $asset->publish($tmp));
			} else {
				$asset->publish($dst);
			}
			if ($marker !== null && is_dir($dst)) {
				@touch($marker);
			}
			$fileMode = $this->getFileMode();
			if (!$isDir && $fileMode !== null && is_file($dst)) {
				@chmod($dst, $fileMode);
			}
		}
		$url = $target['url'];
		if (!$isDir && $this->getAppendTimestamp() && ($timestamp = @filemtime($dst)) > 0) {
			$url .= '?' . $this->getTimestampVar() . '=' . $timestamp;
		}
		if ($asset instanceof IPublishedCapture) {
			$asset->setPublishedPath($dst);
			$asset->setPublishedUrl($url);
		}
		return $this->_published[$cacheKey] = $url;
	}

	/**
	 * Resolves the published target of a virtual asset from its translated file path,
	 * without writing anything. The virtual path's directory keys the hashed published
	 * directory and its basename is the published file name; a trailing
	 * DIRECTORY_SEPARATOR designates a directory.
	 * @param IPublishable $asset the virtual asset to resolve.
	 * @throws TInvalidDataValueException when the virtual file path is empty or invalid.
	 * @return ?array null when publishing is cancelled (a null virtual file path);
	 *   otherwise the keys vpath, isDir, fileName, and those from {@see publishedLocation}.
	 * @since 4.4.0
	 */
	protected function virtualAssetTarget($asset)
	{
		$vpath = $asset->getAssetFilePath();
		if ($vpath === null) {
			return null;
		}
		if (empty($vpath)) {
			throw new TInvalidDataValueException('assetmanager_filepath_invalid', $vpath);
		}
		$isDir = substr($vpath, -1) === DIRECTORY_SEPARATOR;
		$vpath = rtrim($vpath, DIRECTORY_SEPARATOR);
		$fileName = basename($vpath);
		return ['vpath' => $vpath, 'isDir' => $isDir, 'fileName' => $fileName]
			+ $this->publishedLocation($vpath, $isDir, $fileName);
	}

	/**
	 * Computes the hashed published location of a path, shared by the virtual and the
	 * non-virtual publish routes. The hashed sub-directory keys off the path's parent
	 * directory for a file, or the path itself for a directory. The destination is the
	 * published file path for a file, or the hashed directory for a directory.
	 * @param string $path the source file or directory path, without a trailing
	 *   DIRECTORY_SEPARATOR.
	 * @param bool $isDir whether the path designates a directory.
	 * @param string $fileName the published file name (the basename of a file path).
	 * @return array the keys dir (hashed sub-directory name), dstDir (its absolute path,
	 *   the copy target), dst (the published file or directory path), and url.
	 * @since 4.4.0
	 */
	protected function publishedLocation($path, $isDir, $fileName)
	{
		$dir = $this->hash($isDir ? $path : dirname($path));
		$dstDir = $this->_basePath . DIRECTORY_SEPARATOR . $dir;
		return [
			'dir' => $dir,
			'dstDir' => $dstDir,
			'dst' => $isDir ? $dstDir : $dstDir . DIRECTORY_SEPARATOR . $fileName,
			'url' => $this->_baseUrl . '/' . $dir . ($isDir ? '' : '/' . $fileName),
		];
	}

	/**
	 * The completion marker file name for a published source directory. The name embeds a
	 * SHA-1 of the source path, so it is unique to that source even when two sources share
	 * a hashed published directory (a crc32 collision) and cannot be guessed without the
	 * source path. A directory is considered fully published only when its own marker is
	 * present.
	 * @param string $sourcePath the source directory path being published.
	 * @return string the marker file name.
	 * @since 4.4.0
	 */
	protected function directoryCompleteMarker($sourcePath)
	{
		return static::DIRECTORY_COMPLETE_MARKER_PREFIX . sha1($sourcePath);
	}

	/**
	 * Writes an asset atomically: the content is generated or copied into a temporary
	 * file next to the destination, and the temp file is then moved into place. The
	 * temporary file is colocated with the destination so the move is an atomic rename on
	 * the same filesystem, and a half-written asset never appears at the destination. The
	 * temp file is removed whether the writer throws or the move fails, so a failed
	 * publish leaves no debris behind.
	 * @param string $dst the destination file path.
	 * @param callable $writer a callback that writes the content to the path it receives.
	 * @throws \Throwable rethrown from the writer after the temp file is removed.
	 * @return string the destination path.
	 * @since 4.4.0
	 */
	protected function writeAtomic(string $dst, callable $writer): string
	{
		$tmp = dirname($dst) . DIRECTORY_SEPARATOR . 'tmp-' . uniqid('', true) . '-' . basename($dst);
		try {
			$writer($tmp);
		} catch (\Throwable $e) {
			if (is_file($tmp)) {
				@unlink($tmp);
			}
			throw $e;
		}
		if (is_file($tmp) && $tmp !== $dst && !@rename($tmp, $dst)) {
			@unlink($tmp);
		}
		return $dst;
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
	 * Returns the published path of a file path, directory, or virtual asset.
	 * This method does not perform any publishing. It merely tells you where the asset
	 * will go if published.
	 * @param IPublishable|string $path the directory or file path, or a virtual asset.
	 * @return string the published file or directory path; '' when a virtual asset cancels.
	 */
	public function getPublishedPath($path)
	{
		if ($path instanceof IPublishable) {
			$target = $this->virtualAssetTarget($path);
			return $target === null ? '' : $target['dst'];
		}
		$path = realpath($path);
		return $this->publishedLocation($path, !is_file($path), basename($path))['dst'];
	}

	/**
	 * Returns the URL of a published file path, directory, or virtual asset.
	 * This method does not perform any publishing. It merely tells you what the URL will
	 * be to access the asset if published.
	 * @param IPublishable|string $path the directory or file path, or a virtual asset.
	 * @return string the published URL; '' when a virtual asset cancels.
	 */
	public function getPublishedUrl($path)
	{
		if ($path instanceof IPublishable) {
			$target = $this->virtualAssetTarget($path);
			if ($target === null) {
				return '';
			}
			$url = $target['url'];
			if (!$target['isDir'] && $this->getAppendTimestamp() && ($timestamp = @filemtime($target['dst'])) > 0) {
				$url .= '?' . $this->getTimestampVar() . '=' . $timestamp;
			}
			return $url;
		}
		$path = realpath($path);
		return $this->publishedLocation($path, !is_file($path), basename($path))['url'];
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
	 *
	 * The options each fall back to the matching property when absent:
	 *
	 * | key           | type     | effect                                                        |
	 * |---------------|----------|---------------------------------------------------------------|
	 * | only          | string[] | glob patterns the file must match to be copied                |
	 * | except        | string[] | glob patterns that exclude the file                           |
	 * | caseSensitive | bool     | whether the patterns are case sensitive; default true         |
	 * | beforeCopy    | callable | `fn($src, $dst): bool`; return false to skip the file         |
	 * | afterCopy     | callable | `fn($src, $dst): void` run after the file is copied           |
	 * | forceCopy     | bool     | copy even when the destination already exists                 |
	 * | atomic        | bool     | copy through {@see writeAtomic} (temp file then rename); defaults to {@see setAtomic Atomic} |
	 *
	 * @param string $src source file path
	 * @param string $dst destination directory (if not exists, it will be created)
	 * @param array $options the publishing options listed above
	 */
	protected function copyFile($src, $dst, $options = [])
	{
		$only = array_key_exists('only', $options) ? $options['only'] : $this->getOnly();
		$except = array_key_exists('except', $options) ? $options['except'] : $this->getExcept();
		$caseSensitive = $options['caseSensitive'] ?? $this->getCaseSensitive();
		$atomic = $options['atomic'] ?? $this->getAtomic();

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
			if ($atomic) {
				$this->writeAtomic($dstFile, function ($tmp) use ($src) {
					@copy($src, $tmp);
				});
			} else {
				@copy($src, $dstFile);
			}
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
	 *
	 * The options each fall back to the matching property when absent:
	 *
	 * | key           | type     | effect                                                        |
	 * |---------------|----------|---------------------------------------------------------------|
	 * | only          | string[] | glob patterns a file must match to be copied                  |
	 * | except        | string[] | glob patterns that exclude a matching file or directory       |
	 * | caseSensitive | bool     | whether the patterns are case sensitive; default true         |
	 * | beforeCopy    | callable | `fn($src, $dst): bool` per file/sub-directory; false to skip  |
	 * | afterCopy     | callable | `fn($src, $dst): void` after a file/sub-directory is copied   |
	 * | forceCopy     | bool     | copy even when the destination already exists                 |
	 * | atomic        | bool     | copy each file through a temp file and rename; defaults to {@see setAtomic Atomic} |
	 *
	 * Patterns are matched against the path relative to the published root using
	 * gitignore-style rules. The "except" patterns also prune whole sub-directories;
	 * "only" filters files and never prunes a directory, so nested matching files
	 * remain reachable. A sub-directory whose entire contents are filtered out is
	 * removed rather than left empty, while a directory that is empty in the source
	 * is preserved. Directory symlinks are followed once; a realpath already visited
	 * is skipped so a symlink cycle cannot loop forever.
	 *
	 * @param string $src the source directory
	 * @param string $dst the destination directory
	 * @param array $options the publishing options listed above
	 * @param ?string $basePath @internal the root source directory relative paths are
	 *   computed against; defaults to $src on the top-level call and preserved in recursion.
	 * @param array $visited @internal realpaths already entered, keyed by realpath, used
	 *   to break symlink cycles across recursion.
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
				if (in_array($file, self::PATH_COPY_EXCEPTIONS) || str_starts_with($file, static::DIRECTORY_COMPLETE_MARKER_PREFIX)) {
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
	 *
	 * Like {@see publish}, the third argument is a boolean timestamp flag or an options
	 * array. Beyond the publishing options, the array configures the
	 * {@see \Prado\IO\TTarFileExtractor} that performs the extraction:
	 *
	 * | key          | type  | effect                                                                                      |
	 * |--------------|-------|---------------------------------------------------------------------------------------------|
	 * | atomic       | bool  | extract through a staging directory then rename, gating the checksum copy the same way; defaults to {@see setAtomic Atomic} |
	 * | strict       | bool  | reject a malformed archive rather than extracting what it can                               |
	 * | conflictMode | mixed | how an entry already present at the destination is resolved                                 |
	 * | dirMode      | ?int  | permissions for the extracted directories; defaults to {@see setDirMode DirMode}            |
	 * | fileMode     | ?int  | permissions for the extracted files; defaults to {@see setFileMode FileMode}                |
	 *
	 * The extractor's remaining features (exception class, URL timeout, retained temp file,
	 * extraction manifest) are reachable by overriding {@see deployTarFile}.
	 *
	 * @param string $tarfile tar filename
	 * @param string $md5sum MD5 checksum for the corresponding tar file.
	 * @param array|bool $checkTimestamp the modification-time flag, or an options array
	 *   (see {@see publish}). An options array implies the timestamp flag from forceCopy.
	 * @return string URL path to the directory where the tar file was extracted.
	 */
	public function publishTarFile($tarfile, $md5sum, $checkTimestamp = false)
	{
		$options = [];
		if (is_array($checkTimestamp)) {
			$options = $checkTimestamp;
			$checkTimestamp = $options['forceCopy'] ?? false;
		}
		if (isset($this->_published[$md5sum])) {
			return $this->_published[$md5sum];
		} elseif (($fullpath = realpath($md5sum)) === false || !is_file($fullpath)) {
			throw new TInvalidDataValueException('assetmanager_tarchecksum_invalid', $md5sum);
		} else {
			$dir = $this->hash(dirname($fullpath));
			$fileName = basename($fullpath);
			$dst = $this->_basePath . DIRECTORY_SEPARATOR . $dir;
			$atomic = $options['atomic'] ?? $this->getAtomic();
			if (!is_file($dst . DIRECTORY_SEPARATOR . $fileName) || $checkTimestamp || $this->getApplication()->getMode() !== TApplicationMode::Performance) {
				if (@filemtime($dst . DIRECTORY_SEPARATOR . $fileName) < @filemtime($fullpath)) {
					// The checksum file is always published; instance only/except filters do not apply.
					$this->copyFile($fullpath, $dst, array_merge($options, ['only' => null, 'except' => null, 'atomic' => $atomic]));
					$this->deployTarFile($tarfile, $dst, $options);
				}
			}
			return $this->_published[$md5sum] = $this->_baseUrl . '/' . $dir;
		}
	}

	/**
	 * Extracts the tar file to the destination directory with {@see \Prado\IO\TTarFileExtractor}.
	 * These options configure the extractor:
	 *
	 * | option       | type | effect                                                  | default                       |
	 * |--------------|------|---------------------------------------------------------|-------------------------------|
	 * | atomic       | bool | stage to a temporary directory then rename              | {@see getAtomic Atomic}       |
	 * | dirMode      | ?int | permissions for extracted directories                   | {@see getDirMode DirMode}     |
	 * | fileMode     | ?int | permissions for extracted files                         | {@see getFileMode FileMode}   |
	 * | strict       | bool | reject a malformed archive instead of extracting best-effort | extractor default        |
	 * | conflictMode | int  | how an entry already present at the destination resolves | extractor default            |
	 *
	 * @param string $path tar file
	 * @param string $destination path where the contents of tar file are to be extracted
	 * @param array $options the tar publishing options.
	 * @return bool true if extract successful, false otherwise.
	 */
	protected function deployTarFile($path, $destination, array $options = [])
	{
		if (($fullpath = realpath($path)) === false || !is_file($fullpath)) {
			throw new TIOException('assetmanager_tarfile_invalid', $path);
		}
		$tar = new TTarFileExtractor($fullpath);
		$tar->setAtomic($options['atomic'] ?? $this->getAtomic());
		$tar->setDirModeOverride($options['dirMode'] ?? $this->getDirMode());
		$tar->setFileModeOverride($options['fileMode'] ?? $this->getFileMode());
		if (array_key_exists('strict', $options)) {
			$tar->setStrict($options['strict']);
		}
		if (array_key_exists('conflictMode', $options)) {
			$tar->setConflictMode($options['conflictMode']);
		}
		return $tar->extract($destination);
	}
}
