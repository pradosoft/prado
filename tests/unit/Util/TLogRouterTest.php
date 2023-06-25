<?php

use Prado\Util\TLogger;
use Prado\Util\TLogRouter;
use Prado\Util\TBrowserLogRoute;

class TTestLogRouter extends TLogRouter {
	
}

class TLogRouterTest extends PHPUnit\Framework\TestCase
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
					['class' => \Prado\Util\TBrowserLogRoute::class, 'properties' => []]
				]]);
		$routes = $router->getRoutes();
		$this->assertEquals(1, count($routes));
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
		$logger = Prado::getLogger();
		$this->assertEquals(1000, $logger->getFlushCount());
		
		$router = new TTestLogRouter();
		
		$router->setFlushCount('100');
		$this->assertEquals(100, $logger->getFlushCount());
		
		$router->setFlushCount(1000);
		$this->assertEquals(1000, $logger->getFlushCount());
		
	}
	
	public function testTraceLevel()
	{
		$logger = Prado::getLogger();
		$this->assertEquals(0, $logger->getTraceLevel());
		
		$router = new TTestLogRouter();
		
		$router->setTraceLevel('100');
		$this->assertEquals(100, $logger->getTraceLevel());
		
		$router->setTraceLevel(0);
		$this->assertEquals(0, $logger->getTraceLevel());
	}
	
}

