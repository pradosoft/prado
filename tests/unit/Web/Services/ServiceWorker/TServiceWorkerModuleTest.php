<?php

namespace Prado\Test\Unit\Web\Services\ServiceWorker;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Security\TAuthManager;
use Prado\Test\Unit\Harness\TTestApplication;
use Prado\Web\Services\ServiceWorker\IServiceWorkerContributor;
use Prado\Web\Services\ServiceWorker\TServiceWorkerDefinition;
use Prado\Web\Services\ServiceWorker\TServiceWorkerModule;
use Prado\Web\Services\ServiceWorker\TServiceWorkerScript;
use Prado\Web\Services\ServiceWorker\TServiceWorkerService;
use Prado\Web\Services\TJsonService;
use Prado\Web\Services\TPageService;
use Prado\Web\TAssetManager;
use Prado\Web\THttpResponse;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\THead;
use Prado\Web\UI\WebControls\TLinkTag;
use Prado\Web\UI\WebControls\TServiceWorker;
use Prado\Web\UI\WebControls\TWebManifest;
use Prado\Xml\TXmlDocument;

/**
 * A response that records the appended headers.
 */
class TServiceWorkerModuleTestResponse extends THttpResponse
{
	public array $appended = [];

	public function appendHeader($header, bool $replace = true, int $response_code = 0): void
	{
		$this->appended[] = $header;
	}
}

class TServiceWorkerModuleTest extends TestCase
{
	private ?TTestApplication $_app = null;
	private array $_server = [];

