<?php

/**
 * TServiceWorkerDefinition class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\Services\ServiceWorker;

use Prado\Exceptions\TInvalidDataValueException;
use Prado\TPropertyValue;

/**
 * TServiceWorkerDefinition class
 *
 * TServiceWorkerDefinition holds the configuration of one service worker of
 * {@see TServiceWorkerModule}: its name, its scope, the contributors it admits,
 * its fetch strategies, and its web app manifest. The module's own properties
 * configure the `default` worker; each `<worker>` element of the module
 * configures a named worker, which starts from the `default` worker's values.
 * ```xml
 * <module id="serviceworker" class="Prado\Web\Services\ServiceWorker\TServiceWorkerModule" CacheAssets="true">
 *     <worker name="admin" Scope="/admin/" OfflinePage="Admin.Offline" Contributors="adminsync" />
 * </module>
 * ```
 *
 * | Property | Use |
 * |---|---|
 * | {@see setName Name} | Letters, digits, `_`, and `-`; part of the script URL and the cache names |
 * | {@see setScope Scope} | The URL path prefix the worker controls; empty is the entry script's directory, and a relative path is resolved against it |
 * | {@see setEnabled Enabled} | False serves a worker that removes itself |
 * | {@see setContributors Contributors} | Module IDs of the contributors the worker admits; empty admits every contributor |
 * | {@see setCacheAssets CacheAssets} | Cache first for published assets |
 * | {@see setNetworkFirst NetworkFirst} | Page paths and `/` URL path prefixes answered network first |
 * | {@see setNetworkTimeout NetworkTimeout} | Seconds before a network-first request falls back to the cache; 0 waits for the network |
 * | {@see setOfflinePage OfflinePage} | The page shown for a navigation without network |
 * | {@see setNavigationPreload NavigationPreload} | Starts navigation requests while the worker starts |
 * | {@see getManifest Manifest} | The web app manifest; `Manifest.Name` and the other {@see TWebAppManifest} subproperties |
 * | {@see setManifestFile ManifestFile} | A JSON manifest file, the base of the manifest |
 *
 * Browsers match a page to the worker with the longest scope that prefixes
 * its URL, so a named worker's scope is a path below the `default` worker's.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TServiceWorkerDefinition extends \Prado\TApplicationComponent
{
	/**
	 * The default network-first timeout, in seconds.
	 */
	public const DEFAULT_NETWORK_TIMEOUT = 3.0;

	/** @var string the worker name */
	private string $_name = TServiceWorkerModule::DEFAULT_WORKER;

	/** @var string the configured scope */
	private string $_scope = '';

	/** @var bool whether the worker is enabled */
	private bool $_enabled = true;

	/** @var string[] admitted contributor module IDs */
	private array $_contributors = [];

	/** @var bool whether assets are cached */
	private bool $_cacheAssets = false;

	/** @var string[] network-first page paths and URL path prefixes */
	private array $_networkFirst = [];

	/** @var float the network-first timeout, in seconds */
	private float $_networkTimeout = self::DEFAULT_NETWORK_TIMEOUT;

	/** @var string the offline page path */
	private string $_offlinePage = '';

	/** @var bool whether navigation preload is enabled */
	private bool $_navigationPreload = false;

	/** @var ?TWebAppManifest the web app manifest */
	private ?TWebAppManifest $_manifest = null;

	/**
	 * Copies the manifest with the definition.
	 */
	public function __clone()
	{
		parent::__clone();
		if ($this->_manifest !== null) {
			$this->_manifest = clone $this->_manifest;
		}
	}

	/**
	 * @return string the worker name. Defaults to `default`.
	 */
	public function getName(): string
	{
		return $this->_name;
	}

	/**
	 * @param string $value the worker name: letters, digits, `_`, and `-`
	 * @throws TInvalidDataValueException if the name is not valid.
	 */
	public function setName($value): void
	{
		$value = trim(TPropertyValue::ensureString($value));
		if (!preg_match('/^[A-Za-z0-9_-]+$/', $value)) {
			throw new TInvalidDataValueException('serviceworkerdefinition_name_invalid', $value);
		}
		$this->_name = $value;
	}

	/**
	 * Returns the scope URL path. An empty scope is the directory of the entry
	 * script, and a relative scope is resolved against that directory.
	 * @return string the scope, starting with `/`
	 */
	public function getScope(): string
	{
		if (str_starts_with($this->_scope, '/')) {
			return $this->_scope;
		}
		return $this->getEntryDirectory() . $this->_scope;
	}

	/**
	 * @param string $value the scope URL path, such as `/admin/`; empty is the
	 *   directory of the entry script
	 */
	public function setScope($value): void
	{
		$this->_scope = trim(TPropertyValue::ensureString($value));
	}

	/**
	 * @return bool whether the worker is enabled. Defaults to true.
	 */
	public function getEnabled(): bool
	{
		return $this->_enabled;
	}

	/**
	 * @param bool|string $value whether the worker is enabled; false serves a worker
	 *   that clears its caches and unregisters itself
	 */
	public function setEnabled($value): void
	{
		$this->_enabled = TPropertyValue::ensureBoolean($value);
	}

	/**
	 * @return string[] the module IDs of the admitted contributors. Defaults to
	 *   empty, which admits every contributor.
	 */
	public function getContributors(): array
	{
		return $this->_contributors;
	}

	/**
	 * @param string|string[] $value the module IDs of the admitted contributors,
	 *   as a list or comma-separated text
	 */
	public function setContributors($value): void
	{
		$this->_contributors = self::ensureList($value);
	}

	/**
	 * @param string $id the module ID of a contributor
	 * @return bool whether the worker admits the contributor
	 */
	public function admitsContributor(string $id): bool
	{
		$contributors = $this->getContributors();
		return $contributors === [] || in_array($id, $contributors, true);
	}

	/**
	 * @return bool whether published assets are answered cache first. Defaults to false.
	 */
	public function getCacheAssets(): bool
	{
		return $this->_cacheAssets;
	}

	/**
	 * @param bool|string $value whether published assets are answered cache first
	 */
	public function setCacheAssets($value): void
	{
		$this->_cacheAssets = TPropertyValue::ensureBoolean($value);
	}

	/**
	 * @return string[] the page paths and `/` URL path prefixes answered network
	 *   first. Defaults to empty.
	 */
	public function getNetworkFirst(): array
	{
		return $this->_networkFirst;
	}

	/**
	 * @param string|string[] $value the page paths, such as `Catalog`, and URL path
	 *   prefixes, such as `/help/`, answered network first, as a list or
	 *   comma-separated text
	 */
	public function setNetworkFirst($value): void
	{
		$this->_networkFirst = self::ensureList($value);
	}

	/**
	 * @return float the seconds before a network-first request falls back to the
	 *   cache. Defaults to 3.
	 */
	public function getNetworkTimeout(): float
	{
		return $this->_networkTimeout;
	}

	/**
	 * @param float|string $value the seconds before a network-first request falls
	 *   back to the cache; 0 waits for the network
	 * @throws TInvalidDataValueException if the value is negative.
	 */
	public function setNetworkTimeout($value): void
	{
		$value = TPropertyValue::ensureFloat($value);
		if ($value < 0) {
			throw new TInvalidDataValueException('serviceworkerdefinition_networktimeout_invalid', $value);
		}
		$this->_networkTimeout = $value;
	}

	/**
	 * @return string the page path shown for a navigation without network.
	 *   Defaults to empty.
	 */
	public function getOfflinePage(): string
	{
		return $this->_offlinePage;
	}

	/**
	 * @param string $value the page path shown for a navigation without network;
	 *   the worker stores it on install
	 */
	public function setOfflinePage($value): void
	{
		$this->_offlinePage = trim(TPropertyValue::ensureString($value));
	}

	/**
	 * @return bool whether navigation requests start while the worker starts.
	 *   Defaults to false.
	 */
	public function getNavigationPreload(): bool
	{
		return $this->_navigationPreload;
	}

	/**
	 * @param bool|string $value whether navigation requests start while the worker
	 *   starts; the request carries `Service-Worker-Navigation-Preload: true`
	 */
	public function setNavigationPreload($value): void
	{
		$this->_navigationPreload = TPropertyValue::ensureBoolean($value);
	}

	/**
	 * @return TWebAppManifest the web app manifest of the worker
	 */
	public function getManifest(): TWebAppManifest
	{
		return $this->_manifest ??= new TWebAppManifest();
	}

	/**
	 * @param TWebAppManifest $value the web app manifest of the worker
	 */
	public function setManifest(TWebAppManifest $value): void
	{
		$this->_manifest = $value;
	}

	/**
	 * @return string the JSON manifest file. Defaults to empty.
	 */
	public function getManifestFile(): string
	{
		return $this->getManifest()->getFile();
	}

	/**
	 * @param string $value the JSON manifest file: a path, or a namespace whose
	 *   `.json` file is the manifest
	 */
	public function setManifestFile($value): void
	{
		$this->getManifest()->setFile($value);
	}

	/**
	 * @return bool whether the worker has a web app manifest
	 */
	public function getHasManifest(): bool
	{
		return $this->_manifest !== null && !$this->_manifest->getIsEmpty();
	}

	/**
	 * Returns the manifest members, with `start_url` and `scope` defaulting to the
	 * worker's scope.
	 * @return ?array the manifest members, or null without a manifest
	 */
	public function getManifestData(): ?array
	{
		if (!$this->getHasManifest()) {
			return null;
		}
		$scope = $this->getScope();
		return $this->getManifest()->toArray() + ['start_url' => $scope, 'scope' => $scope];
	}

	/**
	 * @return string the directory of the entry script URL, ending with `/`
	 */
	protected function getEntryDirectory(): string
	{
		$url = (string) $this->getRequest()?->getApplicationUrl();
		$directory = str_replace('\\', '/', dirname($url === '' ? '/' : $url));
		return rtrim($directory, '/') . '/';
	}

	/**
	 * @param mixed $value a list or comma-separated text
	 * @return string[] the non-empty trimmed items
	 */
	protected static function ensureList($value): array
	{
		$items = is_array($value) ? $value : explode(',', TPropertyValue::ensureString($value));
		return array_values(array_filter(array_map(fn ($item) => trim(TPropertyValue::ensureString($item)), $items), fn ($item) => $item !== ''));
	}
}
