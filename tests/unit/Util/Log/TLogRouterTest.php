<?php

namespace Prado\Test\Unit\Util\Log;

use Prado\Exceptions\TConfigurationException;
use Prado\Util\Log\TLogger;
use Prado\Util\Log\TLogRouter;
use Prado\Util\Log\TBrowserLogRoute;
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
	
}

