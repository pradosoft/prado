<?php

/**
 * TServiceWorkerModule class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\Services\ServiceWorker;

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Prado;
use Prado\Security\TAuthManager;
use Prado\TPropertyValue;
use Prado\Util\Log\TLogger;
use Prado\Web\THttpHeaderName;
use Prado\Web\Services\TPageService;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TServiceWorker;
use Prado\Web\UI\WebControls\TWebManifest;
use Prado\Xml\TXmlElement;

/**
 * TServiceWorkerModule class
 *
 * TServiceWorkerModule is the one configuration point of the application's
 * browser service workers. It links or registers {@see TServiceWorkerService},
 * defines the workers ({@see TServiceWorkerDefinition}), collects the script
 * fragments of the contributors ({@see IServiceWorkerContributor}), and adds
 * the worker registration ({@see TServiceWorker}) and the manifest link
 * ({@see TWebManifest}) to the pages of {@see TPageService}.
 * ```xml
 * <modules>
 *     <module id="serviceworker" class="Prado\Web\Services\ServiceWorker\TServiceWorkerModule"
 *         CacheAssets="true" OfflinePage="Offline" NavigationPreload="true"
 *         NetworkFirst="Catalog, Help" NetworkTimeout="3"
 *         Manifest.Name="Orders" Manifest.ShortName="Orders" Manifest.ThemeColor="#2b5797">
 *         <worker name="admin" Scope="/admin/" OfflinePage="Admin.Offline" NetworkFirst="" />
 *     </module>
 * </modules>
 * ```
 *
 * **The service.** {@see setServiceID ServiceID} names the service, which this
 * module registers when it is missing:
 *
 * | ServiceID | Registered service | Result |
 * |---|---|---|
 * | `Auto` (default) | A TServiceWorkerService under any ID | The module uses it |
 * | `Auto` | None | The module registers one as `sw` ({@see TServiceWorkerService::SERVICE_ID}) |
 * | An ID | A TServiceWorkerService under that ID | The module uses it |
 * | An ID | None | The module registers one under that ID |
 * | An ID, or `sw` for `Auto` | Another class | TConfigurationException |
 *
 * **Workers.** The module's worker properties, such as
 * {@see setCacheAssets CacheAssets}, {@see setScope Scope}, and the
 * `Manifest.*` subproperties, configure the `default` worker. Each `<worker>`
 * element (`workers` list in PHP configuration) configures a named worker,
 * which starts from the `default` worker's values; `<worker name="default">`
 * configures the `default` worker. A named worker needs its own Scope, and two
 * workers with one scope throw TConfigurationException.
 *
 * **A page's worker.** The first name set in this order selects it:
 * {@see TServiceWorker::setWorker TServiceWorker.Worker},
 * {@see TPage::setServiceWorker TPage.ServiceWorker} (also from
 * `<pages ServiceWorker="…">` of a pages directory), the page service's
 * {@see TPageService::setServiceWorker ServiceWorker}, then
 * {@see setDefaultWorker DefaultWorker}. Browsers give a page to the worker
 * with the longest scope that prefixes its URL, so a selected worker that is
 * not that worker registers nothing and logs a warning.
 *
 * **Pages.** With {@see setAutoRegister AutoRegister} (the default), each page
 * of TPageService without a TServiceWorker gets one, and, when the worker has
 * a manifest, a page without a manifest link gets a TWebManifest in its THead.
 * {@see setExcludePages ExcludePages} lists page paths that get neither.
 *
 * **Removal.** {@see setEnabled Enabled} false stops the registration of
 * every worker, and the service then serves workers that clear their caches
 * and unregister themselves. A worker's own Enabled does so for one worker.
 *
 * **Security.** A worker outlives the page that registered it, so a script
 * injected into a page could register a worker that persists. The service is
 * the one source of the worker scripts, and the scripts never echo request
 * input. A Content Security Policy limits workers and manifests to the origin
 * with {@see \Prado\Web\HttpHeaders\TCspDirective::WorkerSrc} `'self'` and
 * {@see \Prado\Web\HttpHeaders\TCspDirective::ManifestSrc} `'self'`.
 * {@see setClearSiteDataOnLogout ClearSiteDataOnLogout} clears the cached data
 * of a user at logout.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 * @method void dyContributeServiceWorker(TServiceWorkerScript $script) raised after the contributors add to a script
 */
