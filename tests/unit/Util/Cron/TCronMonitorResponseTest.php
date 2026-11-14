<?php

namespace Prado\Test\Unit\Util\Cron;

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Prado;
use Prado\Util\Cron\TCronModule;
use Prado\Util\Cron\TCronMonitorResponse;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\THttpResponse;

/**
 * Answers for a given cron module instead of one registered in the application.
 */
class TTestCronMonitorResponse extends TCronMonitorResponse
{
	public ?TCronModule $module = null;

	public $response;

	public function getCronModule(): TCronModule
	{
		return $this->module ?? parent::getCronModule();
	}

	public function getResponse()
	{
		return $this->response ?? parent::getResponse();
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

	public function testGetJsonContent_WritesThePrometheusMetrics()
	{
		$module = new TCronModule();
		$module->setID('monitorMetrics');
		$module->init([['name' => 'measured', 'schedule' => '0 0 1 1 ? 2099', 'task' => TTestCronModuleTask::class]]);
		$written = '';
		$http = $this->createMock(THttpResponse::class);
		$http->expects(self::once())->method('setContentType')->with(TCronMonitorResponse::PROMETHEUS_CONTENT_TYPE);
		$http->expects(self::once())->method('setCharset')->with('UTF-8');
		$http->method('write')->willReturnCallback(function ($text) use (&$written) {
			$written .= $text;
		});
		try {
			$response = new TTestCronMonitorResponse();
			$response->module = $module;
			$response->response = $http;
			$response->setFormat(TCronMonitorResponse::FORMAT_PROMETHEUS);
			self::assertNull($response->getJsonContent());
			self::assertStringContainsString('task="measured"', $written);
		} finally {
			$module->unlisten();
		}
	}

	public function testGetCronModule_FindsTheModuleOfTheApplication()
	{
		$app = Prado::getApplication();
		$snapshot = PradoUnit::snapshot($app, ['_modules']);
		$module = new TCronModule();
		$id = 'monitorFound' . bin2hex(random_bytes(3));
		try {
			$app->setModule($id, $module);
			$response = new TCronMonitorResponse();
			self::assertSame($module, $response->getCronModule(), 'the first cron module of the application');
			$response->setCronModuleID($id);
			self::assertSame($module, $response->getCronModule(), 'the cron module of the ID');
		} finally {
			PradoUnit::restore($app, $snapshot);
			$module->unlisten();
		}
	}

	public function testTag_LimitsTheReport()
	{
		$module = new TCronModule();
		$module->setID('monitorTagged');
		$module->init([
			['name' => 'taggedOne', 'schedule' => '0 0 1 1 ? 2099', 'task' => TTestCronModuleTask::class, 'tags' => 'web'],
			['name' => 'taggedTwo', 'schedule' => '0 0 1 1 ? 2099', 'task' => TTestCronModuleTask::class],
		]);
		try {
			$response = new TTestCronMonitorResponse();
			$response->module = $module;
			self::assertNull($response->getTag());
			$response->setTag('#Web');
			self::assertSame('web', $response->getTag());
			self::assertSame(['taggedOne'], array_keys($response->getJsonContent()->tasks));
			$response->setTag('');
			self::assertNull($response->getTag());
			self::assertSame(['taggedOne', 'taggedTwo'], array_keys($response->getJsonContent()->tasks));
		} finally {
			$module->unlisten();
		}
	}
}
