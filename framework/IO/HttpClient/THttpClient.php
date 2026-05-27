<?php

/**
 * THttpClient class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\HttpClient;

use Prado\Caching\ICache;
use Prado\TApplicationComponent;
use Prado\TPropertyValue;

/**
 * THttpClient class.
 *
 * THttpClient is the abstract base for HTTP transports — a general-purpose
 * I/O abstraction for fetching resources over HTTP(S). Its canonical consumer
 * is {@see \Prado\IO\TRestClient}, but it is independent of any
 * higher-level protocol and can be used directly anywhere a single HTTP
 * request/response round-trip is needed. Static factories return a working
 * implementation and wrap one with caching.
 *
 * ## Choosing an implementation
 *
 * In normal use, code obtains a downloader via {@see create()}:
 * ```php
 * $downloader = THttpClient::create();
 * ```
 * This returns {@see TCurlHttpClient} when the cURL extension is loaded, and
 * falls back to {@see TFopenHttpClient} (PHP stream wrappers) otherwise. Both
 * implementations honor the same set of properties — {@see setTimeout Timeout},
 * {@see setFollowRedirects FollowRedirects}, and
 * {@see setMaxRedirects MaxRedirects} — so callers do not need to know which
 * one they hold.
 *
 * ## Caching responses
 *
 * Any downloader can be wrapped with a transparent cache via
 * {@see newCacheWrapper()}:
 * ```php
 * $downloader = THttpClient::newCacheWrapper(
 *     THttpClient::create(),
 *     $application->getModule('cache'),
 *     ttl: 300,
 * );
 * ```
 * Only successful idempotent responses (`GET` / `HEAD` with a 2xx status) are
 * cached. See {@see TCachedHttpClient} for details.
 *
 * ## Subclassing
 *
 * Concrete downloaders override {@see download()}, returning a
 * {@see THttpClientResponse} regardless of whether the underlying transport
 * succeeded or failed at the HTTP level. Network-level failures
 * (e.g. DNS resolution failure) should be surfaced as a
 * {@see THttpClientException}.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
abstract class THttpClient extends TApplicationComponent
{
	/**
	 * @var int Request timeout in seconds. Defaults to 30.
	 */
	private int $_timeout = 30;

	/**
	 * @var bool Whether 3xx redirects are followed automatically. Defaults to true.
	 */
	private bool $_followRedirects = true;

	/**
	 * @var int Maximum number of redirects to follow when {@see getFollowRedirects()}
	 *   is true. Defaults to 5.
	 */
	private int $_maxRedirects = 5;

	// ── Abstract contract ──────────────────────────────────────────────────────

	/**
	 * Performs an HTTP request and returns the response.
	 *
	 * Implementations must always return a {@see THttpClientResponse}, even for
	 * HTTP-level error statuses (4xx / 5xx). Network-level failures
	 * (DNS, connection refused, timeout) should be reported by throwing a
	 * {@see THttpClientException}.
	 *
	 * @param string $method HTTP verb (e.g. `'GET'`, `'POST'`).
	 * @param string $url Fully qualified absolute URL.
	 * @param array<string,string> $headers Request headers, keyed by header name.
	 * @param ?string $body Raw request body, or null for no body.
	 * @throws THttpClientException on network-level failure.
	 * @return THttpClientResponse
	 */
	abstract public function download(
		string $method,
		string $url,
		array $headers = [],
		?string $body = null
	): THttpClientResponse;

	// ── Static factories ───────────────────────────────────────────────────────

	/**
	 * Creates a downloader using the best available transport.
	 *
	 * Returns a {@see TCurlHttpClient} when the cURL extension is loaded, and a
	 * {@see TFopenHttpClient} otherwise. The chosen implementation can be
	 * inspected, replaced, or wrapped after the fact.
	 *
	 * @return THttpClient Concrete downloader instance.
	 */
	public static function create(): THttpClient
	{
		if (function_exists('curl_init')) {
			return new TCurlHttpClient();
		}
		return new TFopenHttpClient();
	}

	/**
	 * Wraps a downloader with a transparent response cache.
	 *
	 * The returned wrapper has the same interface as any other downloader and
	 * can itself be wrapped further. Only successful `GET` and `HEAD` responses
	 * are cached. The wrapper does not copy the inner downloader's properties —
	 * it simply delegates to it.
	 *
	 * @param THttpClient $inner Downloader that performs the actual I/O.
	 * @param ICache $cache Cache module to store responses in.
	 * @param int $ttl Cache time-to-live in seconds. Defaults to 300.
	 * @return TCachedHttpClient Caching wrapper around `$inner`.
	 */
	public static function newCacheWrapper(THttpClient $inner, ICache $cache, int $ttl = 300): TCachedHttpClient
	{
		return new TCachedHttpClient($inner, $cache, $ttl);
	}

	// ── Direct accessors (UAP-SE) ──────────────────────────────────────────────

	/**
	 * @return int Stored timeout value.
	 */
	protected function getTimeoutDirect(): int
	{
		return $this->_timeout;
	}

	/**
	 * @param int $value Timeout value to store.
	 */
	protected function setTimeoutDirect(int $value): void
	{
		$this->_timeout = $value;
	}

	/**
	 * @return bool Stored FollowRedirects flag.
	 */
	protected function getFollowRedirectsDirect(): bool
	{
		return $this->_followRedirects;
	}

	/**
	 * @param bool $value FollowRedirects value to store.
	 */
	protected function setFollowRedirectsDirect(bool $value): void
	{
		$this->_followRedirects = $value;
	}

	/**
	 * @return int Stored MaxRedirects value.
	 */
	protected function getMaxRedirectsDirect(): int
	{
		return $this->_maxRedirects;
	}

	/**
	 * @param int $value MaxRedirects value to store.
	 */
	protected function setMaxRedirectsDirect(int $value): void
	{
		$this->_maxRedirects = $value;
	}

	// ── Public accessors ──────────────────────────────────────────────────────

	/**
	 * @return int Request timeout in seconds.
	 */
	public function getTimeout(): int
	{
		return $this->getTimeoutDirect();
	}

	/**
	 * @param int|string $value Request timeout in seconds.
	 */
	public function setTimeout($value): void
	{
		$this->setTimeoutDirect(TPropertyValue::ensureInteger($value));
	}

	/**
	 * @return bool Whether 3xx redirects are followed automatically.
	 */
	public function getFollowRedirects(): bool
	{
		return $this->getFollowRedirectsDirect();
	}

	/**
	 * @param bool|string $value Whether to follow redirects.
	 */
	public function setFollowRedirects($value): void
	{
		$this->setFollowRedirectsDirect(TPropertyValue::ensureBoolean($value));
	}

	/**
	 * @return int Maximum redirect chain length.
	 */
	public function getMaxRedirects(): int
	{
		return $this->getMaxRedirectsDirect();
	}

	/**
	 * @param int|string $value Maximum redirect chain length.
	 */
	public function setMaxRedirects($value): void
	{
		$this->setMaxRedirectsDirect(TPropertyValue::ensureInteger($value));
	}

	// ── Shared utilities for concrete subclasses ──────────────────────────────

	/**
	 * Parses an HTTP/1.x status line into a numeric status code.
	 *
	 * Returns `0` when the line cannot be parsed, which subclasses should
	 * treat as a transport failure.
	 *
	 * @param string $statusLine Status line, e.g. `'HTTP/1.1 200 OK'`.
	 * @return int Parsed status code, or 0 on failure.
	 */
	protected function parseStatusLine(string $statusLine): int
	{
		if (preg_match('#^HTTP/\S+\s+(\d{3})#', $statusLine, $m) === 1) {
			return (int) $m[1];
		}
		return 0;
	}

	/**
	 * Flattens an associative header map into the `Name: value` lines that
	 * both cURL and stream contexts expect.
	 * @param array<string,string> $headers Header map.
	 * @return string[] Flat list of `Name: value` strings.
	 */
	protected function formatHeaderLines(array $headers): array
	{
		$out = [];
		foreach ($headers as $name => $value) {
			$out[] = $name . ': ' . $value;
		}
		return $out;
	}
}
