<?php

namespace Prado\Test\Unit\Util\Cron;

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Prado;
use Prado\Util\Cron\TCronModule;
use Prado\Util\Cron\TCronMonitorResponse;

/**
 * Answers for a given cron module instead of one registered in the application.
 */
class TTestCronMonitorResponse extends TCronMonitorResponse
{
	public ?TCronModule $module = null;

	public function getCronModule(): TCronModule
	{
		return $this->module ?? parent::getCronModule();
	}
}

class TCronMonitorResponseTest extends \PHPUnit\Framework\TestCase
{
	public function testFormatAndModule()
	{
		$response = new TCronMonitorResponse();
		self::assertSame(TCronMonitorResponse::FORMAT_JSON, $response->getFormat());
		$response->setFormat('Prometheus');
		self::assertSame(TCronMonitorResponse::FORMAT_PROMETHEUS, $response->getFormat());
		self::assertNull($response->getCronModuleID());
		$response->setCronModuleID('noSuchCronModule');
		try {
			$response->getCronModule();
			self::fail('an unknown module was found');
		} catch (TConfigurationException $e) {
		}
		self::expectException(TInvalidDataValueException::class);
		$response->setFormat('xml');
	}

	public function testGetJsonContent_IsTheReport()
	{
		$module = new TCronModule();
		$module->init([['name' => 'reported', 'schedule' => '0 0 1 1 ? 2099', 'task' => TTestCronModuleTask::class]]);
		try {
			$response = new TTestCronMonitorResponse();
			$response->module = $module;
			$content = $response->getJsonContent();
			self::assertIsObject($content);
			self::assertArrayHasKey('module', (array) $content);
			self::assertArrayHasKey('reported', $content->tasks);
		} finally {
			$module->unlisten();
		}
	}

}
