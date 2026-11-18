<?php

namespace Prado\Test\Unit\Util\Log;

use Prado\Exceptions\TConfigurationException;
use Prado\Util\Log\TLogger;
use Prado\Util\Log\TLogRouter;
use Prado\Util\Log\TBrowserLogRoute;
use Prado\Util\Cron\TCronTaskInfo;
use Prado\Test\Unit\Harness\Traits\TNestedPathTrait;
use Prado\Xml\TXmlDocument;
use Prado\Exceptions\TInvalidDataTypeException;
use Prado\Prado;
use Prado\TApplication;
use Prado\Test\Unit\Harness\Traits\TTempDirectoryTrait;
use Prado\Test\Unit\PradoUnit;
use Prado\Util\Log\TLogRoute;

class TTestLogRouter extends TLogRouter {
	
}

/**
 * A log route that keeps each collection it receives.
 */
class TCollectingLogRoute extends TLogRoute
{
	public array $collected = [];

	public function collectLogs(null|bool|TLogger $logger = null, bool $final = false)
	{
		$this->collected[] = [$logger, $final];
	}

	protected function processLogs(array $logs, bool $final, array $meta)
	{
	}
}

/**
 * A log route carrying a nested 'Cfg' property, to observe the order the
 * router applies a route's configured properties in.
 */
class TNestedPathLogRoute extends TBrowserLogRoute
{
	use TNestedPathTrait;
}

class TLogRouterTest extends \PHPUnit\Framework\TestCase
{
	use TTempDirectoryTrait;

	private array $aliases = [];

	protected function setUp(): void
	{
		$this->aliases = PradoUnit::snapshotStatic(Prado::class, ['_aliases']);
	}

	protected function tearDown(): void
	{
		$logger = Prado::getLogger();
		foreach ($logger->getEventHandlers('onFlushLogs')->toArray() as $handler) {
			if (is_array($handler) && $handler[0] instanceof TTestLogRouter) {
				$logger->detachEventHandler('onFlushLogs', $handler);
			}
		}
		PradoUnit::restoreStatic(Prado::class, $this->aliases);
		$this->removeTempDirectories();
	}

	/**
	 * Runs a callable with the application configuration type set, then restores it.
	 */
	private function withConfigurationType(string $type, callable $callable): mixed
	{
		$app = Prado::getApplication();
		$saved = PradoUnit::snapshot($app, ['_configType', '_configFileExt']);
		try {
			PradoUnit::setProp($app, '_configType', $type);
			PradoUnit::setProp($app, '_configFileExt', null);
			return $callable();
		} finally {
			PradoUnit::restore($app, $saved);
		}
	}

	/**
	 * Writes a route configuration file and returns its namespace.
	 */
	private function configFile(string $name, string $content): string
	{
		$dir = $this->makeTempDirectory('prado_logrouter_');
		Prado::setPathOfAlias('LogRouterTest', $dir);
		file_put_contents($dir . DIRECTORY_SEPARATOR . $name, $content);
		return 'LogRouterTest.' . pathinfo($name, PATHINFO_FILENAME);
	}
	
	public function testInit()
	{
		$router = new TTestLogRouter();
		$router->init(['routes' => [
					['class' => \Prado\Util\Log\TBrowserLogRoute::class, 'properties' => []]
				]]);
		$routes = $router->getRoutes();
		$this->assertEquals(1, count($routes));
	}
	
	/**
	 * A route's configured properties apply parent path first, so a nested value
	 * survives whatever order the configuration declares them in.
	 */
	public function testInitAppliesParentPathBeforeNestedPath()
	{
		foreach ([
			['Cfg.Size' => 'child', 'Cfg' => 'value'],
			['Cfg' => 'value', 'Cfg.Size' => 'child'],
		] as $properties) {
			$router = new TTestLogRouter();
			$router->init(['routes' => [
				['class' => TNestedPathLogRoute::class, 'properties' => $properties],
			]]);
			$routes = $router->getRoutes();
			$this->assertEquals('child', $routes[0]->getCfg()->getSize(), 'declared: ' . implode(', ', array_keys($properties)));
		}
	}

