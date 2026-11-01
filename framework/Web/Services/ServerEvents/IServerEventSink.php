<?php

/**
 * IServerEventSink interface file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\Services\ServerEvents;

/**
 * IServerEventSink interface
 *
 * IServerEventSink is the response a {@see TServerEventStreamer} writes a server-sent event
 * stream to. The streamer sends the headers once, then the stream in chunks, then closes it.
 *
 * | Host | Implementation |
 * |---|---|
 * | a PHP SAPI request (FPM, mod_php, the built-in server) | {@see TServerEventResponseSink} over {@see \Prado\Web\THttpResponse} |
 * | a server on the event loop, such as an HTTP/2 server | a sink over its response stream; {@see write()} may suspend the fiber for flow control |
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
interface IServerEventSink
{
	/**
	 * Sends the response headers and readies the output for unbuffered chunks.
	 * The headers carry no connection-specific field, so an HTTP/2 response may send them as given.
	 * @param array<string, string> $headers The header values by field name.
	 */
	public function open(array $headers): void;

	/**
	 * Sends a chunk of the stream to the client now.
	 * An event loop host may suspend the calling fiber until the client accepts the chunk.
	 * @param string $chunk The chunk.
	 * @return bool Whether the client is still connected.
	 */
	public function write(string $chunk): bool;

	/**
	 * Ends the stream.
	 */
	public function close(): void;
}
