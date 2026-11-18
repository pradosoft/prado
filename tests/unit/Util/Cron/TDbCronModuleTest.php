<?php

namespace Prado\Test\Unit\Util\Cron;

use Prado\Prado;
use Prado\TApplicationMode;
use Prado\Util\Cron\TDbCronModule;
use Prado\Util\TLogger;

/**
 * Tests the deprecation notice of {@see TDbCronModule}.
 */
class TDbCronModuleTest extends \PHPUnit\Framework\TestCase
{
	private $_mode;

	protected function setUp(): void
	{
		$this->_mode = Prado::getApplication()->getMode();
		Prado::getLogger()->deleteLogs(TLogger::NOTICE, TDbCronModule::class);
	}

	protected function tearDown(): void
	{
		Prado::getApplication()->setMode($this->_mode);
		Prado::getLogger()->deleteLogs(TLogger::NOTICE, TDbCronModule::class);
	}

	public function testInitLogsDeprecationNoticeInDebugMode(): void
	{
		Prado::getApplication()->setMode(TApplicationMode::Debug);
		$module = new TDbCronModule();
		$module->init(null);

		$logs = Prado::getLogger()->getLogs(TLogger::NOTICE, TDbCronModule::class);
		$this->assertCount(1, $logs);
		$this->assertStringContainsString('TDbCronManager', $logs[0][0]);
	}

	public function testInitLogsNoNoticeOutsideDebugMode(): void
	{
		Prado::getApplication()->setMode(TApplicationMode::Normal);
		$module = new TDbCronModule();
		$module->init(null);

		$this->assertSame([], Prado::getLogger()->getLogs(TLogger::NOTICE, TDbCronModule::class));
	}
}