	/**
	 * The XML branch orders a route's attributes the same way.
	 */
	public function testInitFromXmlAppliesParentPathBeforeNestedPath()
	{
		foreach ([
			'Cfg.Size="child" Cfg="value"',
			'Cfg="value" Cfg.Size="child"',
		] as $attributes) {
			$config = new TXmlDocument('1.0', 'utf8');
			$config->loadFromString('<module><route class="' . TNestedPathLogRoute::class . '" ' . $attributes . '/></module>');

			$router = new TTestLogRouter();
			$router->init($config);
			$routes = $router->getRoutes();
			$this->assertEquals('child', $routes[0]->getCfg()->getSize(), "attributes: {$attributes}");
		}
	}

	/**
	 * A PHP route is an array with `class` and `properties` or a class name alone, and
	 * the `routes` array may be keyed.
	 */
	public function testInitPhpRoutesAcceptClassNamesAndKeys()
	{
		$router = new TTestLogRouter();
		$router->init(['routes' => [
			'browser' => TBrowserLogRoute::class,
			'nested' => ['class' => TNestedPathLogRoute::class, 'properties' => ['Cfg.Size' => 'child']],
		]]);
		$routes = $router->getRoutes();
		$this->assertCount(2, $routes);
		$this->assertInstanceOf(TBrowserLogRoute::class, $routes[0]);
		$this->assertInstanceOf(TNestedPathLogRoute::class, $routes[1]);
		$this->assertEquals('child', $routes[1]->getCfg()->getSize());
	}

	public function testInitWithoutRoutes()
	{
		foreach ([null, [], ['routes' => 'invalid']] as $config) {
			$router = new TTestLogRouter();
			$router->init($config);
			$this->assertEquals(0, $router->getRoutesCount());
		}
	}

	public function testInitRouteWithoutClassThrows()
	{
		$xml = new TXmlDocument('1.0', 'utf8');
		$xml->loadFromString('<module><route Levels="Error" /></module>');
		foreach ([
			'php' => ['routes' => [['properties' => ['Levels' => 'Error']]]],
			'php non-array' => ['routes' => [42]],
			'xml' => $xml,
		] as $form => $config) {
			try {
				(new TTestLogRouter())->init($config);
				$this->fail("{$form}: expected TConfigurationException");
			} catch (TConfigurationException $e) {
				$this->assertEquals('module_config_class_required', $e->getErrorCode(), $form);
			}
		}
	}

	public function testInitRouteOfWrongTypeThrows()
	{
		$xml = new TXmlDocument('1.0', 'utf8');
		$xml->loadFromString('<module><route class="' . TLogger::class . '" /></module>');
		foreach ([
			'php' => ['routes' => [['class' => TLogger::class]]],
			'xml' => $xml,
		] as $form => $config) {
			try {
				(new TTestLogRouter())->init($config);
				$this->fail("{$form}: expected TConfigurationException");
			} catch (TConfigurationException $e) {
				$this->assertEquals('logrouter_routetype_invalid', $e->getErrorCode(), $form);
			}
		}
	}

	public function testAddRoute()
	{
		$router = new TTestLogRouter();
		$router->AddRoute($route1 = new TBrowserLogRoute);
		$router->AddRoute($route2 = new TBrowserLogRoute);
		$routes = $router->getRoutes();
		$this->assertEquals(2, count($routes));
		$this->assertEquals([$route1, $route2], $routes);
	}
	
	public function testRemoveRoute()
	{
		$router = new TTestLogRouter();
		$router->AddRoute($route1 = new TBrowserLogRoute);
		$router->AddRoute($route2 = new TBrowserLogRoute);
		$router->AddRoute($route3 = new TBrowserLogRoute);
		
		$this->assertNull($router->removeRoute(10));
		$this->assertNull($router->removeRoute($this));
		$this->assertEquals($route1, $router->removeRoute(0));
		
		$this->assertEquals([$route2, $route3], $router->getRoutes());
		
		$this->assertEquals($route2, $router->removeRoute(0));
		$this->assertEquals([$route3], $router->getRoutes());
		
		$this->assertEquals($route3, $router->removeRoute($route3));
		$this->assertEquals([], $router->getRoutes());
	}
	
