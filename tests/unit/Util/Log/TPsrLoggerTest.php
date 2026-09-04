<?php

use Prado\ISingleton;
use Prado\Prado;
use Prado\Util\Log\TLogger;
use Prado\Util\Log\TPsrLogger;
use Prado\Web\UI\TControl;
use Psr\Log\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class TTestPsrLoggerCaller
{
	public function warn(TPsrLogger $logger, string $message): void
	{
		$logger->warning($message);
	}
}

class TTestPsrLoggerStringable
{
	public function __toString(): string
	{
		return 'stringable';
	}
}

class TPsrLoggerTest extends PHPUnit\Framework\TestCase
{
	private ?TLogger $logger = null;
	private ?TPsrLogger $psr = null;

	protected function setUp(): void
	{
		$this->logger = new TLogger(false);
		$this->psr = new TPsrLogger($this->logger, 'TestCategory');
	}

	protected function tearDown(): void
	{
		$this->logger = null;
		$this->psr = null;
		TPsrLogger::setSingleton(null);
	}

	public function testSingleton(): void
	{
		TPsrLogger::setSingleton(null);
		$this->assertInstanceOf(ISingleton::class, $this->psr);
		$this->assertNull(TPsrLogger::singleton(false));

		$instance = TPsrLogger::singleton();
		$this->assertInstanceOf(TPsrLogger::class, $instance);
		$this->assertSame($instance, TPsrLogger::singleton());
		$this->assertSame($instance, TPsrLogger::singleton(false));
		$this->assertSame(Prado::getLogger(), $instance->getLogger());
		$this->assertNull($instance->getCategory());

		TPsrLogger::setSingleton($this->psr);
		$this->assertSame($this->psr, TPsrLogger::singleton());

		TPsrLogger::setSingleton(null);
		$fresh = TPsrLogger::singleton();
		$this->assertNotSame($instance, $fresh);
		$this->assertNotSame($this->psr, $fresh);
	}

	public function testConstructAndProperties(): void
	{
		$this->assertInstanceOf(LoggerInterface::class, $this->psr);
		$this->assertSame($this->logger, $this->psr->getLogger());
		$this->assertEquals('TestCategory', $this->psr->getCategory());

		$psr = new TPsrLogger();
		$this->assertSame(Prado::getLogger(), $psr->getLogger());
		$this->assertNull($psr->getCategory());

		$this->assertSame($psr, $psr->setLogger($this->logger));
		$this->assertSame($this->logger, $psr->getLogger());
		$psr->setLogger(null);
		$this->assertSame(Prado::getLogger(), $psr->getLogger());

		$this->assertSame($psr, $psr->setCategory('Other'));
		$this->assertEquals('Other', $psr->getCategory());
	}

	public function testToPradoLevel(): void
	{
		$this->assertEquals(TLogger::FATAL, TPsrLogger::toPradoLevel(LogLevel::EMERGENCY));
		$this->assertEquals(TLogger::ALERT, TPsrLogger::toPradoLevel(LogLevel::ALERT));
		$this->assertEquals(TLogger::FATAL, TPsrLogger::toPradoLevel(LogLevel::CRITICAL));
		$this->assertEquals(TLogger::ERROR, TPsrLogger::toPradoLevel(LogLevel::ERROR));
		$this->assertEquals(TLogger::WARNING, TPsrLogger::toPradoLevel(LogLevel::WARNING));
		$this->assertEquals(TLogger::NOTICE, TPsrLogger::toPradoLevel(LogLevel::NOTICE));
		$this->assertEquals(TLogger::INFO, TPsrLogger::toPradoLevel(LogLevel::INFO));
		$this->assertEquals(TLogger::DEBUG, TPsrLogger::toPradoLevel(LogLevel::DEBUG));

		$this->assertEquals(TLogger::ERROR, TPsrLogger::toPradoLevel(' Error '));
		$this->assertEquals(TLogger::INFO, TPsrLogger::toPradoLevel(TLogger::INFO));
		$this->assertEquals(TLogger::PROFILE_BEGIN, TPsrLogger::toPradoLevel(TLogger::PROFILE_BEGIN));
		$this->assertEquals(TLogger::PROFILE_END, TPsrLogger::toPradoLevel(TLogger::PROFILE_END));
	}

	public function testToPradoLevelInvalidName(): void
	{
		$this->expectException(InvalidArgumentException::class);
		TPsrLogger::toPradoLevel('fatal');
	}

	public function testToPradoLevelInvalidInt(): void
	{
		$this->expectException(InvalidArgumentException::class);
		TPsrLogger::toPradoLevel(0x1000);
	}

