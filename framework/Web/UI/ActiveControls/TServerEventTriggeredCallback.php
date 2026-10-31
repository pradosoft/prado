<?php

/**
 * TServerEventTriggeredCallback class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\ActiveControls;

use Prado\Exceptions\TConfigurationException;
use Prado\Web\Services\TServerEventBroker;
use Prado\Web\Services\TServerEventBrokerIDTrait;
use Prado\Web\Services\TServerEventService;

/**
 * TServerEventTriggeredCallback class.
 *
 * TServerEventTriggeredCallback sends a callback request when the server publishes an event
 * on its {@see setChannel Channel}. The page holds an `EventSource` on the stream of a
 * {@see TServerEventService}; each event raises {@see onCallback OnCallback} of this control
 * through a regular callback, so the handler updates active controls with the page state
 * and the callback response, as any callback does.
 *
 * ```xml
 * <com:TServerEventTriggeredCallback Channel="orders" EventName="changed" OnCallback="ordersChanged" />
 * <com:TActivePanel ID="Orders">...</com:TActivePanel>
 * ```
 * ```php
 * public function ordersChanged($sender, $param)
 * {
 *     $event = $param->getCallbackParameter();   // ->ID, ->Event, ->Data
 *     $this->Orders->render($param->getNewWriter());
 * }
 * ```
 *
 * The callback parameter is an object with `ID`, `Event`, and `Data` of the server event.
 * It comes from the browser, so a handler reads the current server state and treats `Data`
 * as user input.
 *
 * Controls of a page on one channel share one `EventSource`. {@see setEventName EventName}
 * selects the events of the channel that trigger this control; empty selects all of them.
 * Events arrive one callback each, in order, through the callback queue. The control does
 * not cause validation by default.
 *
 * The control renders an empty `<span hidden>` with its client ID. When a callback removes
 * the control from the page, the span goes with it and the client unsubscribes; the last
 * subscriber of a stream closes its `EventSource`.
 *
 * The stream URL carries a token that grants the page's user the channel, and the latest
 * event ID of the channel at render time, so the stream starts after the events the page
 * already shows. {@see TTimeTriggeredCallback} remains the polling alternative.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TServerEventTriggeredCallback extends TCallback
{
	use TServerEventBrokerIDTrait;

	/**
	 * Constructor. The control does not cause validation by default.
	 */
	public function __construct()
	{
		parent::__construct();
		$this->getActiveControl()->setCausesValidation(false);
	}

	/**
	 * @return string The channel whose events trigger the callback.
	 */
	public function getChannel(): string
	{
		return $this->getViewState('Channel', '');
	}

	/**
	 * @param string $value The channel whose events trigger the callback.
	 */
	public function setChannel($value): void
	{
		$this->setViewState('Channel', (string) $value, '');
	}

	/**
	 * @return string The event name that triggers the callback; empty for every event of the channel.
	 */
	public function getEventName(): string
	{
		return $this->getViewState('EventName', '');
	}

	/**
	 * @param string $value The event name that triggers the callback; empty for every event of the channel.
	 */
	public function setEventName($value): void
	{
		$this->setViewState('EventName', (string) $value, '');
	}

	/**
	 * @return string The ID of the {@see TServerEventService}. Defaults to `events`.
	 */
	public function getServiceID(): string
	{
		return $this->getViewState('ServiceID', TServerEventService::SERVICE_ID);
	}

	/**
	 * @param string $value The ID of the {@see TServerEventService}.
	 */
	public function setServiceID($value): void
	{
		$this->setViewState('ServiceID', (string) $value, TServerEventService::SERVICE_ID);
	}

	/**
	 * Renders the hidden anchor span and registers the client control.
	 * @param \Prado\Web\UI\THtmlWriter $writer The writer.
	 * @throws TConfigurationException When Channel is empty or not a valid channel name.
	 */
	public function render($writer)
	{
		$channel = $this->getChannel();
		if (!TServerEventBroker::isValidName($channel)) {
			throw new TConfigurationException('servereventtriggeredcallback_channel_invalid', $this->getID(), $channel);
		}
		$writer->addAttribute('id', $this->getClientID());
		$writer->addAttribute('hidden', 'hidden');
		$writer->renderBeginTag('span');
		$writer->renderEndTag();
		parent::render($writer);
		$this->getPage()->getClientScript()->registerPradoScript('serverevents');
		$this->getActiveControl()->registerCallbackClientScript(
			$this->getClientClassName(),
			$this->getTriggerOptions()
		);
	}

	/**
	 * @return array The client control options: `ID`, `EventTarget`, `StreamUrl`, `LastEventID`, `EventName`.
	 */
	protected function getTriggerOptions(): array
	{
		$channel = $this->getChannel();
		return [
			'ID' => $this->getClientID(),
			'EventTarget' => $this->getUniqueID(),
			'StreamUrl' => TServerEventService::constructChannelUrl($channel, $this->getServiceID(), $this->getApplication()),
			'LastEventID' => $this->resolveBroker()->getLastEventId($channel),
			'EventName' => $this->getEventName(),
		];
	}

	/**
	 * @return string The client class name, `Prado.WebUI.TServerEventTriggeredCallback`.
	 */
	protected function getClientClassName(): string
	{
		return 'Prado.WebUI.TServerEventTriggeredCallback';
	}
}
