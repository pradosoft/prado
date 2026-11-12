<?php

namespace Prado\Test\Unit\Util\Log;

use Prado\Util\Log\TLogger;
use Prado\Util\Log\TSysLogRoute;
use Prado\Util\Log\TDbLogRoute;
use Prado\Exceptions\TInvalidDataValueException;

class TTestDbLogRoute extends TDbLogRoute {
}

class TDbLogRouteTest extends \PHPUnit\Framework\TestCase
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

		$this->assertNull(TDbLogRoute::timespanToSeconds('bogus'));
		try {
			$route->setRetainPeriod('bogus');
			$this->fail('An invalid retain period throws TConfigurationException');
		} catch (\Prado\Exceptions\TConfigurationException $e) {
			$this->assertEquals('dblogroute_bad_retain_period', $e->getErrorCode());
		}
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
		$this->assertEquals(4, $route->getDBLogCount(null, 'Prado\\*, ~'.\Prado\TModule::class . ', ~' . self::class . ', '. TTestDbLogRoute::class));
		
		$this->assertEquals(7, $route->getDBLogCount(null, null, $firstTime));
		$this->assertEquals(5, $route->getDBLogCount(null, null, null, $secondTime));
		$this->assertEquals(2, $route->getDBLogCount(null, null, $firstTime, $secondTime));
		$this->assertEquals(1, $route->getDBLogCount(null, 'Prado\\*, ~Prado\\Test\\*', $firstTime, $secondTime));
		$this->assertEquals(1, $route->getDBLogCount(TLogger::WARNING, 'Prado\\*, ~Prado\\Test\\*', $firstTime, $secondTime));
		
		$this->assertEquals(1, count($route->getDBLogs(TLogger::WARNING)->readAll()));
		$this->assertEquals(2, count($route->getDBLogs(TLogger::WARNING | TLogger::INFO)->readAll()));
		$this->assertEquals(3, count($route->getDBLogs(TLogger::PROFILE)->readAll()));
		$this->assertEquals(1, count($route->getDBLogs(TLogger::PROFILE_BEGIN_SELECT)->readAll()));
		$this->assertEquals(2, count($route->getDBLogs(null, \Prado\TApplication::class)->readAll()));
		$this->assertEquals(8, count($route->getDBLogs(null, '!'.\Prado\TApplication::class)->readAll()));
		$this->assertEquals(8, count($route->getDBLogs(null, ['~'.\Prado\TApplication::class])->readAll()));
		$this->assertEquals(4, count($route->getDBLogs(null, 'Prado\\*, ~'.\Prado\TModule::class . ', ~' . self::class . ', '. TTestDbLogRoute::class)->readAll()));
		
		$this->assertEquals(7, count($route->getDBLogs(null, null, $firstTime)->readAll()));
		$this->assertEquals(5, count($route->getDBLogs(null, null, null, $secondTime)->readAll()));
		$this->assertEquals(2, count($route->getDBLogs(null, null, $firstTime, $secondTime)->readAll()));
		$this->assertEquals(1, count($route->getDBLogs(null, 'Prado\\*, ~Prado\\Test\\*', $firstTime, $secondTime)->readAll()));
		$this->assertEquals(1, count($route->getDBLogs(TLogger::WARNING, 'Prado\\*, ~Prado\\Test\\*', $firstTime, $secondTime)->readAll()));

		$rows = $route->getDBLogs(null, null, null, null, 'log_id DESC', '2')->readAll();
		$this->assertCount(2, $rows);
		$this->assertGreaterThan($rows[1]['log_id'], $rows[0]['log_id']);
		$this->assertCount(3, $route->getDBLogs(null, null, null, null, 'logtime, log_id ASC', '3 OFFSET 1')->readAll());
		
		$this->assertEquals(1, $route->deleteDBLog(null, \Prado\TModule::class));
		$this->assertEquals(1, $route->deleteDBLog(null, null, $firstTime, $secondTime));
		$this->assertEquals(3, $route->deleteDBLog(TLogger::INFO | TLogger::NOTICE | TLogger::FATAL));
		
		$route->setRetainPeriod(0.00000001);
		$this->assertEquals(5, $route->getDBLogCount()); 
		
		$route->collectLogs($logger, true); // all and the "new" (but delayed) are deleted
		$this->assertEquals(0, $route->getDBLogCount());
		
	}

	public function testLogTableNameRejectsSql()
	{
		$route = new TDbLogRoute();
		$route->setLogTableName('main.pradolog');
		$this->assertEquals('main.pradolog', $route->getLogTableName());
		$this->expectException(TInvalidDataValueException::class);
		$route->setLogTableName('pradolog; DROP TABLE users');
	}

	public function testGetDBLogsRejectsSqlInOrderAndLimit()
	{
		$route = new TDbLogRoute();
		$cases = [['log_id; DROP TABLE pradolog', ''], ['(SELECT 1)', ''], ['', '1; DROP TABLE pradolog'], ['', '1 UNION SELECT 1']];
		$rejected = 0;
		foreach ($cases as [$order, $limit]) {
			try {
				$route->getDBLogs(null, null, null, null, $order, $limit);
			} catch (TInvalidDataValueException $e) {
				$rejected++;
			}
		}
		$this->assertSame(count($cases), $rejected);
	}

	public function testPruneLogs()
	{
		if (!extension_loaded('pdo_sqlite')) {
			$this->markTestSkipped('The pdo_sqlite extension is not available.');
		}
		$route = new TSqliteFileDbLogRoute();
		try {
			$route->init(null);
			$now = microtime(true);
			$route->insertAt($now - 1000);
			$route->insertAt($now - 600);
			$route->insertAt($now - 10);

			$this->assertSame(0, $route->pruneLogs());
			$this->assertSame(0, $route->pruneLogs(0.0));
			$this->assertEquals(3, $route->getDBLogCount());

			$this->assertSame(1, $route->pruneLogs(800));
			$this->assertEquals(2, $route->getDBLogCount());

			$route->setRetainPeriod(300);
			$this->assertSame(1, $route->pruneLogs());
			$this->assertEquals(1, $route->getDBLogCount());
		} finally {
			$route->removeTestFile();
		}
	}

	public function testPruneOnFlush()
	{
		if (!extension_loaded('pdo_sqlite')) {
			$this->markTestSkipped('The pdo_sqlite extension is not available.');
		}
		$route = new TSqliteFileDbLogRoute();
		try {
			$this->assertTrue($route->getPruneOnFlush());
			$this->assertSame($route, $route->setPruneOnFlush('false'));
			$this->assertFalse($route->getPruneOnFlush());
			$route->init(null);
			$route->setRetainPeriod(300);
			$route->insertAt(microtime(true) - 1000);

			$logger = new TLogger();
			$logger->log('kept', TLogger::WARNING);
			$route->collectLogs($logger);
			$route->collectLogs(true);
			$this->assertEquals(2, $route->getDBLogCount());

			$route->setPruneOnFlush(true);
			$logger = new TLogger();
			$logger->log('pruning', TLogger::WARNING);
			$route->collectLogs($logger);
			$route->collectLogs(true);
			$this->assertEquals(2, $route->getDBLogCount());
			$this->assertEquals(0, $route->getDBLogCount(null, null, null, microtime(true) - 300));
		} finally {
			$route->removeTestFile();
		}
	}
}
