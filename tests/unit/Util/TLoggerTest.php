<?php

use Prado\Util\TLogger;

class TTestLogger extends TLogger {
	
	public function __construct()
	{
		parent::__construct(false);
	}
}

class TLoggerTest extends PHPUnit\Framework\TestCase
{
	protected function setUp(): void
	{
	}

	protected function tearDown(): void
	{
	}
	
	public function testFlushCount(): void
	{
		$logger = new TTestLogger();
	
		// Initially, the flush count should be 1000
		$this->assertEquals(1000, $logger->getFlushCount());
	
		// Set a flush count and test if it returns the expected value
		$logger->setFlushCount(10);
		$this->assertEquals(10, $logger->getFlushCount());
	
		// Set a different flush count and test again
		$logger->setFlushCount(5);
		$this->assertEquals(5, $logger->getFlushCount());
	}
	
	public function testTraceLevel(): void
	{
		$logger = new TTestLogger();
	
		// Initially, the flush count should be 10000
		$this->assertEquals(0, $logger->getTraceLevel());
	
		// Set a flush count and test if it returns the expected value
		$logger->setTraceLevel(10);
		$this->assertEquals(10, $logger->getTraceLevel());
	
		// Set a different flush count and test again
		$logger->setTraceLevel(5);
		$this->assertEquals(5, $logger->getTraceLevel());
		
		$logger->setTraceLevel(0);
	}
	
	public function testGetLogCount()
	{
		$logger = new TTestLogger();
		
		$called = false;
		$logger->onFlushLogs[] = function($sender, $param) use (&$called) {
			$called = true;	
		};
		
		// Log a message and check if it is added to the logs array
		$logger->log('Log message', TLogger::INFO);
		$logger->log('token1', TLogger::PROFILE_BEGIN);
		$logger->log('token2', TLogger::PROFILE_BEGIN);
		
		$this->assertEquals(3, $logger->getLogCount());
		$this->assertEquals(2, $logger->getLogCount(false));
		$this->assertEquals(1, $logger->getLogCount(true));
		$this->assertEquals(3, $logger->getLogCount(0));
		$this->assertEquals(0, $logger->getLoggedProfileLogCount());
		
		$this->assertFalse($called);
		$logger->onFlushLogs();
		$this->asserttrue($called);
		$called = false;
		$this->assertEquals(0, $logger->getLogCount());
		$this->assertEquals(2, $logger->getLogCount(false));
		$this->assertEquals(0, $logger->getLogCount(true));
		$this->assertEquals(2, $logger->getLogCount(0));
		$this->assertEquals(2, $logger->getLoggedProfileLogCount());
		
		$this->assertNull($logger->log('Log message', TLogger::INFO));
		$this->assertNull($logger->log('token3', TLogger::PROFILE_BEGIN));
		$logger->log('token4', TLogger::PROFILE_BEGIN);
		
		$this->assertEquals(3, $logger->getLogCount());
		$this->assertEquals(4, $logger->getLogCount(false));
		$this->assertEquals(1, $logger->getLogCount(true));
		$this->assertEquals(5, $logger->getLogCount(0));
		$this->assertEquals(2, $logger->getLoggedProfileLogCount());

		$this->assertFalse($called);
		
		$this->assertNotNull($logger->log('token3', TLogger::PROFILE_END));
	}
	
