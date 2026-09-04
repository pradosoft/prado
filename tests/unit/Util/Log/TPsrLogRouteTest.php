<?php

use Prado\Exceptions\TConfigurationException;
use Prado\Prado;
use Prado\TModule;
use Prado\Util\Log\TLogger;
use Prado\Util\Log\TPsrLogger;
use Prado\Util\Log\TPsrLogRoute;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Psr\Log\LogLevel;

class TTestPsrCollector extends AbstractLogger
{
	public array $entries = [];

	public function log($level, string|\Stringable $message, array $context = []): void
	{
		$this->entries[] = [$level, (string) $message, $context];
	}
}

class TTestPsrLoggerModule extends TModule implements LoggerInterface
{
	use LoggerTrait;

	public array $entries = [];

	public function log($level, string|\Stringable $message, array $context = []): void
	{
		$this->entries[] = [$level, (string) $message, $context];
	}
}

class TPsrLogRouteTest extends PHPUnit\Framework\TestCase
{
	private ?TLogger $logger = null;
	private ?TPsrLogRoute $route = null;
	private ?TTestPsrCollector $collector = null;

	protected function setUp(): void
	{
		$this->logger = new TLogger(false);
		$this->route = new TPsrLogRoute();
		$this->collector = new TTestPsrCollector();
	}

	protected function tearDown(): void
	{
		$this->logger = null;
		$this->route = null;
		$this->collector = null;
	}

	public function testLoggerRequired(): void
	{
		$this->expectException(TConfigurationException::class);
		$this->route->getLogger();
	}

	public function testLoggerInstance(): void
	{
		$this->assertSame($this->route, $this->route->setLogger($this->collector));
		$this->assertSame($this->collector, $this->route->getLogger());
	}

	public function testLoggerClassName(): void
	{
		$this->route->setLogger(TTestPsrCollector::class);
		$logger = $this->route->getLogger();
		$this->assertInstanceOf(TTestPsrCollector::class, $logger);
		$this->assertSame($logger, $this->route->getLogger());
	}

	public function testLoggerModuleID(): void
	{
		$module = new TTestPsrLoggerModule();
		$id = 'psrLogRouteTestModule' . uniqid();
		Prado::getApplication()->setModule($id, $module);

		$this->route->setLogger($id);
		$this->assertSame($module, $this->route->getLogger());
	}

	public function testLoggerInvalid(): void
	{
		$this->route->setLogger('NoSuchPsrLoggerClass');
		$this->expectException(TConfigurationException::class);
		$this->route->getLogger();
	}

	public function testLoggerNotPsr(): void
	{
		$this->route->setLogger(stdClass::class);
		$this->expectException(TConfigurationException::class);
		$this->route->getLogger();
	}

	public function testLoggerRecursiveInstance(): void
	{
		$this->expectException(TConfigurationException::class);
		$this->route->setLogger(new TPsrLogger());
	}

	public function testLoggerRecursiveClass(): void
	{
		$this->route->setLogger(TPsrLogger::class);
		$this->expectException(TConfigurationException::class);
		$this->route->getLogger();
	}

	public function testFormatMessage(): void
	{
		$this->assertFalse($this->route->getFormatMessage());
		$this->assertSame($this->route, $this->route->setFormatMessage('true'));
		$this->assertTrue($this->route->getFormatMessage());
		$this->route->setFormatMessage(false);
		$this->assertFalse($this->route->getFormatMessage());
	}

