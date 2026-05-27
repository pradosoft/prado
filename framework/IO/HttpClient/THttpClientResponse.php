<?php

/**
 * THttpClientResponse class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\HttpClient;

use Prado\TComponent;

/**
 * THttpClientResponse class.
 *
 * THttpClientResponse is the immutable result of a single
 * {@see THttpClient::download() download()} call. It carries the HTTP
 * status code, response headers, and the raw body string, and offers a few
 * convenience accessors for decoding JSON bodies and classifying the response.
 *
 * Instances are produced by downloader implementations and consumed directly
 * by callers (or via higher-level wrappers such as
 * {@see \Prado\IO\TRestClient}). Response objects are safe to cache (see
 * {@see TCachedHttpClient}) because they hold no live resources.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class THttpClientResponse extends TComponent
{
	/**
	 * @var int HTTP status code.
	 */
	private int $_statusCode;

	/**
	 * @var array<string,string> Response headers, keyed by canonical header
	 *   name (`Title-Case`). When the upstream server sends a header multiple
	 *   times the values are comma-joined per RFC 7230 §3.2.2.
	 */
	private array $_headers;

	/**
	 * @var string Raw response body.
	 */
	private string $_body;

	/**
	 * Constructor.
	 * @param int $statusCode HTTP status code.
	 * @param array<string,string> $headers Response headers keyed by header name.
	 * @param string $body Raw response body.
	 */
	public function __construct(int $statusCode, array $headers = [], string $body = '')
	{
		parent::__construct();
		$this->_statusCode = $statusCode;
		$this->_headers = $this->normalizeHeaderKeys($headers);
		$this->_body = $body;
	}

	/**
	 * Normalizes header name keys to `Title-Case` so lookups are predictable
	 * regardless of how the downloader produced them.
	 * @param array $headers Raw header map.
	 * @return array<string,string> Normalized header map.
	 */
	protected function normalizeHeaderKeys(array $headers): array
	{
		$out = [];
		foreach ($headers as $name => $value) {
			$out[$this->canonicalHeaderName((string) $name)] = (string) $value;
		}
		return $out;
	}

	/**
	 * Converts an arbitrary header name to canonical `Title-Case` form
	 * (e.g. `content-type` → `Content-Type`).
	 * @param string $name Header name.
	 * @return string Canonical header name.
	 */
	protected function canonicalHeaderName(string $name): string
	{
		return str_replace(' ', '-', ucwords(strtolower(str_replace('-', ' ', $name))));
	}

	/**
	 * @return int HTTP status code.
	 */
	public function getStatusCode(): int
	{
		return $this->_statusCode;
	}

	/**
	 * @return array<string,string> Response headers keyed by canonical header name.
	 */
	public function getHeaders(): array
	{
		return $this->_headers;
	}

	/**
	 * Returns a single response header by name (case-insensitive lookup).
	 * @param string $name Header name.
	 * @param ?string $default Value returned when the header is absent.
	 * @return ?string
	 */
	public function getHeader(string $name, ?string $default = null): ?string
	{
		return $this->_headers[$this->canonicalHeaderName($name)] ?? $default;
	}

	/**
	 * @return string Raw response body.
	 */
	public function getBody(): string
	{
		return $this->_body;
	}

	/**
	 * Decodes the response body as JSON.
	 *
	 * Returns the decoded value, or `$default` when the body is empty or not
	 * valid JSON. JSON decoding errors are intentionally swallowed — callers
	 * that need stricter behavior should inspect {@see getBody()} themselves.
	 *
	 * @param bool $assoc When true (default) objects decode to associative arrays.
	 * @param mixed $default Value returned when the body is empty or invalid.
	 * @return mixed
	 */
	public function getJson(bool $assoc = true, mixed $default = null): mixed
	{
		if ($this->_body === '') {
			return $default;
		}
		$decoded = json_decode($this->_body, $assoc);
		if (json_last_error() !== JSON_ERROR_NONE) {
			return $default;
		}
		return $decoded;
	}

	/**
	 * @return bool Whether the status code is in the 2xx range.
	 */
	public function isSuccess(): bool
	{
		return $this->_statusCode >= 200 && $this->_statusCode < 300;
	}

	/**
	 * @return bool Whether the status code is in the 3xx range.
	 */
	public function isRedirect(): bool
	{
		return $this->_statusCode >= 300 && $this->_statusCode < 400;
	}

	/**
	 * @return bool Whether the status code is in the 4xx range.
	 */
	public function isClientError(): bool
	{
		return $this->_statusCode >= 400 && $this->_statusCode < 500;
	}

	/**
	 * @return bool Whether the status code is in the 5xx range.
	 */
	public function isServerError(): bool
	{
		return $this->_statusCode >= 500 && $this->_statusCode < 600;
	}
}