	public function testLog(): void
	{
		$logger = new TTestLogger();
	
		$called = false;
		$logger->onFlushLogs[] = function($sender, $param) use (&$called) {
			$called = true;	
		};
		// Log a message and check if it is added to the logs array
		$logger->log('Log message', TLogger::INFO, 'Category');
		$logs = $logger->getLogs();
		$this->assertEquals('Log message', $logs[0][0]);
		$this->assertEquals(TLogger::INFO, $logs[0][1]);
		$this->assertEquals('Category', $logs[0][2]);
		$this->assertNull($logs[0][5]);
		$this->assertNull($logs[0][6]);
		$this->assertEquals(getmypid(), $logs[0][7]);
		$this->assertTrue(Prado::getApplication()->onEndRequest->contains([$logger, 'onFlushLogs']));
		$this->assertFalse($called);
	
		// Log another message with a control and check if it is added correctly
		// Log a message with TraceLevel and check if it is added correctly
		$logger->deleteLogs();
		$logger->setTraceLevel(100);
		$control = new \Prado\Web\UI\WebControls\TButton();
		$control->setId('ctl1');
		$logger->log('Button clicked', TLogger::DEBUG, 'Category2', $control);
		$logs = $logger->getLogs();
		$this->assertEquals('Button clicked', $logs[0][0]);
		$this->assertEquals(TLogger::DEBUG, $logs[0][1]);
		$this->assertEquals('Category2', $logs[0][2]);
		$this->assertEquals($control->getClientId(), $logs[0][5]);
		$this->assertTrue(is_array($logs[0][6]));
		$this->assertGreaterThan(5, count($logs[0][6]));
		$this->assertEquals(getmypid(), $logs[0][7]);
		$logger->setTraceLevel(0);
		
		
		// Control is bad class and is set to null
		$logger->deleteLogs();
		$logger->log('token', TLogger::INFO, 'Category3', $this);
		$logs = $logger->getLogs();
		$this->assertEquals('token', $logs[0][0]);
		$this->assertEquals(TLogger::INFO, $logs[0][1]);
		$this->assertEquals('Category3', $logs[0][2]);
		$this->assertNull($logs[0][5]); // Control is null with bad $ctl
		$this->assertNull($logs[0][6]);
		$this->assertEquals(getmypid(), $logs[0][7]);	
	}
	
	public function testLog_ProfileBegin()
	{
		$logger = new TTestLogger();
		
		$called = false;
		$logger->onFlushLogs[] = function($sender, $param) use (&$called) {
			$called = true;	
		};
		$logger->setFlushCount(3);
		$this->assertEquals(0, $logger->getLogCount());
		$this->assertFalse($called);
		$logger->log('token1', TLogger::PROFILE_BEGIN);
		$this->assertFalse($called);
		$this->assertEquals(1, $logger->getLogCount());
		$this->assertEquals(1, $logger->getLogCount(false));
	}
	
	public function testLog_ProfileBeginMax()
	{
		$logger = new TTestLogger();
		$called = false;
		$logger->onFlushLogs[] = function($sender, $param) use (&$called) {
			$called = true;	
		};
		
		$logger->setFlushCount(3);
		$logger->log('token1', TLogger::PROFILE_BEGIN);
		$this->assertFalse($called);
		$this->assertEquals(1, $logger->getLogCount());
		$this->assertEquals(1, $logger->getLogCount(false));
			
		$logger->log('token1', TLogger::PROFILE_BEGIN);
		$this->assertFalse($called);
		$this->assertEquals(1, $logger->getLogCount());
		$this->assertEquals(1, $logger->getLogCount(false));
		
		$logs = $logger->getLogs();
		$this->assertEquals('token1', $logs[0][0]);
		$this->assertEquals(TLogger::PROFILE_BEGIN, $logs[0][1]);
		$this->assertEquals(TLoggerTest::class, $logs[0][2]);
		$this->assertNull($logs[0][5]);// ctl
		$this->assertNull($logs[0][6]);// traces
		$this->assertEquals(getmypid(), $logs[0][7]);	
		
		$logger->log('token2', TLogger::PROFILE_BEGIN);
		$this->assertFalse($called);
		$this->assertEquals(2, $logger->getLogCount());
		$this->assertEquals(2, $logger->getLogCount(false));
		
		$logger->log('token3', TLogger::PROFILE_BEGIN);
		$this->assertFalse($called);
		$this->assertEquals(1, $logger->getLogCount());
		$this->assertEquals(0, $logger->getLogCount(false));
		
		$logs = $logger->getLogs();
		$this->assertEquals(TLogger::WARNING, $logs[0][1]);
		$this->assertEquals(TLogger::class . '\Profiler', $logs[0][2]);
		$this->assertNull($logs[0][5]); // Control is null with bad $ctl
		$this->assertNull($logs[0][6]);
		$this->assertEquals(getmypid(), $logs[0][7]);	
	}
	