class TServiceWorkerModule extends \Prado\TModule implements IServiceWorkerContributor
{
	/**
	 * The name of the default worker.
	 */
	public const DEFAULT_WORKER = 'default';

	/**
	 * The value of {@see setServiceID ServiceID} that finds or registers the service.
	 */
	public const AUTO = 'Auto';

	/**
	 * The fragment of the fetch strategies.
	 */
	public const STRATEGIES_FRAGMENT = 'strategies';

	/**
	 * The file of the fetch strategies fragment.
	 */
	public const STRATEGIES_FILE = 'worker-strategies.js';

	/**
	 * The types of {@see setClearSiteDataOnLogout ClearSiteDataOnLogout}.
	 */
	public const CLEAR_SITE_DATA_TYPES = ['cache', 'storage', 'cookies', 'executionContexts', '*'];

	/** @var string the configured service ID */
	private string $_serviceID = self::AUTO;

	/** @var ?string the ID of the linked service */
	private ?string $_resolvedServiceID = null;

	/** @var bool whether the workers are enabled */
	private bool $_enabled = true;

	/** @var bool whether pages get the registration and the manifest link */
	private bool $_autoRegister = true;

	/** @var string the name of the default worker of pages */
	private string $_defaultWorker = self::DEFAULT_WORKER;

	/** @var string[] page paths without registration */
	private array $_excludePages = [];

	/** @var array<string, TServiceWorkerDefinition> the workers by name */
	private array $_workers = [];

	/** @var array<string, IServiceWorkerContributor> contributors added by code */
	private array $_contributors = [];

	/** @var string[] the Clear-Site-Data types sent on logout */
	private array $_clearSiteDataOnLogout = [];

	/**
	 * Configures the named workers, links the service, and attaches to the page
	 * service.
	 * @param mixed $config the module configuration
	 * @throws TConfigurationException if a worker or the service is not valid.
	 */
	public function init($config)
	{
		$this->loadWorkers($config);
		$this->validateScopes();
		$this->resolveService();
		if ($this->getAutoRegister()) {
			$this->getApplication()->attachEventHandler('onBeginRequest', [$this, 'attachPageService']);
		}
		if ($this->getClearSiteDataOnLogout() !== []) {
			$this->getApplication()->attachEventHandler('onBeginRequest', [$this, 'attachAuthManagers']);
		}
		parent::init($config);
	}

	/**
	 * @return string the service ID, or `Auto`. Defaults to `Auto`.
	 */
	public function getServiceID(): string
	{
		return $this->_serviceID;
	}

	/**
	 * @param string $value the service ID, or `Auto` to use any TServiceWorkerService
	 *   or register one as `sw`
	 */
	public function setServiceID($value): void
	{
		$value = trim(TPropertyValue::ensureString($value));
		$this->_serviceID = ($value === '' || strcasecmp($value, self::AUTO) === 0) ? self::AUTO : $value;
	}

	/**
	 * @return string the ID of the linked service, or `sw` before the module links it
	 */
	public function getResolvedServiceID(): string
	{
		$id = $this->getServiceID();
		return $this->_resolvedServiceID ?? ($id === self::AUTO ? TServiceWorkerService::SERVICE_ID : $id);
	}

	/**
	 * @return bool whether the workers are enabled. Defaults to true.
	 */
	public function getEnabled(): bool
	{
		return $this->_enabled;
	}

	/**
	 * @param bool|string $value whether the workers are enabled; false serves
	 *   workers that clear their caches and unregister themselves
	 */
	public function setEnabled($value): void
	{
		$this->_enabled = TPropertyValue::ensureBoolean($value);
	}

	/**
	 * @return bool whether pages get the worker registration and the manifest link.
	 *   Defaults to true.
	 */
	public function getAutoRegister(): bool
	{
		return $this->_autoRegister;
	}

