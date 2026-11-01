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
use Prado\TPropertyValue;
use Prado\Web\Services\ServerEvents\IServerEventEndpoint;
use Prado\Web\Services\ServerEvents\TServerEventBroker;
use Prado\Web\Services\ServerEvents\TServerEventBrokerIDTrait;
use Prado\Web\Services\ServerEvents\TServerEventService;
use Prado\Web\Services\ServerEvents\TServerEventSseEndpoint;

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
 * The callback parameter is an object with `Channel`, `ID`, `Event`, and `Data` of the server event.
 * It comes from the browser, so a handler reads the current server state and treats `Data`
 * as user input.
 *
 * Controls of a page on one channel share one `EventSource`. {@see setEventName EventName}
 * selects the events of the channel that trigger this control; empty selects all of them.
 * Events arrive one callback each, in order, through the callback queue. The control does
 * not cause validation by default.
 *
 * **Client-side handling.** `ClientSide.OnServerEvent` ({@see TServerEventClientSide}) runs in
 * the browser for each selected event before its callback, with the event as `parameter`; a
 * `return false;` skips that callback. With {@see setCallbackOnEvent CallbackOnEvent} false the
 * control sends no callbacks and only runs OnServerEvent, which suits an event whose data is
 * the whole update, such as a count or a notice. Every stream message is also dispatched on
 * `document` as a `prado:serverevent` CustomEvent, `detail` `{Channel, ID, Event, Data}`.
 *
 * ```xml
 * <com:TServerEventTriggeredCallback Channel="inbox" CallbackOnEvent="false"
 *     ClientSide.OnServerEvent="document.getElementById('unread').textContent = parameter.Data.unread;" />
 * ```
 * ```js
 * document.addEventListener('prado:serverevent', (e) => console.log(e.detail.Channel, e.detail.Event));
 * ```
 *
 * The control renders an empty `<span hidden>` with its client ID. When a callback removes
 * the control from the page, the span goes with it and the client unsubscribes; the last
 * subscriber of a stream closes it.
 *
 * **Endpoint and transport.** The control streams its channel from an
 * {@see \Prado\Web\Services\ServerEvents\IServerEventEndpoint}: the module named by
 * {@see setEndpointID EndpointID}, else a
 * {@see \Prado\Web\Services\ServerEvents\TServerEventSseEndpoint} on the
 * {@see setServiceID ServiceID} service, read by the browser's `EventSource`. The endpoint
 * gives the client options, whose `Transport` names the browser transport, and registers its
 * scripts, so a package such as a WebSocket server supplies its own endpoint and transport.
 * The SSE stream URL carries a token that grants the page's user the channel. Every endpoint
 * gets the latest event ID of the channel at render time, so the stream starts after the
 * events the page already shows. {@see TTimeTriggeredCallback} remains the polling alternative.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TServerEventTriggeredCallback extends TCallback
{
	use TServerEventBrokerIDTrait;

	/**
	 * Constructor. The control does not cause validation by default, and its client-side
	 * options are a {@see TServerEventClientSide}.
	 */
	public function __construct()
	{
		parent::__construct();
		$this->getActiveControl()->setClientSide(new TServerEventClientSide());
		$this->getActiveControl()->setCausesValidation(false);
	}

	/**
	 * @return TCallbackClientSide The client-side options: the {@see TServerEventClientSide}
	 *   set in the constructor, with OnServerEvent.
	 */
	public function getClientSide()
	{
		return $this->getActiveControl()->getClientSide();
	}

	/**
	 * @return bool Whether a selected server event sends a callback. Defaults to true.
	 */
	public function getCallbackOnEvent(): bool
	{
		return $this->getViewState('CallbackOnEvent', true);
	}

	/**
	 * @param bool|string $value Whether a selected server event sends a callback; false runs
	 *   only the client-side OnServerEvent.
	 */
	public function setCallbackOnEvent($value): void
	{
		$this->setViewState('CallbackOnEvent', TPropertyValue::ensureBoolean($value), true);
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
	 * @return string The ID of the {@see IServerEventEndpoint} module the channel streams from;
	 *   empty for the server-sent event stream of {@see getServiceID ServiceID}. Defaults to empty.
	 */
	public function getEndpointID(): string
	{
		return $this->getViewState('EndpointID', '');
	}

	/**
	 * @param string $value The ID of the {@see IServerEventEndpoint} module the channel streams
	 *   from; empty for the server-sent event stream of {@see getServiceID ServiceID}.
	 */
	public function setEndpointID($value): void
	{
		$this->setViewState('EndpointID', TPropertyValue::ensureString($value), '');
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
		$endpoint = $this->resolveEndpoint();
		$this->getPage()->getClientScript()->registerPradoScript('serverevents');
		$endpoint->registerClientScripts($this->getPage());
		$this->getActiveControl()->registerCallbackClientScript(
			$this->getClientClassName(),
			$this->getTriggerOptions($endpoint)
		);
	}

	/**
	 * Returns the endpoint the channel streams from.
	 * @throws TConfigurationException When EndpointID does not name an {@see IServerEventEndpoint} module.
	 * @return IServerEventEndpoint The module named by {@see getEndpointID EndpointID}, else a
	 *   {@see TServerEventSseEndpoint} on {@see getServiceID ServiceID}.
	 */
	protected function resolveEndpoint(): IServerEventEndpoint
	{
		if (($id = $this->getEndpointID()) === '') {
			return (new TServerEventSseEndpoint())->setServiceID($this->getServiceID());
		}
		$endpoint = $this->getApplication()->getModule($id);
		if (!($endpoint instanceof IServerEventEndpoint)) {
			throw new TConfigurationException('servereventtriggeredcallback_endpointid_invalid', $this->getID(), $id);
		}
		return $endpoint;
	}

	/**
	 * Returns the client control options: the endpoint's client options (`Transport` and the
	 * transport's options, such as `StreamUrl` and `LastEventID`), then `ID`, `EventTarget`,
	 * `Channel`, `EventName`, and `CallbackOnEvent` when it is false.
	 * @param IServerEventEndpoint $endpoint The endpoint the channel streams from.
	 * @return array The client control options.
	 */
	protected function getTriggerOptions(IServerEventEndpoint $endpoint): array
	{
		$channel = $this->getChannel();
		$options = [
			'ID' => $this->getClientID(),
			'EventTarget' => $this->getUniqueID(),
			'Channel' => $channel,
			'EventName' => $this->getEventName(),
		];
		if (!$this->getCallbackOnEvent()) {
			$options['CallbackOnEvent'] = false;
		}
		return array_merge($endpoint->getClientOptions($channel, $this->resolveBroker()->getLastEventId($channel)), $options);
	}

	/**
	 * @return string The client class name, `Prado.WebUI.TServerEventTriggeredCallback`.
	 */
	protected function getClientClassName(): string
	{
		return 'Prado.WebUI.TServerEventTriggeredCallback';
	}
}