	public function testFlushCount()
	{
		$logger = \Prado::getLogger();
		$this->assertEquals(1000, $logger->getFlushCount());
		
		$router = new TTestLogRouter();
		
		$router->setFlushCount('100');
		$this->assertEquals(100, $logger->getFlushCount());
		
		$router->setFlushCount(1000);
		$this->assertEquals(1000, $logger->getFlushCount());
		
	}
	
	public function testTraceLevel()
	{
		$logger = \Prado::getLogger();
		$this->assertEquals(0, $logger->getTraceLevel());
		
		$router = new TTestLogRouter();
		
		$router->setTraceLevel('100');
		$this->assertEquals(100, $logger->getTraceLevel());
		
		$router->setTraceLevel(0);
		$this->assertEquals(0, $logger->getTraceLevel());
	}
	

	public function testPruneDbLogsAndCronTaskInfo()
	{
		if (!extension_loaded('pdo_sqlite')) {
			$this->markTestSkipped('The pdo_sqlite extension is not available.');
		}
		$router = new TTestLogRouter();
		$router->addRoute(new TBrowserLogRoute());
		$this->assertSame(0, $router->pruneDbLogs());
		$this->assertNull($router->fxGetCronTaskInfos(null, null));
		$router->setID('log');
		$this->assertNull($router->fxGetCronTaskInfos(null, null));

		$a = new TSqliteFileDbLogRoute();
		$b = new TSqliteFileDbLogRoute();
		try {
			$router->addRoute($a);
			$router->addRoute($b);
			$now = microtime(true);
			$a->insertAt($now - 1000);
			$a->insertAt($now - 10);
			$b->insertAt($now - 1000);
			$b->insertAt($now - 500);
			$a->setRetainPeriod(300);

			$this->assertSame(1, $router->pruneDbLogs());
			$this->assertSame(2, $router->pruneDbLogs(100));
			$this->assertEquals(1, $a->getDBLogCount());
			$this->assertEquals(0, $b->getDBLogCount());

			$info = $router->fxGetCronTaskInfos(null, null);
			$this->assertInstanceOf(TCronTaskInfo::class, $info);
			$this->assertSame('dblogprune', $info->getName());
			$this->assertSame('log->pruneDbLogs()', $info->getTask());
			$this->assertSame('log', $info->getModuleId());
		} finally {
			$a->removeTestFile();
			$b->removeTestFile();
		}
	}

	public function testInitAttachesToTheLogger()
	{
		$router = new TTestLogRouter();
		$router->init(null);
		$this->assertTrue(Prado::getLogger()->getEventHandlers('onFlushLogs')->contains([$router, 'collectLogs']));
	}

	public function testInitLoadsAnXmlConfigFile()
	{
		$router = new TTestLogRouter();
		$this->withConfigurationType(TApplication::CONFIG_TYPE_XML, function () use ($router) {
			$router->setConfigFile($this->configFile('routes.xml', '<configuration><route class="' . TBrowserLogRoute::class . '" CssClass="fromfile" /></configuration>'));
			$this->assertStringEndsWith('routes.xml', $router->getConfigFile());
			$router->init(['routes' => [TCollectingLogRoute::class]]);
		});

		$routes = $router->getRoutes();
		$this->assertCount(2, $routes);
		$this->assertSame('fromfile', $routes[0]->getCssClass());
		$this->assertInstanceOf(TCollectingLogRoute::class, $routes[1]);
	}

	public function testInitLoadsAPhpConfigFile()
	{
		$router = new TTestLogRouter();
		$this->withConfigurationType(TApplication::CONFIG_TYPE_PHP, function () use ($router) {
			$router->setConfigFile($this->configFile('routes.php', '<?php return ["routes" => [["class" => "' . addslashes(TBrowserLogRoute::class) . '", "properties" => ["CssClass" => "php"]]]];'));
			$this->assertStringEndsWith('routes.php', $router->getConfigFile());
			$router->init(null);
		});
		$routes = $router->getRoutes();
		$this->assertCount(1, $routes);
		$this->assertSame('php', $routes[0]->getCssClass());
	}