	public function testToPradoLevelInvalidType(): void
	{
		$this->expectException(InvalidArgumentException::class);
		TPsrLogger::toPradoLevel(null);
	}

	public function testToPsrLevel(): void
	{
		$this->assertEquals(LogLevel::CRITICAL, TPsrLogger::toPsrLevel(TLogger::FATAL));
		$this->assertEquals(LogLevel::ALERT, TPsrLogger::toPsrLevel(TLogger::ALERT));
		$this->assertEquals(LogLevel::ERROR, TPsrLogger::toPsrLevel(TLogger::ERROR));
		$this->assertEquals(LogLevel::WARNING, TPsrLogger::toPsrLevel(TLogger::WARNING));
		$this->assertEquals(LogLevel::NOTICE, TPsrLogger::toPsrLevel(TLogger::NOTICE));
		$this->assertEquals(LogLevel::INFO, TPsrLogger::toPsrLevel(TLogger::INFO));
		$this->assertEquals(LogLevel::DEBUG, TPsrLogger::toPsrLevel(TLogger::DEBUG));
		$this->assertEquals(LogLevel::DEBUG, TPsrLogger::toPsrLevel(TLogger::PROFILE));
		$this->assertEquals(LogLevel::DEBUG, TPsrLogger::toPsrLevel(TLogger::PROFILE_BEGIN));
		$this->assertEquals(LogLevel::DEBUG, TPsrLogger::toPsrLevel(TLogger::PROFILE_END));
		$this->assertEquals(LogLevel::DEBUG, TPsrLogger::toPsrLevel(0x1000));
	}

	public function testInterpolate(): void
	{
		$this->assertEquals('plain', TPsrLogger::interpolate('plain', ['a' => 'b']));
		$this->assertEquals('user bob has 3 items', TPsrLogger::interpolate('user {name} has {count} items', ['name' => 'bob', 'count' => 3]));
		$this->assertEquals('null: , true: 1, false: ', TPsrLogger::interpolate('null: {n}, true: {t}, false: {f}', ['n' => null, 't' => true, 'f' => false]));
		$this->assertEquals('stringable', TPsrLogger::interpolate('{s}', ['s' => new TTestPsrLoggerStringable()]));
		$this->assertEquals('{missing}', TPsrLogger::interpolate('{missing}', []));
		$this->assertEquals('[1,"a"]', TPsrLogger::interpolate('{arr}', ['arr' => [1, 'a']]));
		$this->assertEquals('[object stdClass]', TPsrLogger::interpolate('{obj}', ['obj' => new stdClass()]));
		$date = new DateTimeImmutable('2026-09-03T12:00:00+00:00');
		$this->assertEquals('2026-09-03T12:00:00+00:00', TPsrLogger::interpolate('{d}', ['d' => $date]));
	}

	public function testLog(): void
	{
		$this->psr->log(LogLevel::ERROR, 'failed {what}', ['what' => 'hard']);
		$logs = $this->logger->getLogs();
		$this->assertCount(1, $logs);
		$this->assertEquals('failed hard', $logs[0][TLogger::LOG_MESSAGE]);
		$this->assertEquals(TLogger::ERROR, $logs[0][TLogger::LOG_LEVEL]);
		$this->assertEquals('TestCategory', $logs[0][TLogger::LOG_CATEGORY]);
		$this->assertNull($logs[0][TLogger::LOG_CONTROL]);

		$this->psr->log(TLogger::PROFILE_BEGIN, 'token');
		$logs = $this->logger->getLogs();
		$this->assertEquals(TLogger::PROFILE_BEGIN, $logs[1][TLogger::LOG_LEVEL]);
	}

	public function testLogInvalidLevel(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->psr->log('unknown', 'message');
	}

	public function testLogLevelMethods(): void
	{
		$this->psr->emergency('e');
		$this->psr->alert('a');
		$this->psr->critical('c');
		$this->psr->error('er');
		$this->psr->warning('w');
		$this->psr->notice('n');
		$this->psr->info('i');
		$this->psr->debug('d');
		$levels = array_column($this->logger->getLogs(), TLogger::LOG_LEVEL);
		$this->assertEquals([
			TLogger::FATAL, TLogger::ALERT, TLogger::FATAL, TLogger::ERROR,
			TLogger::WARNING, TLogger::NOTICE, TLogger::INFO, TLogger::DEBUG,
		], $levels);
	}

