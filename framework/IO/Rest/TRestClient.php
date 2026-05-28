<?php

/**
 * TRestClient class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\Rest;

use Prado\IO\HttpClient\THttpClientException;
use Prado\IO\HttpClient\THttpClientResponse;
use Prado\IO\HttpClient\THttpClient;
use Prado\TApplicationComponent;
use Prado\TPropertyValue;

/**
 * TRestClient class
 *
 * TRestClient is the abstract base for consumers of third-party REST APIs.
 * It complements {@see \Prado\Web\Services\Rest\TRestService} (the producer) by handling the symmetric
 * job: turning typed PHP method calls into HTTP requests, decoding JSON
 * responses, and surfacing HTTP errors as exceptions.
 *
 * ## Subclassing
 *
 * A concrete client wraps a specific API. Its public methods translate their
 * arguments into a path pattern (with `{name}` placeholders, mirroring
 * TRestService route syntax) and a body, and let the base class do the rest:
 *
 * ```php
 * class GitHubApi extends TRestClient
 * {
 *     public function __construct()
 *     {
 *         parent::__construct();
 *         $this->setBaseUrl('https://api.github.com/');
 *         $this->setDefaultHeaders([
 *             'Accept' => 'application/vnd.github+json',
 *             'User-Agent' => 'MyApp/1.0',
 *         ]);
 *     }
 *
 *     public function getUser(string $login): array
 *     {
 *         return $this->get('users/{login}', ['login' => $login]);
 *     }
 *
 *     public function listRepos(string $login, int $page = 1): array
 *     {
 *         return $this->get('users/{login}/repos', ['login' => $login], ['page' => $page]);
 *     }
 *
 *     public function createIssue(string $owner, string $repo, array $issue): array
 *     {
 *         return $this->post('repos/{owner}/{repo}/issues',
 *             ['owner' => $owner, 'repo' => $repo],
 *             $issue);
 *     }
 * }
 * ```
 *
 * ## Transport
 *
 * The default transport is whatever {@see THttpClient::create()} chooses
 * (cURL when available, PHP stream wrappers otherwise). Replace it via
 * {@see setDownloader()} — for example, to enable response caching:
 *
 * ```php
 * $api = new GitHubApi();
 * $api->setDownloader(THttpClient::newCacheWrapper(
 *     THttpClient::create(),
 *     $application->getModule('cache'),
 *     ttl: 600,
 * ));
 * ```
 *
 * ## Error handling
 *
 * By default the client throws {@see THttpClientException} when the response
 * status falls outside 2xx. Set {@see setThrowOnError ThrowOnError} to
 * `false` to receive the raw {@see THttpClientResponse} instead and inspect
 * the status code yourself. Network-level failures (DNS, timeout) always
 * surface as exceptions because no response exists.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
abstract class TRestClient extends TApplicationComponent
{
	/**
	 * @var string Base URL prepended to every request path. Include the
	 *   trailing slash. Defaults to empty string.
	 */
	private string $_baseUrl = '';

	/**
	 * @var ?THttpClient Transport instance. Lazily created on first
	 *   call to {@see getDownloader()} when null.
	 */
	private ?THttpClient $_downloader = null;

	/**
	 * @var array<string,string> Headers added to every request, unless the
	 *   per-call headers override the same key.
	 */
	private array $_defaultHeaders = [];

	/**
	 * @var bool Whether 4xx/5xx responses raise a {@see THttpClientException}.
	 *   Defaults to true.
	 */
	private bool $_throwOnError = true;

	// ── Verb helpers ──────────────────────────────────────────────────────────

	/**
	 * Performs a `GET` request and returns the decoded JSON body.
	 * @param string $path Path pattern, possibly containing `{name}` placeholders.
	 * @param array<string,scalar> $pathParams Path-placeholder values keyed by name.
	 * @param array<string,scalar> $query Query string parameters.
	 * @param array<string,string> $headers Per-call headers; merged with defaults.
	 * @throws THttpClientException on HTTP or transport error.
	 * @return mixed Decoded JSON body, or raw {@see THttpClientResponse} when
	 *   {@see setThrowOnError ThrowOnError} is false.
	 */
	public function get(string $path, array $pathParams = [], array $query = [], array $headers = []): mixed
	{
		return $this->request('GET', $path, $pathParams, $query, null, $headers);
	}

	/**
	 * Performs a `POST` request.
	 * @param string $path Path pattern.
	 * @param array<string,scalar> $pathParams Path-placeholder values.
	 * @param mixed $body Request body (JSON-encoded automatically when non-null).
	 * @param array<string,scalar> $query Query string parameters.
	 * @param array<string,string> $headers Per-call headers.
	 * @throws THttpClientException on HTTP or transport error.
	 * @return mixed Decoded JSON body or raw response (see {@see get()}).
	 */
	public function post(string $path, array $pathParams = [], mixed $body = null, array $query = [], array $headers = []): mixed
	{
		return $this->request('POST', $path, $pathParams, $query, $body, $headers);
	}

	/**
	 * Performs a `PUT` request. See {@see post()} for parameter semantics.
	 * @param string $path Path pattern.
	 * @param array<string,scalar> $pathParams Path-placeholder values.
	 * @param mixed $body Request body.
	 * @param array<string,scalar> $query Query string parameters.
	 * @param array<string,string> $headers Per-call headers.
	 * @throws THttpClientException on HTTP or transport error.
	 * @return mixed Decoded JSON body or raw response.
	 */
	public function put(string $path, array $pathParams = [], mixed $body = null, array $query = [], array $headers = []): mixed
	{
		return $this->request('PUT', $path, $pathParams, $query, $body, $headers);
	}

	/**
	 * Performs a `PATCH` request. See {@see post()} for parameter semantics.
	 * @param string $path Path pattern.
	 * @param array<string,scalar> $pathParams Path-placeholder values.
	 * @param mixed $body Request body.
	 * @param array<string,scalar> $query Query string parameters.
	 * @param array<string,string> $headers Per-call headers.
	 * @throws THttpClientException on HTTP or transport error.
	 * @return mixed Decoded JSON body or raw response.
	 */
	public function patch(string $path, array $pathParams = [], mixed $body = null, array $query = [], array $headers = []): mixed
	{
		return $this->request('PATCH', $path, $pathParams, $query, $body, $headers);
	}

	/**
	 * Performs a `DELETE` request. See {@see get()} for parameter semantics.
	 * @param string $path Path pattern.
	 * @param array<string,scalar> $pathParams Path-placeholder values.
	 * @param array<string,scalar> $query Query string parameters.
	 * @param array<string,string> $headers Per-call headers.
	 * @throws THttpClientException on HTTP or transport error.
	 * @return mixed Decoded JSON body or raw response.
	 */
	public function delete(string $path, array $pathParams = [], array $query = [], array $headers = []): mixed
	{
		return $this->request('DELETE', $path, $pathParams, $query, null, $headers);
	}

	// ── Core request flow ─────────────────────────────────────────────────────

	/**
	 * Builds and executes an HTTP request, then resolves the response.
	 *
	 * The flow is: expand the path → merge headers → JSON-encode body →
	 * delegate to the downloader → invoke {@see handleResponse()} to decode
	 * or throw.
	 *
	 * @param string $method HTTP verb.
	 * @param string $path Path pattern.
	 * @param array<string,scalar> $pathParams Placeholder values.
	 * @param array<string,scalar> $query Query string parameters.
	 * @param mixed $body Request body (JSON-encoded when non-null).
	 * @param array<string,string> $perCallHeaders Headers for this call only.
	 * @throws THttpClientException on HTTP or transport error.
	 * @return mixed Decoded body or raw response, per {@see handleResponse()}.
	 */
	protected function request(
		string $method,
		string $path,
		array $pathParams,
		array $query,
		mixed $body,
		array $perCallHeaders
	): mixed {
		$url = $this->buildUrl($path, $pathParams, $query);
		$headers = $this->mergeHeaders($perCallHeaders);
		$rawBody = $this->encodeBody($body, $headers);

		$response = $this->getDownloader()->download($method, $url, $headers, $rawBody);
		return $this->handleResponse($response);
	}

	/**
	 * Builds the full request URL from the base URL, path pattern, placeholder
	 * values, and query parameters.
	 *
	 * `{name}` placeholders in `$path` are replaced with URL-encoded values
	 * from `$pathParams`. Any leftover entries in `$pathParams` are silently
	 * ignored. `$query` is appended as a URL-encoded query string.
	 *
	 * @param string $path Path pattern.
	 * @param array<string,scalar> $pathParams Placeholder values.
	 * @param array<string,scalar> $query Query string parameters.
	 * @return string Fully qualified URL.
	 */
	protected function buildUrl(string $path, array $pathParams, array $query): string
	{
		$expanded = preg_replace_callback(
			'/\{([^}]+)\}/',
			function ($m) use ($pathParams) {
				$name = $m[1];
				return isset($pathParams[$name]) ? rawurlencode((string) $pathParams[$name]) : $m[0];
			},
			$path
		);

		$url = rtrim($this->getBaseUrl(), '/') . '/' . ltrim((string) $expanded, '/');

		if ($query !== []) {
			$separator = str_contains($url, '?') ? '&' : '?';
			$url .= $separator . http_build_query($query);
		}

		return $url;
	}

	/**
	 * Merges default headers with per-call headers; per-call entries win.
	 * @param array<string,string> $perCall Per-call header map.
	 * @return array<string,string> Effective header map.
	 */
	protected function mergeHeaders(array $perCall): array
	{
		return array_merge($this->getDefaultHeaders(), $perCall);
	}

	/**
	 * JSON-encodes a request body and sets a `Content-Type` header when the
	 * caller did not already supply one.
	 *
	 * Null and string bodies pass through unchanged so callers retain control
	 * for non-JSON payloads. The `$headers` array is modified by reference
	 * to add the `Content-Type` only when needed.
	 *
	 * @param mixed $body Body value supplied by the caller.
	 * @param array<string,string> &$headers Headers to augment.
	 * @return ?string Raw body string or null.
	 */
	protected function encodeBody(mixed $body, array &$headers): ?string
	{
		if ($body === null) {
			return null;
		}
		if (is_string($body)) {
			return $body;
		}
		if (!$this->hasHeaderCaseInsensitive($headers, 'Content-Type')) {
			$headers['Content-Type'] = 'application/json';
		}
		return (string) json_encode($body);
	}

	/**
	 * @param array<string,string> $headers Header map.
	 * @param string $name Header to look for (case-insensitive).
	 * @return bool
	 */
	protected function hasHeaderCaseInsensitive(array $headers, string $name): bool
	{
		$needle = strtolower($name);
		foreach (array_keys($headers) as $key) {
			if (strtolower((string) $key) === $needle) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Decides what to return (or throw) given a downloader response.
	 *
	 * When {@see setThrowOnError ThrowOnError} is true, non-2xx responses are
	 * converted to {@see THttpClientException}. Otherwise the raw response is
	 * returned so the caller can inspect it. Successful responses are decoded
	 * via {@see THttpClientResponse::getJson() getJson()}.
	 *
	 * @param THttpClientResponse $response Response from the downloader.
	 * @throws THttpClientException when ThrowOnError is true and the response is unsuccessful.
	 * @return mixed Decoded body or the raw response.
	 */
	protected function handleResponse(THttpClientResponse $response): mixed
	{
		if (!$response->isSuccess()) {
			if ($this->getThrowOnError()) {
				throw THttpClientException::fromResponse($response);
			}
			return $response;
		}
		return $response->getJson(true, null);
	}

	// ── Direct accessors (UAP-SE) ─────────────────────────────────────────────

	/**
	 * @return string Stored base URL.
	 */
	protected function getBaseUrlDirect(): string
	{
		return $this->_baseUrl;
	}

	/**
	 * @param string $value Base URL to store.
	 */
	protected function setBaseUrlDirect(string $value): void
	{
		$this->_baseUrl = $value;
	}

	/**
	 * @return ?THttpClient Stored downloader, or null when unset.
	 */
	protected function getDownloaderDirect(): ?THttpClient
	{
		return $this->_downloader;
	}

	/**
	 * @param ?THttpClient $value Downloader to store, or null to clear.
	 */
	protected function setDownloaderDirect(?THttpClient $value): void
	{
		$this->_downloader = $value;
	}

	/**
	 * @return array<string,string> Stored default headers map.
	 */
	protected function getDefaultHeadersDirect(): array
	{
		return $this->_defaultHeaders;
	}

	/**
	 * @param array<string,string> $value Default header map to store.
	 */
	protected function setDefaultHeadersDirect(array $value): void
	{
		$this->_defaultHeaders = $value;
	}

	/**
	 * @return bool Stored ThrowOnError flag.
	 */
	protected function getThrowOnErrorDirect(): bool
	{
		return $this->_throwOnError;
	}

	/**
	 * @param bool $value ThrowOnError flag to store.
	 */
	protected function setThrowOnErrorDirect(bool $value): void
	{
		$this->_throwOnError = $value;
	}

	// ── Public accessors ──────────────────────────────────────────────────────

	/**
	 * @return string Base URL prepended to every request path.
	 */
	public function getBaseUrl(): string
	{
		return $this->getBaseUrlDirect();
	}

	/**
	 * Sets the base URL. Include the trailing slash for predictable joining.
	 * @param string $value Base URL.
	 */
	public function setBaseUrl(string $value): void
	{
		$this->setBaseUrlDirect($value);
	}

	/**
	 * Returns the active downloader, creating a default one on first access.
	 * @return THttpClient
	 */
	public function getDownloader(): THttpClient
	{
		if ($this->getDownloaderDirect() === null) {
			$this->setDownloaderDirect(THttpClient::create());
		}
		/** @var THttpClient */
		return $this->getDownloaderDirect();
	}

	/**
	 * Replaces the active downloader.
	 * @param THttpClient $value Downloader instance.
	 */
	public function setDownloader(THttpClient $value): void
	{
		$this->setDownloaderDirect($value);
	}

	/**
	 * @return array<string,string> Headers added to every request.
	 */
	public function getDefaultHeaders(): array
	{
		return $this->getDefaultHeadersDirect();
	}

	/**
	 * Replaces the entire default-headers map.
	 * @param array<string,string> $value Default headers.
	 */
	public function setDefaultHeaders(array $value): void
	{
		$this->setDefaultHeadersDirect($value);
	}

	/**
	 * Adds or overwrites a single default header.
	 * @param string $name Header name.
	 * @param string $value Header value.
	 */
	public function setDefaultHeader(string $name, string $value): void
	{
		$headers = $this->getDefaultHeadersDirect();
		$headers[$name] = $value;
		$this->setDefaultHeadersDirect($headers);
	}

	/**
	 * @return bool Whether HTTP error responses raise an exception.
	 */
	public function getThrowOnError(): bool
	{
		return $this->getThrowOnErrorDirect();
	}

	/**
	 * @param bool|string $value Whether HTTP errors should throw.
	 */
	public function setThrowOnError($value): void
	{
		$this->setThrowOnErrorDirect(TPropertyValue::ensureBoolean($value));
	}
}