	public function testInitThrowsWhenTheConfigFileIsGone()
	{
		$router = new TTestLogRouter();
		$this->withConfigurationType(TApplication::CONFIG_TYPE_XML, function () use ($router) {
			$router->setConfigFile($this->configFile('routes.xml', '<configuration />'));
			unlink($router->getConfigFile());
			try {
				$router->init(null);
				$this->fail('A missing ConfigFile throws TConfigurationException.');
			} catch (TConfigurationException $e) {
				$this->assertSame('logrouter_configfile_invalid', $e->getErrorCode());
			}
		});
	}

	public function testSetConfigFileRejectsAnInvalidNamespace()
	{
		$router = new TTestLogRouter();
		$this->assertNull($router->getConfigFile());
		try {
			$router->setConfigFile('NoAliasFile');
			$this->fail('An unresolvable ConfigFile throws TConfigurationException.');
		} catch (TConfigurationException $e) {
			$this->assertSame('logrouter_configfile_invalid', $e->getErrorCode());
		}
		$this->assertNull($router->getConfigFile());
	}

	public function testAddRouteRejectsANonRoute()
	{
		$router = new TTestLogRouter();
		try {
			$router->addRoute(new \stdClass());
			$this->fail('A non-route throws TInvalidDataTypeException.');
		} catch (TInvalidDataTypeException $e) {
			$this->assertSame('logrouter_routetype_invalid', $e->getErrorCode());
		}
		$this->assertSame(0, $router->getRoutesCount());
	}

	public function testCollectLogs()
	{
		$router = new TTestLogRouter();
		$router->addRoute($enabled = new TCollectingLogRoute());
		$router->addRoute($disabled = new TCollectingLogRoute());
		$disabled->setEnabled(false);

		$logger = new TLogger(false);
		$router->collectLogs($logger, true);
		$router->collectLogs(null, false);

		$this->assertSame([[$logger, true], [Prado::getLogger(), false]], $enabled->collected);
		$this->assertSame([], $disabled->collected);
	}

	public function testFlushCountAndTraceLevelReadTheLogger()
	{
		$router = new TTestLogRouter();
		$logger = Prado::getLogger();
		$this->assertSame($logger->getFlushCount(), $router->getFlushCount());
		$this->assertSame($logger->getTraceLevel(), $router->getTraceLevel());
	}

	public function testCollectLogsOfOneOrNoRoute()
	{
		$logger = new TLogger(false);
		foreach ([$logger, null] as $given) {
			$expected = $given ?? Prado::getLogger();

			$router = new TTestLogRouter();
			$router->collectLogs($given, true);
			$this->assertSame(0, $router->getRoutesCount());

			$router->addRoute($route = new TCollectingLogRoute());
			$router->collectLogs($given, true);
			$this->assertSame([[$expected, true]], $route->collected);

			$route->setEnabled(false);
			$router->collectLogs($given, false);
			$this->assertSame([[$expected, true]], $route->collected);
		}
	}

	public function testInitWithAnEmptyRoutesArray()
	{
		$router = new TTestLogRouter();
		$router->init(['routes' => []]);
		$this->assertSame(0, $router->getRoutesCount());
	}

	public function testRemoveRouteOfAnArray()
	{
		$router = new TTestLogRouter();
		$router->addRoute($route = new TBrowserLogRoute());
		$this->assertNull($router->removeRoute([]));
		$this->assertSame([$route], $router->getRoutes());
	}

	public function testPruneDbLogsAndCronTaskInfoOfOneOrNoRoute()
	{
		if (!extension_loaded('pdo_sqlite')) {
			$this->markTestSkipped('The pdo_sqlite extension is not available.');
		}
		$router = new TTestLogRouter();
		$router->setID('log');
		$this->assertSame(0, $router->pruneDbLogs());
		$this->assertNull($router->fxGetCronTaskInfos(null, null));

		$route = new TSqliteFileDbLogRoute();
		try {
			$router->addRoute($route);
			$route->insertAt(microtime(true) - 1000);
			$this->assertSame(1, $router->pruneDbLogs(100));
			$this->assertSame('dblogprune', $router->fxGetCronTaskInfos(null, null)->getName());
		} finally {
			$route->removeTestFile();
		}
	}
}