	/**
	 * @param bool|string $value whether pages get the worker registration and the
	 *   manifest link
	 */
	public function setAutoRegister($value): void
	{
		$this->_autoRegister = TPropertyValue::ensureBoolean($value);
	}

	/**
	 * @return string the worker of pages that select none. Defaults to `default`.
	 */
	public function getDefaultWorker(): string
	{
		return $this->_defaultWorker;
	}

	/**
	 * @param string $value the worker of pages that select none
	 */
	public function setDefaultWorker($value): void
	{
		$value = trim(TPropertyValue::ensureString($value));
		$this->_defaultWorker = $value === '' ? self::DEFAULT_WORKER : $value;
	}

	/**
	 * @return string[] the page paths that get no registration and no manifest
	 *   link. Defaults to empty.
	 */
	public function getExcludePages(): array
	{
		return $this->_excludePages;
	}

	/**
	 * @param string|string[] $value the page paths that get no registration and no
	 *   manifest link; `Admin` also excludes `Admin.Users`
	 */
	public function setExcludePages($value): void
	{
		$items = is_array($value) ? $value : explode(',', TPropertyValue::ensureString($value));
		$this->_excludePages = array_values(array_filter(array_map(fn ($item) => trim(TPropertyValue::ensureString($item)), $items), fn ($item) => $item !== ''));
	}

	/**
	 * @return string[] the `Clear-Site-Data` types sent on logout. Defaults to
	 *   empty, which sends none.
	 */
	public function getClearSiteDataOnLogout(): array
	{
		return $this->_clearSiteDataOnLogout;
	}

	/**
	 * Sets the `Clear-Site-Data` types the logout response of TAuthManager sends,
	 * so a shared computer keeps no data of the previous user:
	 *
	 * | Type | Clears |
	 * |---|---|
	 * | `cache` | The HTTP cache |
	 * | `storage` | Cache Storage, which holds the workers' caches, IndexedDB, local storage, and the worker registrations |
	 * | `cookies` | The cookies of the site |
	 * | `executionContexts` | Reloads the open pages of the origin |
	 * | `*` | All of them |
	 *
	 * @param string|string[] $value the types, as a list or comma-separated text
	 * @throws TInvalidDataValueException if a type is not one of those.
	 */
	public function setClearSiteDataOnLogout($value): void
	{
		$types = [];
		$items = is_array($value) ? $value : explode(',', TPropertyValue::ensureString($value));
		foreach ($items as $item) {
			$type = trim(TPropertyValue::ensureString($item), " \t\n\r\0\x0B\"");
			if ($type === '') {
				continue;
			}
			if (!in_array($type, self::CLEAR_SITE_DATA_TYPES, true)) {
				throw new TInvalidDataValueException('serviceworkermodule_clearsitedata_invalid', $type, implode(', ', self::CLEAR_SITE_DATA_TYPES));
			}
			$types[] = $type;
		}
		$this->_clearSiteDataOnLogout = array_values(array_unique($types));
	}

	/**
	 * Attaches {@see sendClearSiteData()} to the onLogout of each TAuthManager.
	 * It handles TApplication::onBeginRequest.
	 * @param mixed $sender the application
	 * @param mixed $param the event parameter
	 */
	public function attachAuthManagers($sender, $param): void
	{
		$app = $this->getApplication();
		foreach ($app->getModulesByType(TAuthManager::class) as $id => $manager) {
			if (($manager ??= $app->getModule($id)) instanceof TAuthManager) {
				$manager->attachEventHandler('onLogout', [$this, 'sendClearSiteData']);
			}
		}
	}

	/**
	 * Adds the `Clear-Site-Data` header of {@see getClearSiteDataOnLogout ClearSiteDataOnLogout}
	 * to the response. It handles TAuthManager::onLogout.
	 * @param mixed $sender the auth manager
	 * @param mixed $param the user
	 */
	public function sendClearSiteData($sender, $param): void
	{
		if (($types = $this->getClearSiteDataOnLogout()) !== []) {
			$this->getResponse()->appendHeader(THttpHeaderName::ClearSiteData . ': "' . implode('", "', $types) . '"');
		}
	}

