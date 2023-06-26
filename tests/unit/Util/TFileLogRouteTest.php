<?php

use Prado\Util\TLogger;
use Prado\Util\TSysLogRoute;

class TTestFileLogRoute extends TFileLogRoute {
}

class TFileLogRouteTest extends PHPUnit\Framework\TestCase
{
	protected function setUp(): void
	{
	}

	protected function tearDown(): void
	{
	}
	
	public function testRotateFiles()
	{
		$logger = new TLogger();
		$route = new TTestFileLogRoute();
		
		$logger->log('token', TLogger::DEBUG);
		$logs = $logger->getLogs();
		
		$route->collectLogs($logger);
		$this->assertEquals(1, $route->getLogCount());
		
		$logFile = $route->getLogPath() . DIRECTORY_SEPARATOR . $route->getLogFile();
		if(is_file($logFile))
			unlink($logFile);
		
		$route->collectLogs(true);
		$this->assertEquals(0, $route->getLogCount());
		
		$logFile = $route->getLogPath() . DIRECTORY_SEPARATOR . $route->getLogFile();
		$this->assertTrue(is_file($logFile));
		$this->assertGreaterThan(0, @filesize($logFile));
		
		unlink($logFile);
	}
}

