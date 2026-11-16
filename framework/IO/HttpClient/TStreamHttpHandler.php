<?php

/**
 * TStreamHttpHandler class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\HttpClient;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * TStreamHttpHandler class.
 *
 * TStreamHttpHandler sends a request through PHP's `http://` stream wrapper, so it needs
 * `allow_url_fopen` and no extension.  The wrapper blocks the process while it connects
 * and reads: a fiber that sends through it blocks every other fiber.
 *
 * The response headers come from the stream's `wrapper_data` metadata.  The handler
 * follows no redirect and reads 4xx and 5xx bodies.  The wrapper sends the request body
 * from memory, so the whole body is read first.  {@see THttpClientOption::TIMEOUT}
 * applies to the connect and to each read, and a transfer that runs past it in total is
 * stopped between reads; {@see THttpClientOption::CONNECT_TIMEOUT} is not separate.  The
 * wrapper does not decode compressed bodies, so the handler sends no `Accept-Encoding`
 * of its own.  Protocol versions other than 1.0 are sent as 1.1.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TStreamHttpHandler extends THttpHandler
{
	/**
	 * Reports whether `allow_url_fopen` is on.  `ini_get()` returns the directive as a
	 * string, so `"Off"` is read through `FILTER_VALIDATE_BOOLEAN` rather than cast.
	 * @return bool Whether `allow_url_fopen` is on.
	 */
	public static function getIsAvailable(): bool
	{
		return filter_var(ini_get('allow_url_fopen'), FILTER_VALIDATE_BOOLEAN);
	}

	/**
	 * Sends a request through the `http://` stream wrapper and returns its response.
	 * @param RequestInterface $request The request.
	 * @param array<string, mixed> $options The {@see THttpClientOption} options.
	 * @throws THttpNetworkException When no response arrives.
	 * @throws THttpRequestException When the request cannot be sent.
	 * @throws \Prado\Exceptions\TCancelledException When the token is cancelled.
	 * @return ResponseInterface The response.
	 */
	public function handle(RequestInterface $request, array $options = []): ResponseInterface
	{
		$options = $this->resolveOptions($options);
		$this->assertSendable($request);
		$token = $this->getToken($options);
		$token?->throwIfCancelled();
		$sink = $this->createSink($request, $options);
		$uri = $this->getTargetUri($request);
		$timeout = (float) $options[THttpClientOption::TIMEOUT];
		$deadline = $timeout > 0 ? hrtime(true) + (int) ($timeout * 1e9) : null;

		error_clear_last();
		$source = @fopen($uri, 'rb', false, $this->createContext($request, $options));
		if ($source === false) {
			$message = error_get_last()['message'] ?? "Failed to open '{$uri}'.";
			if (stripos($message, 'timed out') !== false && $timeout > 0) {
				throw $this->createTimeoutException($request, $timeout);
			}
			throw new THttpNetworkException($request, 'httpclient_transport_error', 0, $message);
		}
		try {
			$meta = stream_get_meta_data($source);
			$parsed = $this->parseHeaderLines(is_array($meta['wrapper_data'] ?? null) ? $meta['wrapper_data'] : []);
			if ($parsed === null) {
				throw new THttpNetworkException($request, 'httpclient_invalid_response', $uri, 'no status line');
			}
			[$version, $status, $reason, $headers] = $parsed;
			$total = 0;
			foreach ($headers as $name => $values) {
				if (strtolower((string) $name) === 'content-length') {
					$total = max(0, (int) end($values));
				}
			}
			$downloaded = 0;
			while (!feof($source)) {
				$chunk = fread($source, static::CHUNK_SIZE);
				if (stream_get_meta_data($source)['timed_out'] || ($deadline !== null && hrtime(true) > $deadline)) {
					throw $this->createTimeoutException($request, $timeout);
				}
				if ($chunk === false) {
					throw new THttpNetworkException($request, 'httpclient_response_incomplete', $uri);
				}
				if ($chunk === '') {
					continue;
				}
				$sink->write($chunk);
				$downloaded += strlen($chunk);
				$token?->throwIfCancelled();
				$this->reportProgress($options, $total, $downloaded, 0, 0);
			}
		} finally {
			fclose($source);
		}
		return $this->createResponse($version, $status, $reason, $headers, $sink);
	}

	/**
	 * Builds the stream context of a request: the `http` options and the `ssl` peer
	 * verification.
	 * @param RequestInterface $request The request.
	 * @param array<string, mixed> $options The resolved options.
	 * @return resource The stream context.
	 */
	protected function createContext(RequestInterface $request, array $options): mixed
	{
		$http = [
			'method' => $request->getMethod(),
			'header' => $this->formatHeaderLines($request),
			'protocol_version' => $request->getProtocolVersion() === '1.0' ? 1.0 : 1.1,
			'ignore_errors' => true,
			'follow_location' => 0,
		];
		$timeout = (float) $options[THttpClientOption::TIMEOUT];
		if ($timeout > 0) {
			$http['timeout'] = $timeout;
		}
		$content = $this->getBodyContents($request);
		$method = strtoupper($request->getMethod());
		if ($content !== '' || ($method !== 'GET' && $method !== 'HEAD')) {
			$http['content'] = $content;
		}
		$verify = $options[THttpClientOption::VERIFY];
		$ssl = [
			'verify_peer' => $verify !== false,
			'verify_peer_name' => $verify !== false,
			'peer_name' => $request->getUri()->getHost(),
			'SNI_enabled' => true,
		];
		if (is_string($verify)) {
			$ssl[is_dir($verify) ? 'capath' : 'cafile'] = $verify;
		}
		return stream_context_create(['http' => $http, 'ssl' => $ssl]);
	}
}
