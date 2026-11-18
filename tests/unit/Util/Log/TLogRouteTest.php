<?php

namespace Prado\Test\Unit\Util\Log;

use Prado\Util\Log\TLogger;
use Prado\Util\Log\TLogRouter;
use Prado\Util\Log\TBrowserLogRoute;
use Prado\Exceptions\TConfigurationException;
use Prado\Util\Log\TLogRoute;
use Prado\Test\Unit\PradoUnit;

class TTestLogRoute extends TLogRoute {
	protected function processLogs(array $logs, bool $final, array $meta)
	{
		
	}
}

/**
 * A TLogRoute whose application can be replaced, and that keeps what it processes.
 */
class TAppLogRoute extends TLogRoute
{
	public bool $useApp = false;

	public mixed $app = null;

	public array $processed = [];

	public function getApplication()
	{
		return $this->useApp ? $this->app : parent::getApplication();
	}

	protected function processLogs(array $logs, bool $final, array $meta)
	{
		$this->processed[] = [$logs, $final, $meta];
	}
}

class TLogRouteTest extends \PHPUnit\Framework\TestCase
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

	/**
	 * Returns the prefix of a log through a replaced application.
	 */
	private function prefixWithApp(?object $app, array $log, ?callable $callback = null): string
	{
		$route = new TAppLogRoute();
		if ($callback) {
			$route->setPrefixCallback($callback);
		}
		$route->useApp = true;
		$route->app = $app;
		try {
			return $route->getLogPrefix($log);
		} finally {
			$route->useApp = false;
		}
	}

	/**
	 * Returns an application stub with a request address, a user name, and a session.
	 */
	private function app(?string $ip, mixed $userName, ?string $sessionID): object
	{
		return new class ($ip, $userName, $sessionID) {
			public function __construct(public ?string $ip, public mixed $userName, public ?string $sessionID)
			{
			}

			public function getRequest()
			{
				return new class ($this->ip) {
					public function __construct(public ?string $ip)
					{
					}

					public function getUserHostAddress()
					{
						return $this->ip;
					}
				};
			}

			public function getUser()
			{
				if ($this->userName === false) {
					return null;
				}
				return new class ($this->userName) {
					public function __construct(public mixed $name)
					{
					}

					public function getName()
					{
						return $this->name;
					}
				};
			}

			public function getSession()
			{
				if ($this->sessionID === null) {
					return null;
				}
				return new class ($this->sessionID) {
					public function __construct(public string $id)
					{
					}

					public function getIsStarted()
					{
						return $this->id !== '';
					}

					public function getSessionID()
					{
						return $this->id;
					}
				};
			}
		};
	}

	public function testLogPrefixWithoutAnApplication()
	{
		$saved = $_SERVER['REMOTE_ADDR'] ?? null;
		$log = TLogEntryFactory::entry();
		try {
			unset($_SERVER['REMOTE_ADDR']);
			$this->assertSame('', $this->prefixWithApp(null, $log));

			$_SERVER['REMOTE_ADDR'] = '192.0.2.4';
			$this->assertSame('[192.0.2.4]', $this->prefixWithApp(null, $log));
			$this->assertSame('<[192.0.2.4]>', $this->prefixWithApp(null, $log, fn ($l, $p) => '<' . $p . '>'));
		} finally {
			if ($saved === null) {
				unset($_SERVER['REMOTE_ADDR']);
			} else {
				$_SERVER['REMOTE_ADDR'] = $saved;
			}
		}
	}

	public function testLogPrefixWithAnApplication()
	{
		$log = TLogEntryFactory::entry();
		$this->assertSame('[-][-][-]', $this->prefixWithApp($this->app(null, false, null), $log));
		$this->assertSame('[-][-][-]', $this->prefixWithApp($this->app(null, '', ''), $log));
		$this->assertSame('[192.0.2.4][admin][abc123]', $this->prefixWithApp($this->app('192.0.2.4', 'admin', 'abc123'), $log));

		$child = TLogEntryFactory::entry('m', TLogger::INFO, 'Cat', 0.5, 1.5, null, getmypid() + 1);
		$this->assertSame('[-][-][-][pid:' . (getmypid() + 1) . ']', $this->prefixWithApp($this->app(null, false, null), $child));
		$this->assertSame('+[-][-][-]', $this->prefixWithApp($this->app(null, false, null), $log, fn ($l, $p) => '+' . $p));
	}

	public function testSetPrefixCallbackRejectsANonCallable()
	{
		$route = new TTestLogRoute();
		try {
			$route->setPrefixCallback('not a function');
			$this->fail('A non-callable PrefixCallback throws TConfigurationException.');
		} catch (TConfigurationException $e) {
			$this->assertSame('logroute_bad_prefix_callback', $e->getErrorCode());
		}
		$this->assertNull($route->getPrefixCallback());
	}

	public function testLevelNamesAndValues()
	{
		$route = new TTestLogRoute();
		$this->assertSame('Warning', PradoUnit::invoke($route, 'getLevelName', TLogger::WARNING));
		$this->assertSame('Unknown', PradoUnit::invoke($route, 'getLevelName', 0x80));
		$this->assertSame(TLogger::WARNING, PradoUnit::invoke($route, 'getLevelValue', 'warning'));
		$this->assertSame(0, PradoUnit::invoke($route, 'getLevelValue', 'bogus'));
	}

	public function testSetLevelsIgnoresUnknownNames()
	{
		$route = new TTestLogRoute();
		$route->setLevels('bogus');
		$this->assertNull($route->getLevels());
		$route->setLevels(['warning', 'bogus']);
		$this->assertSame(TLogger::WARNING, $route->getLevels());
		$route->setLevels(0);
		$this->assertSame(0, $route->getLevels());
	}

	public function testSetCategoriesSkipsEmptyNames()
	{
		$route = new TTestLogRoute();
		$route->setCategories(' , ');
		$this->assertNull($route->getCategories());
		$route->setCategories('one');
		$this->assertSame(['one'], $route->getCategories());
	}

	public function testGetTime()
	{
		$route = new TTestLogRoute();
		$time = 1767323045.25;
		$this->assertSame(date('Y-m-d H:i:s', 1767323045), PradoUnit::invoke($route, 'getTime', $time));
		$route->setDisplaySubSeconds(true);
		$this->assertSame(date('Y-m-d H:i:s', 1767323045) . '.250000', PradoUnit::invoke($route, 'getTime', $time));
	}

	public function testGetLogColor()
	{
		[$r, $g, $b] = TLogRoute::getLogColor(1.0);
		$this->assertEqualsWithDelta(255, $r, 0.001);
		$this->assertLessThan(1, $b);

		[$r, $g, $b] = TLogRoute::getLogColor(0.5);
		$this->assertEqualsWithDelta(204, $g, 0.001);

		[$r, $g, $b] = TLogRoute::getLogColor(0.13);
		$this->assertEqualsWithDelta(255, $b, 0.001);
		$this->assertLessThan(1, $r);
	}

	public function testFormatLogMessage()
	{
		$route = new TTestLogRoute();
		$time = date('Y-m-d H:i:s', 1767323045);
		$prefix = $route->getLogPrefix(TLogEntryFactory::entry());

		$this->assertSame($time . ' ' . $prefix . '[Info] [Cat] text', $route->formatLogMessage(TLogEntryFactory::entry('text', TLogger::INFO, 'Cat')));
		$this->assertSame($time . ' ' . $prefix . '[Debug] [Cat] 3.5', $route->formatLogMessage(TLogEntryFactory::entry(3.5, TLogger::DEBUG, 'Cat')));

		$exception = new \LogicException('broken');
		$this->assertStringStartsWith($time . ' ' . $prefix . '[Error] [Cat] LogicException: broken', $route->formatLogMessage(TLogEntryFactory::entry($exception, TLogger::ERROR, 'Cat')));

		$traces = [['file' => '/a.php', 'line' => 3]];
		$this->assertSame($time . ' ' . $prefix . "[Info] [Cat] text\n    in /a.php:3", $route->formatLogMessage(TLogEntryFactory::entry('text', TLogger::INFO, 'Cat', 0.5, 1.5, $traces)));
	}

	public function testFilterLogsTimesRepeatedProfileEnds()
	{
		$route = new TTestLogRoute();
		$pid = getmypid();
		$logs = [
			['token', TLogger::PROFILE_BEGIN, 'Cat', 100.0, 0, null, null, $pid],
			['token', TLogger::PROFILE_END, 'Cat', 101.0, 0, null, null, $pid],
			['token', TLogger::PROFILE_END, 'Cat', 103.5, 0, null, null, $pid],
			['other', TLogger::PROFILE_END, 'Cat', 104.0, 0, null, null, $pid],
			['logged', TLogger::INFO | TLogger::LOGGED, 'Cat', 105.0, 0, null, null, $pid],
		];
		$route->filterLogs($logs);

		$this->assertCount(4, $logs);
		$this->assertSame('Profile Begin: token', $logs[0][TLogger::LOG_MESSAGE]);
		$this->assertSame(0, $logs[0]['delta']);
		$this->assertSame(1.0, $logs[1]['delta']);
		$this->assertSame(1.0, $logs[1]['total']);
		$this->assertSame(2.5, $logs[2]['delta']);
		$this->assertSame(3.5, $logs[2]['total']);
		$this->assertSame('Profile End: other', $logs[3][TLogger::LOG_MESSAGE]);
		$this->assertSame(1.0, $logs[3]['delta']);
	}

	public function testFormatLogMessageWithEmptyTraces()
	{
		$route = new TTestLogRoute();
		$this->assertStringEndsWith('[Info] [Cat] text', $route->formatLogMessage(TLogEntryFactory::entry('text', TLogger::INFO, 'Cat', 0.5, 1.5, [])));
	}

	public function testSetLevelsWithAnArray()
	{
		$route = new TTestLogRoute();
		$route->setLevels([]);
		$this->assertNull($route->getLevels());
		$route->setLevels(['bogus']);
		$this->assertNull($route->getLevels());
		$route->setLevels(['fatal']);
		$this->assertSame(TLogger::FATAL, $route->getLevels());
	}

	public function testCollectLogsHoldsUntilTheProcessInterval()
	{
		$route = new TAppLogRoute();
		$route->setProcessInterval(2);
		$logger = new TLogger(false);
		$logger->log('one', TLogger::INFO, 'Cat');
		$route->collectLogs($logger);
		$this->assertSame([], $route->processed);
		$this->assertSame(1, $route->getLogCount());

		$route->collectLogs(true);
		$this->assertCount(1, $route->processed);
		$this->assertTrue($route->processed[0][1]);
		$this->assertSame(0, $route->getLogCount());
		$this->assertSame(2, $route->getProcessInterval());
	}
}