	protected function setUp(): void
	{
		$this->_server = ['SCRIPT_NAME' => $_SERVER['SCRIPT_NAME'] ?? null, 'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? null];
	}

	protected function tearDown(): void
	{
		$this->_app?->restoreApplication();
		$this->_app = null;
		foreach ($this->_server as $key => $value) {
			if ($value === null) {
				unset($_SERVER[$key]);
			} else {
				$_SERVER[$key] = $value;
			}
		}
	}

	private function createApp(string $requestUri = '/app/index.php'): TTestApplication
	{
		$_SERVER['SCRIPT_NAME'] = '/app/index.php';
		$_SERVER['REQUEST_URI'] = $requestUri;
		$this->_app = new TTestApplication();
		$assets = new TAssetManager();
		$assets->setBaseUrl('/app/assets');
		$this->_app->setAssetManager($assets);
		return $this->_app;
	}

	private function createModule(array $properties = [], $config = null, string $id = 'serviceworker'): TServiceWorkerModule
	{
		if ($this->_app === null) {
			$this->createApp();
		}
		$module = new TServiceWorkerModule();
		$module->setID($id);
		$this->_app->setModule($id, $module);
		$module->setSubProperties($properties);
		$module->init($config);
		return $module;
	}

	private function xml(string $xml): \Prado\Xml\TXmlElement
	{
		$doc = new TXmlDocument();
		$doc->loadFromString($xml);
		return $doc;
	}

	private function createPage(bool $withHead = true, string $pagePath = 'Home'): TPage
	{
		$page = new TPage();
		$page->setPagePath($pagePath);
		if ($withHead) {
			$head = new THead();
			$page->getControls()->add($head);
			$page->setHead($head);
		}
		return $page;
	}

	// Service resolution.

	public function testAutoRegistersTheServiceAsSw()
	{
		$module = $this->createModule();
		$this->assertSame(TServiceWorkerModule::AUTO, $module->getServiceID());
		$this->assertSame('sw', $module->getResolvedServiceID());
		$this->assertSame(
			[TServiceWorkerService::class, ['AutoRegistered' => true, 'ModuleID' => 'serviceworker'], null],
			$this->_app->getRegisteredService('sw')
		);
	}

	public function testAutoAdoptsARegisteredService()
	{
		$this->createApp()->registerService('workers', TServiceWorkerService::class);
		$module = $this->createModule();
		$this->assertSame('workers', $module->getResolvedServiceID());
		$this->assertFalse($this->_app->hasRegisteredService('sw'));
	}

	public function testNamedIdUsesOrRegistersTheService()
	{
		$this->createApp()->registerService('mine', TServiceWorkerService::class, ['ModuleID' => 'serviceworker']);
		$module = $this->createModule(['ServiceID' => 'mine']);
		$this->assertSame('mine', $module->getResolvedServiceID());
		$this->assertSame(['ModuleID' => 'serviceworker'], $this->_app->getRegisteredService('mine')[1]);

		$other = $this->createModule(['ServiceID' => 'theirs'], null, 'other');
		$this->assertSame('theirs', $other->getResolvedServiceID());
		$this->assertSame(['AutoRegistered' => true, 'ModuleID' => 'other'], $this->_app->getRegisteredService('theirs')[1]);
	}

	public function testIdOfAnotherClassThrows()
	{
		$this->createApp()->registerService('sw', TJsonService::class);
		try {
			$this->createModule();
			$this->fail('A sw service of another class was accepted.');
		} catch (TConfigurationException $e) {
			$this->assertSame('serviceworkermodule_service_invalid', $e->getErrorCode());
		}
	}

	public function testServiceIdAutoAliases()
	{
		$module = new TServiceWorkerModule();
		$module->setServiceID('auto');
		$this->assertSame(TServiceWorkerModule::AUTO, $module->getServiceID());
		$module->setServiceID('');
		$this->assertSame(TServiceWorkerModule::AUTO, $module->getServiceID());
		$module->setServiceID('x');
		$this->assertSame('x', $module->getResolvedServiceID());
	}

	// Workers.

	public function testModulePropertiesConfigureTheDefaultWorker()
	{
		$module = $this->createModule([
			'Scope' => '/app/',
			'CacheAssets' => 'true',
			'NetworkFirst' => 'Catalog',
			'NetworkTimeout' => '2',
			'OfflinePage' => 'Offline',
			'NavigationPreload' => 'true',
			'Contributors' => 'a, b',
			'Manifest.Name' => 'Orders',
			'ManifestFile' => '',
		]);
		$worker = $module->getDefinition();
		$this->assertSame($worker, $module->getWorker('default'));
		$this->assertSame(['default'], array_keys($module->getWorkers()));
		$this->assertSame('/app/', $module->getScope());
		$this->assertTrue($module->getCacheAssets());
		$this->assertSame(['Catalog'], $module->getNetworkFirst());
		$this->assertSame(2.0, $module->getNetworkTimeout());
		$this->assertSame('Offline', $module->getOfflinePage());
		$this->assertTrue($module->getNavigationPreload());
		$this->assertSame(['a', 'b'], $module->getContributors());
		$this->assertSame('Orders', $module->getManifest()->getName());
		$this->assertSame('', $module->getManifestFile());
		$this->assertTrue($worker->getCacheAssets());
	}

	public function testXmlWorkersStartFromTheDefaultWorker()
	{
		$module = $this->createModule(
			['CacheAssets' => true, 'OfflinePage' => 'Offline', 'Manifest.Name' => 'Orders'],
			$this->xml('<module><worker name="admin" Scope="/app/admin/" OfflinePage="Admin.Offline" Manifest.Name="Admin" /><worker name="default" NavigationPreload="true" /></module>')
		);
		$this->assertSame(['default', 'admin'], array_keys($module->getWorkers()));
		$admin = $module->getWorker('admin');
		$this->assertSame('/app/admin/', $admin->getScope());
		$this->assertTrue($admin->getCacheAssets());
		$this->assertSame('Admin.Offline', $admin->getOfflinePage());
		$this->assertSame('Admin', $admin->getManifest()->getName());
		$this->assertSame('Orders', $module->getManifest()->getName());
		$this->assertTrue($module->getNavigationPreload());
		$this->assertFalse($admin->getNavigationPreload());
	}

	public function testPhpWorkersList()
	{
		$module = $this->createModule([], ['workers' => [
			['name' => 'admin', 'Scope' => 'admin/'],
			['name' => 'shop', 'properties' => ['Scope' => '/app/shop/', 'CacheAssets' => true]],
		]]);
		$this->assertSame('/app/admin/', $module->getWorker('admin')->getScope());
		$this->assertTrue($module->getWorker('shop')->getCacheAssets());
		$this->assertNull($module->getWorker('missing'));
	}

	public function testWorkerConfigurationErrors()
	{
		$cases = [
			'serviceworkermodule_worker_name_required' => '<module><worker Scope="/x/" /></module>',
			'serviceworkermodule_worker_scope_required' => '<module><worker name="admin" /></module>',
			'serviceworkermodule_worker_duplicate' => '<module><worker name="a" Scope="/a/" /><worker name="a" Scope="/b/" /></module>',
			'serviceworkermodule_scope_duplicate' => '<module><worker name="a" Scope="/app/" /></module>',
		];
		foreach ($cases as $code => $xml) {
			$this->createApp();
			try {
				$this->createModule([], $this->xml($xml));
				$this->fail("$code was not thrown");
			} catch (TConfigurationException $e) {
				$this->assertSame($code, $e->getErrorCode());
			}
			$this->_app->restoreApplication();
			$this->_app = null;
		}
	}

	public function testControllingWorkerIsTheLongestScope()
	{
		$module = $this->createModule([], $this->xml('<module><worker name="admin" Scope="/app/admin/" /></module>'));
		$this->assertSame('default', $module->getControllingWorker('/app/index.php')->getName());
		$this->assertSame('admin', $module->getControllingWorker('/app/admin/users')->getName());
		$this->assertNull($module->getControllingWorker('/other/'));
	}

	public function testWorkerEnabled()
	{
		$module = $this->createModule();
		$worker = $module->getDefinition();
		$this->assertTrue($module->getIsWorkerEnabled($worker));
		$worker->setEnabled(false);
		$this->assertFalse($module->getIsWorkerEnabled($worker));
		$worker->setEnabled(true);
		$module->setEnabled(false);
		$this->assertFalse($module->getIsWorkerEnabled($worker));
	}

	// URLs and scripts.

	public function testServiceUrlsNameTheWorker()
	{
		$module = $this->createModule([], $this->xml('<module><worker name="admin" Scope="/app/admin/" /></module>'));
		$this->assertSame('/app/index.php?sw=worker', $module->getScriptUrl($module->getDefinition()));
		$this->assertSame('/app/index.php?sw=manifest', $module->getManifestUrl($module->getDefinition()));
		$this->assertSame('/app/index.php?sw=worker&w=admin', $module->getScriptUrl($module->getWorker('admin')));
	}

	public function testScriptHasTheStrategiesAndExcludesTheServiceUrls()
	{
		$module = $this->createModule(['CacheAssets' => true, 'NetworkFirst' => 'Catalog, /help/', 'NetworkTimeout' => 1.5, 'OfflinePage' => 'Offline', 'NavigationPreload' => true]);
		$script = $module->createScript($module->getDefinition());
		$this->assertSame(['/app/index.php?sw=worker', '/app/index.php?sw=manifest'], $script->getExclude());
		$this->assertSame(['/app/index.php?page=Offline'], $script->getPrecache());
		$fragment = $script->getFragments()[TServiceWorkerModule::STRATEGIES_FRAGMENT];
		$this->assertSame(0.0, $fragment['priority']);
		$this->assertSame([
			'assets' => '/app/assets/',
			'networkFirst' => ['/app/index.php?page=Catalog', '/help/'],
			'networkTimeout' => 1500,
			'offline' => '/app/index.php?page=Offline',
			'navigationPreload' => true,
		], $fragment['config']);
		$this->assertStringContainsString('function matchesPrefix', $fragment['code']);
	}

	public function testDefaultStrategiesAddNoFetchHandlerConfig()
	{
		$module = $this->createModule();
		$config = $module->createScript($module->getDefinition())->getFragments()['strategies']['config'];
		$this->assertSame(['assets' => '', 'networkFirst' => [], 'networkTimeout' => 3000, 'offline' => '', 'navigationPreload' => false], $config);
	}

	public function testContributorsAreModulesAndAddedObjects()
	{
		$this->createApp();
		$contributor = new TServiceWorkerTestContributor();
		$this->_app->setModule('push', $contributor);
		$module = $this->createModule([], $this->xml('<module><worker name="admin" Scope="/app/admin/" Contributors="other" /></module>'));
		$added = new class () implements IServiceWorkerContributor {
			public int $calls = 0;
			public function contributeServiceWorker(TServiceWorkerScript $script): void
			{
				$this->calls++;
			}
		};
		$module->addContributor($added);
		$named = new TServiceWorkerTestContributor();
		$module->addContributor($named, 'other');

		$contributors = $module->getContributorObjects();
		$this->assertSame($contributor, $contributors['push']);
		$this->assertSame($module, $contributors['serviceworker']);
		$this->assertSame($named, $contributors['other']);
		$this->assertContains($added, $contributors);

		$default = $module->createScript($module->getDefinition());
		$this->assertTrue($default->hasFragment('test-default'));
		$this->assertSame(1, $added->calls);

		$admin = $module->createScript($module->getWorker('admin'));
		$this->assertTrue($admin->hasFragment('strategies'));
		$this->assertSame(['default'], $contributor->workers);
		$this->assertSame(['default', 'admin'], $named->workers);
		$this->assertSame(1, $added->calls);
	}

	public function testRenderScriptOfADisabledWorkerRemovesIt()
	{
		$module = $this->createModule();
		$worker = $module->getDefinition();
		$this->assertStringContainsString('prado.start();', $module->renderScript($worker));
		$worker->setEnabled(false);
		$js = $module->renderScript($worker);
		$this->assertStringContainsString('registration.unregister()', $js);
		$this->assertStringNotContainsString('prado.start();', $js);
	}

	public function testRequestHandlerClaimsNamesButNotReservedOnes()
	{
		$this->createApp();
		$contributor = new TServiceWorkerTestContributor();
		$this->_app->setModule('push', $contributor);
		$module = $this->createModule();
		$this->assertSame($contributor, $module->getRequestHandler('ping'));
		$this->assertNull($module->getRequestHandler('worker'));
		$this->assertNull($module->getRequestHandler('manifest'));
		$this->assertNull($module->getRequestHandler('unknown'));
	}

	// Pages.

	public function testPageWorkerNameOrder()
	{
		$module = $this->createModule(['DefaultWorker' => 'main']);
		$page = $this->createPage();
		$this->assertSame('main', $module->getPageWorkerName($page));
		$service = new TPageService();
		$service->setServiceWorker('service');
		$this->_app->setService($service);
		$this->assertSame('service', $module->getPageWorkerName($page));
		$page->setServiceWorker('page');
		$this->assertSame('page', $module->getPageWorkerName($page));
		$this->assertSame('control', $module->getPageWorkerName($page, 'control'));
		$module->setDefaultWorker('');
		$this->assertSame('default', $module->getDefaultWorker());
	}

	public function testPageWorkerChecksTheScope()
	{
		$this->createApp('/app/admin/users');
		$module = $this->createModule([], $this->xml('<module><worker name="admin" Scope="/app/admin/" /></module>'));
		$page = $this->createPage();
		$this->assertNull($module->getPageWorker($page));
		$this->assertSame('admin', $module->getPageWorker($page, 'admin')->getName());
		$this->assertNull($module->getPageWorker($page, 'missing'));
		$module->getWorker('admin')->setEnabled(false);
		$this->assertNull($module->getPageWorker($page, 'admin'));
	}

	public function testPageWorkerOfTheDefaultScope()
	{
		$module = $this->createModule();
		$this->assertSame('default', $module->getPageWorker($this->createPage())->getName());
	}

	public function testExcludePages()
	{
		$module = $this->createModule(['ExcludePages' => 'Admin, Login']);
		$this->assertSame(['Admin', 'Login'], $module->getExcludePages());
		$this->assertTrue($module->isPageExcluded('Admin'));
		$this->assertTrue($module->isPageExcluded('Admin.Users'));
		$this->assertFalse($module->isPageExcluded('Administrator'));
		$this->assertFalse($module->isPageExcluded('Home'));
	}

	public function testAutoRegisterAttachesToThePageService()
	{
		$module = $this->createModule();
		$this->assertTrue($module->getAutoRegister());
		$this->assertTrue($this->_app->getEventHandlers('onBeginRequest')->contains([$module, 'attachPageService']));
		$service = new TPageService();
		$this->_app->setService($service);
		$module->attachPageService($this->_app, null);
		$this->assertTrue($service->getEventHandlers('onPreRunPage')->contains([$module, 'attachPage']));

		$page = $this->createPage();
		$module->attachPage($service, $page);
		$this->assertTrue($page->getEventHandlers('OnInitComplete')->contains([$module, 'addPageControls']));

		$excluded = $this->createPage(true, 'Login');
		$module->setExcludePages('Login');
		$module->attachPage($service, $excluded);
		$this->assertFalse($excluded->getEventHandlers('OnInitComplete')->contains([$module, 'addPageControls']));
	}

	public function testAutoRegisterOff()
	{
		$module = $this->createModule(['AutoRegister' => false]);
		$this->assertFalse($this->_app->getEventHandlers('onBeginRequest')->contains([$module, 'attachPageService']));
	}

	public function testAddPageControlsAddsTheRegistrationAndTheManifestLink()
	{
		$module = $this->createModule(['Manifest.Name' => 'Orders']);
		$page = $this->createPage();
		$module->addPageControls($page, null);
		$head = $page->getHead();
		$this->assertCount(1, $head->findControlsByType(TServiceWorker::class));
		$this->assertCount(1, $head->findControlsByType(TWebManifest::class));
		$this->assertTrue($module->getPageHasManifestLink($page));

		$module->addPageControls($page, null);
		$this->assertCount(1, $page->findControlsByType(TServiceWorker::class));
		$this->assertCount(1, $page->findControlsByType(TWebManifest::class));
	}

	public function testAddPageControlsWithoutAManifestOrHead()
	{
		$module = $this->createModule();
		$page = $this->createPage();
		$module->addPageControls($page, null);
		$this->assertCount(0, $page->findControlsByType(TWebManifest::class));

		$headless = $this->createPage(false);
		$module->getManifest()->setName('Orders');
		$module->addPageControls($headless, null);
		$this->assertCount(1, $headless->getControls()->toArray());
		$this->assertInstanceOf(TServiceWorker::class, $headless->getControls()->itemAt(0));
	}

	public function testManifestLinkTagCountsAsTheManifestLink()
	{
		$module = $this->createModule(['Manifest.Name' => 'Orders']);
		$page = $this->createPage();
		$link = new TLinkTag();
		$link->setRel('Manifest');
		$link->setHref('/app.webmanifest');
		$page->getHead()->getLinkTags()->add($link);
		$this->assertTrue($module->getPageHasManifestLink($page));
		$module->addPageControls($page, null);
		$this->assertCount(0, $page->findControlsByType(TWebManifest::class));
	}

	public function testClearSiteDataOnLogoutTypes()
	{
		$module = new TServiceWorkerModule();
		$this->assertSame([], $module->getClearSiteDataOnLogout());
		$module->setClearSiteDataOnLogout('"cache", storage, cache');
		$this->assertSame(['cache', 'storage'], $module->getClearSiteDataOnLogout());
		$module->setClearSiteDataOnLogout(['*']);
		$this->assertSame(['*'], $module->getClearSiteDataOnLogout());
		try {
			$module->setClearSiteDataOnLogout('cache, everything');
			$this->fail('An unknown type was accepted.');
		} catch (TInvalidDataValueException $e) {
			$this->assertSame('serviceworkermodule_clearsitedata_invalid', $e->getErrorCode());
		}
	}

	public function testClearSiteDataIsSentOnLogout()
	{
		$this->createApp();
		$response = new TServiceWorkerModuleTestResponse();
		$this->_app->setResponse($response);
		$auth = new TAuthManager();
		$this->_app->setModule('auth', $auth);
		$module = $this->createModule(['ClearSiteDataOnLogout' => 'cache, storage']);
		$this->assertTrue($this->_app->getEventHandlers('onBeginRequest')->contains([$module, 'attachAuthManagers']));
		$module->attachAuthManagers($this->_app, null);
		$this->assertTrue($auth->getEventHandlers('onLogout')->contains([$module, 'sendClearSiteData']));
		$auth->onLogout(null);
		$this->assertSame(['Clear-Site-Data: "cache", "storage"'], $response->appended);
	}

	public function testClearSiteDataOffAttachesNothing()
	{
		$module = $this->createModule();
		$this->assertFalse($this->_app->getEventHandlers('onBeginRequest')->contains([$module, 'attachAuthManagers']));
	}

	public function testWorkerDefinitionsAreCloned()
	{
		$module = $this->createModule(['Manifest.Name' => 'Orders'], $this->xml('<module><worker name="admin" Scope="/app/admin/" /></module>'));
		$admin = $module->getWorker('admin');
		$this->assertInstanceOf(TServiceWorkerDefinition::class, $admin);
		$admin->getManifest()->setName('Admin');
		$this->assertSame('Orders', $module->getManifest()->getName());
	}
}