	public function testProcessLogs(): void
	{
		$this->route->setLogger($this->collector);
		$exception = new RuntimeException('boom');

		$this->logger->log('first', TLogger::WARNING, 'Cat1', 'ctl1');
		$this->logger->log($exception, TLogger::ERROR, 'Cat2');
		$this->logger->log(['a' => 1], TLogger::INFO, 'Cat3');
		$this->logger->log('token', TLogger::PROFILE_BEGIN, 'Cat4');
		$this->logger->log('token', TLogger::PROFILE_END, 'Cat4');

		$this->route->collectLogs($this->logger, true);
		$this->assertEquals(0, $this->route->getLogCount());
		$entries = $this->collector->entries;
		$this->assertCount(5, $entries);

		[$level, $message, $context] = $entries[0];
		$this->assertEquals(LogLevel::WARNING, $level);
		$this->assertEquals('first', $message);
		$this->assertEquals('Cat1', $context[TPsrLogger::CONTEXT_CATEGORY]);
		$this->assertEquals(TLogger::WARNING, $context[TPsrLogger::CONTEXT_LEVEL]);
		$this->assertEquals('ctl1', $context[TPsrLogger::CONTEXT_CONTROL]);
		$this->assertEquals(getmypid(), $context[TPsrLogger::CONTEXT_PID]);
		$this->assertIsFloat($context[TPsrLogger::CONTEXT_TIME]);
		$this->assertIsInt($context[TPsrLogger::CONTEXT_MEMORY]);
		$this->assertIsString($context[TPsrLogger::CONTEXT_PREFIX]);
		$this->assertArrayHasKey(TPsrLogger::CONTEXT_DELTA, $context);
		$this->assertArrayHasKey(TPsrLogger::CONTEXT_TOTAL, $context);
		$this->assertArrayNotHasKey(TPsrLogger::CONTEXT_TRACES, $context);
		$this->assertArrayNotHasKey(TPsrLogger::CONTEXT_EXCEPTION, $context);

		[$level, $message, $context] = $entries[1];
		$this->assertEquals(LogLevel::ERROR, $level);
		$this->assertEquals('boom', $message);
		$this->assertSame($exception, $context[TPsrLogger::CONTEXT_EXCEPTION]);
		$this->assertArrayNotHasKey(TPsrLogger::CONTEXT_CONTROL, $context);

		[$level, $message] = $entries[2];
		$this->assertEquals(LogLevel::INFO, $level);
		$this->assertStringContainsString("'a' => 1", $message);

		$this->assertEquals(LogLevel::DEBUG, $entries[3][0]);
		$this->assertEquals('Profile Begin: token', $entries[3][1]);
		$this->assertEquals(LogLevel::DEBUG, $entries[4][0]);
		$this->assertEquals('Profile End: token', $entries[4][1]);
	}

	public function testProcessLogsFormatted(): void
	{
		$this->route->setLogger($this->collector);
		$this->route->setFormatMessage(true);
		$this->logger->log('formatted', TLogger::NOTICE, 'Cat');
		$this->route->collectLogs($this->logger, true);

		[$level, $message] = $this->collector->entries[0];
		$this->assertEquals(LogLevel::NOTICE, $level);
		$this->assertStringContainsString('[Notice] [Cat] formatted', $message);
	}

	public function testProcessLogsTraces(): void
	{
		$this->route->setLogger($this->collector);
		$this->logger->setTraceLevel(2);
		$this->logger->log('traced', TLogger::DEBUG, 'Cat');
		$this->route->collectLogs($this->logger, true);

		$context = $this->collector->entries[0][2];
		$this->assertArrayHasKey(TPsrLogger::CONTEXT_TRACES, $context);
		$this->assertIsArray($context[TPsrLogger::CONTEXT_TRACES]);
	}

	public function testRoundTrip(): void
	{
		$this->route->setLogger($this->collector);
		$this->logger->setTraceLevel(1);
		$exception = new RuntimeException('boom');
		$this->logger->log('plain', TLogger::WARNING, 'Cat1', 'ctl1');
		$this->logger->log($exception, TLogger::FATAL, 'Cat2');
		$source = $this->logger->getLogs();
		$this->route->collectLogs($this->logger, true);

		$target = new TLogger(false);
		$psr = new TPsrLogger($target);
		foreach ($this->collector->entries as [$level, $message, $context]) {
			$psr->log($level, $message, $context);
		}
		$this->assertEquals($source, $target->getLogs());
		$this->assertSame($exception, $target->getLogs()[1][TLogger::LOG_MESSAGE]);
	}

	public function testLevelsFilter(): void
	{
		$this->route->setLogger($this->collector);
		$this->route->setLevels('fatal');
		$this->assertEquals(TLogger::FATAL, $this->route->getLevels());

		$this->logger->log('kept', TLogger::FATAL, 'Cat');
		$this->logger->log('dropped', TLogger::INFO, 'Cat');
		$this->route->collectLogs($this->logger, true);

		$this->assertCount(1, $this->collector->entries);
		$this->assertEquals(LogLevel::CRITICAL, $this->collector->entries[0][0]);
	}
}