	/**
	 * @param string $pagePath a page path
	 * @return bool whether the page is excluded from the registration
	 */
	public function isPageExcluded(string $pagePath): bool
	{
		foreach ($this->getExcludePages() as $excluded) {
			if ($pagePath === $excluded || str_starts_with($pagePath, $excluded . '.')) {
				return true;
			}
		}
		return false;
	}

	/**
	 * @return TServiceWorkerDefinition the `default` worker
	 */
	public function getDefinition(): TServiceWorkerDefinition
	{
		if (!isset($this->_workers[self::DEFAULT_WORKER])) {
			$this->_workers[self::DEFAULT_WORKER] = new TServiceWorkerDefinition();
		}
		return $this->_workers[self::DEFAULT_WORKER];
	}

	/**
	 * @return array<string, TServiceWorkerDefinition> the workers by name, the
	 *   `default` worker first
	 */
	public function getWorkers(): array
	{
		$this->getDefinition();
		return $this->_workers;
	}

	/**
	 * @param string $name the worker name
	 * @return ?TServiceWorkerDefinition the worker, or null
	 */
	public function getWorker(string $name): ?TServiceWorkerDefinition
	{
		return $this->getWorkers()[$name] ?? null;
	}

	/**
	 * Adds a named worker.
	 * @param TServiceWorkerDefinition $worker the worker
	 * @throws TConfigurationException if the name is taken.
	 */
	public function addWorker(TServiceWorkerDefinition $worker): void
	{
		$name = $worker->getName();
		if (isset($this->getWorkers()[$name])) {
			throw new TConfigurationException('serviceworkermodule_worker_duplicate', $name);
		}
		$this->_workers[$name] = $worker;
	}

	/**
	 * Returns the worker that controls a URL path: the worker with the longest
	 * scope that prefixes it.
	 * @param string $path the URL path
	 * @return ?TServiceWorkerDefinition the worker, or null when no scope matches
	 */
	public function getControllingWorker(string $path): ?TServiceWorkerDefinition
	{
		$controlling = null;
		foreach ($this->getWorkers() as $worker) {
			$scope = $worker->getScope();
			if (str_starts_with($path, $scope) && ($controlling === null || strlen($scope) > strlen($controlling->getScope()))) {
				$controlling = $worker;
			}
		}
		return $controlling;
	}

	/**
	 * @param TServiceWorkerDefinition $worker the worker
	 * @return bool whether the module and the worker are enabled
	 */
	public function getIsWorkerEnabled(TServiceWorkerDefinition $worker): bool
	{
		return $this->getEnabled() && $worker->getEnabled();
	}

	// Worker properties of the `default` worker.

	/**
	 * @return string the scope of the `default` worker
	 */
	public function getScope(): string
	{
		return $this->getDefinition()->getScope();
	}

	/**
	 * @param string $value the scope of the `default` worker; empty is the
	 *   directory of the entry script
	 */
	public function setScope($value): void
	{
		$this->getDefinition()->setScope($value);
	}

	/**
	 * @return string[] the contributors admitted by the `default` worker
	 */
	public function getContributors(): array
	{
		return $this->getDefinition()->getContributors();
	}

	/**
	 * @param string|string[] $value the module IDs of the contributors admitted by
	 *   the `default` worker; empty admits every contributor
	 */
	public function setContributors($value): void
	{
		$this->getDefinition()->setContributors($value);
	}

	/**
	 * @return bool whether the `default` worker caches published assets
	 */
	public function getCacheAssets(): bool
	{
		return $this->getDefinition()->getCacheAssets();
	}

	/**
	 * @param bool|string $value whether the `default` worker answers published
	 *   assets cache first
	 */
	public function setCacheAssets($value): void
	{
		$this->getDefinition()->setCacheAssets($value);
	}

	/**
	 * @return string[] the network-first paths of the `default` worker
	 */
	public function getNetworkFirst(): array
	{
		return $this->getDefinition()->getNetworkFirst();
	}

