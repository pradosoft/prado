<?php

namespace Prado\Test\Unit\Web\UI\ActiveControls;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TConfigurationException;
use Prado\Prado;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Test\Unit\Harness\Web\UI\TTestCallbackPage;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\Services\ServerEvents\TServerEventMemoryBroker;
use Prado\Web\Services\ServerEvents\TServerEventService;
use Prado\Web\UI\ActiveControls\ICallbackEventHandler;
use Prado\Web\UI\ActiveControls\TCallback;
use Prado\Web\UI\ActiveControls\TServerEventTriggeredCallback;
use Prado\Web\UI\TClientScriptManager;

/**
 * Client script manager that records script registrations without publishing assets.
 */
class TServerEventTriggeredCallbackTestClientScript extends TClientScriptManager
{
	public array $callbackControls = [];

	public array $pradoScripts = [];

	public function registerCallbackControl($class, $options)
	{
		$this->callbackControls[] = [$class, $options];
	}

	public function registerPradoScript($name)
	{
		$this->pradoScripts[] = $name;
	}
}

class TServerEventTriggeredCallbackTest extends TestCase
{
	use TWebControlRenderTrait;

	private array $snapshot = [];

	private TServerEventMemoryBroker $broker;

	protected function setUp(): void
	{
		$app = Prado::getApplication();
		$this->snapshot = PradoUnit::snapshot($app, ['_modules']);
		$this->broker = new TServerEventMemoryBroker();
		$app->setModule('sse-test-broker', $this->broker);
	}

	protected function tearDown(): void
	{
		PradoUnit::restore(Prado::getApplication(), $this->snapshot);
	}

	private function createControl(string $channel = 'orders'): array
	{
		$page = new TTestCallbackPage();
		$page->callback = false;
		$cs = new TServerEventTriggeredCallbackTestClientScript($page);
		PradoUnit::setProp($page, '_clientScript', $cs);
		$control = new TServerEventTriggeredCallback();
		$control->setID('Orders');
		$control->setChannel($channel);
		$page->getControls()->add($control);
		return [$page, $control, $cs];
	}

	public function testIsACallback(): void
	{
		$control = new TServerEventTriggeredCallback();
		self::assertInstanceOf(TCallback::class, $control);
		self::assertInstanceOf(ICallbackEventHandler::class, $control);
	}

	public function testPropertyDefaults(): void
	{
		$control = new TServerEventTriggeredCallback();
		self::assertSame('', $control->getChannel());
		self::assertSame('', $control->getEventName());
		self::assertSame(TServerEventService::SERVICE_ID, $control->getServiceID());
		self::assertSame('', $control->getBrokerID());
		self::assertFalse($control->getActiveControl()->getCausesValidation());
		$control->setChannel('orders');
		$control->setEventName('changed');
		$control->setServiceID('push');
		$control->setBrokerID('sse-test-broker');
		self::assertSame('orders', $control->getChannel());
		self::assertSame('changed', $control->getEventName());
		self::assertSame('push', $control->getServiceID());
		self::assertSame('sse-test-broker', $control->getBrokerID());
	}

	public function testRendersAHiddenAnchorAndRegistersTheClientControl(): void
	{
		[$page, $control, $cs] = $this->createControl();
		$control->setEventName('changed');
		$latest = $this->broker->publish('orders', 'changed', 1)->getID();

		$html = $this->render($control);

		self::assertSame('<span id="' . $control->getClientID() . '" hidden="hidden"></span>', $html);
		self::assertSame(['serverevents'], $cs->pradoScripts);
		self::assertCount(1, $cs->callbackControls);
		[$class, $options] = $cs->callbackControls[0];
		self::assertSame('Prado.WebUI.TServerEventTriggeredCallback', $class);
		self::assertSame($control->getClientID(), $options['ID']);
		self::assertSame($control->getUniqueID(), $options['EventTarget']);
		self::assertSame(TServerEventService::constructChannelUrl('orders'), $options['StreamUrl']);
		self::assertSame($latest, $options['LastEventID']);
		self::assertSame('changed', $options['EventName']);
		self::assertFalse($options['CausesValidation']);
	}

	public function testLastEventIdIsEmptyForAQuietChannel(): void
	{
		[$page, $control, $cs] = $this->createControl('quiet');
		$this->render($control);
		self::assertSame('', $cs->callbackControls[0][1]['LastEventID']);
	}

	public function testServiceIDSelectsTheStreamUrl(): void
	{
		[$page, $control, $cs] = $this->createControl();
		$control->setServiceID('push');
		$this->render($control);
		self::assertSame(TServerEventService::constructChannelUrl('orders', 'push'), $cs->callbackControls[0][1]['StreamUrl']);
	}

	public function testRenderRequiresAValidChannel(): void
	{
		foreach (['', 'bad channel'] as $channel) {
			[$page, $control, $cs] = $this->createControl($channel);
			try {
				$this->render($control);
				self::fail('Expected TConfigurationException');
			} catch (TConfigurationException $e) {
				self::assertSame('servereventtriggeredcallback_channel_invalid', $e->getErrorCode());
			}
			self::assertSame([], $cs->callbackControls);
		}
	}
}
