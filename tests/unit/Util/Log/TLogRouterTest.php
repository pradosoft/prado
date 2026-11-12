<?php

namespace Prado\Test\Unit\Util\Log;

use Prado\Util\Log\TLogger;
use Prado\Util\Log\TLogRouter;
use Prado\Util\Log\TBrowserLogRoute;
use Prado\Util\Cron\TCronTaskInfo;
use Prado\Test\Unit\Harness\Traits\TNestedPathTrait;
use Prado\Xml\TXmlDocument;

class TTestLogRouter extends TLogRouter {
	
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
	protected function setUp(): void
	{
	}

	protected function tearDown(): void
	{
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
	 * The XML branch orders a route's attributes the same way, and supplies a
	 * TAttributeCollection rather than an array.
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
}
