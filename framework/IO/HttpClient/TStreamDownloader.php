<?php

/**
 * TStreamDownloader class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\HttpClient;

use Prado\IO\TStreamNotificationCallback;

/**
 * TStreamDownloader class.
 *
 * TStreamDownloader is a streaming, **download-oriented** variant of
 * {@see TFopenHttpClient}. Where the parent reads the whole response into
 * memory via `file_get_contents()`, this class opens an `fopen` stream and
 * copies it to a destination — either a file path or an open PHP stream
 * resource — in chunks, so very large transfers do not load the entire body
 * into memory.
 *
 * It also exposes a {@see setNotification() Notification} property that
 * accepts a {@see TStreamNotificationCallback}, which the stream wrapper
 * notifies as the transfer progresses. Callers can listen to
 * `onResolve`, `onConnected`, `onMimeType`, `onFileSize`, `onProgress`,
 * `onRedirected`, `onAuthRequired`, `onAuthResult`, `onCompleted`,
 * and `onFailure` — see {@see TStreamNotificationCallback} for the full
 * set and how the supplied {@see \Prado\IO\TStreamNotificationParameter}
 * is populated.
 *
 * The streaming code path is engaged either by calling
 * {@see downloadTo()} (which supports an explicit destination), or by
 * calling the standard {@see download()} method, which writes the response
 * into an in-memory buffer just like the parent class — but still emits
 * notifications along the way.
 *
 * ## Example
 *
 * ```php
 * $callback = new TStreamNotificationCallback();
 * $callback->onProgress[] = function ($sender, $param) {
 *     printf("transferred %d / %d bytes\n",
 *         $param->getBytesTransferred(), $param->getBytesMax());
 * };
 *
 * $downloader = (new TStreamDownloader())->setNotification($callback);
 * $response = $downloader->downloadTo(
 *     'https://example.com/large.bin',
 *     '/tmp/large.bin'
 * );
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TStreamDownloader extends TFopenHttpClient
{
	/**
	 * @var ?TStreamNotificationCallback Optional progress / lifecycle
	 *   listener for stream events.
	 */
	private ?TStreamNotificationCallback $_notification = null;

	/**
	 * @var int Chunk size, in bytes, used when copying the source stream to
	 *   the destination. Defaults to 8 KiB.
	 */
	private int $_chunkSize = 8192;

	/**
	 * Performs an HTTP request, streaming the response body into memory.
	 *
	 * Identical externally to {@see TFopenHttpClient::download()}, but routes
	 * through the streaming code path so {@see getNotification() Notification}
	 * events fire.
	 *
	 * @param string $method HTTP verb.
	 * @param string $url Absolute URL.
	 * @param array<string,string> $headers Request headers.
	 * @param ?string $body Raw request body, or null.
	 * @throws THttpClientException on transport failure.
	 * @return THttpClientResponse
	 */
	public function download(string $method, string $url, array $headers = [], ?string $body = null): THttpClientResponse
	{
		return $this->streamRequest($method, $url, $headers, $body, null);
	}

	/**
	 * Streams a `GET` response into the given destination.
	 *
	 * `$destination` may be either:
	 *
	 *  - a **file path**: the file is opened with mode `'wb'`, replacing any
	 *    existing content; the downloader closes the handle when done.
	 *  - an **open stream resource**: bytes are written to it directly and
	 *    the caller retains ownership (the downloader does not close it).
	 *
	 * The returned response has the upstream status and headers but an empty
	 * body — the body is already on disk (or in the caller's stream).
	 *
	 * @param string $url Absolute URL to fetch.
	 * @param resource|string $destination File path or open writable stream.
	 * @param array<string,string> $headers Request headers.
	 * @throws THttpClientException on transport failure or when the
	 *   destination cannot be opened.
	 * @return THttpClientResponse
	 */
	public function downloadTo(string $url, mixed $destination, array $headers = []): THttpClientResponse
	{
		return $this->streamRequest('GET', $url, $headers, null, $destination);
	}

	/**
	 * Core streaming implementation shared by {@see download()} and
	 * {@see downloadTo()}.
	 *
	 * @param string $method HTTP verb.
	 * @param string $url Absolute URL.
	 * @param array<string,string> $headers Request headers.
	 * @param ?string $body Raw request body.
	 * @param ?(resource|string) $destination File path, stream, or null to buffer in memory.
	 * @throws THttpClientException
	 * @return THttpClientResponse
	 */
	protected function streamRequest(string $method, string $url, array $headers, ?string $body, mixed $destination): THttpClientResponse
	{
		$context = $this->createStreamContext($method, $headers, $body);

		// Suppress the warning fopen emits on transport failure; failure is
		// reported explicitly via the exception.
		$source = @fopen($url, 'rb', false, $context);
		if ($source === false) {
			throw new THttpClientException('httpclient_transport_error', 0, "Failed to open '{$url}'.");
		}

		try {
			// Grab response headers from the wrapper metadata before reading.
			$meta = stream_get_meta_data($source);
			$rawHeaders = $meta['wrapper_data'] ?? [];

			[$dest, $ownsDest] = $this->resolveDestination($destination);

			try {
				$buffered = $this->copy($source, $dest);
			} finally {
				if ($ownsDest && is_resource($dest)) {
					fclose($dest);
				}
			}
		} finally {
			fclose($source);
		}

		[$statusCode, $responseHeaders] = $this->parseResponseHeaders($rawHeaders);
		if ($statusCode === 0) {
			// Non-HTTP schemes (file://, php://) produce no status line. When
			// the stream opened cleanly, treat that as success.
			$statusCode = $this->isHttpScheme($url) ? 0 : 200;
			if ($statusCode === 0) {
				throw new THttpClientException('httpclient_transport_error', 0, "No response headers from '{$url}'.");
			}
		}

		return new THttpClientResponse(
			$statusCode,
			$responseHeaders,
			$destination === null ? $buffered : ''
		);
	}

	/**
	 * Builds the stream context with HTTP options and the optional
	 * notification callback.
	 * @param string $method HTTP verb.
	 * @param array<string,string> $headers Request headers.
	 * @param ?string $body Request body.
	 * @return resource Stream context.
	 */
	protected function createStreamContext(string $method, array $headers, ?string $body): mixed
	{
		$httpOptions = [
			'method' => strtoupper($method),
			'header' => $this->formatHeaderLines($headers),
			'timeout' => $this->getTimeout(),
			'ignore_errors' => true,
			'follow_location' => $this->getFollowRedirects() ? 1 : 0,
			'max_redirects' => $this->getMaxRedirects(),
			'protocol_version' => 1.1,
		];
		if ($body !== null) {
			$httpOptions['content'] = $body;
		}

		$contextOpts = ['http' => $httpOptions, 'https' => $httpOptions];
		if ($this->_notification !== null) {
			$contextOpts['notification'] = $this->_notification;
		}

		return TStreamNotificationCallback::filterStreamContext($contextOpts);
	}

	/**
	 * Normalizes the destination argument.
	 *
	 * Strings are opened as files (mode `'wb'`); resources are passed through;
	 * `null` means "buffer in memory" (the caller will accumulate via
	 * {@see copy()}).
	 *
	 * @param ?(resource|string) $destination
	 * @throws THttpClientException when a string path cannot be opened.
	 * @return array{0: ?resource, 1: bool} `[stream, ownsStream]` — the
	 *   second flag is true when the downloader opened the stream and must
	 *   close it.
	 */
	protected function resolveDestination(mixed $destination): array
	{
		if ($destination === null) {
			return [null, false];
		}
		if (is_string($destination)) {
			$dest = @fopen($destination, 'wb');
			if ($dest === false) {
				throw new THttpClientException('httpclient_transport_error', 0, "Failed to open destination '{$destination}'.");
			}
			return [$dest, true];
		}
		if (is_resource($destination)) {
			return [$destination, false];
		}
		throw new THttpClientException('httpclient_transport_error', 0, 'Destination must be a path or stream resource.');
	}

	/**
	 * Copies the source stream into the destination (or into a memory buffer
	 * when no destination is given), one chunk at a time.
	 *
	 * @param resource $source Source stream (already open).
	 * @param ?resource $dest Destination stream, or null to buffer.
	 * @return string Buffered body when `$dest` is null; empty string otherwise.
	 */
	protected function copy($source, $dest): string
	{
		$buf = '';
		while (!feof($source)) {
			$chunk = fread($source, $this->_chunkSize);
			if ($chunk === false || $chunk === '') {
				break;
			}
			if ($dest === null) {
				$buf .= $chunk;
			} else {
				fwrite($dest, $chunk);
			}
		}
		return $buf;
	}

	/**
	 * @param string $url URL to inspect.
	 * @return bool Whether the URL's scheme is `http` or `https`.
	 */
	protected function isHttpScheme(string $url): bool
	{
		$scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
		return $scheme === 'http' || $scheme === 'https';
	}

	// ── Direct accessors (UAP-SE) ─────────────────────────────────────────────

	/**
	 * @return ?TStreamNotificationCallback Stored notification listener.
	 */
	protected function getNotificationDirect(): ?TStreamNotificationCallback
	{
		return $this->_notification;
	}

	/**
	 * @param ?TStreamNotificationCallback $value Listener to store.
	 */
	protected function setNotificationDirect(?TStreamNotificationCallback $value): void
	{
		$this->_notification = $value;
	}

	/**
	 * @return int Stored chunk size in bytes.
	 */
	protected function getChunkSizeDirect(): int
	{
		return $this->_chunkSize;
	}

	/**
	 * @param int $value Chunk size in bytes to store.
	 */
	protected function setChunkSizeDirect(int $value): void
	{
		$this->_chunkSize = max(1, $value);
	}

	// ── Public accessors ──────────────────────────────────────────────────────

	/**
	 * @return ?TStreamNotificationCallback Active progress / lifecycle listener.
	 */
	public function getNotification(): ?TStreamNotificationCallback
	{
		return $this->getNotificationDirect();
	}

	/**
	 * Sets the {@see TStreamNotificationCallback} that receives stream events
	 * for subsequent calls. Pass `null` to detach.
	 * @param ?TStreamNotificationCallback $value
	 * @return static For object call chaining.
	 */
	public function setNotification(?TStreamNotificationCallback $value): static
	{
		$this->setNotificationDirect($value);
		return $this;
	}

	/**
	 * @return int Chunk size in bytes.
	 */
	public function getChunkSize(): int
	{
		return $this->getChunkSizeDirect();
	}

	/**
	 * @param int $value Chunk size in bytes; values below 1 are clamped to 1.
	 */
	public function setChunkSize(int $value): void
	{
		$this->setChunkSizeDirect($value);
	}
}