	/**
	 * @param string|string[] $value the page paths and `/` URL path prefixes the
	 *   `default` worker answers network first
	 */
	public function setNetworkFirst($value): void
	{
		$this->getDefinition()->setNetworkFirst($value);
	}

	/**
	 * @return float the network-first timeout of the `default` worker, in seconds
	 */
	public function getNetworkTimeout(): float
	{
		return $this->getDefinition()->getNetworkTimeout();
	}

	/**
	 * @param float|string $value the seconds before a network-first request of the
	 *   `default` worker falls back to the cache
	 */
	public function setNetworkTimeout($value): void
	{
		$this->getDefinition()->setNetworkTimeout($value);
	}

	/**
	 * @return string the offline page of the `default` worker
	 */
	public function getOfflinePage(): string
	{
		return $this->getDefinition()->getOfflinePage();
	}

	/**
	 * @param string $value the page path the `default` worker shows for a
	 *   navigation without network
	 */
	public function setOfflinePage($value): void
	{
		$this->getDefinition()->setOfflinePage($value);
	}

	/**
	 * @return bool whether the `default` worker preloads navigations
	 */
	public function getNavigationPreload(): bool
	{
		return $this->getDefinition()->getNavigationPreload();
	}

	/**
	 * @param bool|string $value whether the `default` worker preloads navigations
	 */
	public function setNavigationPreload($value): void
	{
		$this->getDefinition()->setNavigationPreload($value);
	}

	/**
	 * @return TWebAppManifest the web app manifest of the `default` worker
	 */
	public function getManifest(): TWebAppManifest
	{
		return $this->getDefinition()->getManifest();
	}

	/**
	 * @return string the JSON manifest file of the `default` worker
	 */
	public function getManifestFile(): string
	{
		return $this->getDefinition()->getManifestFile();
	}

	/**
	 * @param string $value the JSON manifest file of the `default` worker
	 */
	public function setManifestFile($value): void
	{
		$this->getDefinition()->setManifestFile($value);
	}

	// Contributors and scripts.

	/**
	 * Adds a contributor that is not an application module.
	 * @param IServiceWorkerContributor $contributor the contributor
	 * @param ?string $id the ID a worker's Contributors names it by; null admits it
	 *   only to workers that admit every contributor
	 */
	public function addContributor(IServiceWorkerContributor $contributor, ?string $id = null): void
	{
		$this->_contributors[$id ?? ('_contributor' . spl_object_id($contributor))] = $contributor;
	}

	/**
	 * Returns the contributors: the application modules that implement
	 * IServiceWorkerContributor, this module among them, then those added by code.
	 * @return array<string, IServiceWorkerContributor> the contributors by ID
	 */
	public function getContributorObjects(): array
	{
		$app = $this->getApplication();
		$contributors = [];
		foreach ($app->getModulesByType(IServiceWorkerContributor::class) as $id => $module) {
			if (($module ??= $app->getModule($id)) instanceof IServiceWorkerContributor) {
				$contributors[$id] = $module;
			}
		}
		return $contributors + $this->_contributors;
	}

	/**
	 * Returns the contributor that answers a request name.
	 * @param string $name the request name
	 * @return ?IServiceWorkerRequestHandler the handler, or null
	 */
	public function getRequestHandler(string $name): ?IServiceWorkerRequestHandler
	{
		if (in_array($name, TServiceWorkerService::RESERVED_REQUESTS, true)) {
			return null;
		}
		foreach ($this->getContributorObjects() as $contributor) {
			if ($contributor instanceof IServiceWorkerRequestHandler && in_array($name, $contributor->getServiceWorkerRequestNames(), true)) {
				return $contributor;
			}
		}
		return null;
	}

	/**
	 * Builds the script of a worker from the admitted contributors.
	 * @param TServiceWorkerDefinition $worker the worker
	 * @return TServiceWorkerScript the script
	 */
	public function createScript(TServiceWorkerDefinition $worker): TServiceWorkerScript
	{
		$script = new TServiceWorkerScript($worker);
		$script->addExclude([$this->getScriptUrl($worker), $this->getManifestUrl($worker)]);
		foreach ($this->getContributorObjects() as $id => $contributor) {
			if ($contributor === $this || $worker->admitsContributor($id)) {
				$contributor->contributeServiceWorker($script);
			}
		}
		$this->dyContributeServiceWorker($script);
		return $script;
	}

