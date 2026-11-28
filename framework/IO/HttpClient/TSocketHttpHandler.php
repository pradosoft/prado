<?php

/**
 * TSocketHttpHandler class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\HttpClient;

use Prado\Exceptions\TCancelledException;
use Prado\Exceptions\TSocketException;
use Prado\IO\Async\TAsync;
use Prado\IO\Async\TAsyncStream;
use Prado\IO\Async\TCancellationToken;
use Prado\IO\Async\TFuture;
use Prado\IO\Socket\TSocketStream;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * TSocketHttpHandler class.
 *
 * TSocketHttpHandler speaks HTTP/1.1 over a {@see TSocketStream}, with no extension
 * beyond `openssl` for https.  Every wait is on the event loop: the connect
 * ({@see TSocketStream::connectAsync()}), the TLS handshake
 * ({@see TSocketStream::enableCryptoAsync()}), and each read and write
 * ({@see TAsyncStream}).  A fiber that sends through it yields to the other fibers;
 * from the main fiber it runs the loop until the response is complete.
 * {@see handleAsync()} runs the request in a new fiber and returns its future.
 *
 * | Aspect | Behavior |
 * |---|---|
 * | Connection | One connection per request, sent with `Connection: close`. |
 * | Request body | `Content-Length` for a sized body; `Transfer-Encoding: chunked` for an unsized one, or the body read first for HTTP/1.0. |
 * | Response body | Read by `Content-Length`, by chunked framing, or to the end of the connection. |
 * | Interim responses | `1xx` responses other than `101` are skipped. |
 * | Compression | With {@see THttpClientOption::DECODE_CONTENT} and the zlib extension, asks for `gzip, deflate` and decodes them; the decoded response moves `Content-Encoding` and `Content-Length` to `X-Encoded-Content-Encoding` and `X-Encoded-Content-Length`. |
 * | Timeouts | {@see THttpClientOption::TIMEOUT} bounds the whole transfer; {@see THttpClientOption::CONNECT_TIMEOUT} the connect and handshake. |
 * | Protocol | Versions other than 1.0 are sent as 1.1. |
 *
 * Host-name resolution inside `stream_socket_client()` blocks the process: PHP has no
 * asynchronous resolver.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TSocketHttpHandler extends THttpHandler implements IAsyncHttpHandler
{
	/** The largest response header block, in bytes. */
	public const MAX_HEADER_SIZE = 65536;

	/** The longest chunk-size line, in bytes. */
	public const MAX_LINE_SIZE = 4096;

	/**
	 * Reports whether socket clients are available.
	 * @return bool Whether `stream_socket_client()` exists.
	 */
	public static function getIsAvailable(): bool
	{
		return function_exists('stream_socket_client');
	}

	/**
	 * Sends a request in a new fiber and returns the future of its response.
	 * @param RequestInterface $request The request.
	 * @param array<string, mixed> $options The {@see THttpClientOption} options.
	 * @return TFuture Resolves with the response, or rejects with the exception {@see handle()} would throw.
	 */
	public function handleAsync(RequestInterface $request, array $options = []): TFuture
	{
		return TAsync::run(fn () => $this->handle($request, $options));
	}

	/**
	 * Sends a request over a socket and returns its response, waiting on the event loop.
	 * @param RequestInterface $request The request.
	 * @param array<string, mixed> $options The {@see THttpClientOption} options.
	 * @throws THttpNetworkException When no complete response arrives.
	 * @throws THttpRequestException When the request cannot be sent.
	 * @throws TCancelledException When the token is cancelled.
	 * @return ResponseInterface The response.
	 */
	public function handle(RequestInterface $request, array $options = []): ResponseInterface
	{
		$options = $this->resolveOptions($options);
		$this->assertSendable($request);
		$token = $this->getToken($options);
		$token?->throwIfCancelled();
		$sink = $this->createSink($request, $options);
		$timeout = (float) $options[THttpClientOption::TIMEOUT];
		$deadline = new TCancellationToken($timeout > 0 ? $timeout : null, ...($token === null ? [] : [$token]));
		$socket = null;
		try {
			$socket = $this->connect($request, $options, $deadline);
			$stream = new TAsyncStream($socket);
			$this->writeRequest($stream, $request, $options, $deadline);
			return $this->readResponse($stream, $request, $options, $sink, $deadline);
		} catch (TCancelledException $e) {
			if ($token !== null && $token->isCancelled()) {
				throw $token->getReason() ?? $e;
			}
			throw $this->createTimeoutException($request, $timeout);
		} catch (\RuntimeException $e) {
			throw new THttpNetworkException($request, 'httpclient_transport_error', 0, $e->getMessage(), $e);
		} finally {
			$deadline->cancel();
			$socket?->close();
		}
	}

	/**
	 * Connects to the request's host and port, and for https completes the TLS handshake.
	 * @param RequestInterface $request The request.
	 * @param array<string, mixed> $options The resolved options.
	 * @param TCancellationToken $deadline The token of the transfer's timeout and cancellation.
	 * @throws THttpNetworkException When the connect or handshake fails.
	 * @return TSocketStream The connected socket, non-blocking.
	 */
	protected function connect(RequestInterface $request, array $options, TCancellationToken $deadline): TSocketStream
	{
		$uri = $request->getUri();
		$https = strtolower($uri->getScheme()) === 'https';
		$host = $uri->getHost();
		$peer = trim($host, '[]');
		$address = 'tcp://' . (str_contains($peer, ':') ? '[' . $peer . ']' : $peer) . ':' . ($uri->getPort() ?? ($https ? 443 : 80));
		$connectTimeout = (float) $options[THttpClientOption::CONNECT_TIMEOUT];
		$timeout = (float) $options[THttpClientOption::TIMEOUT];
		$limit = $connectTimeout > 0 ? $connectTimeout : ($timeout > 0 ? $timeout : null);
		$verify = $options[THttpClientOption::VERIFY];
		$ssl = [
			'peer_name' => $peer,
			'verify_peer' => $verify !== false,
			'verify_peer_name' => $verify !== false,
			'SNI_enabled' => true,
		];
		if (is_string($verify)) {
			$ssl[is_dir($verify) ? 'capath' : 'cafile'] = $verify;
		}
		try {
			$socket = TSocketStream::connectAsync($address, $limit, stream_context_create(['ssl' => $ssl]), $deadline)->await();
			if ($https) {
				$socket->enableCryptoAsync(true, null, $limit, $deadline)->await();
			}
		} catch (TSocketException $e) {
			throw new THttpNetworkException($request, 'httpclient_connect_failed', $address, $e->getMessage(), $e);
		}
		return $socket;
	}

	/**
	 * Writes the request line, the headers, and the body.
	 * @param TAsyncStream $stream The connection.
	 * @param RequestInterface $request The request.
	 * @param array<string, mixed> $options The resolved options.
	 * @param TCancellationToken $deadline The token of the transfer's timeout and cancellation.
	 */
	protected function writeRequest(TAsyncStream $stream, RequestInterface $request, array $options, TCancellationToken $deadline): void
	{
		$version = $request->getProtocolVersion() === '1.0' ? '1.0' : '1.1';
		$method = $request->getMethod();
		$body = $request->getBody();
		if ($body->isSeekable()) {
			$body->rewind();
		}
		$size = $body->getSize();
		$buffered = null;
		if ($size === null && $version === '1.0') {
			$buffered = $body->getContents();
			$size = strlen($buffered);
		}
		$lines = $this->formatHeaderLines($request, ['connection', 'content-length', 'transfer-encoding']);
		if (!$request->hasHeader('Host')) {
			$uri = $request->getUri();
			array_unshift($lines, 'Host: ' . $uri->getHost() . ($uri->getPort() === null ? '' : ':' . $uri->getPort()));
		}
		$upper = strtoupper($method);
		if ($size === null) {
			$lines[] = 'Transfer-Encoding: chunked';
		} elseif ($size > 0 || ($upper !== 'GET' && $upper !== 'HEAD')) {
			$lines[] = 'Content-Length: ' . $size;
		}
		if ($options[THttpClientOption::DECODE_CONTENT] && !$request->hasHeader('Accept-Encoding') && function_exists('inflate_init')) {
			$lines[] = 'Accept-Encoding: gzip, deflate';
		}
		$lines[] = 'Connection: close';
		$stream->write($method . ' ' . $request->getRequestTarget() . ' HTTP/' . $version . "\r\n" . implode("\r\n", $lines) . "\r\n\r\n", $deadline);

		$uploaded = 0;
		$total = $size ?? 0;
		if ($buffered !== null) {
			$stream->write($buffered, $deadline);
			$this->reportProgress($options, 0, 0, $total, strlen($buffered));
			return;
		}
		while (!$body->eof()) {
			$chunk = $body->read(static::CHUNK_SIZE);
			if ($chunk === '') {
				break;
			}
			$stream->write($size === null ? dechex(strlen($chunk)) . "\r\n" . $chunk . "\r\n" : $chunk, $deadline);
			$uploaded += strlen($chunk);
			$this->reportProgress($options, 0, 0, $total, $uploaded);
		}
		if ($size === null) {
			$stream->write("0\r\n\r\n", $deadline);
		}
	}

	/**
	 * Reads the response: skips interim responses, then reads the final header block and
	 * the body into the sink.
	 * @param TAsyncStream $stream The connection.
	 * @param RequestInterface $request The request.
	 * @param array<string, mixed> $options The resolved options.
	 * @param StreamInterface $sink The body sink.
	 * @param TCancellationToken $deadline The token of the transfer's timeout and cancellation.
	 * @throws THttpNetworkException When the response is invalid or incomplete.
	 * @return ResponseInterface The response.
	 */
	protected function readResponse(TAsyncStream $stream, RequestInterface $request, array $options, StreamInterface $sink, TCancellationToken $deadline): ResponseInterface
	{
		$buffer = '';
		do {
			$head = $this->readHead($stream, $request, $buffer, $deadline);
			$parsed = $this->parseHeaderLines(explode("\r\n", $head));
			if ($parsed === null) {
				throw new THttpNetworkException($request, 'httpclient_invalid_response', $this->getTargetUri($request), 'invalid status line');
			}
			[$version, $status, $reason, $headers] = $parsed;
		} while ($status >= 100 && $status < 200 && $status !== 101);

		$lower = [];
		foreach ($headers as $name => $values) {
			$lower[strtolower((string) $name)] = implode(',', $values);
		}
		$noBody = strtoupper($request->getMethod()) === 'HEAD' || $status < 200 || $status === 204 || $status === 304;
		if (!$noBody) {
			$decoder = $this->createDecoder($request, $options, $lower['content-encoding'] ?? '');
			$total = isset($lower['content-length']) && ctype_digit($lower['content-length']) ? (int) $lower['content-length'] : 0;
			$received = 0;
			$emit = function (string $data) use ($sink, $decoder, $options, $total, &$received, $request, $lower): void {
				$received += strlen($data);
				if ($decoder !== null) {
					$data = @inflate_add($decoder, $data, ZLIB_SYNC_FLUSH);
					if ($data === false) {
						throw new THttpNetworkException($request, 'httpclient_decode_failed', $lower['content-encoding'], $this->getTargetUri($request));
					}
				}
				if ($data !== '') {
					$sink->write($data);
				}
				$this->reportProgress($options, $total, $received, 0, 0);
			};
			$transferEncoding = strtolower($lower['transfer-encoding'] ?? '');
			if ($transferEncoding !== '' && str_ends_with(rtrim($transferEncoding), 'chunked')) {
				$this->readChunked($stream, $request, $buffer, $emit, $deadline);
			} elseif (isset($lower['content-length'])) {
				if (!ctype_digit($lower['content-length'])) {
					throw new THttpNetworkException($request, 'httpclient_invalid_response', $this->getTargetUri($request), 'invalid Content-Length');
				}
				$this->readLength($stream, $request, $buffer, $total, $emit, $deadline);
			} else {
				$this->readToEnd($stream, $buffer, $emit, $deadline);
			}
			if ($decoder !== null) {
				$headers = $this->renameEncodedHeaders($headers);
			}
		}
		return $this->createResponse($version, $status, $reason, $headers, $sink);
	}

	/**
	 * Reads one header block, up to and without the blank line that ends it.
	 * @param TAsyncStream $stream The connection.
	 * @param RequestInterface $request The request.
	 * @param string $buffer The bytes read past the previous part, by reference.
	 * @param TCancellationToken $deadline The token of the transfer's timeout and cancellation.
	 * @throws THttpNetworkException When the connection ends first or the block is too large.
	 * @return string The header block.
	 */
	protected function readHead(TAsyncStream $stream, RequestInterface $request, string &$buffer, TCancellationToken $deadline): string
	{
		while (($end = strpos($buffer, "\r\n\r\n")) === false) {
			if (strlen($buffer) > static::MAX_HEADER_SIZE) {
				throw new THttpNetworkException($request, 'httpclient_invalid_response', $this->getTargetUri($request), 'the header block is too large');
			}
			$data = $stream->read(static::CHUNK_SIZE, $deadline);
			if ($data === '') {
				throw new THttpNetworkException($request, 'httpclient_invalid_response', $this->getTargetUri($request), 'the connection closed before the headers ended');
			}
			$buffer .= $data;
		}
		$head = substr($buffer, 0, $end);
		$buffer = (string) substr($buffer, $end + 4);
		return $head;
	}

	/**
	 * Reads a body of a known length.
	 * @param TAsyncStream $stream The connection.
	 * @param RequestInterface $request The request.
	 * @param string $buffer The bytes read past the header block, by reference.
	 * @param int $length The body length.
	 * @param callable $emit Receives each part of the body.
	 * @param TCancellationToken $deadline The token of the transfer's timeout and cancellation.
	 * @throws THttpNetworkException When the connection ends before the body is complete.
	 */
	protected function readLength(TAsyncStream $stream, RequestInterface $request, string &$buffer, int $length, callable $emit, TCancellationToken $deadline): void
	{
		$remaining = $length;
		while ($remaining > 0) {
			if ($buffer === '') {
				$buffer = $stream->read(min($remaining, static::CHUNK_SIZE), $deadline);
				if ($buffer === '') {
					throw new THttpNetworkException($request, 'httpclient_response_incomplete', $this->getTargetUri($request));
				}
			}
			$part = substr($buffer, 0, $remaining);
			$buffer = (string) substr($buffer, strlen($part));
			$remaining -= strlen($part);
			$emit($part);
		}
	}

	/**
	 * Reads a body to the end of the connection.
	 * @param TAsyncStream $stream The connection.
	 * @param string $buffer The bytes read past the header block, by reference.
	 * @param callable $emit Receives each part of the body.
	 * @param TCancellationToken $deadline The token of the transfer's timeout and cancellation.
	 */
	protected function readToEnd(TAsyncStream $stream, string &$buffer, callable $emit, TCancellationToken $deadline): void
	{
		if ($buffer !== '') {
			$emit($buffer);
			$buffer = '';
		}
		while (($data = $stream->read(static::CHUNK_SIZE, $deadline)) !== '') {
			$emit($data);
		}
	}

	/**
	 * Reads a chunked body and its trailer section.  Trailer fields are discarded.
	 * @param TAsyncStream $stream The connection.
	 * @param RequestInterface $request The request.
	 * @param string $buffer The bytes read past the header block, by reference.
	 * @param callable $emit Receives each chunk.
	 * @param TCancellationToken $deadline The token of the transfer's timeout and cancellation.
	 * @throws THttpNetworkException When the framing is invalid or the connection ends early.
	 */
	protected function readChunked(TAsyncStream $stream, RequestInterface $request, string &$buffer, callable $emit, TCancellationToken $deadline): void
	{
		while (true) {
			$line = $this->readLine($stream, $request, $buffer, $deadline);
			$size = trim(explode(';', $line, 2)[0]);
			if ($size === '' || !ctype_xdigit($size) || strlen($size) > 15) {
				throw new THttpNetworkException($request, 'httpclient_invalid_response', $this->getTargetUri($request), 'invalid chunk size');
			}
			$length = (int) hexdec($size);
			if ($length === 0) {
				while ($this->readLine($stream, $request, $buffer, $deadline) !== '') {
				}
				return;
			}
			$this->readLength($stream, $request, $buffer, $length, $emit, $deadline);
			if ($this->readLine($stream, $request, $buffer, $deadline) !== '') {
				throw new THttpNetworkException($request, 'httpclient_invalid_response', $this->getTargetUri($request), 'a chunk is longer than its size');
			}
		}
	}

	/**
	 * Reads one CRLF-terminated line of the chunked framing.
	 * @param TAsyncStream $stream The connection.
	 * @param RequestInterface $request The request.
	 * @param string $buffer The unread bytes, by reference.
	 * @param TCancellationToken $deadline The token of the transfer's timeout and cancellation.
	 * @throws THttpNetworkException When the line is too long or the connection ends first.
	 * @return string The line without its CRLF.
	 */
	protected function readLine(TAsyncStream $stream, RequestInterface $request, string &$buffer, TCancellationToken $deadline): string
	{
		while (($end = strpos($buffer, "\r\n")) === false) {
			if (strlen($buffer) > static::MAX_LINE_SIZE) {
				throw new THttpNetworkException($request, 'httpclient_invalid_response', $this->getTargetUri($request), 'a chunk line is too long');
			}
			$data = $stream->read(static::CHUNK_SIZE, $deadline);
			if ($data === '') {
				throw new THttpNetworkException($request, 'httpclient_response_incomplete', $this->getTargetUri($request));
			}
			$buffer .= $data;
		}
		$line = substr($buffer, 0, $end);
		$buffer = (string) substr($buffer, $end + 2);
		return $line;
	}

	/**
	 * Creates the zlib decoder of a response's content coding.
	 * @param RequestInterface $request The request.
	 * @param array<string, mixed> $options The resolved options.
	 * @param string $encoding The `Content-Encoding` header value.
	 * @return mixed The `inflate_init()` context, or null when the body is not decoded.
	 */
	protected function createDecoder(RequestInterface $request, array $options, string $encoding): mixed
	{
		if (!$options[THttpClientOption::DECODE_CONTENT] || !function_exists('inflate_init')) {
			return null;
		}
		$encoding = strtolower(trim($encoding));
		$mode = match ($encoding) {
			'gzip', 'x-gzip' => ZLIB_ENCODING_GZIP,
			'deflate' => ZLIB_ENCODING_DEFLATE,
			default => null,
		};
		if ($mode === null) {
			return null;
		}
		$decoder = @inflate_init($mode);
		return $decoder === false ? null : $decoder;
	}

	/**
	 * Moves `Content-Encoding` and `Content-Length` of a decoded response to
	 * `X-Encoded-Content-Encoding` and `X-Encoded-Content-Length`.
	 * @param array<string, string[]> $headers The response headers.
	 * @return array<string, string[]> The headers of the decoded body.
	 */
	protected function renameEncodedHeaders(array $headers): array
	{
		$out = [];
		foreach ($headers as $name => $values) {
			$lower = strtolower((string) $name);
			if ($lower === 'content-encoding' || $lower === 'content-length') {
				$out['X-Encoded-' . $name] = $values;
			} else {
				$out[$name] = $values;
			}
		}
		return $out;
	}
}
