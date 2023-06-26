<?php

use Prado\Util\TLogger;
use Prado\Util\TLogRouter;
use Prado\Util\TBrowserLogRoute;

class TTestLogRoute extends TLogRoute {
	protected function processLogs(array $logs, bool $final, array $meta)
	{
		
	}
}

class TLogRouteTest extends PHPUnit\Framework\TestCase
{
	protected function setUp(): void
	{
	}

	protected function tearDown(): void
	{
	}
	
	public function testLevels()
	{
		$route = new TTestLogRoute();
		
		$this->assertEquals(0, $route->getLevels());
		$this->assertEquals($route, $route->setLevels(TLogger::DEBUG));
		$this->assertEquals(TLogger::DEBUG, $route->getLevels());
		$route->setLevels(TLogger::PROFILE_BEGIN | TLogger::WARNING);
		$this->assertEquals(TLogger::PROFILE_BEGIN | TLogger::WARNING, $route->getLevels());

		$route->setLevels('Error, fatal, PROFILE');
		$this->assertEquals(TLogger::ERROR | TLogger::FATAL | TLogger::PROFILE, $route->getLevels());
		
		$route->setLevels('Notice | Warning | PROFILE BEGIN');
		$this->assertEquals(TLogger::NOTICE | TLogger::WARNING | TLogger::PROFILE_BEGIN_SELECT, $route->getLevels());
		
		$route->setLevels(['Debug ', ' info ', 'PROFILE']);
		$this->assertEquals(TLogger::DEBUG | TLogger::INFO | TLogger::PROFILE, $route->getLevels());
		
		
		$route->setLevels('Notice | Warning | PROFILE_END');
		$this->assertEquals(TLogger::NOTICE | TLogger::WARNING | TLogger::PROFILE_END_SELECT, $route->getLevels());

		$this->expectException(TConfigurationException::class);
		$route->setLevels(0x100000);
	}
	
	public function testCategories()
	{
		$route = new TTestLogRoute();
		
		$this->assertEquals([], $route->getCategories());
		
		$route->setCategories(['cat1', 'cat2*']);
		$this->assertEquals(['cat1', 'cat2*'], $route->getCategories());
		
		$route->setCategories(' dog1, dog2* ');
		$this->assertEquals(['dog1', 'dog2*'], $route->getCategories());
	}
	
	public function testEnabled()
	{
		$route = new TTestLogRoute();
		
		$this->assertTrue($route->getEnabled());
		
		$this->assertEquals($route, $route->setEnabled(false));
		$this->assertFalse($route->getEnabled());
		
		$enabled = true;
		$this->assertEquals($route, $route->setEnabled(function(TLogRoute $route) use (&$enabled) {return $enabled;} ));
		$this->assertTrue($route->getEnabled());
		$enabled = false;
		$this->assertFalse($route->getEnabled());
	}
	
	public function testProcessInterval()
	{
		$route = new TTestLogRoute();
		
		$this->assertEquals(1000, $route->getProcessInterval());
		$route->setProcessInterval(5);
		$this->assertEquals(5, $route->getProcessInterval());
		$route->setProcessInterval(10);
		$this->assertEquals(10, $route->getProcessInterval());
	}
	
	public function testPrefix()
	{
		$route = new TTestLogRoute();
		
		$this->assertNull($route->getPrefixCallback());
		$route->setPrefixCallback(function(array $log, string $prefix) {return 'test' . $prefix;});
		$this->assertEquals(0, strncmp($route->getLogPrefix([7 => getmypid()]), 'test[', 5));
	}
	
	
	public function testDisplaySubSecond()
	{
		$route = new TTestLogRoute();
		
		$this->assertFalse($route->getDisplaySubSeconds());
		$route->setDisplaySubSeconds(true);
		$this->assertTrue($route->getDisplaySubSeconds());
		$route->setDisplaySubSeconds('false');
		$this->assertFalse($route->getDisplaySubSeconds());
		$route->setDisplaySubSeconds('true');
		$this->assertTrue($route->getDisplaySubSeconds());
		$route->setDisplaySubSeconds(false);
		$this->assertFalse($route->getDisplaySubSeconds());
	}
}