	/**
	 * Renders the script of a worker; a disabled worker renders the script that
	 * removes it.
	 * @param TServiceWorkerDefinition $worker the worker
	 * @return string the JavaScript of the worker
	 */
	public function renderScript(TServiceWorkerDefinition $worker): string
	{
		if (!$this->getIsWorkerEnabled($worker)) {
			return TServiceWorkerScript::renderUnregister($worker);
		}
		return $this->createScript($worker)->render();
	}

	/**
	 * Adds the fetch strategies of the worker.
	 * @param TServiceWorkerScript $script the script being built
	 */
	public function contributeServiceWorker(TServiceWorkerScript $script): void
	{
		$worker = $script->getWorker();
		$config = [
			'assets' => $worker->getCacheAssets() ? $this->getAssetBaseUrl() : '',
			'networkFirst' => array_map(fn ($path) => $this->resolvePathUrl($path), $worker->getNetworkFirst()),
			'networkTimeout' => (int) round($worker->getNetworkTimeout() * 1000),
			'offline' => '',
			'navigationPreload' => $worker->getNavigationPreload(),
		];
		if (($offlinePage = $worker->getOfflinePage()) !== '') {
			$config['offline'] = $this->resolvePathUrl($offlinePage);
			$script->addPrecache($config['offline']);
		}
		$script->addFragmentFile(self::STRATEGIES_FRAGMENT, TServiceWorkerScript::getSourcePath(self::STRATEGIES_FILE), 0.0, $config);
	}

	/**
	 * @param TServiceWorkerDefinition $worker the worker
	 * @return string the URL of the worker's script
	 */
	public function getScriptUrl(TServiceWorkerDefinition $worker): string
	{
		return $this->constructServiceUrl(TServiceWorkerService::REQUEST_WORKER, $worker);
	}

	/**
	 * @param TServiceWorkerDefinition $worker the worker
	 * @param bool $encodeAmpersand whether `&` is `&amp;`, for an HTML attribute
	 * @return string the URL of the worker's manifest
	 */
	public function getManifestUrl(TServiceWorkerDefinition $worker, bool $encodeAmpersand = false): string
	{
		return $this->constructServiceUrl(TServiceWorkerService::REQUEST_MANIFEST, $worker, $encodeAmpersand);
	}

	/**
	 * Constructs a URL of the service for a worker.
	 * @param string $request the request name
	 * @param TServiceWorkerDefinition $worker the worker
	 * @param bool $encodeAmpersand whether `&` is `&amp;`, for an HTML attribute
	 * @return string the URL
	 */
	public function constructServiceUrl(string $request, TServiceWorkerDefinition $worker, bool $encodeAmpersand = false): string
	{
		$name = $worker->getName();
		$getItems = $name === self::DEFAULT_WORKER ? null : [TServiceWorkerService::WORKER_PARAMETER => $name];
		return $this->getRequest()->constructUrl($this->getResolvedServiceID(), $request, $getItems, $encodeAmpersand);
	}

	// Pages.

	/**
	 * Returns the worker name a page selects.
	 * @param TPage $page the page
	 * @param string $worker the name a TServiceWorker or TWebManifest sets; empty for none
	 * @return string the worker name
	 */
	public function getPageWorkerName(TPage $page, string $worker = ''): string
	{
		if ($worker !== '') {
			return $worker;
		}
		if (($name = $page->getServiceWorker()) !== '') {
			return $name;
		}
		$service = $this->getService();
		if ($service instanceof TPageService && ($name = $service->getServiceWorker()) !== '') {
			return $name;
		}
		return $this->getDefaultWorker();
	}