	public function testLog_ProfileEnd()
	{
		$logger = new TTestLogger();
		$called = false;
		$logger->onFlushLogs[] = function($sender, $param) use (&$called) {
			$called = true;	
		};
		
		$logger->log('token1', TLogger::PROFILE_BEGIN);
		$logger->log('token2', TLogger::PROFILE_BEGIN);
		$this->assertEquals(2, $logger->getLogCount());
		$this->assertEquals(2, $logger->getLogCount(false));
		
		$logger->log('message', TLogger::DEBUG);
		
		$logger->log('token1', TLogger::PROFILE_END);
		$this->assertEquals(4, $logger->getLogCount());
		$this->assertEquals(1, $logger->getLogCount(false));
		
		$logs = $logger->getLogs();
		$this->assertEquals('token1', $logs[0][0]);
		$this->assertEquals(TLogger::PROFILE_BEGIN, $logs[0][1]);
		$this->assertEquals('token2', $logs[1][0]);
		$this->assertEquals('message', $logs[2][0]);
		$this->assertEquals('token1', $logs[3][0]);
		$this->assertEquals(TLogger::PROFILE_END, $logs[3][1]);
		
		$logger->onFlushLogs();
		$this->assertEquals(0, $logger->getLogCount());
		$this->assertEquals(1, $logger->getLogCount(0));
		$this->assertEquals(0, $logger->getLogCount(true));
		$this->assertEquals(1, $logger->getLogCount(false));
		$logs = $logger->getLogs();
		$this->assertEquals('token2', $logs[0][0]);
		$this->assertEquals(TLogger::LOGGED, $logs[0][1] & TLogger::LOGGED);
		
		$logger->log('token2', TLogger::PROFILE_END);
		$this->assertEquals(0, $logger->getLogCount(false));
		$this->assertEquals(2, $logger->getLogCount());
		$this->assertEquals(2, $logger->getLogCount(0));
		$this->assertEquals(2, $logger->getLogCount(true));
	}
	
	public function testLog_Flush()
	{
		$logger = new TTestLogger();
		
		$called = false;
		$logger->onFlushLogs[] = function($sender, $param) use (&$called) {
			$called = true;	
		};
		$logger->setFlushCount(3);
		
		// Log a message and check if it is added to the logs array
		$logger->log('Log message', TLogger::INFO);
		$this->assertFalse($called);
		$logger->log('token1', TLogger::WARNING);
		$this->assertFalse($called);
		$logger->log('token2', TLogger::ERROR);
		$this->assertTrue($called);
	}
	
	public function testOnFlushLogs()
	{
		$logger = new TTestLogger();
		
		$called = 0;
		$final = null;
		$logger->onFlushLogs[] = function($sender, $param) use (&$called, &$final, $logger) {
			$final = $param;
			if (is_int($called)) {
				$called++;
				$this->assertEquals(1, $called);
				$sender->log('inner', TLogger::WARNING);
				$logger->onFlushLogs();
			} else {
				$called = true;
			}
		};
		
		$logger->onFlushLogs();  // Not calling due to no log items
		$this->assertEquals(0, $called);
		$this->assertNull($final);
		
		$logger->log('token1', TLogger::INFO);
		$logger->log('token2', TLogger::PROFILE_BEGIN);
		$logger->onFlushLogs();  // Calling due to no log items
		$this->assertFalse($final);
		
		$logs = $logger->getLogs();
		$this->assertEquals('token2', $logs[0][0]);
		$this->assertEquals(TLogger::LOGGED, $logs[0][1] & TLogger::LOGGED);
		$this->assertEquals('inner', $logs[1][0]);
		$this->assertEquals(0, $logs[1][1] & TLogger::LOGGED);
		
		$logger->log('token2', TLogger::PROFILE_END);
		
		$called = false;
		$final = null;
		$logger->onFlushLogs(true);
		$this->assertTrue($final);
		
		$logger->log('token1', TLogger::INFO);
		$final = null;
		$logger->onFlushLogs($logger, false);
		$this->assertFalse($final);
		
		$logger->log('token1', TLogger::INFO);
		$final = null;
		$logger->onFlushLogs($logger, true);
		$this->assertTrue($final);
		
		$logger->log('token1', TLogger::INFO);
		$final = null;
		$logger->onFlushLogs($logger, false);
		$this->assertFalse($final);
	}

