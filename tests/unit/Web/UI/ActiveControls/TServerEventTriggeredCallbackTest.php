<?php

namespace Prado\Test\Unit\Web\UI\ActiveControls;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TConfigurationException;
use Prado\Prado;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Test\Unit\Harness\Web\UI\TTestCallbackPage;
use Prado\Test\Unit\PradoUnit;
use Prado\TModule;
use Prado\Web\Services\ServerEvents\IServerEventEndpoint;
use Prado\Web\Services\ServerEvents\TServerEventMemoryBroker;
use Prado\Web\Services\ServerEvents\TServerEventService;
use Prado\Web\Services\ServerEvents\TServerEventSseEndpoint;
use Prado\Web\UI\ActiveControls\ICallbackEventHandler;
use Prado\Web\UI\ActiveControls\TCallback;
use Prado\Web\UI\ActiveControls\TServerEventClientSide;
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

/**
 * An endpoint of another transport, as a WebSocket package supplies.
 */
class TServerEventTriggeredCallbackTestEndpoint extends TModule implements IServerEventEndpoint
{
	public array $scriptPages = [];

	public function getClientOptions(string $channel, string $lastEventId): array
	{
		return ['Transport' => 'websocket', 'Url' => 'wss://example.test/ws', 'Token' => 't-' . $channel, 'LastEventID' => $lastEventId, 'ID' => 'endpoint-id', 'Channel' => 'endpoint-channel'];
	}

	public function registerClientScripts($page): void
	{
		$this->scriptPages[] = $page;
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

	public function testClientSideOnServerEventReachesTheClientOptions(): void
	{
		[$page, $control, $cs] = $this->createControl();
		self::assertInstanceOf(TServerEventClientSide::class, $control->getClientSide());
		self::assertNull($control->getClientSide()->getOnServerEvent());
		$control->getClientSide()->setOnServerEvent('return parameter.Data > 0;');

		$this->render($control);

		$handler = (string) $cs->callbackControls[0][1]['OnServerEvent'];
		self::assertStringContainsString('function(sender, parameter)', $handler);
		self::assertStringContainsString('return parameter.Data > 0;', $handler);
	}

	public function testCallbackOnEvent(): void
	{
		[$page, $control, $cs] = $this->createControl();
		self::assertTrue($control->getCallbackOnEvent());
		$this->render($control);
		self::assertArrayNotHasKey('CallbackOnEvent', $cs->callbackControls[0][1], 'The default true is not sent.');

		[$page, $control, $cs] = $this->createControl();
		$control->setCallbackOnEvent('false');
		self::assertFalse($control->getCallbackOnEvent());
		$this->render($control);
		self::assertFalse($cs->callbackControls[0][1]['CallbackOnEvent']);
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

	public function testDefaultEndpointIsTheSseStream(): void
	{
		[$page, $control, $cs] = $this->createControl();
		self::assertSame('', $control->getEndpointID());
		$this->render($control);
		$options = $cs->callbackControls[0][1];
		self::assertSame('sse', $options['Transport']);
		self::assertSame('orders', $options['Channel']);
		self::assertSame(TServerEventService::constructChannelUrl('orders'), $options['StreamUrl']);
	}

	public function testSseEndpoint(): void
	{
		$endpoint = new TServerEventSseEndpoint();
		self::assertSame(TServerEventService::SERVICE_ID, $endpoint->getServiceID());
		self::assertSame($endpoint, $endpoint->setServiceID('push'));
		self::assertSame([
			'Transport' => 'sse',
			'StreamUrl' => TServerEventService::constructChannelUrl('orders', 'push'),
			'LastEventID' => 'g-3',
		], $endpoint->getClientOptions('orders', 'g-3'));
	}

	public function testEndpointIDSelectsAnotherTransport(): void
	{
		$endpoint = new TServerEventTriggeredCallbackTestEndpoint();
		Prado::getApplication()->setModule('ws-test-endpoint', $endpoint);
		[$page, $control, $cs] = $this->createControl();
		$control->setEndpointID('ws-test-endpoint');
		$latest = $this->broker->publish('orders', 'changed', 1)->getID();

		$this->render($control);

		$options = $cs->callbackControls[0][1];
		self::assertSame('websocket', $options['Transport']);
		self::assertSame('wss://example.test/ws', $options['Url']);
		self::assertSame('t-orders', $options['Token']);
		self::assertSame($latest, $options['LastEventID'], 'The endpoint gets the channel\'s latest event ID.');
		self::assertSame($control->getClientID(), $options['ID'], 'The control options win over the endpoint\'s.');
		self::assertSame('orders', $options['Channel']);
		self::assertArrayNotHasKey('StreamUrl', $options);
		self::assertSame([$page], $endpoint->scriptPages);
		self::assertSame(['serverevents'], $cs->pradoScripts);
	}

	public function testEndpointIDMustNameAnEndpointModule(): void
	{
		[$page, $control, $cs] = $this->createControl();
		$control->setEndpointID('sse-test-broker');
		try {
			$this->render($control);
			self::fail('Expected TConfigurationException for an EndpointID that is not an endpoint');
		} catch (TConfigurationException $e) {
			self::assertSame('servereventtriggeredcallback_endpointid_invalid', $e->getErrorCode());
		}
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