	/**
	 * Returns the worker of a page, when it is enabled and controls the page's URL.
	 * Otherwise it logs a warning for a missing worker or a scope that does not
	 * control the URL.
	 * @param TPage $page the page
	 * @param string $worker the name a TServiceWorker or TWebManifest sets; empty for none
	 * @return ?TServiceWorkerDefinition the worker, or null
	 */
	public function getPageWorker(TPage $page, string $worker = ''): ?TServiceWorkerDefinition
	{
		$name = $this->getPageWorkerName($page, $worker);
		if (($definition = $this->getWorker($name)) === null) {
			Prado::log("Service worker '$name' of page '" . $page->getPagePath() . "' is not defined.", TLogger::WARNING, static::class);
			return null;
		}
		if (!$this->getIsWorkerEnabled($definition)) {
			return null;
		}
		$path = (string) parse_url((string) $this->getRequest()->getRequestUri(), PHP_URL_PATH);
		if ($this->getControllingWorker($path) !== $definition) {
			Prado::log("Service worker '$name' with scope '" . $definition->getScope() . "' does not control '$path' of page '" . $page->getPagePath() . "'; the page registers no worker.", TLogger::WARNING, static::class);
			return null;
		}
		return $definition;
	}

	/**
	 * Attaches {@see attachPage()} to TPageService::onPreRunPage. It handles
	 * TApplication::onBeginRequest.
	 * @param mixed $sender the application
	 * @param mixed $param the event parameter
	 */
	public function attachPageService($sender, $param): void
	{
		$service = $this->getService();
		if ($service instanceof TPageService) {
			$service->attachEventHandler('onPreRunPage', [$this, 'attachPage']);
		}
	}

	/**
	 * Attaches {@see addPageControls()} to the page's OnInitComplete. It handles
	 * TPageService::onPreRunPage.
	 * @param mixed $sender the page service
	 * @param mixed $page the page
	 */
	public function attachPage($sender, $page): void
	{
		if ($page instanceof TPage && $this->getEnabled() && !$this->isPageExcluded($page->getPagePath())) {
			$page->attachEventHandler('OnInitComplete', [$this, 'addPageControls']);
		}
	}

	/**
	 * Adds a TServiceWorker to a page without one, and a TWebManifest to the head
	 * of a page without a manifest link when the page's worker has a manifest.
	 * It handles the page's OnInitComplete.
	 * @param mixed $page the page
	 * @param mixed $param the event parameter
	 */
	public function addPageControls($page, $param): void
	{
		if (!$page instanceof TPage) {
			return;
		}
		$head = $page->getHead();
		if (empty($page->findControlsByType(TServiceWorker::class, false))) {
			$registration = new TServiceWorker();
			($head ?? $page)->getControls()->add($registration);
		}
		if ($head !== null && !$this->getPageHasManifestLink($page)) {
			$worker = $this->getWorker($this->getPageWorkerName($page));
			if ($worker !== null && $worker->getHasManifest()) {
				$head->getControls()->add(new TWebManifest());
			}
		}
	}

	/**
	 * @param TPage $page the page
	 * @return bool whether the page has a TWebManifest, or a TLinkTag with
	 *   `Rel="manifest"` in its head
	 */
	public function getPageHasManifestLink(TPage $page): bool
	{
		if (!empty($page->findControlsByType(TWebManifest::class, false))) {
			return true;
		}
		if (($head = $page->getHead()) !== null) {
			foreach ($head->getLinkTags() as $linkTag) {
				if (in_array('manifest', explode(' ', strtolower($linkTag->getRel())), true)) {
					return true;
				}
			}
		}
		return false;
	}

	// Configuration.