	public function testGetLogs()
	{
		$logger = new TTestLogger();
		
		$logger->log('msg0', TLogger::INFO, \Prado\TApplication::class, 'page.div.ctl2');
		$logger->log('token1', TLogger::PROFILE_BEGIN, \Prado\Web\THttpRequest::class, 'page.ctl1');
		$middleTime = microtime(true);
		$logger->log('token2', TLogger::PROFILE_BEGIN);
		$logger->log('token1', TLogger::PROFILE_END, \Prado\TApplication::class, 'page.ctl1');
		$logger->log('msg1', TLogger::DEBUG, \Prado\TModule::class, 'page2.other.ctl3');
		
		$logs = $logger->getLogs();
		$this->assertEquals(5, count($logs));
		
		$logs = $logger->getLogs(TLogger::PROFILE_BEGIN_SELECT);
		$this->assertEquals(2, count($logs));
		$this->assertEquals(TLogger::PROFILE_BEGIN, $logs[0][1]);
		$this->assertEquals(TLogger::PROFILE_BEGIN, $logs[1][1]);
		
		$logs = $logger->getLogs(TLogger::PROFILE_END_SELECT);
		$this->assertEquals(1, count($logs));
		$this->assertEquals(TLogger::PROFILE_END, $logs[0][1]);
		
		$logs = $logger->getLogs(TLogger::PROFILE_BEGIN);
		$this->assertEquals(TLogger::PROFILE_BEGIN, $logs[0][1]);
		$this->assertEquals(TLogger::PROFILE_BEGIN, $logs[1][1]);
		$this->assertEquals(TLogger::PROFILE_END, $logs[2][1]);
			
		$logs = $logger->getLogs(TLogger::PROFILE_END);
		$this->assertEquals(TLogger::PROFILE_BEGIN, $logs[0][1]);
		$this->assertEquals(TLogger::PROFILE_BEGIN, $logs[1][1]);
		$this->assertEquals(TLogger::PROFILE_END, $logs[2][1]);
		
		$logs = $logger->getLogs(TLogger::DEBUG);
		$this->assertEquals(TLogger::DEBUG, $logs[0][1]);
		
		$logs = $logger->getLogs(null, 'Prado\\');
		$this->assertEquals(0, count($logs));
		
		$logs = $logger->getLogs(null, ['Prado\\*']);
		$this->assertEquals(4, count($logs));
		
		$logs = $logger->getLogs(null, ['Prado\\*', '!Prado\\Web\\*']);
		$this->assertEquals(3, count($logs));
		$this->assertEquals(\Prado\TApplication::class, $logs[0][2]);
		$this->assertEquals(\Prado\TApplication::class, $logs[1][2]);
		$this->assertEquals(\Prado\TModule::class, $logs[2][2]);
		
		$logs = $logger->getLogs(null, ['Prado\\*', '~Prado\\Web\\*']);
		$this->assertEquals(3, count($logs));
		$this->assertEquals(\Prado\TApplication::class, $logs[0][2]);
		$this->assertEquals(\Prado\TApplication::class, $logs[1][2]);
		$this->assertEquals(\Prado\TModule::class, $logs[2][2]);
		
		$logs = $logger->getLogs(null, 'Prado\\Web\\*');
		$this->assertEquals(1, count($logs));
		
		$logs = $logger->getLogs(null, null, 'page.ctl1');
		$this->assertEquals(2, count($logs));
		$this->assertEquals('page.ctl1', $logs[0][5]);
		$this->assertEquals('page.ctl1', $logs[1][5]);
		
		$logs = $logger->getLogs(null, null, ['page.']);
		$this->assertEquals(0, count($logs));
		
		$logs = $logger->getLogs(null, null, ['page.*']);
		$this->assertEquals(3, count($logs));
		$this->assertEquals('page.div.ctl2', $logs[0][5]);
		$this->assertEquals('page.ctl1', $logs[1][5]);
		$this->assertEquals('page.ctl1', $logs[2][5]);
		
		$logs = $logger->getLogs(null, null, ['!page.div.*']);
		$this->assertEquals(4, count($logs));
		
		$logs = $logger->getLogs(null, null, ['page.*', '!page.div.*']);
		$this->assertEquals(2, count($logs));
		$this->assertEquals('page.ctl1', $logs[0][5]);
		$this->assertEquals('page.ctl1', $logs[1][5]);
		
		$logs = $logger->getLogs(null, null, ['page.*', '~page.div.*']);
		$this->assertEquals(2, count($logs));
		$this->assertEquals('page.ctl1', $logs[0][5]);
		$this->assertEquals('page.ctl1', $logs[1][5]);
		
		$logs = $logger->getLogs(null, null, null, $middleTime);
		$this->assertEquals(2, count($logs));
		$this->assertEquals('msg0', $logs[0][0]);
		$this->assertEquals('token1', $logs[1][0]);
		$this->assertEquals(TLogger::PROFILE_BEGIN, $logs[1][1]);
	}
	
