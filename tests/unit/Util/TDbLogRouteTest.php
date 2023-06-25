<?php

use Prado\Util\TLogger;
use Prado\Util\TSysLogRoute;

class TTestDbLogRoute extends TDbLogRoute {
}

class TDbLogRouteTest extends PHPUnit\Framework\TestCase
{
	protected function setUp(): void
	{
	}

	protected function tearDown(): void
	{
	}
	public function testRetainPeriod()
	{
		$route = new TTestDbLogRoute();
		
		$this->assertNull($route->getRetainPeriod());
		$route->setRetainPeriod(null);
		$this->assertNull($route->getRetainPeriod());
		$route->setRetainPeriod(0);
		$this->assertNull($route->getRetainPeriod());
		$route->setRetainPeriod('');
		$this->assertNull($route->getRetainPeriod());
		$route->setRetainPeriod('0');
		$this->assertNull($route->getRetainPeriod());
		
		$route->setRetainPeriod('20');
		$this->assertEquals(20, $route->getRetainPeriod());
		
		$route->setRetainPeriod(30);
		$this->assertEquals(30, $route->getRetainPeriod());
		
		$route->setRetainPeriod('PT45S');
		$this->assertEquals(45, $route->getRetainPeriod());
	}
	
	public function testCollect()
	{
		$logger = new TLogger();
		$route = new TTestDbLogRoute();
		
		$route->init(null);
		$route->deleteDBLog();
		
		$logger->log('debug', TLogger::DEBUG, \Prado\TApplication::class);
			usleep(100);
		$logger->log('info', TLogger::INFO);
			usleep(100);
		$logger->log('notice', TLogger::NOTICE);
			usleep(100);
		$firstTime = microtime(true);
		$logger->log('warning', TLogger::WARNING, \Prado\TModule::class);
			usleep(100);
		$logger->log('error', TLogger::ERROR, TTestDbLogRoute::class);
			usleep(100);
		$secondTime = microtime(true);
		$logger->log('alert', TLogger::ALERT);
			usleep(100);
		$logger->log('fatal', TLogger::FATAL, \Prado\TApplication::class);
			usleep(100);
		$logger->log('profile', TLogger::PROFILE, \Prado\Web\THttpRequest::class);
			usleep(100);
		$logger->log('token', TLogger::PROFILE_BEGIN);
			usleep(100);
		$logger->log('token', TLogger::PROFILE_END);
		$logs = $logger->getLogs();
		
		$route->collectLogs($logger);
		$this->assertEquals(10, $route->getLogCount());
		$this->assertEquals(0, $route->getDBLogCount());
		
		$route->collectLogs(true);
		
		$this->assertEquals(0, $route->getLogCount());
		
		$this->assertEquals(10, $route->getDBLogCount());
		$this->assertEquals(1, $route->getDBLogCount(TLogger::WARNING));
		$this->assertEquals(2, $route->getDBLogCount(TLogger::WARNING | TLogger::INFO));
		$this->assertEquals(3, $route->getDBLogCount(TLogger::PROFILE));
		$this->assertEquals(1, $route->getDBLogCount(TLogger::PROFILE_BEGIN_SELECT));
		$this->assertEquals(2, $route->getDBLogCount(null, \Prado\TApplication::class));
		$this->assertEquals(8, $route->getDBLogCount(null, '!'.\Prado\TApplication::class));
		$this->assertEquals(8, $route->getDBLogCount(null, ['~'.\Prado\TApplication::class]));
		$this->assertEquals(4, $route->getDBLogCount(null, 'Prado\\*, ~'.\Prado\TModule::class . ', '. TTestDbLogRoute::class));
		
		$this->assertEquals(7, $route->getDBLogCount(null, null, $firstTime));
		$this->assertEquals(5, $route->getDBLogCount(null, null, null, $secondTime));
		$this->assertEquals(2, $route->getDBLogCount(null, null, $firstTime, $secondTime));
		$this->assertEquals(1, $route->getDBLogCount(null, 'Prado\\*', $firstTime, $secondTime));
		$this->assertEquals(1, $route->getDBLogCount(TLogger::WARNING, 'Prado\\*', $firstTime, $secondTime));
		
		$this->assertEquals(1, count($route->getDBLogs(TLogger::WARNING)->readAll()));
		$this->assertEquals(2, count($route->getDBLogs(TLogger::WARNING | TLogger::INFO)->readAll()));
		$this->assertEquals(3, count($route->getDBLogs(TLogger::PROFILE)->readAll()));
		$this->assertEquals(1, count($route->getDBLogs(TLogger::PROFILE_BEGIN_SELECT)->readAll()));
		$this->assertEquals(2, count($route->getDBLogs(null, \Prado\TApplication::class)->readAll()));
		$this->assertEquals(8, count($route->getDBLogs(null, '!'.\Prado\TApplication::class)->readAll()));
		$this->assertEquals(8, count($route->getDBLogs(null, ['~'.\Prado\TApplication::class])->readAll()));
		$this->assertEquals(4, count($route->getDBLogs(null, 'Prado\\*, ~'.\Prado\TModule::class . ', '. TTestDbLogRoute::class)->readAll()));
		
		$this->assertEquals(7, count($route->getDBLogs(null, null, $firstTime)->readAll()));
		$this->assertEquals(5, count($route->getDBLogs(null, null, null, $secondTime)->readAll()));
		$this->assertEquals(2, count($route->getDBLogs(null, null, $firstTime, $secondTime)->readAll()));
		$this->assertEquals(1, count($route->getDBLogs(null, 'Prado\\*', $firstTime, $secondTime)->readAll()));
		$this->assertEquals(1, count($route->getDBLogs(TLogger::WARNING, 'Prado\\*', $firstTime, $secondTime)->readAll()));
		
		$this->assertEquals(1, $route->deleteDBLog(null, \Prado\TModule::class));
		$this->assertEquals(1, $route->deleteDBLog(null, null, $firstTime, $secondTime));
		$this->assertEquals(3, $route->deleteDBLog(TLogger::INFO | TLogger::NOTICE | TLogger::FATAL));
		
		$route->setRetainPeriod(0.00000001);
		$this->assertEquals(5, $route->getDBLogCount()); 
		
		$route->collectLogs($logger, true); // all and the "new" (but delayed) are deleted
		$this->assertEquals(0, $route->getDBLogCount());
		
	}
}

