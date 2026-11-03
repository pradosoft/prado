<?php

/**
 * IServerEventEndpoint interface file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\Services\ServerEvents;

/**
 * IServerEventEndpoint interface
 *
 * IServerEventEndpoint is where a browser streams the server events of a channel, and the
 * browser transport that reads it. {@see \Prado\Web\UI\ActiveControls\TServerEventTriggeredCallback}
 * asks its endpoint for the client options of its channel and registers the endpoint's client
 * scripts.
 *
 * | Endpoint | Transport | Client options |
 * |---|---|---|
 * | {@see TServerEventSseEndpoint}, the default | `sse`, built into `Prado.ServerEvents` | `StreamUrl`, `LastEventID` |
 * | a module of another package, such as a WebSocket server's | a transport its script registers with `Prado.ServerEvents.registerTransport()` | what that transport reads |
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
interface IServerEventEndpoint
{
	/**
	 * Returns the options a browser transport needs to stream a channel for the current user.
	 * @param string $channel The valid channel name.
	 * @param string $lastEventId The event ID the stream starts after.
	 * @return array<string, mixed> The client options; `Transport` names the browser transport.
	 */
	public function getClientOptions(string $channel, string $lastEventId): array;

	/**
	 * Registers the client scripts of the endpoint's browser transport on a page.
	 * The `serverevents` script package, with the `sse` transport, is registered by the control.
	 * @param \Prado\Web\UI\TPage $page The page.
	 */
	public function registerClientScripts($page): void;
}