	public function testMergeLogs()
	{
		$logger = new TTestLogger();
		
		$logger->log('message1', TLogger::INFO);
		$newLogs = [
				['profiler1', TLogger::PROFILE_BEGIN, \Prado\TApplication::class, microtime(true), memory_get_usage(), 'page.ctl1', null, -10000],
				['messageChild', TLogger::INFO, \Prado\TModule::class, microtime(true), memory_get_usage(), 'page.ctl1', null, -10000],
				['profiler1', TLogger::PROFILE_BEGIN, \Prado\TApplication::class, microtime(true), memory_get_usage(), 'page.ctl1', null, -11111],
				['profiler1', TLogger::PROFILE_END, \Prado\TApplication::class, microtime(true), memory_get_usage(), 'page.ctl1', null, -11111],
			];
		$logger->mergeLogs($newLogs);
		
		$logs = $logger->getLogs();
		$this->assertEquals(5, count($logs));
		$this->assertEquals('message1', $logs[0][0]);
		$this->assertEquals(TLogger::INFO, $logs[0][1]);
		$this->assertEquals(getmypid(), $logs[0][7]);
		
		$this->assertEquals('profiler1', $logs[1][0]);
		$this->assertEquals(TLogger::PROFILE_BEGIN, $logs[1][1]);
		$this->assertEquals(-10000, $logs[1][7]);
		
		$this->assertEquals('messageChild', $logs[2][0]);
		$this->assertEquals(TLogger::INFO, $logs[2][1]);
		$this->assertEquals(-10000, $logs[2][7]);
		
		$this->assertEquals('profiler1', $logs[3][0]);
		$this->assertEquals(TLogger::PROFILE_BEGIN, $logs[3][1]);
		$this->assertEquals(-11111, $logs[3][7]);
		
		$this->assertEquals('profiler1', $logs[4][0]);
		$this->assertEquals(TLogger::PROFILE_END, $logs[4][1]);
		$this->assertEquals(-11111, $logs[4][7]);
		
		// getLogs with pid
		$logs = $logger->getLogs(null, null, null, null, -1);
		$this->assertEquals(0, count($logs));
		
		$logs = $logger->getLogs(null, null, null, null, -10000);
		$this->assertEquals('profiler1', $logs[0][0]);
		$this->assertEquals(TLogger::PROFILE_BEGIN, $logs[0][1]);
		$this->assertEquals(-10000, $logs[0][7]);
		
		$this->assertEquals('messageChild', $logs[1][0]);
		$this->assertEquals(TLogger::INFO, $logs[1][1]);
		$this->assertEquals(-10000, $logs[1][7]);
		
		//deleteLogs with pid
		$logs = $logger->deleteLogs(null, null, null, null, -11111);
		
		$logs = $logger->getLogs();
		$this->assertEquals('message1', $logs[0][0]);
		$this->assertEquals(getmypid(), $logs[0][7]);
		
		$this->assertEquals('profiler1', $logs[1][0]);
		$this->assertEquals(TLogger::PROFILE_BEGIN, $logs[1][1]);
		$this->assertEquals(-10000, $logs[1][7]);
		
		$this->assertEquals('messageChild', $logs[2][0]);
		$this->assertEquals(-10000, $logs[2][7]);
	}
	
