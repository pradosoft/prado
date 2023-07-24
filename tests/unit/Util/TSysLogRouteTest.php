<?php

use Prado\Util\TLogger;
use Prado\Util\TSysLogRoute;

class TTestSysLogRoute extends TSysLogRoute {
}

class TSysLogRouteTest extends PHPUnit\Framework\TestCase
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
}

