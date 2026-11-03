<?php

/**
 * TServerEventResponseSink class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\Services\ServerEvents;

use Prado\TComponent;
use Prado\Web\THttpHeaderName;

/**
 * TServerEventResponseSink class
 *
 * TServerEventResponseSink writes a server-sent event stream to a PHP SAPI response through
 * {@see \Prado\Web\THttpResponse}. It is the sink of {@see TServerEventService}.
 *
 * {@see open()} sets the `Content-Type` field through `setContentType()` and `setCharset()` and
 * appends the other fields. It then turns off `zlib.output_compression`, keeps the request running
 * after a disconnect (`ignore_user_abort`), sets {@see getTimeLimit TimeLimit}, sends the headers,
 * and ends every removable output buffer. {@see write()} writes and flushes each chunk, and reports
 * a disconnect through `connection_aborted()`.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TServerEventResponseSink extends TComponent implements IServerEventSink
{
	/** @var \Prado\Web\THttpResponse The response. */
	private $_response;

	/** @var int The seconds of `set_time_limit()` for the stream; 0 leaves the limit unchanged. */
	private int $_timeLimit;

	/**
	 * Constructor.
	 * @param \Prado\Web\THttpResponse $response The response.
	 * @param int $timeLimit The seconds of `set_time_limit()` for the stream; 0 leaves the limit unchanged.
	 */
	public function __construct($response, int $timeLimit = 0)
	{
		$this->_response = $response;
		$this->_timeLimit = max(0, $timeLimit);
		parent::__construct();
	}

	/**
	 * @return \Prado\Web\THttpResponse The response.
	 */
	public function getResponse()
	{
		return $this->_response;
	}

	/**
	 * @return int The seconds of `set_time_limit()` for the stream; 0 leaves the limit unchanged.
	 */
	public function getTimeLimit(): int
	{
		return $this->_timeLimit;
	}

	/**
	 * Sets the headers on the response and opens unbuffered output.
	 * @param array<string, string> $headers The header values by field name.
	 */
	public function open(array $headers): void
	{
		$response = $this->getResponse();
		foreach ($headers as $name => $value) {
			if (strcasecmp($name, THttpHeaderName::ContentType) === 0) {
				$this->applyContentType($value);
			} else {
				$response->appendHeader($name . ': ' . $value);
			}
		}
		$this->openOutput();
	}

	/**
	 * Writes a chunk and flushes it to the client.
	 * @param string $chunk The chunk.
	 * @return bool Whether the client is still connected.
	 */
	public function write(string $chunk): bool
	{
		$response = $this->getResponse();
		$response->write($chunk);
		$response->flush();
		return $this->isClientConnected();
	}

	/**
	 * Ends the stream. The SAPI response ends with the request, so nothing is sent.
	 */
	public function close(): void
	{
	}

	/**
	 * Sets the media type and charset of a `Content-Type` value on the response.
	 * @param string $value The field value, such as `text/event-stream; charset=UTF-8`.
	 */
	protected function applyContentType(string $value): void
	{
		$response = $this->getResponse();
		$parts = explode(';', $value);
		$response->setContentType(trim((string) array_shift($parts)));
		foreach ($parts as $part) {
			[$key, $param] = array_pad(explode('=', $part, 2), 2, '');
			if (strcasecmp(trim($key), 'charset') === 0) {
				$response->setCharset(trim($param, " \t\""));
			}
		}
	}

	/**
	 * Turns off output compression and the removable output buffers, sends the headers, and
	 * keeps the request running after a disconnect, so the stream ends through {@see isClientConnected()}.
	 */
	protected function openOutput(): void
	{
		if (!headers_sent()) {
			ini_set('zlib.output_compression', '0');
		}
		ignore_user_abort(true);
		if ($this->getTimeLimit() > 0 && function_exists('set_time_limit')) {
			set_time_limit($this->getTimeLimit());
		}
		$this->getResponse()->flush(false);
		while (($status = ob_get_status()) !== [] && ($status['flags'] & PHP_OUTPUT_HANDLER_REMOVABLE)) {
			ob_end_flush();
		}
	}

	/**
	 * @return bool Whether the client is still connected.
	 */
	protected function isClientConnected(): bool
	{
		return connection_aborted() === 0;
	}
}
