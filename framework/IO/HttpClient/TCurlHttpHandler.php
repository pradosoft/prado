<?php

/**
 * TCurlHttpHandler class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\HttpClient;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * TCurlHttpHandler class.
 *
 * TCurlHttpHandler sends a request with a cURL easy handle.  `curl_exec()` blocks until
 * the transfer ends, so a fiber that sends through it blocks every other fiber;
 * {@see TCurlMultiHttpHandler} is its asynchronous counterpart.  {@see THttpClient}
 * chooses this handler for a request sent from the main fiber when cURL is loaded.
 *
 * The handler restricts cURL to the http and https protocols and follows no redirect.
 * A request body of at most {@see POSTFIELDS_MAX} bytes is sent from memory; a larger or
 * unsized body is read from its stream as cURL sends it, with `Transfer-Encoding:
 * chunked` when the size is unknown.  The `Expect: 100-continue` handshake is off.  With
 * {@see THttpClientOption::DECODE_CONTENT} true, cURL asks for and decodes every
 * encoding it supports, or the ones of the request's `Accept-Encoding` header; the
 * response keeps its `Content-Encoding` header.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TCurlHttpHandler extends THttpHandler
{
	/** The largest request body, in bytes, sent from memory. */
	public const POSTFIELDS_MAX = 1048576;

	/**
	 * Reports whether the cURL extension is loaded.
	 * @return bool Whether cURL is loaded.
	 */
	public static function getIsAvailable(): bool
	{
		return function_exists('curl_init');
	}

	/**
	 * Sends a request with a cURL easy handle and returns its response.
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
		$transfer = $this->createTransfer($request, $options);
		curl_exec($transfer->getHandle());
		return $this->completeTransfer($transfer, curl_errno($transfer->getHandle()));
	}

	/**
	 * Builds a configured cURL transfer for a request.
	 * @param RequestInterface $request The request.
	 * @param array<string, mixed> $options The resolved options.
	 * @throws THttpRequestException When the sink is invalid.
	 * @return TCurlTransfer The transfer, ready for `curl_exec()` or a multi handle.
	 */
	protected function createTransfer(RequestInterface $request, array $options): TCurlTransfer
	{
		$transfer = new TCurlTransfer($request, $options, $this->createSink($request, $options));
		$ch = curl_init();
		$transfer->setHandle($ch);
		$method = $request->getMethod();

		curl_setopt($ch, CURLOPT_URL, $this->getTargetUri($request));
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
		curl_setopt($ch, CURLOPT_HTTP_VERSION, $this->getCurlHttpVersion($request->getProtocolVersion()));
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_NOSIGNAL, true);
		if (defined('CURLOPT_PROTOCOLS_STR')) {
			curl_setopt($ch, CURLOPT_PROTOCOLS_STR, 'http,https');
		} else {
			curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
		}
		curl_setopt($ch, CURLOPT_TIMEOUT_MS, (int) round(1000 * (float) $options[THttpClientOption::TIMEOUT]));
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, (int) round(1000 * (float) $options[THttpClientOption::CONNECT_TIMEOUT]));
		$this->applyVerify($ch, $options[THttpClientOption::VERIFY]);
		if (strtoupper($method) === 'HEAD') {
			curl_setopt($ch, CURLOPT_NOBODY, true);
		}

		$omit = ['expect'];
		if ($options[THttpClientOption::DECODE_CONTENT]) {
			curl_setopt($ch, CURLOPT_ACCEPT_ENCODING, $request->getHeaderLine('Accept-Encoding'));
			$omit[] = 'accept-encoding';
		}
		$headers = $this->formatHeaderLines($request, $omit);
		$headers[] = 'Expect:';
		$this->applyBody($ch, $request, $transfer, $headers);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		curl_setopt($ch, CURLOPT_HEADERFUNCTION, static function ($ch, string $line) use ($transfer): int {
			if (str_starts_with($line, 'HTTP/')) {
				$transfer->clearHeaderLines();
			}
			$trimmed = rtrim($line, "\r\n");
			if ($trimmed !== '') {
				$transfer->addHeaderLine($trimmed);
			}
			return strlen($line);
		});
		curl_setopt($ch, CURLOPT_WRITEFUNCTION, static function ($ch, string $data) use ($transfer): int {
			try {
				return $transfer->getSink()->write($data);
			} catch (\Throwable $e) {
				$transfer->setError($e);
				return 0;
			}
		});
		if ($options[THttpClientOption::PROGRESS] !== null || $this->getToken($options) !== null) {
			curl_setopt($ch, CURLOPT_NOPROGRESS, false);
			curl_setopt($ch, CURLOPT_XFERINFOFUNCTION, function ($ch, int $downloadTotal, int $downloaded, int $uploadTotal, int $uploaded) use ($transfer): int {
				try {
					$this->getToken($transfer->getOptions())?->throwIfCancelled();
					$this->reportProgress($transfer->getOptions(), $downloadTotal, $downloaded, $uploadTotal, $uploaded);
				} catch (\Throwable $e) {
					$transfer->setError($e);
					return 1;
				}
				return 0;
			});
		}
		return $transfer;
	}

	/**
	 * Sets the request body on a handle: from memory up to {@see POSTFIELDS_MAX} bytes,
	 * otherwise read from the body stream as cURL sends it.
	 * @param \CurlHandle $ch The handle.
	 * @param RequestInterface $request The request.
	 * @param TCurlTransfer $transfer The transfer, which records a read failure.
	 * @param string[] $headers The header lines, which gain `Transfer-Encoding: chunked` for an unsized body.
	 */
	protected function applyBody(\CurlHandle $ch, RequestInterface $request, TCurlTransfer $transfer, array &$headers): void
	{
		$body = $request->getBody();
		$size = $body->getSize();
		$method = strtoupper($request->getMethod());
		if ($size === 0) {
			if ($method !== 'GET' && $method !== 'HEAD') {
				curl_setopt($ch, CURLOPT_POSTFIELDS, '');
			}
			return;
		}
		if ($size !== null && $size <= static::POSTFIELDS_MAX) {
			curl_setopt($ch, CURLOPT_POSTFIELDS, $this->getBodyContents($request));
			return;
		}
		if ($body->isSeekable()) {
			$body->rewind();
		}
		curl_setopt($ch, CURLOPT_UPLOAD, true);
		if ($size !== null) {
			curl_setopt($ch, CURLOPT_INFILESIZE, $size);
		} elseif (!$request->hasHeader('Transfer-Encoding')) {
			$headers[] = 'Transfer-Encoding: chunked';
		}
		curl_setopt($ch, CURLOPT_READFUNCTION, static function ($ch, $fd, int $length) use ($body, $transfer): string {
			try {
				return $body->eof() ? '' : $body->read($length);
			} catch (\Throwable $e) {
				$transfer->setError($e);
				return '';
			}
		});
	}

	/**
	 * Sets the TLS peer verification of a handle.
	 * @param \CurlHandle $ch The handle.
	 * @param bool|string $verify Whether to verify, or the CA file or directory.
	 */
	protected function applyVerify(\CurlHandle $ch, bool|string $verify): void
	{
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $verify !== false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $verify === false ? 0 : 2);
		if (is_string($verify)) {
			curl_setopt($ch, is_dir($verify) ? CURLOPT_CAPATH : CURLOPT_CAINFO, $verify);
		}
	}

	/**
	 * Returns the cURL constant of a protocol version.
	 * @param string $version The protocol version, such as `1.1`.
	 * @return int The `CURL_HTTP_VERSION_*` constant.
	 */
	protected function getCurlHttpVersion(string $version): int
	{
		return match ($version) {
			'1.0' => CURL_HTTP_VERSION_1_0,
			'2', '2.0' => CURL_HTTP_VERSION_2_0,
			default => CURL_HTTP_VERSION_1_1,
		};
	}

	/**
	 * Builds the response of a finished transfer, or throws its failure.
	 * @param TCurlTransfer $transfer The finished transfer.
	 * @param int $result The `CURLE_*` result.
	 * @throws \Throwable The failure of the transfer.
	 * @return ResponseInterface The response.
	 */
	protected function completeTransfer(TCurlTransfer $transfer, int $result): ResponseInterface
	{
		$error = $this->getTransferError($transfer, $result);
		if ($error !== null) {
			throw $error;
		}
		$parsed = $this->parseHeaderLines($transfer->getHeaderLines());
		if ($parsed === null) {
			throw new THttpNetworkException($transfer->getRequest(), 'httpclient_invalid_response', $this->getTargetUri($transfer->getRequest()), 'no status line');
		}
		[$version, $status, $reason, $headers] = $parsed;
		return $this->createResponse($version, $status, $reason, $headers, $transfer->getSink());
	}

	/**
	 * Returns the failure of a finished transfer: an exception raised in a callback, a
	 * cancellation, a timeout, or a cURL error.
	 * @param TCurlTransfer $transfer The finished transfer.
	 * @param int $result The `CURLE_*` result.
	 * @return ?\Throwable The failure, or null when the transfer succeeded.
	 */
	protected function getTransferError(TCurlTransfer $transfer, int $result): ?\Throwable
	{
		if ($transfer->getError() !== null) {
			return $transfer->getError();
		}
		if ($result === CURLE_OK) {
			return null;
		}
		$request = $transfer->getRequest();
		if ($result === CURLE_OPERATION_TIMEDOUT && (float) $transfer->getOptions()[THttpClientOption::TIMEOUT] > 0) {
			return $this->createTimeoutException($request, (float) $transfer->getOptions()[THttpClientOption::TIMEOUT]);
		}
		if ($result === CURLE_UNSUPPORTED_PROTOCOL) {
			return new THttpRequestException($request, 'httpclient_scheme_unsupported', $request->getUri()->getScheme());
		}
		$message = curl_error($transfer->getHandle());
		return new THttpNetworkException($request, 'httpclient_transport_error', $result, $message === '' ? curl_strerror($result) : $message);
	}
}
