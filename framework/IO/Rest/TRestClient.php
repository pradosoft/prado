<?php

/**
 * TRestClient class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\Rest;

use Prado\Exceptions\TInvalidDataValueException;
use Prado\IO\Http\TRequest;
use Prado\IO\HttpClient\THttpClient;
use Prado\IO\HttpClient\THttpClientException;
use Prado\IO\HttpClient\THttpResponseException;
use Prado\TApplicationComponent;
use Prado\Web\THttpHeaderName;
use Prado\Web\TMediaType;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

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
 * Requests go through a PSR-18 {@see ClientInterface}, a {@see THttpClient} by default.
 * Replace it with {@see setHttpClient()}, for example to cache responses:
 *
 * ```php
 * $api = new GitHubApi();
 * $api->setHttpClient(new TCachedHttpClient(new THttpClient(), $application->getCache(), 600));
 * ```
 *
 * ## Error handling
 *
 * The verb helpers throw {@see THttpResponseException} when the response status
 * falls outside 2xx. When the status code itself is part of the expected
 * flow (e.g. probing for existence via 404), use {@see requestRaw()}, which
 * returns the PSR-7 response for any status without throwing.
 * Network-level failures (DNS, timeout) always surface as a
 * {@see THttpClientException} because no response exists.
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
	 * @var ?ClientInterface The HTTP client, created on first use when null.
	 */
	private ?ClientInterface $_httpClient = null;

	/**
	 * @var array<string,string> Headers added to every request, unless the
	 *   per-call headers override the same key.
	 */
	private array $_defaultHeaders = [];

	// ── Verb helpers ──────────────────────────────────────────────────────────

	/**
	 * Performs a `GET` request and returns the decoded JSON body.
	 * @param string $path Path pattern, possibly containing `{name}` placeholders.
	 * @param array<string,scalar> $pathParams Path-placeholder values keyed by name.
	 * @param array<string,array|scalar> $query Query string parameters.
	 * @param array<string,string> $headers Per-call headers; merged with defaults.
	 * @throws THttpClientException on HTTP or transport error.
	 * @return mixed Decoded JSON body; `null` for an empty body; the raw body
	 *   string when the body is not valid JSON.
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
	 * @param array<string,array|scalar> $query Query string parameters.
	 * @param array<string,string> $headers Per-call headers.
	 * @throws THttpClientException on HTTP or transport error.
	 * @throws \JsonException when the body cannot be JSON-encoded.
	 * @return mixed Decoded JSON body, `null`, or raw body string (see {@see get()}).
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
	 * @param array<string,array|scalar> $query Query string parameters.
	 * @param array<string,string> $headers Per-call headers.
	 * @throws THttpClientException on HTTP or transport error.
	 * @throws \JsonException when the body cannot be JSON-encoded.
	 * @return mixed Decoded JSON body, `null`, or raw body string (see {@see get()}).
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
	 * @param array<string,array|scalar> $query Query string parameters.
	 * @param array<string,string> $headers Per-call headers.
	 * @throws THttpClientException on HTTP or transport error.
	 * @throws \JsonException when the body cannot be JSON-encoded.
	 * @return mixed Decoded JSON body, `null`, or raw body string (see {@see get()}).
	 */
	public function patch(string $path, array $pathParams = [], mixed $body = null, array $query = [], array $headers = []): mixed
	{
		return $this->request('PATCH', $path, $pathParams, $query, $body, $headers);
	}

	/**
	 * Performs a `DELETE` request. See {@see post()} for parameter semantics.
	 * The body is usually null; some APIs accept a `DELETE` body for bulk or
	 * conditional deletes.
	 * @param string $path Path pattern.
	 * @param array<string,scalar> $pathParams Path-placeholder values.
	 * @param mixed $body Request body.
	 * @param array<string,array|scalar> $query Query string parameters.
	 * @param array<string,string> $headers Per-call headers.
	 * @throws THttpClientException on HTTP or transport error.
	 * @throws \JsonException when the body cannot be JSON-encoded.
	 * @return mixed Decoded JSON body, `null`, or raw body string (see {@see get()}).
	 */
	public function delete(string $path, array $pathParams = [], mixed $body = null, array $query = [], array $headers = []): mixed
	{
		return $this->request('DELETE', $path, $pathParams, $query, $body, $headers);
	}

	// ── Core request flow ─────────────────────────────────────────────────────

	/**
	 * Builds and executes an HTTP request, then resolves the response.
	 *
	 * Delegates the transport work to {@see requestRaw()} and invokes
	 * {@see handleResponse()} to decode the body or throw on HTTP error.
	 *
	 * @param string $method HTTP verb.
	 * @param string $path Path pattern.
	 * @param array<string,scalar> $pathParams Placeholder values.
	 * @param array<string,array|scalar> $query Query string parameters.
	 * @param mixed $body Request body (JSON-encoded when non-null).
	 * @param array<string,string> $perCallHeaders Headers for this call only.
	 * @throws THttpClientException on HTTP or transport error.
	 * @throws \JsonException when the body cannot be JSON-encoded.
	 * @return mixed Decoded body, per {@see handleResponse()}.
	 */
	protected function request(
		string $method,
		string $path,
		array $pathParams,
		array $query,
		mixed $body,
		array $perCallHeaders
	): mixed {
		$request = $this->createRequest($method, $path, $pathParams, $query, $body, $perCallHeaders);
		return $this->handleResponse($this->getHttpClient()->sendRequest($request), $request);
	}

	/**
	 * Executes an HTTP request and returns the response for any status code.
	 *
	 * Unlike the verb helpers, this method does not throw on 4xx/5xx
	 * statuses.  The caller inspects {@see ResponseInterface::getStatusCode()},
	 * which suits flows where an error status is an expected outcome (e.g.
	 * probing for existence via 404).  Network-level failures still throw
	 * because no response exists.
	 *
	 * @param string $method HTTP verb.
	 * @param string $path Path pattern, possibly containing `{name}` placeholders.
	 * @param array<string,scalar> $pathParams Placeholder values keyed by name.
	 * @param array<string,array|scalar> $query Query string parameters.
	 * @param mixed $body Request body (JSON-encoded when non-null).
	 * @param array<string,string> $headers Per-call headers; merged with defaults.
	 * @throws THttpClientException on transport (network-level) failure.
	 * @throws \JsonException when the body cannot be JSON-encoded.
	 * @return ResponseInterface The response, regardless of status code.
	 */
	public function requestRaw(
		string $method,
		string $path,
		array $pathParams = [],
		array $query = [],
		mixed $body = null,
		array $headers = []
	): ResponseInterface {
		return $this->getHttpClient()->sendRequest($this->createRequest($method, $path, $pathParams, $query, $body, $headers));
	}

	/**
	 * Builds the PSR-7 request of a call: the URL from {@see buildUrl()}, the headers
	 * from {@see mergeHeaders()}, and the body from {@see encodeBody()}.
	 * @param string $method HTTP verb.
	 * @param string $path Path pattern, possibly containing `{name}` placeholders.
	 * @param array<string,scalar> $pathParams Placeholder values keyed by name.
	 * @param array<string,array|scalar> $query Query string parameters.
	 * @param mixed $body Request body (JSON-encoded when non-null).
	 * @param array<string,string> $headers Per-call headers; merged with defaults.
	 * @throws TInvalidDataValueException when a per-call header is unsafe.
	 * @throws \JsonException when the body cannot be JSON-encoded.
	 * @return RequestInterface The request.
	 */
	protected function createRequest(string $method, string $path, array $pathParams, array $query, mixed $body, array $headers): RequestInterface
	{
		$url = $this->buildUrl($path, $pathParams, $query);
		$headers = $this->mergeHeaders($headers);
		$rawBody = $this->encodeBody($body, $headers);
		return new TRequest($method, $url, $headers, $rawBody);
	}

	/**
	 * Builds the full request URL from the base URL, path pattern, placeholder
	 * values, and query parameters.
	 *
	 * `{name}` placeholders in `$path` are replaced with URL-encoded values
	 * from `$pathParams`. Any leftover entries in `$pathParams` are silently
	 * ignored. `$query` is appended as a URL-encoded query string built by
	 * {@see buildQuery()}.
	 *
	 * `$path` is treated as a trusted template owned by the subclass. Only the
	 * placeholder values may be untrusted; they are URL-encoded. Do not build
	 * `$path` itself from untrusted input, as it is joined to the base URL
	 * without origin validation.
	 *
	 * @param string $path Path pattern.
	 * @param array<string,scalar> $pathParams Placeholder values.
	 * @param array<string,array|scalar> $query Query string parameters.
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

		$queryString = $this->buildQuery($query);
		if ($queryString !== '') {
			$separator = str_contains($url, '?') ? '&' : '?';
			$url .= $separator . $queryString;
		}

		return $url;
	}

	/**
	 * Builds a URL-encoded query string from a parameter map.
	 *
	 * List-valued parameters are emitted as repeated keys (`tags=a&tags=b`),
	 * which most REST APIs expect, instead of PHP's bracketed `tags[0]=a`
	 * form. Associative-array values and scalars are encoded via
	 * `http_build_query()` unchanged. Override this method when an API needs
	 * a different array convention.
	 *
	 * A `null` value and an empty list produce no output. Empty fragments are
	 * skipped so the result never contains a dangling or doubled `&`.
	 *
	 * @param array<string,array|scalar> $query Query string parameters.
	 * @return string URL-encoded query string without a leading `?`.
	 */
	protected function buildQuery(array $query): string
	{
		$parts = [];
		foreach ($query as $key => $value) {
			$items = (is_array($value) && array_is_list($value)) ? $value : [$value];
			foreach ($items as $item) {
				$fragment = http_build_query([$key => $item]);
				if ($fragment !== '') {
					$parts[] = $fragment;
				}
			}
		}
		return implode('&', $parts);
	}

	/**
	 * Merges default headers with per-call headers; per-call entries win.
	 *
	 * Each per-call name and value is validated by {@see assertHeaderSafe()} so
	 * that a CR/LF in a caller-supplied header cannot inject or split the request.
	 *
	 * @param array<string,string> $perCall Per-call header map.
	 * @throws TInvalidDataValueException when a per-call header name or value
	 *   contains a CR/LF or the name is not a valid token.
	 * @return array<string,string> Effective header map.
	 */
	protected function mergeHeaders(array $perCall): array
	{
		foreach ($perCall as $name => $value) {
			$this->assertHeaderSafe((string) $name, (string) $value);
		}
		return array_merge($this->getDefaultHeaders(), $perCall);
	}

	/**
	 * Validates that a header name and value are free of header-injection
	 * characters.
	 *
	 * Rejects any value containing a carriage return or line feed, and any name
	 * that is empty or contains a character outside the RFC 7230 `token`
	 * grammar. Centralizes the boundary check so the client never forwards a
	 * splittable header to the transport.
	 *
	 * @param string $name Header name.
	 * @param string $value Header value.
	 * @throws TInvalidDataValueException when the name or value is unsafe.
	 */
	protected function assertHeaderSafe(string $name, string $value): void
	{
		if ($name === '' || preg_match('/[^!#$%&\'*+\-.^_`|~0-9A-Za-z]/', $name) || preg_match('/[\r\n]/', $value)) {
			throw new TInvalidDataValueException('restclient_invalid_header', $name);
		}
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
	 * @throws \JsonException when the body cannot be JSON-encoded (e.g.
	 *   invalid UTF-8 strings or non-finite floats).
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
		if (!$this->hasHeaderCaseInsensitive($headers, THttpHeaderName::ContentType)) {
			$headers[THttpHeaderName::ContentType] = TMediaType::JSON;
		}
		return json_encode($body, JSON_THROW_ON_ERROR);
	}

	/**
	 * Returns whether a header is present in the map, comparing names
	 * case-insensitively.
	 * @param array<string,string> $headers Header map.
	 * @param string $name Header to look for (case-insensitive).
	 * @return bool Whether a header with that name exists.
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
	 * Decodes a successful response or throws on HTTP error.
	 *
	 * Non-2xx responses are converted to {@see THttpResponseException}. An
	 * empty body (e.g. `204 No Content`) decodes to `null`. A non-empty body
	 * that is not valid JSON is returned as the raw body string so non-JSON
	 * endpoints (plain text, HTML) are not silently lost.
	 *
	 * @param ResponseInterface $response The response.
	 * @param RequestInterface $request The request it answers.
	 * @throws THttpResponseException when the response status is outside 2xx.
	 * @return mixed Decoded JSON value, raw body string, or null for an empty body.
	 */
	protected function handleResponse(ResponseInterface $response, RequestInterface $request): mixed
	{
		$status = $response->getStatusCode();
		if ($status < 200 || $status >= 300) {
			throw new THttpResponseException($request, $response);
		}
		$body = (string) $response->getBody();
		if ($body === '') {
			return null;
		}
		try {
			return json_decode($body, true, 512, JSON_THROW_ON_ERROR);
		} catch (\JsonException $e) {
			return $body;
		}
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
	 * @return ?ClientInterface Stored HTTP client, or null when unset.
	 */
	protected function getHttpClientDirect(): ?ClientInterface
	{
		return $this->_httpClient;
	}

	/**
	 * @param ?ClientInterface $value HTTP client to store, or null to clear.
	 */
	protected function setHttpClientDirect(?ClientInterface $value): void
	{
		$this->_httpClient = $value;
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
	 * Returns the PSR-18 client requests are sent through, creating a {@see THttpClient}
	 * on first access.
	 * @return ClientInterface The HTTP client.
	 */
	public function getHttpClient(): ClientInterface
	{
		if ($this->getHttpClientDirect() === null) {
			$this->setHttpClientDirect(new THttpClient());
		}
		/** @var ClientInterface */
		return $this->getHttpClientDirect();
	}

	/**
	 * Replaces the PSR-18 client requests are sent through.
	 * @param ClientInterface $value The HTTP client.
	 */
	public function setHttpClient(ClientInterface $value): void
	{
		$this->setHttpClientDirect($value);
	}

	/**
	 * @return array<string,string> Headers added to every request.
	 */
	public function getDefaultHeaders(): array
	{
		return $this->getDefaultHeadersDirect();
	}

	/**
	 * Replaces the entire default-headers map. Each name and value is validated by
	 * {@see assertHeaderSafe()} to prevent header injection.
	 * @param array<string,string> $value Default headers.
	 * @throws TInvalidDataValueException when a name or value is unsafe.
	 */
	public function setDefaultHeaders(array $value): void
	{
		foreach ($value as $name => $header) {
			$this->assertHeaderSafe((string) $name, (string) $header);
		}
		$this->setDefaultHeadersDirect($value);
	}

	/**
	 * Adds or overwrites a single default header.
	 *
	 * The name and value are validated by {@see assertHeaderSafe()} to prevent
	 * header injection.
	 *
	 * @param string $name Header name.
	 * @param string $value Header value.
	 * @throws TInvalidDataValueException when the name or value is unsafe.
	 */
	public function setDefaultHeader(string $name, string $value): void
	{
		$this->assertHeaderSafe($name, $value);
		$headers = $this->getDefaultHeadersDirect();
		$headers[$name] = $value;
		$this->setDefaultHeadersDirect($headers);
	}

	/**
	 * Sets the default `Authorization` header to an OAuth2-style bearer token.
	 *
	 * The header is a default, so it is sent to whatever host {@see buildUrl()}
	 * resolves for every request. Use one client instance per API origin so a
	 * token is not disclosed to an unintended host.
	 *
	 * @param string $token Bearer token, without the `Bearer ` prefix.
	 * @throws TInvalidDataValueException when the token contains a CR/LF.
	 */
	public function setBearerToken(string $token): void
	{
		$this->setDefaultHeader(THttpHeaderName::Authorization, 'Bearer ' . $token);
	}

	/**
	 * Sets the default `Authorization` header to HTTP Basic credentials.
	 *
	 * The username must not contain a colon, which RFC 7617 reserves as the
	 * credential separator. Like {@see setBearerToken()}, the resulting header
	 * is sent to every host the client targets.
	 *
	 * @param string $username User name (must not contain `:`).
	 * @param string $password Password.
	 * @throws TInvalidDataValueException when the username contains a colon, or
	 *   a credential contains a CR/LF.
	 */
	public function setBasicAuth(string $username, string $password): void
	{
		if (str_contains($username, ':')) {
			throw new TInvalidDataValueException('restclient_basicauth_username_colon');
		}
		$this->setDefaultHeader(THttpHeaderName::Authorization, 'Basic ' . base64_encode($username . ':' . $password));
	}
}