	/**
	 * Configures the workers of the `<worker>` elements or the `workers` list.
	 * @param mixed $config the module configuration
	 * @throws TConfigurationException if a worker has no name or no scope, or a
	 *   name is taken.
	 */
	protected function loadWorkers($config): void
	{
		$elements = [];
		if ($config instanceof TXmlElement) {
			foreach ($config->getElementsByTagName('worker') as $element) {
				$elements[] = $element->getAttributes()->toArray();
			}
		} elseif (is_array($config) && isset($config['workers']) && is_array($config['workers'])) {
			foreach ($config['workers'] as $worker) {
				$properties = (array) $worker;
				if (isset($properties['properties']) && is_array($properties['properties'])) {
					$properties = $properties['properties'] + array_diff_key($properties, ['properties' => true]);
				}
				$elements[] = $properties;
			}
		}
		$default = $this->getDefinition();
		foreach ($elements as $properties) {
			$name = $this->removeProperty($properties, 'name');
			if ($name === null || trim((string) $name) === '') {
				throw new TConfigurationException('serviceworkermodule_worker_name_required');
			}
			if ($name === self::DEFAULT_WORKER) {
				$default->setSubProperties($properties);
				continue;
			}
			$worker = clone $default;
			$worker->setName($name);
			$worker->setScope('');
			$worker->setSubProperties($properties);
			if ($this->findProperty($properties, 'scope') === null) {
				throw new TConfigurationException('serviceworkermodule_worker_scope_required', $name);
			}
			$this->addWorker($worker);
		}
	}

	/**
	 * Checks that no two workers have one scope.
	 * @throws TConfigurationException if two workers have one scope.
	 */
	protected function validateScopes(): void
	{
		$scopes = [];
		foreach ($this->getWorkers() as $name => $worker) {
			$scope = $worker->getScope();
			if (isset($scopes[$scope])) {
				throw new TConfigurationException('serviceworkermodule_scope_duplicate', $scopes[$scope], $name, $scope);
			}
			$scopes[$scope] = $name;
		}
	}

	/**
	 * Links the service of {@see getServiceID ServiceID}, registering it when it
	 * is missing.
	 * @throws TConfigurationException if the ID names a service of another class.
	 */
	protected function resolveService(): void
	{
		$app = $this->getApplication();
		$id = $this->getServiceID();
		if ($id === self::AUTO) {
			if (($found = $app->getRegisteredServiceByClass(TServiceWorkerService::class)) !== null) {
				$this->_resolvedServiceID = $found;
				return;
			}
			$id = TServiceWorkerService::SERVICE_ID;
		}
		if (($entry = $app->getRegisteredService($id)) === null) {
			$properties = ['AutoRegistered' => true];
			if (($moduleID = $this->getID()) !== null && $moduleID !== '') {
				$properties['ModuleID'] = $moduleID;
			}
			$app->registerService($id, TServiceWorkerService::class, $properties);
		} elseif (!is_a($entry[0], TServiceWorkerService::class, true)) {
			throw new TConfigurationException('serviceworkermodule_service_invalid', $id, $entry[0]);
		}
		$this->_resolvedServiceID = $id;
	}

	/**
	 * @return string the base URL of published assets, ending with `/`
	 */
	protected function getAssetBaseUrl(): string
	{
		return rtrim((string) $this->getApplication()->getAssetManager()->getBaseUrl(), '/') . '/';
	}

	/**
	 * Resolves a page path, or a `/` URL path kept as is, to a URL path and query.
	 * @param string $path a page path, such as `Catalog`, or a URL path, such as `/help/`
	 * @return string the URL
	 */
	protected function resolvePathUrl(string $path): string
	{
		if (str_starts_with($path, '/')) {
			return $path;
		}
		return $this->getRequest()->constructUrl($this->getApplication()->getPageServiceID(), $path, null, false);
	}

	/**
	 * Finds a property, case-insensitively.
	 * @param array $properties the properties
	 * @param string $name the lowercase property name
	 * @return ?string the key of the property, or null
	 */
	private function findProperty(array $properties, string $name): ?string
	{
		foreach (array_keys($properties) as $key) {
			if (strtolower((string) $key) === $name) {
				return (string) $key;
			}
		}
		return null;
	}

	/**
	 * Removes a property, case-insensitively.
	 * @param array $properties the properties
	 * @param string $name the lowercase property name
	 * @return mixed the value, or null
	 */
	private function removeProperty(array &$properties, string $name): mixed
	{
		if (($key = $this->findProperty($properties, $name)) === null) {
			return null;
		}
		$value = $properties[$key];
		unset($properties[$key]);
		return $value;
	}
}