	public function testLogContextCategoryAndControl(): void
	{
		$control = new TControl();
		$control->setID('ctl1');
		$this->psr->info('m1', [TPsrLogger::CONTEXT_CATEGORY => 'Custom', TPsrLogger::CONTEXT_CONTROL => $control]);
		$this->psr->info('m2', [TPsrLogger::CONTEXT_CONTROL => 'clientid']);
		$this->psr->info('m3', [TPsrLogger::CONTEXT_CONTROL => 42]);
		$logs = $this->logger->getLogs();
		$this->assertEquals('Custom', $logs[0][TLogger::LOG_CATEGORY]);
		$this->assertEquals($control->getClientID(), $logs[0][TLogger::LOG_CONTROL]);
		$this->assertEquals('TestCategory', $logs[1][TLogger::LOG_CATEGORY]);
		$this->assertEquals('clientid', $logs[1][TLogger::LOG_CONTROL]);
		$this->assertNull($logs[2][TLogger::LOG_CONTROL]);
	}

	public function testLogContextException(): void
	{
		$exception = new RuntimeException('boom');
		$this->psr->error('failed', [TPsrLogger::CONTEXT_EXCEPTION => $exception]);
		$this->psr->error('ignored', [TPsrLogger::CONTEXT_EXCEPTION => 'not a throwable']);
		$this->psr->error('boom', [TPsrLogger::CONTEXT_EXCEPTION => $exception]);
		$logs = $this->logger->getLogs();
		$this->assertEquals('failed' . "\n" . $exception, $logs[0][TLogger::LOG_MESSAGE]);
		$this->assertEquals('ignored', $logs[1][TLogger::LOG_MESSAGE]);
		$this->assertSame($exception, $logs[2][TLogger::LOG_MESSAGE]);
	}

	public function testLogContextLevelOverride(): void
	{
		$this->psr->info('m', [TPsrLogger::CONTEXT_LEVEL => TLogger::ALERT]);
		$this->assertEquals(TLogger::ALERT, $this->logger->getLogs()[0][TLogger::LOG_LEVEL]);

		$this->expectException(InvalidArgumentException::class);
		$this->psr->info('m', [TPsrLogger::CONTEXT_LEVEL => 0x1000]);
	}

	public function testLogContextEntryDecoding(): void
	{
		$traces = [['file' => 'a.php', 'line' => 1]];
		$this->psr->notice('decoded', [
			TPsrLogger::CONTEXT_TIME => 1234.5,
			TPsrLogger::CONTEXT_MEMORY => 4096,
			TPsrLogger::CONTEXT_PID => 999,
			TPsrLogger::CONTEXT_TRACES => $traces,
			TPsrLogger::CONTEXT_CONTROL => 'ctl',
			TPsrLogger::CONTEXT_PREFIX => '[prefix]',
			TPsrLogger::CONTEXT_DELTA => 0.1,
			TPsrLogger::CONTEXT_TOTAL => 0.2,
		]);
		$log = $this->logger->getLogs()[0];
		$this->assertEquals('decoded', $log[TLogger::LOG_MESSAGE]);
		$this->assertEquals(TLogger::NOTICE, $log[TLogger::LOG_LEVEL]);
		$this->assertEquals('TestCategory', $log[TLogger::LOG_CATEGORY]);
		$this->assertSame(1234.5, $log[TLogger::LOG_TIME]);
		$this->assertSame(4096, $log[TLogger::LOG_MEMORY]);
		$this->assertEquals('ctl', $log[TLogger::LOG_CONTROL]);
		$this->assertSame($traces, $log[TLogger::LOG_TRACES]);
		$this->assertSame(999, $log[TLogger::LOG_PID]);
		$this->assertArrayNotHasKey('delta', $log);
		$this->assertCount(8, $log);
	}

	public function testLogContextEntryDecodingPartial(): void
	{
		$before = microtime(true);
		$this->psr->debug('partial', [TPsrLogger::CONTEXT_PID => 'bad', TPsrLogger::CONTEXT_MEMORY => 12]);
		$log = $this->logger->getLogs()[0];
		$this->assertSame(12, $log[TLogger::LOG_MEMORY]);
		$this->assertSame(getmypid(), $log[TLogger::LOG_PID]);
		$this->assertGreaterThanOrEqual($before, $log[TLogger::LOG_TIME]);
		$this->assertNull($log[TLogger::LOG_TRACES]);
		$this->assertNull($log[TLogger::LOG_CONTROL]);
	}

	public function testDefaultCategoryIsCallingClass(): void
	{
		$this->psr->setCategory(null);
		(new TTestPsrLoggerCaller())->warn($this->psr, 'from caller');
		$this->psr->notice('from test');
		$logs = $this->logger->getLogs();
		$this->assertEquals(TTestPsrLoggerCaller::class, $logs[0][TLogger::LOG_CATEGORY]);
		$this->assertEquals(self::class, $logs[1][TLogger::LOG_CATEGORY]);
	}
}
