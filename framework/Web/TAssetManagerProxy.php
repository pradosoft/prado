<?php

/**
 * TAssetManagerProxy class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web;

use Prado\Exceptions\TConfigurationException;
use Prado\IModuleDependency;
use Prado\IProxy;
use Prado\Prado;
use Prado\TComponent;
use Prado\TComponentProxyTrait;
use Prado\TModule;
use Prado\Util\Log\TLogger;

/**
 * TAssetManagerProxy class.
 *
 * TAssetManagerProxy is a transparent asset manager module that delegates every
 * {@see TAssetManager} operation to another TAssetManager module registered with
 * the application. The application asset manager slot can be swapped at
 * configuration time without changing the consumers that publish through it.
 *
 * ## Configuration
 *
 * {@see getBackingAssetManagerId BackingAssetManagerId} names the backing asset
 * manager module. TAssetManagerProxy declares that module as a required
 * {@see IModuleDependency}, so the application initializes it first.
 *
 * ## Initialization
 *
 * {@see init()} registers the proxy as the application asset manager and raises
 * `dyInit` through {@see TModule::init()}. It does not call
 * {@see TAssetManager::init()}: the proxy owns no publishing directory, so it
 * resolves no {@see TAssetManager::getBasePath BasePath} or
 * {@see TAssetManager::getBaseUrl BaseUrl}, creates no directory, and needs no
 * request. The backing module resolves those on its own initialization.
 *
 * ## Transparency
 *
 * Every public property and method of {@see TAssetManager} is overridden to call
 * the backing's public method, so the backing's paths, options, publish cache,
 * and asset map apply unchanged. Other property reads and writes, method calls,
 * and events reach the backing through {@see TComponentProxyTrait}, which wires
 * the backing's public `on` events on first resolution.
 *
 * Replacing a {@see setBackingAssetManagerId BackingAssetManagerId} logs a
 * {@see TLogger::WARNING} so a runtime swap is visible in the application log.
 *
 * Configure in `application.xml`:
 * ```xml
 * <module id="asset" class="Prado\Web\TAssetManagerProxy" BackingAssetManagerId="fileAssets" />
 * <module id="fileAssets" class="Prado\Web\TAssetManager" BasePath="Application.assets" BaseUrl="/assets" />
 * ```
 *
 * Or instantiate directly:
 * ```php
 * $proxy = new TAssetManagerProxy();
 * $proxy->setBackingAssetManagerId('fileAssets');
 * $proxy->init(null);
 * // All operations now delegate to the 'fileAssets' module.
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TAssetManagerProxy extends TAssetManager implements IModuleDependency, IProxy
{
	use TComponentProxyTrait;

	/** @var string module ID of the backing asset manager; empty until configured */
	private string $_backingAssetManagerId = '';

	// ----------------------------------------------------------------- lifecycle

	/**
	 * Declares the backing asset manager module as a required dependency so that
	 * {@see \Prado\TApplication} initializes it before this proxy, in every pass.
	 * @param bool $isPreInit `true` for the dyPreInit pass, `false` for init(); not used
	 * @return ?string the backing asset manager module ID, or null when none is configured
	 */
	public function getModuleDependencies(bool $isPreInit = false): ?string
	{
		$id = $this->getBackingAssetManagerId();
		return $id === '' ? null : $id;
	}

	/**
	 * Initializes the proxy module: registers it as the application asset manager,
	 * raises `dyInit` through {@see TModule::init()}, and marks it initialized.
	 * {@see TAssetManager::init()} is bypassed because the proxy has no publishing
	 * directory of its own; the backing module resolves its paths on its own init.
	 * @param null|array|\Prado\Xml\TXmlElement $config module configuration
	 * @throws TConfigurationException when {@see getBackingAssetManagerId BackingAssetManagerId} is empty
	 */
	public function init($config)
	{
		if ($this->getBackingAssetManagerId() === '') {
			throw new TConfigurationException('assetmanagerproxy_backing_asset_manager_id_required');
		}
		$this->setAppAssetManager();
		TModule::init($config);
		$this->markInitialized();
	}

	// ----------------------------------------------------------------- TComponentProxyTrait implementation

	/**
	 * Returns the backing asset manager through {@see getAssetManager()}.
	 * @throws TConfigurationException when {@see getBackingAssetManagerId BackingAssetManagerId} is empty
	 * @throws TConfigurationException when the referenced module does not exist
	 * @throws TConfigurationException when the referenced module is not a {@see TAssetManager}
	 * @return ?TComponent the backing asset manager
	 */
	public function getProxyBacking(): ?TComponent
	{
		return $this->getAssetManager();
	}

	/**
	 * Returns whether a {@see getBackingAssetManagerId BackingAssetManagerId} is
	 * configured, which enables lazy resolution from the module registry.
	 * @return bool whether lazy resolution is possible
	 */
	protected function canResolveProxyBacking(): bool
	{
		return $this->getBackingAssetManagerId() !== '';
	}

	// --------------------------------------------------------------- accessors

	/**
	 * @return string the stored module ID of the backing asset manager
	 */
	protected function getBackingAssetManagerIdDirect(): string
	{
		return $this->_backingAssetManagerId;
	}

	/**
	 * @param string $value the module ID to store
	 */
	protected function setBackingAssetManagerIdDirect(string $value): void
	{
		$this->_backingAssetManagerId = $value;
	}

	/**
	 * @return string the module ID of the backing asset manager
	 */
	public function getBackingAssetManagerId(): string
	{
		return $this->getBackingAssetManagerIdDirect();
	}

	/**
	 * Sets the module ID of the backing asset manager. Replacing a non-empty ID
	 * detaches the event forwarders, logs a {@see TLogger::WARNING}, and drops the
	 * resolved asset manager so the next operation resolves the new module.
	 * @param string $value the module ID of the asset manager to proxy
	 */
	public function setBackingAssetManagerId(string $value): void
	{
		$current = $this->getBackingAssetManagerIdDirect();
		if ($value === $current) {
			return;
		}
		if ($current !== '') {
			$this->detachProxy();
			Prado::log(
				sprintf("TAssetManagerProxy.BackingAssetManagerId changed from '%s' to '%s'.", $current, $value),
				TLogger::WARNING,
				'prado.web'
			);
		}
		$this->setBackingAssetManagerIdDirect($value);
		$this->setAssetManagerDirect(null);
	}

	/**
	 * Returns the stored backing asset manager, narrowing the trait's `?TComponent`
	 * to `?TAssetManager`.
	 * @return ?TAssetManager the backing asset manager, or null when not yet resolved
	 */
	protected function getAssetManagerDirect(): ?TAssetManager
	{
		$backing = $this->getProxyBackingDirect();
		return $backing instanceof TAssetManager ? $backing : null;
	}

	/**
	 * @param ?TAssetManager $assetManager the backing asset manager to store
	 */
	protected function setAssetManagerDirect(?TAssetManager $assetManager): void
	{
		$this->setProxyBackingDirect($assetManager);
	}

	/**
	 * Returns the backing {@see TAssetManager}, resolving it through
	 * {@see \Prado\TApplication::getModule()} on first call. The first resolution
	 * calls {@see attachProxy()}.
	 * @throws TConfigurationException when {@see getBackingAssetManagerId BackingAssetManagerId} is empty
	 * @throws TConfigurationException when the referenced module does not exist
	 * @throws TConfigurationException when the referenced module is not a {@see TAssetManager}
	 * @return TAssetManager the backing asset manager module
	 */
	public function getAssetManager(): TAssetManager
	{
		$assetManager = $this->getAssetManagerDirect();
		if ($assetManager === null) {
			$id = $this->getBackingAssetManagerId();
			if ($id === '') {
				throw new TConfigurationException('assetmanagerproxy_backing_asset_manager_id_required');
			}
			$assetManager = $this->getApplication()->getModule($id);
			if ($assetManager === null) {
				throw new TConfigurationException('assetmanagerproxy_asset_manager_not_found', $id);
			}
			if (!($assetManager instanceof TAssetManager)) {
				throw new TConfigurationException('assetmanagerproxy_invalid_asset_manager_type', $id);
			}
			$this->setAssetManagerDirect($assetManager);
			$this->attachProxy();
		}
		return $assetManager;
	}

	// ----------------------------------------------------------------- TAssetManager properties

	/**
	 * Returns the backing's root directory storing published asset files.
	 * @return string the root directory storing published asset files
	 */
	public function getBasePath()
	{
		return $this->getAssetManager()->getBasePath();
	}

	/**
	 * Sets the backing's root directory storing published asset files.
	 * @param string $value the root directory storing published asset files, in namespace format
	 */
	public function setBasePath($value)
	{
		$this->getAssetManager()->setBasePath($value);
	}

	/**
	 * Returns the backing's base URL for accessing published asset files.
	 * @return string the base url that the published asset files can be accessed
	 */
	public function getBaseUrl()
	{
		return $this->getAssetManager()->getBaseUrl();
	}

	/**
	 * Sets the backing's base URL for accessing published asset files.
	 * @param string $value the base url that the published asset files can be accessed
	 */
	public function setBaseUrl($value)
	{
		$this->getAssetManager()->setBaseUrl($value);
	}

	/**
	 * Returns whether the backing publishes asset files as symbolic links.
	 * @return bool whether to use symbolic link to publish asset files
	 */
	public function getLinkAssets()
	{
		return $this->getAssetManager()->getLinkAssets();
	}

	/**
	 * Sets whether the backing publishes asset files as symbolic links.
	 * @param bool $value whether to use symbolic link to publish asset files
	 */
	public function setLinkAssets($value)
	{
		$this->getAssetManager()->setLinkAssets($value);
	}

	/**
	 * Returns whether the backing copies asset files that already exist.
	 * @return bool whether to copy asset files even if they already exist in the target directory
	 */
	public function getForceCopy()
	{
		return $this->getAssetManager()->getForceCopy();
	}

	/**
	 * Sets whether the backing copies asset files that already exist.
	 * @param bool $value whether to copy asset files even if they already exist in the target directory
	 */
	public function setForceCopy($value)
	{
		$this->getAssetManager()->setForceCopy($value);
	}

	/**
	 * Returns whether the backing publishes files through a temporary file and rename.
	 * @return bool whether files publish through a temporary file and rename
	 */
	public function getAtomic()
	{
		return $this->getAssetManager()->getAtomic();
	}

	/**
	 * Sets whether the backing publishes files through a temporary file and rename.
	 * @param bool $value whether files publish through a temporary file and rename
	 */
	public function setAtomic($value)
	{
		$this->getAssetManager()->setAtomic($value);
	}

	/**
	 * Returns whether the backing appends a timestamp to published asset URLs.
	 * @return bool whether to append timestamp to the URL of every published asset
	 */
	public function getAppendTimestamp()
	{
		return $this->getAssetManager()->getAppendTimestamp();
	}

	/**
	 * Sets whether the backing appends a timestamp to published asset URLs.
	 * @param bool $value whether to append timestamp to the URL of every published asset
	 */
	public function setAppendTimestamp($value)
	{
		$this->getAssetManager()->setAppendTimestamp($value);
	}

	/**
	 * Returns the backing's query parameter name for timestamp appending.
	 * @return string the query parameter name to use for timestamp appending
	 */
	public function getTimestampVar()
	{
		return $this->getAssetManager()->getTimestampVar();
	}

	/**
	 * Sets the backing's query parameter name for timestamp appending.
	 * @param string $value the query parameter name to use for timestamp appending
	 */
	public function setTimestampVar($value)
	{
		$this->getAssetManager()->setTimestampVar($value);
	}

	/**
	 * Returns the backing's hash callback for asset directory generation.
	 * @return null|callable a callback that produces the hash for asset directory generation
	 */
	public function getHashCallback()
	{
		return $this->getAssetManager()->getHashCallback();
	}

	/**
	 * Sets the backing's hash callback for asset directory generation.
	 * @param null|callable $value a callback that produces the hash for asset directory generation
	 */
	public function setHashCallback($value)
	{
		$this->getAssetManager()->setHashCallback($value);
	}

	/**
	 * Returns the backing's callback called before copying each sub-directory or file.
	 * @return null|callable a PHP callback that is called before copying each sub-directory or file
	 */
	public function getBeforeCopy()
	{
		return $this->getAssetManager()->getBeforeCopy();
	}

	/**
	 * Sets the backing's callback called before copying each sub-directory or file.
	 * @param null|callable $value a PHP callback that is called before copying each sub-directory or file
	 */
	public function setBeforeCopy($value)
	{
		$this->getAssetManager()->setBeforeCopy($value);
	}

	/**
	 * Returns the backing's callback called after a sub-directory or file is copied.
	 * @return null|callable a PHP callback that is called after a sub-directory or file is successfully copied
	 */
	public function getAfterCopy()
	{
		return $this->getAssetManager()->getAfterCopy();
	}

	/**
	 * Sets the backing's callback called after a sub-directory or file is copied.
	 * @param null|callable $value a PHP callback that is called after a sub-directory or file is successfully copied
	 */
	public function setAfterCopy($value)
	{
		$this->getAssetManager()->setAfterCopy($value);
	}

	/**
	 * Returns the backing's mapping from source asset files to target asset files.
	 * @return array mapping from source asset files (keys) to target asset files (values)
	 */
	public function getAssetMap()
	{
		return $this->getAssetManager()->getAssetMap();
	}

	/**
	 * Sets the backing's mapping from source asset files to target asset files.
	 * @param array $value mapping from source asset files (keys) to target asset files (values)
	 */
	public function setAssetMap($value)
	{
		$this->getAssetManager()->setAssetMap($value);
	}

	/**
	 * Returns the backing's patterns that file paths must match to be copied.
	 * @return null|array list of patterns that the file paths should match if they want to be copied
	 */
	public function getOnly()
	{
		return $this->getAssetManager()->getOnly();
	}

	/**
	 * Sets the backing's patterns that file paths must match to be copied.
	 * @param null|array $value list of patterns that the file paths should match if they want to be copied
	 */
	public function setOnly($value)
	{
		$this->getAssetManager()->setOnly($value);
	}

	/**
	 * Returns the backing's patterns that exclude files or directories from copying.
	 * @return null|array list of patterns that exclude matching files or directories from being copied
	 */
	public function getExcept()
	{
		return $this->getAssetManager()->getExcept();
	}

	/**
	 * Sets the backing's patterns that exclude files or directories from copying.
	 * @param null|array $value list of patterns that exclude matching files or directories from being copied
	 */
	public function setExcept($value)
	{
		$this->getAssetManager()->setExcept($value);
	}

	/**
	 * Returns whether the backing's "only" and "except" patterns are case sensitive.
	 * @return bool whether patterns specified at "only" or "except" should be case sensitive
	 */
	public function getCaseSensitive()
	{
		return $this->getAssetManager()->getCaseSensitive();
	}

	/**
	 * Sets whether the backing's "only" and "except" patterns are case sensitive.
	 * @param bool $value whether patterns specified at "only" or "except" should be case sensitive
	 */
	public function setCaseSensitive($value)
	{
		$this->getAssetManager()->setCaseSensitive($value);
	}

	/**
	 * Returns the backing's permission for newly published asset files.
	 * @return null|int the permission to be set for newly published asset files
	 */
	public function getFileMode()
	{
		return $this->getAssetManager()->getFileMode();
	}

	/**
	 * Sets the backing's permission for newly published asset files.
	 * @param null|int $value the permission to be set for newly published asset files
	 */
	public function setFileMode($value)
	{
		$this->getAssetManager()->setFileMode($value);
	}

	/**
	 * Returns the backing's permission for newly generated asset directories.
	 * @return int the permission to be set for newly generated asset directories
	 */
	public function getDirMode()
	{
		return $this->getAssetManager()->getDirMode();
	}

	/**
	 * Sets the backing's permission for newly generated asset directories.
	 * @param null|int $value the permission to be set for newly generated asset directories
	 */
	public function setDirMode($value)
	{
		$this->getAssetManager()->setDirMode($value);
	}

	// ----------------------------------------------------------------- TAssetManager operations

	/**
	 * Publishes a file, directory, or virtual asset through the backing.
	 * @param IPublishable|string $path the file or directory path, or a virtual asset
	 * @param array|bool $checkTimestamp the modification-time flag, or the options array
	 * @return string the absolute URL to the published file, directory, or asset
	 */
	public function publishFilePath($path, $checkTimestamp = false)
	{
		return $this->getAssetManager()->publishFilePath($path, $checkTimestamp);
	}

	/**
	 * Publishes a file, directory, or virtual asset through the backing.
	 * @param IPublishable|string $path the path to publish, or a virtual asset
	 * @param array|bool $checkTimestamp the modification-time flag, or an options array
	 * @return string the absolute URL to the published file, directory, or asset
	 */
	public function publish($path, $checkTimestamp = false)
	{
		return $this->getAssetManager()->publish($path, $checkTimestamp);
	}

	/**
	 * Returns the backing's list of published assets.
	 * @return array list of published assets
	 */
	public function getPublished()
	{
		return $this->getAssetManager()->getPublished();
	}

	/**
	 * Returns the published path of a path or virtual asset through the backing.
	 * @param IPublishable|string $path the directory or file path, or a virtual asset
	 * @return string the published file or directory path; '' when a virtual asset cancels
	 */
	public function getPublishedPath($path)
	{
		return $this->getAssetManager()->getPublishedPath($path);
	}

	/**
	 * Returns the published URL of a path or virtual asset through the backing.
	 * @param IPublishable|string $path the directory or file path, or a virtual asset
	 * @return string the published URL; '' when a virtual asset cancels
	 */
	public function getPublishedUrl($path)
	{
		return $this->getAssetManager()->getPublishedUrl($path);
	}

	/**
	 * Validates symlinks through the backing, optionally removing the broken ones.
	 * @param ?string $path the link or directory to validate; defaults to the backing's publishing root
	 * @param bool $remove whether to delete broken links
	 * @return null|bool|int the validation result, by path kind
	 */
	public function validateSymlinks($path = null, $remove = true)
	{
		return $this->getAssetManager()->validateSymlinks($path, $remove);
	}

	/**
	 * Copies a directory recursively through the backing.
	 * @param string $src the source directory
	 * @param string $dst the destination directory
	 * @param array $options the publishing options
	 * @param ?string $basePath the root source directory relative paths are computed against
	 * @param array $visited realpaths already entered, keyed by realpath
	 */
	public function copyDirectory($src, $dst, $options = [], $basePath = null, &$visited = [])
	{
		$this->getAssetManager()->copyDirectory($src, $dst, $options, $basePath, $visited);
	}

	/**
	 * Resolves the actual URL of an asset through the backing's asset map.
	 * @param string $asset the asset path
	 * @param ?string $sourcePath the source path of the asset bundle
	 * @return ?string the actual URL for the asset, or null when no mapping matches
	 */
	public function resolveAsset($asset, $sourcePath = null)
	{
		return $this->getAssetManager()->resolveAsset($asset, $sourcePath);
	}

	/**
	 * Publishes a tar file through the backing by extracting it to the assets directory.
	 * @param string $tarfile tar filename
	 * @param string $md5sum MD5 checksum file for the corresponding tar file
	 * @param array|bool $checkTimestamp the modification-time flag, or an options array
	 * @return string URL path to the directory where the tar file was extracted
	 */
	public function publishTarFile($tarfile, $md5sum, $checkTimestamp = false)
	{
		return $this->getAssetManager()->publishTarFile($tarfile, $md5sum, $checkTimestamp);
	}

	// -------------------------------------------------- serialization

	/**
	 * Excludes the transient forwarder list, the resolved backing asset manager
	 * (re-resolved from the module registry after unserialization), and an empty
	 * backing ID from serialization.
	 * @param array $exprops excluded-properties list, passed by reference
	 */
	protected function _getZappableSleepProps(&$exprops)
	{
		parent::_getZappableSleepProps($exprops);
		$this->_addProxyEventNamesZappable($exprops);
		$this->_addProxyBackingZappable($exprops);
		if ($this->getBackingAssetManagerIdDirect() === '') {
			$exprops[] = "\0" . __CLASS__ . "\0_backingAssetManagerId";
		}
	}
}
