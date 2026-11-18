<?php

namespace Prado\Test\Unit\Util\Log;

use Prado\Util\Log\TLogger;
use Prado\Util\Log\TSysLogRoute;
use Prado\Exceptions\TConfigurationException;
use Prado\Test\Unit\PradoUnit;

class TTestSysLogRoute extends TSysLogRoute {
}

/**
 * A TSysLogRoute that records its entries instead of writing the system log.
 */
class TRecordingSysLogRoute extends TSysLogRoute
{
	public array $entries = [];

	protected function writeSysLog(array $entries): void
	{
		$this->entries = $entries;
	}
}

class TSysLogRouteTest extends \PHPUnit\Framework\TestCase
{
	protected function setUp(): void
	{
	}

	protected function tearDown(): void
	{
	}
	
	public function testSysLogFlags()
	{
		$route = new TTestSysLogRoute();
		
		$this->assertEquals(LOG_ODELAY | LOG_PID,  $route->getSysLogFlags());
		
		$route->setSysLogFlags(LOG_PERROR);
		$this->assertEquals(LOG_PERROR,  $route->getSysLogFlags());
		
		$route->setSysLogFlags(null);
		$this->assertEquals(LOG_ODELAY | LOG_PID,  $route->getSysLogFlags());
		

		$route->setSysLogFlags(['log_Cons', ' log_ndelay']);
		$this->assertEquals(LOG_CONS | LOG_NDELAY,  $route->getSysLogFlags());
		
		$route->setSysLogFlags('log_Cons | Log_Pid , LOG_PERROR');
		$this->assertEquals(LOG_CONS | LOG_PID | LOG_PERROR,  $route->getSysLogFlags());
		
		$route->setSysLogFlags('LOG_PERROR');
		$this->assertEquals(LOG_PERROR,  $route->getSysLogFlags());
		
		$route->setSysLogFlags('log_odelay');
		$this->assertEquals(LOG_ODELAY,  $route->getSysLogFlags());
		
		$this->expectException(TConfigurationException::class);
		$route->setSysLogFlags(~LOG_PERROR);
	}
	
	public function testSysLogFacilities()
	{
		$route = new TTestSysLogRoute();
		
		$this->assertEquals(LOG_USER, $route->getFacility());
		
		if (\Prado\Util\Helpers\TProcessHelper::isSystemWindows()) {
			return;
		}
		
		$route->setFacility('LOG_CRON');
		$this->assertEquals(LOG_CRON, $route->getFacility());
		
		$route->setFacility('LOG_DAEMON');
		$this->assertEquals(LOG_DAEMON, $route->getFacility());
		
		$route->setFacility('LOG_KERN');
		$this->assertEquals(LOG_KERN, $route->getFacility());
		
		$route->setFacility('LOG_LOCAL0');
		$this->assertEquals(LOG_LOCAL0, $route->getFacility());
		
		$route->setFacility('LOG_LOCAL1');
		$this->assertEquals(LOG_LOCAL1, $route->getFacility());
		
		$route->setFacility('LOG_LOCAL2');
		$this->assertEquals(LOG_LOCAL2, $route->getFacility());
		
		$route->setFacility('LOG_LOCAL3');
		$this->assertEquals(LOG_LOCAL3, $route->getFacility());
		
		$route->setFacility('LOG_LOCAL4');
		$this->assertEquals(LOG_LOCAL4, $route->getFacility());
		
		$route->setFacility('LOG_LOCAL5');
		$this->assertEquals(LOG_LOCAL5, $route->getFacility());
		
		$route->setFacility('LOG_LOCAL6');
		$this->assertEquals(LOG_LOCAL6, $route->getFacility());
		
		$route->setFacility('LOG_LOCAL7');
		$this->assertEquals(LOG_LOCAL7, $route->getFacility());
		
		$route->setFacility('LOG_LPR');
		$this->assertEquals(LOG_LPR, $route->getFacility());
		
		$route->setFacility('LOG_MAIL');
		$this->assertEquals(LOG_MAIL, $route->getFacility());
		
		$route->setFacility('LOG_NEWS');
		$this->assertEquals(LOG_NEWS, $route->getFacility());
		
		$route->setFacility('LOG_SYSLOG');
		$this->assertEquals(LOG_SYSLOG, $route->getFacility());
		
		$route->setFacility('LOG_USER');
		$this->assertEquals(LOG_USER, $route->getFacility());
		
		$route->setFacility('LOG_UUCP');
		$this->assertEquals(LOG_UUCP, $route->getFacility());
		
		$this->expectException(TConfigurationException::class);
		$route->setSysLogFlags(~LOG_USER);
	}

	public function testSysLogPrefix()
	{
		$route = new TTestSysLogRoute();
		$this->assertFalse($route->getSysLogPrefix());
		$route->setSysLogPrefix('myapp');
		$this->assertSame('myapp', $route->getSysLogPrefix());
		$route->setSysLogPrefix('');
		$this->assertFalse($route->getSysLogPrefix());
	}

