<?php

/**
 * THttpHandler class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\HttpClient;

use Prado\IO\Async\TCancellationToken;
use Prado\IO\Http\TResponse;
use Prado\IO\TStream;
use Prado\TApplicationComponent;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * THttpHandler class.
 *
 * THttpHandler is the abstract base of the framework's {@see IHttpHandler} transports.  It
 * holds what every transport shares: the option defaults, the scheme check, the header
 * lines of a request, the response sink, the parsing of a response header block, and
 * progress reports.
 *
 * Used directly, without a {@see THttpClient}, a handler applies these defaults:
 *
 * | Option | Default |
 * |---|---|
 * | {@see THttpClientOption::TIMEOUT}, {@see THttpClientOption::CONNECT_TIMEOUT} | 0, no limit |
 * | {@see THttpClientOption::VERIFY} | true |
 * | {@see THttpClientOption::DECODE_CONTENT} | true |
 * | {@see THttpClientOption::SINK} | a new `php://temp` {@see TStream} |
 *
 * A handler extends {@see TApplicationComponent}, so it listens for global `fx` events
 * such as `fxPrepareForFork`.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
abstract class THttpHandler extends TApplicationComponent implements IHttpHandler
{
	/** The request URI schemes a handler sends. */
	public const SCHEMES = ['http', 'https'];

	/** The size, in bytes, of one read or write of a body. */
	public const CHUNK_SIZE = 65536;

	/**
	 * Reports whether this handler can run on the current PHP.  The base answer is true.
	 * @return bool Whether the handler can run here.
	 */
	public static function getIsAvailable(): bool
	{
		return true;
	}

	/**
	 * Merges the options over the handler defaults.
	 * @param array<string, mixed> $options The options of a request.
	 * @return array<string, mixed> The options with every handler key present.
	 */
	protected function resolveOptions(array $options): array
	{
		return $options + [
			THttpClientOption::TIMEOUT => 0.0,
			THttpClientOption::CONNECT_TIMEOUT => 0.0,
			THttpClientOption::VERIFY => true,
			THttpClientOption::DECODE_CONTENT => true,
			THttpClientOption::SINK => null,
			THttpClientOption::PROGRESS => null,
			THttpClientOption::TOKEN => null,
		];
	}

	/**
	 * Checks that a request has an http or https URI with a host.
	 * @param RequestInterface $request The request.
	 * @throws THttpRequestException When the scheme is not http or https, or the URI has no host.
	 */
	protected function assertSendable(RequestInterface $request): void
	{
		$uri = $request->getUri();
		$scheme = strtolower($uri->getScheme());
		if (!in_array($scheme, static::SCHEMES, true)) {
			throw new THttpRequestException($request, 'httpclient_scheme_unsupported', $scheme);
		}
		if ($uri->getHost() === '') {
			throw new THttpRequestException($request, 'httpclient_host_missing', (string) $uri);
		}
	}

	/**
	 * Returns the URI a transport requests: the request URI without its fragment.
	 * @param RequestInterface $request The request.
	 * @return string The URI.
	 */
	protected function getTargetUri(RequestInterface $request): string
	{
		return (string) $request->getUri()->withFragment('');
	}

	/**
	 * Formats the headers of a request as `Name: value` lines, one line per header.  The
	 * values of a header are joined with a comma; `Cookie` values with `; `.  CR, LF, and
	 * NUL are removed, for a request whose class does not validate its headers.
	 * @param RequestInterface $request The request.
	 * @param string[] $omit The lower-case names of the headers to leave out.
	 * @return string[] The header lines.
	 */
	protected function formatHeaderLines(RequestInterface $request, array $omit = []): array
	{
		$lines = [];
		foreach ($request->getHeaders() as $name => $values) {
			$name = (string) $name;
			$lower = strtolower($name);
			if (in_array($lower, $omit, true)) {
				continue;
			}
			$value = implode($lower === 'cookie' ? '; ' : ', ', $values);
			$lines[] = str_replace(["\r", "\n", "\0"], '', $name . ': ' . $value);
		}
		return $lines;
	}

	/**
	 * Reads the whole body of a request, from the start when the body is seekable.
	 * @param RequestInterface $request The request.
	 * @return string The body.
	 */
	protected function getBodyContents(RequestInterface $request): string
	{
		$body = $request->getBody();
		if ($body->isSeekable()) {
			$body->rewind();
		}
		return $body->getContents();
	}

	/**
	 * Returns the stream the response body is written to: the {@see THttpClientOption::SINK}
	 * option, or a new `php://temp` stream.  A file path is opened for writing and
	 * truncated; a resource is wrapped without taking ownership.
	 * @param RequestInterface $request The request.
	 * @param array<string, mixed> $options The resolved options.
	 * @throws THttpRequestException When the sink is of another type.
	 * @return StreamInterface The sink.
	 */
	protected function createSink(RequestInterface $request, array $options): StreamInterface
	{
		$sink = $options[THttpClientOption::SINK] ?? null;
		if ($sink === null) {
			return TStream::fromTemp();
		}
		if ($sink instanceof StreamInterface) {
			return $sink;
		}
		if (is_resource($sink)) {
			return TStream::fromResource($sink, false);
		}
		if (is_string($sink) && $sink !== '') {
			return TStream::fromFile($sink, 'w+b');
		}
		throw new THttpRequestException($request, 'httpclient_sink_invalid', get_debug_type($sink));
	}

	/**
	 * Parses a response header block: the status line and the header lines after it.  The
	 * last status line starts the block, so an earlier interim block, such as
	 * `100 Continue`, is skipped.
	 * @param string[] $lines The status line and header lines, without line endings.
	 * @return ?array{0: string, 1: int, 2: string, 3: array<string, string[]>} The protocol
	 *   version, status code, reason phrase, and headers; null when there is no valid status line.
	 */
	protected function parseHeaderLines(array $lines): ?array
	{
		$status = null;
		$headers = [];
		foreach ($lines as $line) {
			$line = rtrim($line, "\r\n");
			if (str_starts_with($line, 'HTTP/')) {
				if (preg_match('#^HTTP/(\d+(?:\.\d+)?)\s+(\d{3})(?:\s+(.*))?$#', $line, $m) !== 1) {
					return null;
				}
				$status = [$m[1], (int) $m[2], trim($m[3] ?? '')];
				$headers = [];
				continue;
			}
			$colon = strpos($line, ':');
			if ($colon === false || $colon === 0) {
				continue;
			}
			$name = trim(substr($line, 0, $colon));
			$value = trim(substr($line, $colon + 1), " \t");
			if (preg_match('/^[a-zA-Z0-9\'`#$%&*+.^_|~!-]+$/', $name) !== 1 || preg_match('/^[\x20\x09\x21-\x7E\x80-\xFF]*$/D', $value) !== 1) {
				continue;
			}
			$headers[$name][] = $value;
		}
		if ($status === null || $status[1] < 100 || $status[1] > 599) {
			return null;
		}
		return [$status[0], $status[1], $status[2], $headers];
	}

	/**
	 * Builds the response of a completed transfer.  A seekable body is rewound.
	 * @param string $version The protocol version.
	 * @param int $status The status code.
	 * @param string $reason The reason phrase, or `''` for the standard phrase.
	 * @param array<string, string[]> $headers The headers.
	 * @param StreamInterface $body The body.
	 * @return ResponseInterface The {@see TResponse}.
	 */
	protected function createResponse(string $version, int $status, string $reason, array $headers, StreamInterface $body): ResponseInterface
	{
		if ($body->isSeekable()) {
			$body->rewind();
		}
		return new TResponse($status, $headers, $body, $version, $reason === '' ? null : $reason);
	}

	/**
	 * Calls the {@see THttpClientOption::PROGRESS} callback, when one is set.
	 * @param array<string, mixed> $options The resolved options.
	 * @param int $downloadTotal The expected bytes to download, or 0 when unknown.
	 * @param int $downloaded The bytes downloaded.
	 * @param int $uploadTotal The expected bytes to upload, or 0 when unknown.
	 * @param int $uploaded The bytes uploaded.
	 */
	protected function reportProgress(array $options, int $downloadTotal, int $downloaded, int $uploadTotal, int $uploaded): void
	{
		$progress = $options[THttpClientOption::PROGRESS] ?? null;
		if ($progress !== null) {
			$progress($downloadTotal, $downloaded, $uploadTotal, $uploaded);
		}
	}

	/**
	 * Returns the {@see THttpClientOption::TOKEN} option.
	 * @param array<string, mixed> $options The resolved options.
	 * @return ?TCancellationToken The token, or null.
	 */
	protected function getToken(array $options): ?TCancellationToken
	{
		$token = $options[THttpClientOption::TOKEN] ?? null;
		return $token instanceof TCancellationToken ? $token : null;
	}

	/**
	 * Builds the exception of a transfer that ran past its {@see THttpClientOption::TIMEOUT}.
	 * @param RequestInterface $request The request.
	 * @param float $seconds The timeout.
	 * @return THttpNetworkException The exception.
	 */
	protected function createTimeoutException(RequestInterface $request, float $seconds): THttpNetworkException
	{
		return new THttpNetworkException($request, 'httpclient_timeout', $this->getTargetUri($request), $seconds);
	}
}
