<?php

/**
 * TPageServiceTest
 *
 * Unit tests for {@see \Prado\Web\Services\TPageService}.
 *
 * The tests run against the fixture application trees beside this file:
 * `PageServiceApp/` holds a `Pages` directory with template-only, class-only
 * and template-plus-class pages; `PageServiceFallbackApp/` holds only the
 * lowercase `pages` fallback; `PageServiceEmptyApp/` holds neither.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 */

namespace Prado\Test\Unit\Web\Services;

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\THttpException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Prado;
use Prado\TApplication;
use Prado\Test\Unit\Harness\Traits\TNestedPathTrait;
use Prado\Test\Unit\Harness\TTestApplication;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\Services\TPageConfiguration;
use Prado\Web\Services\TPageService;
use Prado\Web\UI\TControl;
use Prado\Web\UI\TPage;
use Prado\Web\UI\TTemplateManager;
use Prado\Web\UI\TThemeManager;
use Prado\Xml\TXmlDocument;

/**
 * A page carrying a nested 'Cfg' property, with an inert run() so runPage()
 * exercises only the property application.
 */
class TNestedPathPage extends TPage
{
	use TNestedPathTrait;

	public bool $ran = false;

	public function run($writer)
	{
		$this->ran = true;
	}
}

/**
 * A page service that records the page it built and skips running it, so run()
 * is observable without the page lifecycle.
 */
class TRecordingPageService extends TPageService
{
	public $builtPage = null;
	public $ranProperties = null;

	protected function runPage($page, $properties)
	{
		$this->builtPage = $page;
		$this->ranProperties = $properties;
	}
}

/**
 * A plain in-memory cache. TMemoryCache registers itself as the application's
 * primary cache module, which a second test would then collide with.
 */
class TPageServiceArrayCache implements \Prado\Caching\ICache
{
	public array $items = [];

	public function get($id)
	{
		return $this->items[$id] ?? false;
	}
	public function set($id, $value, $expire = 0, $dependency = null)
	{
		$this->items[$id] = $value;
		return true;
	}
	public function add($id, $value, $expire = 0, $dependency = null)
	{
		if (array_key_exists($id, $this->items)) {
			return false;
		}
		return $this->set($id, $value);
	}
	public function delete($id)
	{
		unset($this->items[$id]);
		return true;
	}
	public function flush()
	{
		$this->items = [];
		return true;
	}
	public static function getIsAvailable(): bool
	{
		return true;
	}
}

/**
 * A page configuration whose external configurations the test dictates, so
 * applyConfiguration()'s include branches are reachable without fixture files.
 */
class TPageServiceStubConfiguration extends TPageConfiguration
{
	public array $externals = [];
	public array $appConfigs = [];

	public function __construct()
	{
		parent::__construct('Home');
	}

	public function getExternalConfigurations()
	{
		return $this->externals;
	}

	public function getApplicationConfigurations()
	{
		return $this->appConfigs;
	}
}

class TPageServiceTest extends \PHPUnit\Framework\TestCase
{
	private ?TTestApplication $_app = null;
	private string $_fixtureRoot;
	/** @var string[] the temporary directories a test made. */
	private array $_tempDirs = [];

	protected function setUp(): void
	{
		$this->_fixtureRoot = __DIR__;
	}

	protected function tearDown(): void
	{
		$this->_app?->restoreApplication();
		$this->_app = null;
		foreach ($this->_tempDirs as $dir) {
			foreach (scandir($dir) as $entry) {
				if ($entry !== '.' && $entry !== '..') {
					$path = $dir . DIRECTORY_SEPARATOR . $entry;
					is_dir($path) ? rmdir($path) : unlink($path);
				}
			}
			rmdir($dir);
		}
		$this->_tempDirs = [];
	}