	public function testDeleteLogs()
	{
		$logger = new TTestLogger();
		
		$logger->log('token1', TLogger::DEBUG);
		$logger->log('token2', TLogger::INFO);
		$logger->log('token3', TLogger::NOTICE);
		$middleTime = microtime(true);
		$logger->log('token4', TLogger::WARNING, null, 'page.ctl1');
		$logger->log('token5', TLogger::ERROR, null, 'page.div.ctl2');
		$logger->log('token6', TLogger::ALERT, null, 'page.ctl3');
		$logger->log('token7', TLogger::FATAL, \Prado\TApplication::class);
		$logger->log('token8', TLogger::PROFILE);
		$logger->log('token9', TLogger::PROFILE_BEGIN);
		$logger->log('token10', TLogger::PROFILE_BEGIN);
		$logger->log('token10', TLogger::PROFILE_END);
		
		$this->assertEquals(11, $logger->getLogCount());
		$this->assertEquals(1, count($logger->getLogs(TLogger::DEBUG)));
		
		$this->assertEquals(1, count($logger->deleteLogs(TLogger::DEBUG)));
		$this->assertEquals(0, count($logger->getLogs(TLogger::DEBUG)));
		$this->assertEquals(10, $logger->getLogCount());
		
		
		$this->assertEquals(1, count($logger->deleteLogs(TLogger::PROFILE_BEGIN_SELECT)));
		$this->assertEquals(1, count($logger->getLogs(TLogger::PROFILE_BEGIN_SELECT)));
		$this->assertEquals(9, $logger->getLogCount());
		
		$this->assertEquals(2, count($logger->deleteLogs(TLogger::PROFILE)));
		$this->assertEquals(1, count($logger->getLogs(TLogger::PROFILE)));
		$this->assertEquals(7, $logger->getLogCount());
		
		$this->assertEquals(1, count($logger->getLogs(null, \Prado\TApplication::class)));
		$this->assertEquals(1, count($logger->deleteLogs(null, \Prado\TApplication::class)));
		$this->assertEquals(0, count($logger->getLogs(null, \Prado\TApplication::class)));
		$this->assertEquals(6, $logger->getLogCount());
		
		
		$this->assertEquals(1, count($logger->getLogs(null, null, 'page.ctl3')));
		$this->assertEquals(1, count($logger->deleteLogs(null, null, 'page.ctl3')));
		$this->assertEquals(1, count($logger->getLogs(null, null, 'page.ctl1')));
		$this->assertEquals(2, count($logger->getLogs(null, null, ['page.*'])));
		$this->assertEquals(1, count($logger->getLogs(null, null, ['page.*', '!page.div.*'])));
		$this->assertEquals(5, $logger->getLogCount());
		
		$this->assertEquals(1, count($logger->deleteLogs(null, null, ['page.*', '!page.div.*'])));
		$this->assertEquals(1, count($logger->getLogs(null, null, 'page.div.ctl2')));
		
		$this->assertEquals(2, count($logger->getLogs(null, null, null, $middleTime)));
		$this->assertEquals(2, count($logger->deleteLogs(null, null, null, $middleTime)));
		$this->assertEquals(2, $logger->getLogCount());
		
		$logs = $logger->getLogs();
		$this->assertEquals('token5', $logs[0][0]); // only remaining log item
		$this->assertEquals('token9', $logs[1][0]); // profile begin without end
	}
	
	public function testDeleteProfileLogs()
	{
		$logger = new TTestLogger();
		
		$logger->log('token', TLogger::PROFILE_BEGIN);
		
		$this->assertEquals(1, $logger->getLogCount());
		$this->assertEquals(1, $logger->getLogCount(false));
		
		$logs = $logger->deleteProfileLogs();
		
		$this->assertEquals(0, $logger->getLogCount());
		$this->assertEquals(0, $logger->getLogCount(false));
		
		$this->assertEquals(1, count($logs));
		$this->assertEquals('token', $logs[0][0]);
	}
}
