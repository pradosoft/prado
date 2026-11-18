<?php

namespace Prado\Test\Unit\Util\Log;

use Prado\IO\TStdOutWriter;
use Prado\IO\TTextWriter;
use Prado\Shell\Actions\TWebServerAction;
use Prado\Shell\TShellWriter;
use Prado\Test\Unit\PradoUnit;
use Prado\Util\Log\TLogger;
use Prado\Util\Log\TStdOutLogRoute;

/**
 * A text writer that keeps what it flushed.
 */
class TFlushRecordingTextWriter extends TTextWriter
{
	public string $flushed = '';

	public function flush()
	{
		return $this->flushed .= parent::flush();
	}
}

/**
 * A TStdOutLogRoute that writes into a recording writer.
 */
class TRecordingStdOutLogRoute extends TStdOutLogRoute
{
	public ?TFlushRecordingTextWriter $inner = null;

	public bool $color = false;

	protected function createWriter(): TShellWriter
	{
		$this->inner = new TFlushRecordingTextWriter();
		$writer = new TShellWriter($this->inner);
		$writer->setColorSupported($this->color);
		return $writer;
	}
}

class TStdOutLogRouteTest extends \PHPUnit\Framework\TestCase
{
	private $devServer;

	protected function setUp(): void
	{
		$this->devServer = getenv(TWebServerAction::DEV_WEBSERVER_ENV);
	}

	protected function tearDown(): void
	{
		putenv(TWebServerAction::DEV_WEBSERVER_ENV . ($this->devServer === false ? '' : '=' . $this->devServer));
	}

	public function testOnlyDevServer()
	{
		$route = new TStdOutLogRoute();
		$this->assertFalse($route->getOnlyDevServer());
		$this->assertSame($route, $route->setOnlyDevServer('true'));
		$this->assertTrue($route->getOnlyDevServer());
	}

	public function testEnabled()
	{
		$route = new TStdOutLogRoute();
		putenv(TWebServerAction::DEV_WEBSERVER_ENV);
		$this->assertTrue($route->getEnabled());

		$route->setOnlyDevServer(true);
		$this->assertFalse($route->getEnabled());

		putenv(TWebServerAction::DEV_WEBSERVER_ENV . '=1');
		$this->assertTrue($route->getEnabled());

		$route->setEnabled(false);
		$this->assertFalse($route->getEnabled());
	}

	public function testCreateWriter()
	{
		$writer = PradoUnit::invoke(new TStdOutLogRoute(), 'createWriter');
		$this->assertInstanceOf(TShellWriter::class, $writer);
		$this->assertInstanceOf(TStdOutWriter::class, $writer->getWriter());
	}

	public function testProcessLogs()
	{
		$route = new TRecordingStdOutLogRoute();
		PradoUnit::invoke($route, 'processLogs', [
			TLogEntryFactory::entry('first', TLogger::WARNING, 'Cat\A'),
			TLogEntryFactory::entry(new \RuntimeException('failed'), TLogger::ERROR, 'Cat\B'),
		], true, []);
		$lines = explode("\n", $route->inner->flushed);
		$this->assertSame('[Warning][Cat\A] first', $lines[0]);
		$this->assertStringStartsWith('[Error][Cat\B] RuntimeException: failed', $lines[1]);
	}

	public function testProcessLogsColorsTheLevel()
	{
		$route = new TRecordingStdOutLogRoute();
		$route->color = true;
		PradoUnit::invoke($route, 'processLogs', [
			TLogEntryFactory::entry('fatal', TLogger::FATAL, 'Cat'),
			TLogEntryFactory::entry('info', TLogger::INFO, 'Cat'),
		], true, []);
		$this->assertSame("\033[" . TShellWriter::BG_RED . "m[Fatal]\033[0m[Cat] fatal\n[Info][Cat] info\n", $route->inner->flushed);
	}

	public function testFormatLogMessage()
	{
		$route = new TStdOutLogRoute();
		$this->assertSame('[Cat] text', $route->formatLogMessage(TLogEntryFactory::entry('text', TLogger::INFO, 'Cat')));
		$this->assertSame('[Cat] true', $route->formatLogMessage(TLogEntryFactory::entry(true, TLogger::INFO, 'Cat')));
	}

	public function testLevelColor()
	{
		$expected = [
			TLogger::PROFILE => TShellWriter::BG_LIGHT_GREEN,
			TLogger::PROFILE_BEGIN => TShellWriter::BG_LIGHT_GREEN,
			TLogger::PROFILE_END => TShellWriter::BG_LIGHT_GREEN,
			TLogger::DEBUG => TShellWriter::BG_GREEN,
			TLogger::INFO => null,
			TLogger::NOTICE => TShellWriter::BG_BLUE,
			TLogger::WARNING => TShellWriter::BG_CYAN,
			TLogger::ERROR => TShellWriter::BG_LIGHT_MAGENTA,
			TLogger::ALERT => TShellWriter::BG_MAGENTA,
			TLogger::FATAL => TShellWriter::BG_RED,
			0x80 => TShellWriter::BG_GREEN,
		];
		foreach ($expected as $level => $color) {
			$this->assertSame($color, PradoUnit::invoke(TStdOutLogRoute::class, 'levelColor', $level), 'level 0x' . dechex($level));
		}
	}

	public function testProcessLogsWithoutLogs()
	{
		$route = new TRecordingStdOutLogRoute();
		PradoUnit::invoke($route, 'processLogs', [], true, []);
		$this->assertSame('', $route->inner->flushed);
	}
}