	/**
	 * Builds a path below the fixture root with the platform's separator. Windows
	 * normalizes a stored alias to backslashes, so a path compared against one is
	 * built the same way.
	 * @param string ...$segments the path segments below the fixture root.
	 */
	private function fixturePath(string ...$segments): string
	{
		return $this->_fixtureRoot . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $segments);
	}

	/**
	 * Installs a fixture application, restoring any application a previous call
	 * installed. Each TTestApplication snapshots the global state it finds, so
	 * building a second one without restoring the first strands the first one's
	 * changes in the global state.
	 * @param string $appDir the fixture application directory name.
	 */
	private function application(string $appDir): TTestApplication
	{
		$this->_app?->restoreApplication();
		return $this->_app = new TTestApplication($this->_fixtureRoot . DIRECTORY_SEPARATOR . $appDir);
	}

	/**
	 * Installs a fixture application and returns a service bound to it.
	 * @param string $appDir the fixture application directory name.
	 * @param string $pagePath the requested page path, '' for none.
	 * @param string $serviceClass the service class to build.
	 */
	private function service(string $appDir = 'PageServiceApp', string $pagePath = '', string $serviceClass = TPageService::class): TPageService
	{
		$this->application($appDir);
		$this->_app->getRequest()->setServiceParameter($pagePath);
		$service = new $serviceClass();
		$service->setID('page');
		// the page service is the running service; TTemplateControl::loadTemplate()
		// reaches the template manager through it
		$this->_app->setService($service);
		return $service;
	}

	/**
	 * Runs the given callable with any output buffer it opens discarded after.
	 * @param callable $run the work to run.
	 */
	private function withResponse(callable $run): void
	{
		$obLevel = ob_get_level();
		ob_start();
		try {
			$run();
		} finally {
			while (ob_get_level() > $obLevel) {
				ob_end_clean();
			}
		}
	}

	// -------------------------------------------------------------------------
	// BasePath
	// -------------------------------------------------------------------------

	/**
	 * The base path defaults to the 'Pages' directory under the application base path.
	 */
	public function testBasePathDefaultsToThePagesDirectory()
	{
		$service = $this->service();
		$this->assertSame(realpath($this->fixturePath('PageServiceApp', 'Pages')), $service->getBasePath());
	}

	/**
	 * The base path falls back to the lowercase 'pages' directory.
	 */
	public function testBasePathFallsBackToTheLowercasePagesDirectory()
	{
		$service = $this->service('PageServiceFallbackApp');
		// A case-insensitive filesystem resolves the 'Pages' default to this same
		// directory, so the comparison ignores case.
		$this->assertEqualsIgnoringCase(
			realpath($this->fixturePath('PageServiceFallbackApp', 'pages')),
			$service->getBasePath()
		);
	}

	/**
	 * An application with neither directory is refused.
	 */
	public function testBasePathWithNeitherDirectoryIsRefused()
	{
		$service = $this->service('PageServiceEmptyApp');
		$this->expectException(TConfigurationException::class);
		$service->getBasePath();
	}

	/**
	 * The base path is resolved once and reused.
	 */
	public function testBasePathIsResolvedOnce()
	{
		$service = $this->service();
		$this->assertSame($service->getBasePath(), $service->getBasePath());
	}

	/**
	 * setBasePath() accepts a namespace naming a directory.
	 */
	public function testSetBasePathAcceptsANamespace()
	{
		$service = $this->service();
		Prado::setPathOfAlias('PageFixtures', $this->fixturePath('PageServiceApp'));
		$service->setBasePath('PageFixtures.Pages');
		$this->assertSame(realpath($this->fixturePath('PageServiceApp', 'Pages')), $service->getBasePath());
	}

	/**
	 * setBasePath() refuses a namespace that names no directory.
	 */
	public function testSetBasePathRefusesAnInvalidNamespace()
	{
		$service = $this->service();
		$this->expectException(TConfigurationException::class);
		$service->setBasePath('No.Such.Namespace');
	}

	/**
	 * setBasePath() refuses a namespace that names a file.
	 */
	public function testSetBasePathRefusesANamespaceThatIsNotADirectory()
	{
		$service = $this->service();
		Prado::setPathOfAlias('PageFixtures', $this->fixturePath('PageServiceApp'));
		$this->expectException(TConfigurationException::class);
		$service->setBasePath('PageFixtures.external');
	}

	/**
	 * Builds an application base directory in the temporary directory holding
	 * the given entries, each a file or a directory.
	 * @param array<string, bool> $entries name => whether it is a directory.
	 * @return ?string the directory, null when the filesystem cannot hold the entries.
	 */
	private function baseDirectory(array $entries): ?string
	{
		$dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'prado-pageservice-' . bin2hex(random_bytes(6));
		mkdir($dir);
		$this->_tempDirs[] = $dir;
		foreach ($entries as $name => $isDirectory) {
			$path = $dir . DIRECTORY_SEPARATOR . $name;
			if (file_exists($path)) {
				return null;
			}
			$isDirectory ? mkdir($path) : touch($path);
		}
		return $dir;
	}

	/**
	 * Which base path the service takes for an application whose 'Pages' and
	 * 'pages' entries are files or directories. A case-insensitive filesystem
	 * reads both names as one entry.
	 */
	public static function baseEntriesProvider(): array
	{
		return [
			'a Pages file' => [['Pages' => false], null],
			'a pages file' => [['pages' => false], null],
			'a Pages file and a pages directory' => [['Pages' => false, 'pages' => true], 'pages'],
			'a pages directory' => [['pages' => true], 'pages'],
		];
	}

	/**
	 * The base path falls back to 'pages' when 'Pages' is not a directory, and
	 * is refused when neither is.
	 * @dataProvider baseEntriesProvider
	 * @param array<string, bool> $entries name => whether it is a directory.
	 * @param ?string $expected the directory taken, null for none.
	 */
	public function testBasePathMustBeADirectory(array $entries, ?string $expected)
	{
		if (($dir = $this->baseDirectory($entries)) === null) {
			$this->markTestSkipped('the filesystem is case-insensitive');
		}
		$this->_app?->restoreApplication();
		$this->_app = new TTestApplication($dir);
		$service = new TPageService();
		if ($expected === null) {
			$this->expectException(TConfigurationException::class);
		}
		// a case-insensitive filesystem may name the directory 'Pages'
		$this->assertEqualsIgnoringCase(realpath($dir . DIRECTORY_SEPARATOR . $expected), $service->getBasePath());
	}

	// -------------------------------------------------------------------------
	// Simple properties
	// -------------------------------------------------------------------------

	/**
	 * The default page defaults to 'Home' and is settable before initialization.
	 */
	public function testDefaultPage()
	{
		$service = $this->service();
		$this->assertSame('Home', $service->getDefaultPage());
		$service->setDefaultPage('Other');
		$this->assertSame('Other', $service->getDefaultPage());
	}

	/**
	 * The service worker of the service's pages defaults to empty and is trimmed.
	 */
	public function testServiceWorker()
	{
		$service = $this->service();
		$this->assertSame('', $service->getServiceWorker());
		$service->setServiceWorker(' admin ');
		$this->assertSame('admin', $service->getServiceWorker());
	}

	/**
	 * The default page cannot change once the service is initialized.
	 */
	public function testSetDefaultPageAfterInitializationIsRefused()
	{
		$service = $this->service('PageServiceApp', 'Home');
		$service->init(null);
		$this->expectException(TInvalidOperationException::class);
		$service->setDefaultPage('Other');
	}

	/**
	 * The base path cannot change once the service is initialized.
	 */
	public function testSetBasePathAfterInitializationIsRefused()
	{
		$service = $this->service('PageServiceApp', 'Home');
		$service->init(null);
		$this->expectException(TInvalidOperationException::class);
		$service->setBasePath('PageFixtures.Pages');
	}

	/**
	 * The base page class defaults to TPage and is settable.
	 */
	public function testBasePageClass()
	{
		$service = $this->service();
		$this->assertSame(TPage::class, $service->getBasePageClass());
		$service->setBasePageClass(TNestedPathPage::class);
		$this->assertSame(TNestedPathPage::class, $service->getBasePageClass());
	}

	/**
	 * The client script manager class defaults to TClientScriptManager and is settable.
	 */
	public function testClientScriptManagerClass()
	{
		$service = $this->service();
		$this->assertSame(\Prado\Web\UI\TClientScriptManager::class, $service->getClientScriptManagerClass());
		$service->setClientScriptManagerClass('My.Manager');
		$this->assertSame('My.Manager', $service->getClientScriptManagerClass());
	}

	/**
	 * The requested page is null until the service runs.
	 */
	public function testRequestedPageIsNullBeforeTheServiceRuns()
	{
		$service = $this->service();
		$this->assertNull($service->getRequestedPage());
	}

	// -------------------------------------------------------------------------
	// Template and theme managers — deprecated application delegators
	// -------------------------------------------------------------------------

	/**
	 * The template manager accessors delegate to the application.
	 */
	public function testTemplateManagerDelegatesToTheApplication()
	{
		$service = $this->service();
		$this->assertSame($this->_app->getTemplateManager(), $service->getTemplateManager());

		$service->setTemplateManager($manager = new TTemplateManager());
		$this->assertSame($manager, $this->_app->getTemplateManager());
		$this->assertSame($manager, $service->getTemplateManager());
	}

	/**
	 * The theme manager accessors delegate to the application.
	 */
	public function testThemeManagerDelegatesToTheApplication()
	{
		$service = $this->service();
		$this->assertSame($this->_app->getThemeManager(), $service->getThemeManager());

		$service->setThemeManager($manager = new TThemeManager());
		$this->assertSame($manager, $this->_app->getThemeManager());
		$this->assertSame($manager, $service->getThemeManager());
	}

	// -------------------------------------------------------------------------
	// Requested page path
	// -------------------------------------------------------------------------

	/**
	 * The requested page path comes from the request's service parameter, with
	 * slashes normalized to dots.
	 */
	public function testRequestedPagePathReadsTheServiceParameter()
	{
		$service = $this->service('PageServiceApp', 'Sub/Nested');
		$this->assertSame('Sub.Nested', $service->getRequestedPagePath());
	}

	/**
	 * A backslash separator normalizes the same way.
	 */
	public function testRequestedPagePathNormalizesBackslashes()
	{
		$service = $this->service('PageServiceApp', 'Sub\\Nested');
		$this->assertSame('Sub.Nested', $service->getRequestedPagePath());
	}

	/**
	 * An absent service parameter falls back to the default page.
	 */
	public function testRequestedPagePathFallsBackToTheDefaultPage()
	{
		$service = $this->service();
		$service->setDefaultPage('Home');
		$this->assertSame('Home', $service->getRequestedPagePath());
	}

	/**
	 * The requested page path is resolved once and reused.
	 */
	public function testRequestedPagePathIsResolvedOnce()
	{
		$service = $this->service('PageServiceApp', 'Home');
		$this->assertSame('Home', $service->getRequestedPagePath());
		$this->_app->getRequest()->setServiceParameter('Other');
		$this->assertSame('Home', $service->getRequestedPagePath());
	}

	/**
	 * An empty page path, with an empty default page, is a 404.
	 */
	public function testAnEmptyRequestedPagePathIsNotFound()
	{
		$service = $this->service();
		$service->setDefaultPage('');
		$this->expectException(THttpException::class);
		$service->getRequestedPagePath();
	}

	/**
	 * determineRequestedPagePath() returns the raw service parameter.
	 */
	public function testDetermineRequestedPagePathReadsTheServiceParameter()
	{
		$service = $this->service('PageServiceApp', 'Sub/Nested');
		$this->assertSame('Sub/Nested', PradoUnit::invoke($service, 'determineRequestedPagePath'));
	}

	// -------------------------------------------------------------------------
	// URLs
	// -------------------------------------------------------------------------

	/**
	 * constructUrl() delegates to the request, keyed by the service ID.
	 */
	public function testConstructUrlDelegatesToTheRequest()
	{
		$service = $this->service();
		$this->assertSame(
			$this->_app->getRequest()->constructUrl('page', 'Home', ['a' => 1], true, true),
			$service->constructUrl('Home', ['a' => 1])
		);
	}

	/**
	 * The default page URL is the URL of the default page.
	 */
	public function testDefaultPageUrlIsTheUrlOfTheDefaultPage()
	{
		$service = $this->service();
		$service->setDefaultPage('Home');
		$this->assertSame($service->constructUrl('Home'), $service->getDefaultPageUrl());
	}

	// -------------------------------------------------------------------------
	// init() and configuration
	// -------------------------------------------------------------------------

	/**
	 * init() with no configuration marks the service initialized.
	 */
	public function testInitWithoutAConfigurationIsAccepted()
	{
		$service = $this->service('PageServiceApp', 'Home');
		$service->init(null);
		$this->assertTrue($service->getIsInitialized());
	}

	/**
	 * init() applies page properties from an XML configuration.
	 */
	public function testInitAppliesPagePropertiesFromXml()
	{
		$service = $this->service('PageServiceApp', 'Home');
		$config = new TXmlDocument('1.0', 'utf8');
		$config->loadFromString('<service><pages><page id="Home" Title="From config"/></pages></service>');
		$service->init($config);
		$this->assertSame('From config', PradoUnit::getProp($service, '_properties')['Title'] ?? null);
	}

	/**
	 * init() applies page properties from a PHP configuration.
	 */
	public function testInitAppliesPagePropertiesFromPhp()
	{
		$this->application('PageServiceApp');
		$this->_app->setConfigurationType(TApplication::CONFIG_TYPE_PHP);
		$this->_app->getRequest()->setServiceParameter('Home');
		$service = new TPageService();
		$service->setID('page');
		$this->_app->setService($service);
		$service->init(['pages' => ['Home' => ['properties' => ['Title' => 'From php']]]]);
		$this->assertSame('From php', PradoUnit::getProp($service, '_properties')['Title'] ?? null);
	}

	/**
	 * loadPageConfig() reads and stores the configuration through the cache when
	 * the application has one, and reuses the cached copy on a second pass.
	 */
	public function testLoadPageConfigUsesTheApplicationCache()
	{
		$service = $this->service('PageServiceApp', 'Home');
		$this->_app->setCache($cache = new TPageServiceArrayCache());

		$first = PradoUnit::invoke($service, 'loadPageConfig', null);
		$this->assertNotNull($first);

		$second = PradoUnit::invoke($service, 'loadPageConfig', null);
		$this->assertNotNull($second);
	}

	/**
	 * A cached configuration is discarded when the application is not in
	 * Performance mode and a configuration file is newer than the cached copy.
	 */
	public function testLoadPageConfigRebuildsAStaleCachedConfiguration()
	{
		$service = $this->service('PageServiceApp', 'Home');
		$this->_app->setCache($cache = new TPageServiceArrayCache());
		PradoUnit::invoke($service, 'loadPageConfig', null);

		// Re-seed the cache entry with timestamps that are older than the files.
		$key = TPageService::CONFIG_CACHE_PREFIX . $service->getID() . 'Home';
		[$pageConfig, $timestamps] = $cache->get($key);
		$stale = [];
		foreach ($timestamps as $file => $timestamp) {
			$stale[$file] = 1;
		}
		$cache->set($key, [$pageConfig, $stale]);

		$this->assertNotNull(PradoUnit::invoke($service, 'loadPageConfig', null));
	}

	/**
	 * The cache records the application configuration file's time and rebuilds
	 * the configuration once the file is newer or gone.
	 */
	public function testLoadPageConfigFollowsTheApplicationConfigurationFile()
	{
		$service = $this->service('PageServiceApp', 'Home');
		$this->_app->setCache($cache = new TPageServiceArrayCache());
		$appConfig = $this->fixturePath('PageServiceApp', 'external.xml');
		$this->_app->setConfigurationFile($appConfig);
		$key = TPageService::CONFIG_CACHE_PREFIX . $service->getID() . 'Home';

		$first = PradoUnit::invoke($service, 'loadPageConfig', null);
		$this->assertSame(filemtime($appConfig), $cache->get($key)[1][0]);
		$this->assertEquals($first, PradoUnit::invoke($service, 'loadPageConfig', null));

		$this->_app->setConfigurationFile($this->fixturePath('PageServiceApp', 'missing.xml'));
		PradoUnit::invoke($service, 'loadPageConfig', null);
		$this->assertFalse($cache->get($key)[1][0]);
	}

	/**
	 * In Performance mode the cached configuration is taken as current, so the
	 * timestamps are never consulted.
	 */
	public function testLoadPageConfigTrustsTheCacheInPerformanceMode()
	{
		$service = $this->service('PageServiceApp', 'Home');
		$this->_app->setCache($cache = new TPageServiceArrayCache());
		PradoUnit::invoke($service, 'loadPageConfig', null);

		$this->_app->setMode(\Prado\TApplicationMode::Performance);
		$this->assertNotNull(PradoUnit::invoke($service, 'loadPageConfig', null));
	}

	/**
	 * An external configuration whose condition is false is skipped.
	 */
	public function testApplyConfigurationSkipsAFalseExternalCondition()
	{
		$service = $this->service('PageServiceApp', 'Home');
		$config = new TPageServiceStubConfiguration();
		$config->externals = ['Nowhere.config' => ['', 'false']];
		PradoUnit::invoke($service, 'applyConfiguration', $config);
		$this->assertTrue(true); // reached without loading the external file
	}

	/**
	 * An external configuration naming a missing file is refused.
	 */
	public function testApplyConfigurationRefusesAMissingExternalFile()
	{
		$service = $this->service('PageServiceApp', 'Home');
		$config = new TPageServiceStubConfiguration();
		$config->externals = ['No.Such.File' => ['', true]];
		$this->expectException(TConfigurationException::class);
		PradoUnit::invoke($service, 'applyConfiguration', $config);
	}

	/**
	 * An external configuration is refused when its namespace does not resolve
	 * or names no file, whether its condition is true or evaluates to true.
	 * @dataProvider missingExternalProvider
	 * @param string $file the namespace of the external configuration.
	 * @param bool|string $condition its condition.
	 */
	public function testApplyConfigurationRefusesAnExternalFileThatIsNotThere(string $file, bool|string $condition)
	{
		$service = $this->service('PageServiceApp', 'Home');
		Prado::setPathOfAlias('PageFixtures', $this->fixturePath('PageServiceApp'));
		$config = new TPageServiceStubConfiguration();
		$config->externals = [$file => ['', $condition]];
		$this->expectException(TConfigurationException::class);
		PradoUnit::invoke($service, 'applyConfiguration', $config);
	}

	public static function missingExternalProvider(): array
	{
		return [
			'unresolved, evaluated' => ['No.Such.File', 'true'],
			'no file, evaluated' => ['PageFixtures.missing', '1 === 1'],
			'no file, true' => ['PageFixtures.missing', true],
		];
	}

	// -------------------------------------------------------------------------
	// createPage()
	// -------------------------------------------------------------------------

	/**
	 * A page with only a template file is built from the base page class.
	 */
	public function testCreatePageFromATemplateOnlyPage()
	{
		$service = $this->service('PageServiceApp', 'Home');
		$page = PradoUnit::invoke($service, 'createPage', 'Home');
		$this->assertInstanceOf(TPage::class, $page);
		$this->assertSame('Home', $page->getPagePath());
		$this->assertNotNull($page->getTemplate());
	}

	/**
	 * A page with a class file is built from that class.
	 */
	public function testCreatePageFromAClassFile()
	{
		$service = $this->service('PageServiceApp', 'Classed');
		$page = PradoUnit::invoke($service, 'createPage', 'Classed');
		$this->assertInstanceOf(\Application\Pages\Classed::class, $page);
		$this->assertNotNull($page->getTemplate());
	}

	/**
	 * A page with a class file and no template has no template.
	 */
	public function testCreatePageFromAClassFileWithoutATemplate()
	{
		$service = $this->service('PageServiceApp', 'ClassOnly');
		$page = PradoUnit::invoke($service, 'createPage', 'ClassOnly');
		$this->assertInstanceOf(\Application\Pages\ClassOnly::class, $page);
		$this->assertNull($page->getTemplate());
	}

	/**
	 * A class file whose class is not a TPage is refused.
	 */
	public function testCreatePageRefusesAClassThatIsNotAPage()
	{
		$service = $this->service('PageServiceApp', 'NotAPage');
		$this->expectException(THttpException::class);
		PradoUnit::invoke($service, 'createPage', 'NotAPage');
	}

	/**
	 * A base page class named by its short name builds each page. The first
	 * resolution of a short name aliases it, and later ones return the alias.
	 */
	public function testCreatePageAcceptsTheShortNameOfTheBasePageClass()
	{
		$service = $this->service('PageServiceApp', 'Home');
		$service->setBasePageClass('TPage');
		$this->assertSame(TPage::class, PradoUnit::invoke($service, 'createPage', 'Home')::class);
		$this->assertSame(TPage::class, PradoUnit::invoke($service, 'createPage', 'Home')::class);
	}

	/**
	 * A base page class that names no class is refused.
	 */
	public function testCreatePageRefusesAnUnknownBasePageClass()
	{
		$service = $this->service('PageServiceApp', 'Home');
		$service->setBasePageClass('No.Such.PageClass');
		$this->expectException(TConfigurationException::class);
		PradoUnit::invoke($service, 'createPage', 'Home');
	}

	/**
	 * A page path matching no file, with no additional paths offered, is a 404.
	 */
	public function testCreatePageWithAnUnknownPagePathIsNotFound()
	{
		$service = $this->service('PageServiceApp', 'Missing');
		$this->expectException(THttpException::class);
		PradoUnit::invoke($service, 'createPage', 'Missing');
	}

	/**
	 * An additional page path outside the application path is refused.
	 */
	public function testCreatePageRefusesAnAdditionalPathOutsideTheApplication()
	{
		$service = $this->service('PageServiceApp', 'Missing');
		Prado::setPathOfAlias('Application', $this->fixturePath('PageServiceApp'));
		$service->attachEventHandler('OnAdditionalPagePaths', function ($sender, $param) {
			return '/somewhere/else/Missing';
		});
		$this->expectException(THttpException::class);
		PradoUnit::invoke($service, 'createPage', 'Missing');
	}

	/**
	 * The first additional page path that holds a page is taken.
	 */
	public function testCreatePageTakesTheFirstAdditionalPathWithAPage()
	{
		$service = $this->service('PageServiceApp', 'Elsewhere');
		Prado::setPathOfAlias('Application', $this->fixturePath('PageServiceApp'));
		$pages = realpath($this->fixturePath('PageServiceApp', 'Pages'));
		$service->attachEventHandler('OnAdditionalPagePaths', fn ($sender, $param) => $pages . DIRECTORY_SEPARATOR . 'Missing');
		$service->attachEventHandler('OnAdditionalPagePaths', fn ($sender, $param) => $pages . DIRECTORY_SEPARATOR . 'Home');
		$page = PradoUnit::invoke($service, 'createPage', 'Elsewhere');
		$this->assertSame(TPage::class, $page::class);
	}

	/**
	 * Additional page paths that hold no page leave the page unknown.
	 */
	public function testCreatePageWithAdditionalPathsWithoutAPageIsNotFound()
	{
		$service = $this->service('PageServiceApp', 'Elsewhere');
		Prado::setPathOfAlias('Application', $this->fixturePath('PageServiceApp'));
		$pages = realpath($this->fixturePath('PageServiceApp', 'Pages'));
		$service->attachEventHandler('OnAdditionalPagePaths', fn ($sender, $param) => $pages . DIRECTORY_SEPARATOR . 'Missing');
		try {
			PradoUnit::invoke($service, 'createPage', 'Elsewhere');
			$this->fail('a page was built from a path without one');
		} catch (THttpException $e) {
			$this->assertSame(404, $e->getStatusCode());
		}
	}

	/**
	 * An additional page path inside the application path is accepted.
	 */
	public function testCreatePageAcceptsAnAdditionalPathInsideTheApplication()
	{
		$service = $this->service('PageServiceApp', 'Elsewhere');
		Prado::setPathOfAlias('Application', $this->fixturePath('PageServiceApp'));
		$pages = realpath($this->fixturePath('PageServiceApp', 'Pages'));
		$service->attachEventHandler('OnAdditionalPagePaths', function ($sender, $param) use ($pages) {
			return $pages . DIRECTORY_SEPARATOR . 'Home';
		});
		$page = PradoUnit::invoke($service, 'createPage', 'Elsewhere');
		$this->assertInstanceOf(TPage::class, $page);
	}

	/**
	 * An additional page path that leaves the application path through '..'
	 * is refused, though the page it reaches exists.
	 */
	public function testCreatePageRefusesAnAdditionalPathThatClimbsOutOfTheApplication()
	{
		$service = $this->service('PageServiceApp', 'Elsewhere');
		Prado::setPathOfAlias('Application', $this->fixturePath('PageServiceApp'));
		$outside = $this->fixturePath('PageServiceApp', '..', 'PageServiceFallbackApp', 'pages', 'Home');
		$this->assertFileExists($outside . '.page');
		$service->attachEventHandler('OnAdditionalPagePaths', fn ($sender, $param) => $outside);
		try {
			PradoUnit::invoke($service, 'createPage', 'Elsewhere');
			$this->fail('a page outside the application was built');
		} catch (THttpException $e) {
			$this->assertSame(403, $e->getStatusCode());
		}
	}

	/**
	 * An additional page path in a sibling directory whose name begins with the
	 * application directory's name is refused.
	 */
	public function testCreatePageRefusesAnAdditionalPathInASiblingDirectory()
	{
		$service = $this->service('PageServiceApp', 'Elsewhere');
		Prado::setPathOfAlias('Application', $this->fixturePath('PageServiceApp'));
		$service->attachEventHandler('OnAdditionalPagePaths', fn ($sender, $param) => $this->fixturePath('PageServiceAppSibling', 'Home'));
		try {
			PradoUnit::invoke($service, 'createPage', 'Elsewhere');
			$this->fail('a path beside the application was accepted');
		} catch (THttpException $e) {
			$this->assertSame(403, $e->getStatusCode());
		}
	}

	/**
	 * Which paths lie inside an application path.
	 */
	public static function applicationPathProvider(): array
	{
		return [
			'the application path' => ['/app/protected', '/app/protected', true],
			'below it' => ['/app/protected/Pages/Home', '/app/protected', true],
			'below it in another case' => ['/APP/Protected/Pages/Home', '/app/protected', true],
			'below it with a trailing slash' => ['/app/protected/Pages', '/app/protected/', true],
			'a sibling with the same prefix' => ['/app/protected2/Pages/Home', '/app/protected', false],
			'out through ..' => ['/app/protected/../secret/Home', '/app/protected', false],
			'out of the root' => ['/../app/protected/Home', '/app/protected', false],
			'.. that stays inside' => ['/app/protected/Pages/../Home', '/app/protected', true],
			'.. that comes back' => ['/app/../app/protected/Home', '/app/protected', true],
			'. and empty segments' => ['/app/protected/./Pages//Home', '/app/protected', true],
			'backslashes' => ['\\app\\protected\\Pages\\Home', '/app/protected', true],
			'a drive' => ['C:\\app\\protected\\Pages', 'C:\\app\\protected', true],
			'out of a drive' => ['C:\\app\\..\\..\\secret', 'C:\\app\\protected', false],
			'a relative path' => ['app/protected/Home', '/app/protected', false],
			'no application path' => ['/anywhere/Home', '', true],
		];
	}

	/**
	 * isApplicationPath() accepts the application path and the paths below it.
	 * @dataProvider applicationPathProvider
	 * @param string $path the path offered.
	 * @param string $applicationPath the application path.
	 * @param bool $inside whether the path is inside.
	 */
	public function testIsApplicationPath(string $path, string $applicationPath, bool $inside)
	{
		$service = $this->service();
		$this->assertSame($inside, PradoUnit::invoke($service, 'isApplicationPath', $path, $applicationPath));
	}

	// -------------------------------------------------------------------------
	// Events
	// -------------------------------------------------------------------------

	/**
	 * onAdditionalPagePaths() collects the paths handlers offer.
	 */
	public function testOnAdditionalPagePathsCollectsHandlerResults()
	{
		$service = $this->service();
		$service->attachEventHandler('OnAdditionalPagePaths', fn($sender, $param) => '/a/path');
		$this->assertContains('/a/path', $service->onAdditionalPagePaths('Missing'));
	}

	/**
	 * onAdditionalPagePaths() yields no paths when nothing handles it.
	 */
	public function testOnAdditionalPagePathsWithoutHandlersYieldsNoPaths()
	{
		$service = $this->service();
		$this->assertSame([], $service->onAdditionalPagePaths('Missing'));
	}

	/**
	 * onPreRunPage() raises the event with the page.
	 */
	public function testOnPreRunPageRaisesTheEvent()
	{
		$service = $this->service();
		$seen = null;
		$service->attachEventHandler('OnPreRunPage', function ($sender, $param) use (&$seen) {
			$seen = $param;
		});
		$service->onPreRunPage($page = new TNestedPathPage());
		$this->assertSame($page, $seen);
	}

	// -------------------------------------------------------------------------
	// run() and runPage()
	// -------------------------------------------------------------------------

	/**
	 * run() builds the requested page and runs it with the configured properties.
	 */
	public function testRunBuildsAndRunsTheRequestedPage()
	{
		$service = $this->service('PageServiceApp', 'Home', TRecordingPageService::class);
		PradoUnit::setProp($service, '_properties', ['Title' => 'A title']);
		$service->run();

		$this->assertInstanceOf(TPage::class, $service->builtPage);
		$this->assertSame($service->builtPage, $service->getRequestedPage());
		$this->assertSame(['Title' => 'A title'], $service->ranProperties);
	}

	/**
	 * A parent path is written before any path nested beneath it, so a nested
	 * page property survives whatever order the configuration declares them in.
	 */
	public function testRunPageAppliesParentPathBeforeNestedPath()
	{
		$this->withResponse(function () {
			foreach ([
				['Cfg.Size' => 'child', 'Cfg' => 'value'],
				['Cfg' => 'value', 'Cfg.Size' => 'child'],
			] as $properties) {
				$service = $this->service();
				$page = new TNestedPathPage();
				PradoUnit::invoke($service, 'runPage', $page, $properties);
				$this->assertTrue($page->ran);
				$this->assertEquals('child', $page->getCfg()->getSize(), 'declared: ' . implode(', ', array_keys($properties)));
			}
		});
	}

	/**
	 * initPageContext() hands each application configuration to the application.
	 */
	public function testInitPageContextAppliesApplicationConfigurations()
	{
		$service = $this->service('PageServiceApp', 'Home');
		$config = new TPageServiceStubConfiguration();
		$config->appConfigs = [new \Prado\TApplicationConfiguration()];
		PradoUnit::invoke($service, 'initPageContext', $config);
		$this->assertTrue($service->getIsInitialized() === false); // init() was never called
	}

	/**
	 * An external configuration whose condition holds is loaded and applied.
	 */
	public function testApplyConfigurationLoadsATrueExternalConfiguration()
	{
		$service = $this->service('PageServiceApp', 'Home');
		Prado::setPathOfAlias('PageFixtures', $this->fixturePath('PageServiceApp'));
		$config = new TPageServiceStubConfiguration();
		$config->externals = ['PageFixtures.external' => ['', true]];
		PradoUnit::invoke($service, 'applyConfiguration', $config);
		$this->assertSame('From external', PradoUnit::getProp($service, '_properties')['Title'] ?? null);
	}

	/**
	 * An external configuration whose condition is an expression that evaluates
	 * true is loaded the same way.
	 */
	public function testApplyConfigurationEvaluatesATrueExternalCondition()
	{
		$service = $this->service('PageServiceApp', 'Home');
		Prado::setPathOfAlias('PageFixtures', $this->fixturePath('PageServiceApp'));
		$config = new TPageServiceStubConfiguration();
		$config->externals = ['PageFixtures.external' => ['', 'true']];
		PradoUnit::invoke($service, 'applyConfiguration', $config);
		$this->assertSame('From external', PradoUnit::getProp($service, '_properties')['Title'] ?? null);
	}

	/**
	 * A cache miss under a PHP configuration reads the PHP config file name and
	 * loads the supplied configuration through the PHP branch.
	 */
	public function testLoadPageConfigFromPhpThroughTheCache()
	{
		$this->application('PageServiceApp');
		$this->_app->setConfigurationType(TApplication::CONFIG_TYPE_PHP);
		$this->_app->getRequest()->setServiceParameter('Home');
		$this->_app->setCache(new TPageServiceArrayCache());
		$service = new TPageService();
		$service->setID('page');
		$this->_app->setService($service);

		$config = PradoUnit::invoke($service, 'loadPageConfig', ['pages' => ['Home' => ['properties' => ['Title' => 'Php cached']]]]);
		$this->assertSame('Php cached', $config->getProperties()['Title'] ?? null);
	}

	/**
	 * A cache miss under an XML configuration loads the supplied configuration
	 * through the XML branch.
	 */
	public function testLoadPageConfigFromXmlThroughTheCache()
	{
		$service = $this->service('PageServiceApp', 'Home');
		$this->_app->setCache(new TPageServiceArrayCache());

		$xml = new TXmlDocument('1.0', 'utf8');
		$xml->loadFromString('<service><pages><page id="Home" Title="Xml cached"/></pages></service>');
		$config = PradoUnit::invoke($service, 'loadPageConfig', $xml);
		$this->assertSame('Xml cached', $config->getProperties()['Title'] ?? null);
	}

	// ================================================================
	// IdSeparator
	// ================================================================

	/**
	 * IdSeparator reads and sets the separator of every control's UniqueID.
	 */
	public function testIdSeparatorIsTheControlIdSeparator()
	{
		$service = $this->service();
		try {
			$this->assertSame(':', $service->getIdSeparator());
			$service->setIdSeparator('$');
			$this->assertSame('$', $service->getIdSeparator());
			$this->assertSame('$', TControl::getIdSeparator());
		} finally {
			TControl::setIdSeparator(TControl::DEFAULT_ID_SEPARATOR);
		}
	}

	/**
	 * An invalid separator is refused and the separator in use is kept.
	 */
	public function testSetIdSeparatorRefusesAnInvalidCharacter()
	{
		$service = $this->service();
		try {
			$service->setIdSeparator('_');
			$this->fail('TInvalidDataValueException expected');
		} catch (TInvalidDataValueException $e) {
			$this->assertSame('control_idseparator_invalid', $e->getErrorCode());
		}
		$this->assertSame(':', TControl::getIdSeparator());
	}

	/**
	 * The separator cannot change once the service is initialized.
	 */
	public function testSetIdSeparatorAfterInitializationIsRefused()
	{
		$service = $this->service('PageServiceApp', 'Home');
		$service->init(null);
		try {
			$service->setIdSeparator('$');
			$this->fail('TInvalidOperationException expected');
		} catch (TInvalidOperationException $e) {
			$this->assertSame(':', TControl::getIdSeparator());
		}
	}
}
