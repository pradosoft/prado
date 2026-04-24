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
 * TAssetManager may be configured in application configuration file as follows,
 * ```xml
 * <module id="asset" BasePath="Application.assets" BaseUrl="/assets" />
 * ```
 * where {@see getBasePath BasePath} and {@see getBaseUrl BaseUrl} are
 * configurable properties of TAssetManager. Make sure that BasePath is a namespace
 * pointing to a valid directory writable by the Web server process.
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
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 */
class TAssetManager extends \Prado\TModule
{
	use TInitializedTrait;

	/** Default web accessible base path for storing private files  */
	public const DEFAULT_BASEPATH = 'assets';

	/** @var string base web accessible path for storing private files */
	private $_basePath;
	/** @var string base URL for accessing the publishing directory. */
	private $_baseUrl;
	/** @var bool whether to use timestamp checking to ensure files are published with up-to-date versions. */
	private $_checkTimestamp = false;
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
	 * @param \Prado\Xml\TXmlElement $config module configuration
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
		$application->setAssetManager($this);
		parent::init($config);
		$this->markInitialized();
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
		$this->_basePath = Prado::getPathOfNamespace($value);
		if ($this->_basePath === null || !is_dir($this->_basePath) || !is_writable($this->_basePath)) {
			throw new TInvalidDataValueException('assetmanager_basepath_invalid', $value);
		}
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
	 * @return string an absolute URL to the published directory
	 */
	public function publishFilePath($path, $checkTimestamp = false)
	{
		$options = [];
		if (is_array($checkTimestamp)) {
			$options = $checkTimestamp;
			$checkTimestamp = $options['forceCopy'] ?? false;
		}

		if (isset($this->_published[$path])) {
			return $this->_published[$path];
		} elseif (empty($path) || ($fullpath = realpath($path)) === false) {
			throw new TInvalidDataValueException('assetmanager_filepath_invalid', $path);
		} elseif (is_file($fullpath)) {
			$dir = $this->hash(dirname($fullpath));
			$fileName = basename($fullpath);
			$dst = $this->_basePath . DIRECTORY_SEPARATOR . $dir;
			if (!is_file($dst . DIRECTORY_SEPARATOR . $fileName) || $checkTimestamp || $this->getApplication()->getMode() !== TApplicationMode::Performance) {
				$this->copyFile($fullpath, $dst, $options);
			}
			$publishedUrl = $this->_baseUrl . '/' . $dir . '/' . $fileName;
			if ($this->getAppendTimestamp()) {
				$dstFile = $dst . DIRECTORY_SEPARATOR . $fileName;
				if (($timestamp = @filemtime($dstFile)) > 0) {
					$publishedUrl .= '?' . $this->getTimestampVar() . '=' . $timestamp;
				}
			}
			return $this->_published[$path] = $publishedUrl;
		} else {
			$dir = $this->hash($fullpath);
			$forceCopy = $options['forceCopy'] ?? $this->getForceCopy();
			if (!is_dir($this->_basePath . DIRECTORY_SEPARATOR . $dir) || $checkTimestamp || $forceCopy || $this->getApplication()->getMode() !== TApplicationMode::Performance) {
				Prado::trace("Publishing directory $fullpath", TAssetManager::class);
				$this->copyDirectory($fullpath, $this->_basePath . DIRECTORY_SEPARATOR . $dir, $options);
			}
			return $this->_published[$path] = $this->_baseUrl . '/' . $dir;
		}
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
	 * Generate a CRC32 hash for the directory path. Collisions are higher
	 * than MD5 but generates a much smaller hash string.
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
		if (!is_dir($dst)) {
			$dirMode = $this->getDirMode();
			@mkdir($dst, $dirMode);
			@chmod($dst, $dirMode);
		}
		$dstFile = $dst . DIRECTORY_SEPARATOR . basename($src);

		$only = $options['only'] ?? $this->getOnly();
		$except = $options['except'] ?? $this->getExcept();
		$caseSensitive = $options['caseSensitive'] ?? $this->getCaseSensitive();

		if (!$this->matchFilePattern($src, $only, $except, $caseSensitive)) {
			return;
		}

		$beforeCopy = $options['beforeCopy'] ?? $this->getBeforeCopy();
		if ($beforeCopy !== null && !call_user_func($beforeCopy, $src, $dstFile)) {
			return;
		}

		if ($this->getLinkAssets()) {
			if (!is_file($dstFile) && !is_link($dstFile)) {
				try {
					@unlink($dstFile);
					@symlink($src, $dstFile);
				} catch (\Throwable $e) {
					if (!is_file($dstFile) && !is_link($dstFile)) {
						throw $e;
					}
				}
			}
		} elseif (@filemtime($dstFile) < @filemtime($src)) {
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
	 */
	public function copyDirectory($src, $dst, $options = [])
	{
		$only = $options['only'] ?? $this->getOnly();
		$except = $options['except'] ?? $this->getExcept();
		$caseSensitive = $options['caseSensitive'] ?? $this->getCaseSensitive();
		$beforeCopy = $options['beforeCopy'] ?? $this->getBeforeCopy();
		$afterCopy = $options['afterCopy'] ?? $this->getAfterCopy();
		$forceCopy = $options['forceCopy'] ?? $this->getForceCopy();
		$linkAssets = $this->getLinkAssets();
		$fileMode = $this->getFileMode();
		$dirMode = $this->getDirMode();

		if (!is_dir($dst)) {
			@mkdir($dst, $dirMode);
			@chmod($dst, $dirMode);
		}
		if ($folder = @opendir($src)) {
			while ($file = @readdir($folder)) {
				if ($file === '.' || $file === '..') {
					continue;
				}
				if ($file[0] === '.' && ($file === '.svn' || $file === '.git' || $file === '.github' || $file === '.claude' || strlen($file) > 1)) {
					continue;
				}
				$srcPath = $src . DIRECTORY_SEPARATOR . $file;
				$dstPath = $dst . DIRECTORY_SEPARATOR . $file;

				if (is_file($srcPath)) {
					if (!$this->matchFilePattern($srcPath, $only, $except, $caseSensitive)) {
						continue;
					}

					if ($beforeCopy !== null && !call_user_func($beforeCopy, $srcPath, $dstPath)) {
						continue;
					}

					$shouldCopy = $forceCopy || !is_file($dstPath) || @filemtime($dstPath) < @filemtime($srcPath);
					if ($shouldCopy) {
						if ($linkAssets) {
							if (!is_link($dstPath)) {
								try {
									@unlink($dstPath);
									@symlink($srcPath, $dstPath);
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
					$this->copyDirectory($srcPath, $dstPath, $options);
				}
			}
			closedir($folder);
		} else {
			throw new TInvalidDataValueException('assetmanager_source_directory_invalid', $src);
		}
	}

	/**
	 * Checks if a file path matches the "only" or "except" patterns.
	 * @param string $path the file path to check
	 * @param null|array $only list of patterns to match if wanting to be copied
	 * @param null|array $except list of patterns to match if wanting to be excluded
	 * @param bool $caseSensitive whether the patterns are case sensitive
	 * @return bool true if the file should be copied
	 * @since 4.3.3
	 */
	protected function matchFilePattern($path, $only, $except, $caseSensitive)
	{
		$filename = basename($path);
		$checkCase = $caseSensitive ? 'fnmatch' : function ($pattern, $name) {
			return fnmatch($pattern, $name, FNM_CASEFOLD);
		};

		if ($except !== null) {
			$fnFlags = $caseSensitive ? 0 : FNM_CASEFOLD;
			foreach ($except as $pattern) {
				if (fnmatch($pattern, $filename, $fnFlags)) {
					return false;
				}
			}
		}

		if ($only !== null) {
			$matched = false;
			$fnFlags = $caseSensitive ? 0 : FNM_CASEFOLD;
			foreach ($only as $pattern) {
				if (fnmatch($pattern, $filename, $fnFlags)) {
					$matched = true;
					break;
				}
			}
			if (!$matched) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Returns the actual URL for the specified asset.
	 * @param string $asset the asset path.
	 * @param string $sourcePath the source path of the asset bundle
	 * @return string the actual URL for the asset.
	 * @since 4.3.3
	 */
	public function resolveAsset($asset, $sourcePath = null)
	{
		if (isset($this->_assetMap[$asset])) {
			return $this->_assetMap[$asset];
		}
		if ($sourcePath !== null) {
			$assetWithSource = $sourcePath . '/' . $asset;
		} else {
			$assetWithSource = $asset;
		}

		$n = strlen($asset);
		foreach ($this->_assetMap as $from => $to) {
			$n2 = strlen($from);
			if ($n2 <= $n && substr_compare($assetWithSource, $from, -$n2, $n2) === 0) {
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
					$this->copyFile($fullpath, $dst);
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