	public function testSetFacilityInteger()
	{
		$route = new TTestSysLogRoute();
		$route->setFacility(LOG_MAIL);
		$this->assertSame(LOG_MAIL, $route->getFacility());

		$route->setFacility(LOG_AUTH);
		$this->assertSame(defined('LOG_AUTHPRIV') ? LOG_AUTHPRIV : LOG_AUTH, $route->getFacility());

		$route->setFacility(' log_auth ');
		$this->assertSame(defined('LOG_AUTHPRIV') ? LOG_AUTHPRIV : LOG_AUTH, $route->getFacility());

		try {
			$route->setFacility(0x7);
			$this->fail('An unknown facility number throws TConfigurationException.');
		} catch (TConfigurationException $e) {
			$this->assertSame('syslogroute_bad_facility', $e->getErrorCode());
			$this->assertStringContainsString('0x7', $e->getMessage());
		}
	}

	public function testSetFacilityUnknownNameThrows()
	{
		$route = new TTestSysLogRoute();
		try {
			$route->setFacility('log_bogus');
			$this->fail('An unknown facility name throws TConfigurationException.');
		} catch (TConfigurationException $e) {
			$this->assertSame('syslogroute_bad_facility', $e->getErrorCode());
			$this->assertStringContainsString('LOG_BOGUS', $e->getMessage());
		}
		$this->assertSame(LOG_USER, $route->getFacility());
	}

	public function testProcessLogs()
	{
		$route = new TRecordingSysLogRoute();
		PradoUnit::invoke($route, 'processLogs', [
			TLogEntryFactory::entry('warn', TLogger::WARNING, 'Cat\\A'),
			TLogEntryFactory::entry(['k' => 'v'], TLogger::FATAL, 'Cat\\B'),
		], true, []);

		$this->assertCount(2, $route->entries);
		$this->assertSame(LOG_WARNING, $route->entries[0][0]);
		$this->assertMatchesRegularExpression('/^\\[[^]]*\\]\\[[^]]*\\]\\[[^]]*\\]\\[Warning\\]\\[Cat\\\\A\\] warn$/', $route->entries[0][1]);
		$this->assertSame(LOG_CRIT, $route->entries[1][0]);
		$this->assertStringEndsWith("[Fatal][Cat\\B] array\n(\n    'k' => 'v'\n)", $route->entries[1][1]);
	}

	public function testWriteSysLogOpensAndClosesTheLog()
	{
		$route = new TTestSysLogRoute();
		$route->setSysLogPrefix('prado-test');
		$this->assertNull(PradoUnit::invoke($route, 'writeSysLog', []));
	}

	public function testTranslateLogLevel()
	{
		$expected = [
			TLogger::PROFILE => LOG_DEBUG,
			TLogger::PROFILE_BEGIN => LOG_DEBUG,
			TLogger::PROFILE_END => LOG_DEBUG,
			TLogger::DEBUG => LOG_DEBUG,
			TLogger::INFO => LOG_INFO,
			TLogger::NOTICE => LOG_NOTICE,
			TLogger::WARNING => LOG_WARNING,
			TLogger::ERROR => LOG_ERR,
			TLogger::ALERT => LOG_ALERT,
			TLogger::FATAL => LOG_CRIT,
			0x80 => LOG_INFO,
		];
		foreach ($expected as $level => $priority) {
			$this->assertSame($priority, PradoUnit::invoke(TSysLogRoute::class, 'translateLogLevel', $level), 'level 0x' . dechex($level));
		}
	}

	public function testProcessLogsWithoutLogs()
	{
		$route = new TRecordingSysLogRoute();
		$route->entries = [['x', 'y']];
		PradoUnit::invoke($route, 'processLogs', [], true, []);
		$this->assertSame([], $route->entries);
	}

	public function testSysLogFlagsRejectUnknownNames()
	{
		$route = new TTestSysLogRoute();
		$route->setSysLogFlags(LOG_PERROR);
		foreach (['bogus', 'log_pid | bogus', ['log_pid', 'bogus']] as $value) {
			try {
				$route->setSysLogFlags($value);
				$this->fail('An unknown flag name throws TConfigurationException.');
			} catch (TConfigurationException $e) {
				$this->assertSame('syslogroute_bad_flags', $e->getErrorCode());
				$this->assertStringContainsString('BOGUS', $e->getMessage());
			}
			$this->assertSame(LOG_PERROR, $route->getSysLogFlags());
		}
	}

	public function testSysLogFlagsSkipEmptyNames()
	{
		$route = new TTestSysLogRoute();
		$route->setSysLogFlags('log_pid, ');
		$this->assertSame(LOG_PID, $route->getSysLogFlags());
		$route->setSysLogFlags(['log_pid', '']);
		$this->assertSame(LOG_PID, $route->getSysLogFlags());
		$route->setSysLogFlags([]);
		$this->assertSame(0, $route->getSysLogFlags());
	}
}

