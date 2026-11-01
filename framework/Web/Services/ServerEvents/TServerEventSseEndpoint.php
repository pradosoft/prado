<?php

/**
 * TServerEventSseEndpoint class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\Services\ServerEvents;

use Prado\TModule;
use Prado\TPropertyValue;

/**
 * TServerEventSseEndpoint class
 *
 * TServerEventSseEndpoint is the server-sent event endpoint: the {@see TServerEventService}
 * named by {@see getServiceID ServiceID}, read by the browser's `EventSource` through the `sse`
 * transport. It is the default endpoint of
 * {@see \Prado\Web\UI\ActiveControls\TServerEventTriggeredCallback}; as a module it is named by
 * the control's `EndpointID`.
 *
 * ```xml
 * <module id="sse" class="Prado\Web\Services\ServerEvents\TServerEventSseEndpoint" ServiceID="push" />
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TServerEventSseEndpoint extends TModule implements IServerEventEndpoint
{
	/** The name of the browser transport. */
	public const TRANSPORT = 'sse';

	/** @var string The ID of the TServerEventService. */
	private string $_serviceID = TServerEventService::SERVICE_ID;

	/**
	 * @return string The ID of the {@see TServerEventService}. Defaults to `events`.
	 */
	public function getServiceID(): string
	{
		return $this->_serviceID;
	}

	/**
	 * @param string $value The ID of the {@see TServerEventService}.
	 * @return static The endpoint.
	 */
	public function setServiceID($value): static
	{
		$this->_serviceID = TPropertyValue::ensureString($value);
		return $this;
	}

	/**
	 * Returns the `sse` transport options of a channel: the stream URL, with the channel token
	 * of the current user, and the event ID the stream starts after.
	 * @param string $channel The valid channel name.
	 * @param string $lastEventId The event ID the stream starts after.
	 * @return array{Transport: string, StreamUrl: string, LastEventID: string} The client options.
	 */
	public function getClientOptions(string $channel, string $lastEventId): array
	{
		return [
			'Transport' => static::TRANSPORT,
			'StreamUrl' => TServerEventService::constructChannelUrl($channel, $this->getServiceID(), $this->getApplication()),
			'LastEventID' => $lastEventId,
		];
	}

	/**
	 * Registers nothing: the `sse` transport is in the `serverevents` script package.
	 * @param \Prado\Web\UI\TPage $page The page.
	 */
	public function registerClientScripts($page): void
	{
	}
}
