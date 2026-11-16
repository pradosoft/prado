<?php

/**
 * THttpClient class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\HttpClient;

use Prado\Exceptions\TConfigurationException;
use Prado\IO\Async\TAsync;
use Prado\IO\Async\TFuture;
use Prado\IO\Http\TRequest;
use Prado\IO\TResourceUri;
use Prado\IO\TStream;
use Prado\Prado;
use Prado\TApplicationComponent;
use Prado\TPropertyValue;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

/**
 * THttpClient class.
 *
 * THttpClient is the PSR-18 {@see ClientInterface} HTTP client.  It sends a PSR-7
 * request through an {@see IHttpHandler} and returns the response for every status code.
 * ```php
 * $client = new THttpClient();
 * $response = $client->sendRequest(new TRequest('GET', 'https://api.example.com/items'));
 * $response = $client->request('POST', 'https://api.example.com/items', [
 *     THttpClientOption::JSON => ['name' => 'a'],
 * ]);
 * $future = $client->requestAsync('GET', 'https://api.example.com/items/1');
 * ```
 *
 * | Method | Behavior |
 * |---|---|
 * | {@see sendRequest()} | PSR-18: sends a request; never throws for a 4xx or 5xx response. |
 * | {@see send()} | Sends a request with per-call {@see THttpClientOption} options. |
 * | {@see sendAsync()} | Sends a request in a new fiber and returns the future of the response. |
 * | {@see request()} / {@see requestAsync()} | Builds the request from a method, a URI, and the {@see THttpClientOption::HEADERS}, {@see THttpClientOption::BODY}, {@see THttpClientOption::JSON}, {@see THttpClientOption::FORM_PARAMS}, and {@see THttpClientOption::QUERY} options. |
 *
 * ## Handlers
 * Without a {@see setHandler Handler}, the client chooses one per request: from inside a
 * fiber, the first available of {@see ASYNC_HANDLERS}, so the fiber yields while the
 * response is in flight; from the main fiber, the first available of {@see HANDLERS}.
 * cURL is used when it is loaded, and the dependency-free {@see TSocketHttpHandler}
 * otherwise.  Each handler class is created once per client.
 *
 * ## Redirects
 * The client follows redirects, not the handler, so every handler behaves alike.  With
 * {@see setFollowRedirects FollowRedirects} true, a 301, 302, 303, 307, or 308 response
 * with a `Location` is followed up to {@see setMaxRedirects MaxRedirects} times.  A 303,
 * or a 301 or 302 to a POST, becomes a GET without a body; a 307 or 308 resends the
 * method and the rewound body.  A redirect to another host drops the `Authorization`
 * and `Cookie` headers, and a redirect to a scheme other than http or https is not
 * followed.
 *
 * ## Defaults
 * {@see setTimeout Timeout}, {@see setConnectTimeout ConnectTimeout},
 * {@see setVerify Verify}, {@see setDecodeContent DecodeContent}, and
 * {@see setHttpErrors HttpErrors} are the defaults of their options.
 * {@see setHeaders Headers} are added to a request that lacks them; the default is a
 * `User-Agent` of `Prado/<version>`.  {@see setBaseUri BaseUri} resolves a relative
 * {@see request()} URI.
 *
 * ## Events and behaviors
 * A behavior changes a request before it is sent with `dyBeforeSend($request, $options)`,
 * and a response after it arrives with `dyAfterResponse($response, $request, $options)`.
 * They run once per {@see send()}, around the whole redirect chain.  `OnBeforeSend`,
 * `OnResponse`, and `OnError` notify listeners with a {@see THttpClientEventParameter}.
 *
 * @method RequestInterface dyBeforeSend(RequestInterface $request, array $options) Filters the request before it is sent.
 * @method ResponseInterface dyAfterResponse(ResponseInterface $response, RequestInterface $request, array $options) Filters the response after it arrives.
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class THttpClient extends TApplicationComponent implements ClientInterface
{
	/** The handlers a request from the main fiber uses, in order of preference. */
	public const HANDLERS = [TCurlHttpHandler::class, TSocketHttpHandler::class, TStreamHttpHandler::class];

	/** The handlers a request from inside a fiber uses, in order of preference. */
	public const ASYNC_HANDLERS = [TCurlMultiHttpHandler::class, TSocketHttpHandler::class];

	/** The redirect status codes the client follows. */
	public const REDIRECT_STATUSES = [301, 302, 303, 307, 308];

	/** @var ?IHttpHandler The handler every request uses, or null to choose per request. */
	private ?IHttpHandler $_handler = null;

	/** @var array<string, IHttpHandler> The handlers created for this client, by class. */
	private array $_handlers = [];

	/** @var float The seconds a transfer may take; 0 for no limit. */
	private float $_timeout = 30.0;

	/** @var float The seconds a connect may take; 0 for no limit. */
	private float $_connectTimeout = 10.0;

	/** @var bool Whether redirects are followed. */
	private bool $_followRedirects = true;

	/** @var int The longest redirect chain followed. */
	private int $_maxRedirects = 5;

	/** @var bool|string Whether the TLS peer is verified, or the CA file or directory. */
	private bool|string $_verify = true;

	/** @var bool Whether compressed bodies are asked for and decoded. */
	private bool $_decodeContent = true;

	/** @var bool Whether {@see send()} throws for a 4xx or 5xx response. */
	private bool $_httpErrors = false;

	/** @var ?array<string, string|string[]> The default request headers, or null for the built-in default. */
	private ?array $_headers = null;

	/** @var ?UriInterface The base of relative {@see request()} URIs. */
	private ?UriInterface $_baseUri = null;

	/**
	 * Reports whether any handler of {@see HANDLERS} can run on the current PHP.
	 * @return bool Whether the client can send requests.
	 */
	public static function getHasAvailableHandler(): bool
	{
		foreach (static::HANDLERS as $class) {
			if ($class::getIsAvailable()) {
				return true;
			}
		}
		return false;
	}

	//
	// ─── Sending ─────────────────────────────────────────────────────────────
	//

	/**
	 * Sends a PSR-7 request and returns the response for every status code.
	 * @param RequestInterface $request The request.
	 * @throws THttpNetworkException When no response arrives.
	 * @throws THttpRequestException When the request cannot be sent.
	 * @return ResponseInterface The response.
	 */
	public function sendRequest(RequestInterface $request): ResponseInterface
	{
		return $this->send($request, [THttpClientOption::HTTP_ERRORS => false]);
	}

	/**
	 * Sends a request with per-call options, following redirects.
	 * @param RequestInterface $request The request.
	 * @param array<string, mixed> $options The {@see THttpClientOption} options; they override the client's defaults.
	 * @throws THttpNetworkException When no response arrives.
	 * @throws THttpRequestException When the request cannot be sent or redirects too often.
	 * @throws THttpResponseException When {@see THttpClientOption::HTTP_ERRORS} is true and the status is 4xx or 5xx.
	 * @throws \Prado\Exceptions\TCancelledException When the {@see THttpClientOption::TOKEN} is cancelled.
	 * @return ResponseInterface The response.
	 */
	public function send(RequestInterface $request, array $options = []): ResponseInterface
	{
		$options = $options + $this->getDefaultOptions();
		$request = $this->applyDefaultHeaders($request);
		$request = $this->callBehaviorsFilter('dyBeforeSend', $request, $options);
		$this->onBeforeSend(new THttpClientEventParameter($request, $options));
		try {
			$response = $this->transfer($request, $options);
			$response = $this->callBehaviorsFilter('dyAfterResponse', $response, $request, $options);
			if ($options[THttpClientOption::HTTP_ERRORS] && $response->getStatusCode() >= 400) {
				throw new THttpResponseException($request, $response);
			}
		} catch (\Throwable $e) {
			$this->onError(new THttpClientEventParameter($request, $options, null, $e));
			throw $e;
		}
		$this->onResponse(new THttpClientEventParameter($request, $options, $response));
		return $response;
	}

	/**
	 * Sends a request in a new fiber and returns the future of its response.  The fiber
	 * uses an asynchronous handler, so several requests proceed at once.
	 * @param RequestInterface $request The request.
	 * @param array<string, mixed> $options The {@see THttpClientOption} options.
	 * @return TFuture Resolves with the response, or rejects with the exception {@see send()} would throw.
	 */
	public function sendAsync(RequestInterface $request, array $options = []): TFuture
	{
		return TAsync::run(fn () => $this->send($request, $options));
	}

	/**
	 * Builds a request and sends it.
	 * @param string $method The request method.
	 * @param string|UriInterface $uri The URI; a relative URI resolves against {@see getBaseUri BaseUri}.
	 * @param array<string, mixed> $options The {@see THttpClientOption} options, including the request-building ones.
	 * @throws \InvalidArgumentException When the method, URI, or a header is invalid.
	 * @throws THttpClientException When the request fails; see {@see send()}.
	 * @return ResponseInterface The response.
	 */
	public function request(string $method, string|UriInterface $uri, array $options = []): ResponseInterface
	{
		return $this->send($this->createRequest($method, $uri, $options), $options);
	}

	/**
	 * Builds a request and sends it in a new fiber.
	 * @param string $method The request method.
	 * @param string|UriInterface $uri The URI; a relative URI resolves against {@see getBaseUri BaseUri}.
	 * @param array<string, mixed> $options The {@see THttpClientOption} options, including the request-building ones.
	 * @return TFuture Resolves with the response, or rejects with the exception {@see request()} would throw.
	 */
	public function requestAsync(string $method, string|UriInterface $uri, array $options = []): TFuture
	{
		return TAsync::run(fn () => $this->request($method, $uri, $options));
	}

	/**
	 * Raises `OnBeforeSend` before a request is sent.
	 * @param THttpClientEventParameter $param The request and its options.
	 */
	public function onBeforeSend(THttpClientEventParameter $param): void
	{
		$this->raiseEvent('OnBeforeSend', $this, $param);
	}

	/**
	 * Raises `OnResponse` after the final response of a request arrives.
	 * @param THttpClientEventParameter $param The request, its options, and the response.
	 */
	public function onResponse(THttpClientEventParameter $param): void
	{
		$this->raiseEvent('OnResponse', $this, $param);
	}

	/**
	 * Raises `OnError` when a request fails.
	 * @param THttpClientEventParameter $param The request, its options, and the exception.
	 */
	public function onError(THttpClientEventParameter $param): void
	{
		$this->raiseEvent('OnError', $this, $param);
	}

	//
	// ─── Request building and redirects ──────────────────────────────────────
	//

	/**
	 * Builds the request of {@see request()}.
	 * @param string $method The request method.
	 * @param string|UriInterface $uri The URI.
	 * @param array<string, mixed> $options The options holding the request parts.
	 * @throws \InvalidArgumentException When a part is invalid.
	 * @return RequestInterface The request.
	 */
	protected function createRequest(string $method, string|UriInterface $uri, array $options): RequestInterface
	{
		$uri = $uri instanceof UriInterface ? $uri : new TResourceUri($uri);
		$base = $this->getBaseUri();
		if ($base !== null && $uri->getScheme() === '') {
			$uri = $this->resolveUri($base, (string) $uri);
		}
		if (isset($options[THttpClientOption::QUERY])) {
			$query = $options[THttpClientOption::QUERY];
			$uri = $uri->withQuery(is_array($query) ? http_build_query($query, '', '&', PHP_QUERY_RFC3986) : (string) $query);
		}
		$headers = $options[THttpClientOption::HEADERS] ?? [];
		$body = $options[THttpClientOption::BODY] ?? null;
		if (array_key_exists(THttpClientOption::JSON, $options)) {
			$body = json_encode($options[THttpClientOption::JSON], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
			$headers = $this->withDefaultHeader($headers, 'Content-Type', 'application/json');
		} elseif (isset($options[THttpClientOption::FORM_PARAMS])) {
			$body = http_build_query($options[THttpClientOption::FORM_PARAMS], '', '&');
			$headers = $this->withDefaultHeader($headers, 'Content-Type', 'application/x-www-form-urlencoded');
		}
		return new TRequest($method, $uri, $headers, $body);
	}

	/**
	 * Adds a header to a header map unless a header of that name, in any case, is present.
	 * @param array<string, string|string[]> $headers The headers.
	 * @param string $name The header name.
	 * @param string $value The value.
	 * @return array<string, string|string[]> The headers.
	 */
	protected function withDefaultHeader(array $headers, string $name, string $value): array
	{
		foreach (array_keys($headers) as $existing) {
			if (strcasecmp((string) $existing, $name) === 0) {
				return $headers;
			}
		}
		$headers[$name] = $value;
		return $headers;
	}

	/**
	 * Adds the {@see getHeaders Headers} a request lacks.
	 * @param RequestInterface $request The request.
	 * @return RequestInterface The request with the default headers.
	 */
	protected function applyDefaultHeaders(RequestInterface $request): RequestInterface
	{
		foreach ($this->getHeaders() as $name => $value) {
			if (!$request->hasHeader((string) $name)) {
				$request = $request->withHeader((string) $name, $value);
			}
		}
		return $request;
	}

	/**
	 * Sends a request through the handler and follows its redirects.
	 * @param RequestInterface $request The request.
	 * @param array<string, mixed> $options The merged options.
	 * @throws THttpRequestException When the redirect chain is too long.
	 * @return ResponseInterface The final response.
	 */
	protected function transfer(RequestInterface $request, array $options): ResponseInterface
	{
		$handler = $this->resolveHandler();
		$max = max(0, (int) $options[THttpClientOption::MAX_REDIRECTS]);
		$redirects = 0;
		while (true) {
			$response = $handler->handle($request, $options);
			$next = $options[THttpClientOption::ALLOW_REDIRECTS] ? $this->createRedirectRequest($request, $response) : null;
			if ($next === null) {
				return $response;
			}
			if (++$redirects > $max) {
				throw new THttpRequestException($request, 'httpclient_too_many_redirects', $max, (string) $request->getUri());
			}
			$request = $next;
		}
	}

	/**
	 * Builds the request that follows a redirect response.
	 * @param RequestInterface $request The request that was answered.
	 * @param ResponseInterface $response The response.
	 * @throws THttpRequestException When a 307 or 308 must resend a body that cannot be rewound.
	 * @return ?RequestInterface The next request, or null when the response is not followed.
	 */
	protected function createRedirectRequest(RequestInterface $request, ResponseInterface $response): ?RequestInterface
	{
		$status = $response->getStatusCode();
		$location = $response->getHeaderLine('Location');
		if (!in_array($status, static::REDIRECT_STATUSES, true) || $location === '') {
			return null;
		}
		try {
			$uri = $this->resolveUri($request->getUri(), $location);
		} catch (\InvalidArgumentException $e) {
			return null;
		}
		if (!in_array(strtolower($uri->getScheme()), THttpHandler::SCHEMES, true) || $uri->getHost() === '') {
			return null;
		}
		$next = $request;
		$method = strtoupper($request->getMethod());
		if ($status === 303 || (($status === 301 || $status === 302) && $method === 'POST')) {
			if ($method !== 'HEAD') {
				$next = $next->withMethod('GET');
			}
			$next = $next->withBody(TStream::fromString(''))->withoutHeader('Content-Type')->withoutHeader('Content-Length')->withoutHeader('Transfer-Encoding');
		} else {
			$body = $next->getBody();
			if ($body->getSize() !== 0 && $body->tell() !== 0) {
				if (!$body->isSeekable()) {
					throw new THttpRequestException($request, 'httpclient_redirect_body_unrewindable', (string) $request->getUri(), $status);
				}
				$body->rewind();
			}
		}
		$current = $request->getUri();
		if (strcasecmp($current->getHost(), $uri->getHost()) !== 0 || $current->getScheme() !== $uri->getScheme() || $current->getPort() !== $uri->getPort()) {
			$next = $next->withoutHeader('Authorization')->withoutHeader('Cookie');
		}
		return $next->withUri($uri);
	}

	/**
	 * Resolves a URI reference against a base URI, per RFC 3986 section 5.2.
	 * @param UriInterface $base The absolute base URI.
	 * @param string $reference The reference, such as a `Location` header value.
	 * @throws \InvalidArgumentException When the reference cannot be parsed.
	 * @return UriInterface The resolved URI.
	 */
	protected function resolveUri(UriInterface $base, string $reference): UriInterface
	{
		$ref = new TResourceUri($reference);
		if ($ref->getScheme() !== '') {
			return $ref->withPath($this->removeDotSegments($ref->getPath()));
		}
		if ($ref->getAuthority() !== '') {
			return $ref->withScheme($base->getScheme())->withPath($this->removeDotSegments($ref->getPath()));
		}
		$path = $ref->getPath();
		$query = $ref->getQuery();
		if ($path === '') {
			$path = $base->getPath();
			if ($query === '' && !str_contains($reference, '?')) {
				$query = $base->getQuery();
			}
		} elseif ($path[0] !== '/') {
			$basePath = $base->getPath();
			if ($base->getAuthority() !== '' && $basePath === '') {
				$path = '/' . $path;
			} else {
				$slash = strrpos($basePath, '/');
				$path = ($slash === false ? '' : substr($basePath, 0, $slash + 1)) . $path;
			}
		}
		return $base->withPath($this->removeDotSegments($path))->withQuery($query)->withFragment($ref->getFragment());
	}

	/**
	 * Removes the `.` and `..` segments of a path, per RFC 3986 section 5.2.4.
	 * @param string $path The path.
	 * @return string The path without dot segments.
	 */
	protected function removeDotSegments(string $path): string
	{
		if ($path === '' || (!str_contains($path, './') && !str_ends_with($path, '/.') && !str_ends_with($path, '/..') && $path !== '.' && $path !== '..')) {
			return $path;
		}
		$output = [];
		$segments = explode('/', $path);
		$last = count($segments) - 1;
		foreach ($segments as $i => $segment) {
			if ($segment === '..') {
				if (count($output) > 1 || (count($output) === 1 && $output[0] !== '')) {
					array_pop($output);
				}
			} elseif ($segment !== '.') {
				$output[] = $segment;
			}
			if (($segment === '.' || $segment === '..') && $i === $last) {
				$output[] = '';
			}
		}
		$result = implode('/', $output);
		if ($path[0] === '/' && ($result === '' || $result[0] !== '/')) {
			$result = '/' . $result;
		}
		return $result;
	}

	//
	// ─── Handlers ────────────────────────────────────────────────────────────
	//

	/**
	 * Returns the handler of the next request: the {@see getHandler Handler}, or the first
	 * available of {@see ASYNC_HANDLERS} inside a fiber and of {@see HANDLERS} outside one.
	 * @throws THttpClientException When no handler is available.
	 * @return IHttpHandler The handler.
	 */
	protected function resolveHandler(): IHttpHandler
	{
		$handler = $this->getHandler();
		if ($handler !== null) {
			return $handler;
		}
		$classes = \Fiber::getCurrent() === null ? static::HANDLERS : static::ASYNC_HANDLERS;
		foreach ($classes as $class) {
			if ($class::getIsAvailable()) {
				return $this->_handlers[$class] ??= Prado::createComponent($class);
			}
		}
		throw new THttpClientException('httpclient_transport_required');
	}

	/**
	 * Returns the options from the client's properties.
	 * @return array<string, mixed> The default options.
	 */
	protected function getDefaultOptions(): array
	{
		return [
			THttpClientOption::TIMEOUT => $this->getTimeout(),
			THttpClientOption::CONNECT_TIMEOUT => $this->getConnectTimeout(),
			THttpClientOption::VERIFY => $this->getVerify(),
			THttpClientOption::DECODE_CONTENT => $this->getDecodeContent(),
			THttpClientOption::ALLOW_REDIRECTS => $this->getFollowRedirects(),
			THttpClientOption::MAX_REDIRECTS => $this->getMaxRedirects(),
			THttpClientOption::HTTP_ERRORS => $this->getHttpErrors(),
		];
	}

	//
	// ─── Properties ──────────────────────────────────────────────────────────
	//

	/**
	 * Returns the handler every request uses.
	 * @return ?IHttpHandler The handler, or null when the client chooses one per request.
	 */
	public function getHandler(): ?IHttpHandler
	{
		return $this->_handler;
	}

	/**
	 * Sets the handler every request uses.  A class name is created as a component.
	 * @param null|IHttpHandler|string $value The handler, its class name, or null to choose per request.
	 * @throws TConfigurationException When the value is not an IHttpHandler.
	 * @return static The client.
	 */
	public function setHandler(null|IHttpHandler|string $value): static
	{
		if (is_string($value)) {
			if (!is_a($value, IHttpHandler::class, true)) {
				throw new TConfigurationException('httpclient_handler_invalid', $value);
			}
			$value = Prado::createComponent($value);
		}
		$this->_handler = $value;
		return $this;
	}

	/**
	 * Returns the seconds a transfer may take.
	 * @return float The timeout; 0 for no limit.
	 */
	public function getTimeout(): float
	{
		return $this->_timeout;
	}

	/**
	 * Sets the seconds a transfer may take.
	 * @param float|int|string $value The timeout; 0 for no limit.
	 * @return static The client.
	 */
	public function setTimeout($value): static
	{
		$this->_timeout = max(0.0, TPropertyValue::ensureFloat($value));
		return $this;
	}

	/**
	 * Returns the seconds a connect may take.
	 * @return float The connect timeout; 0 for no limit.
	 */
	public function getConnectTimeout(): float
	{
		return $this->_connectTimeout;
	}

	/**
	 * Sets the seconds a connect may take.
	 * @param float|int|string $value The connect timeout; 0 for no limit.
	 * @return static The client.
	 */
	public function setConnectTimeout($value): static
	{
		$this->_connectTimeout = max(0.0, TPropertyValue::ensureFloat($value));
		return $this;
	}

	/**
	 * Returns whether redirects are followed.
	 * @return bool Whether redirects are followed.
	 */
	public function getFollowRedirects(): bool
	{
		return $this->_followRedirects;
	}

	/**
	 * Sets whether redirects are followed.
	 * @param bool|string $value Whether redirects are followed.
	 * @return static The client.
	 */
	public function setFollowRedirects($value): static
	{
		$this->_followRedirects = TPropertyValue::ensureBoolean($value);
		return $this;
	}

	/**
	 * Returns the longest redirect chain followed.
	 * @return int The maximum redirects.
	 */
	public function getMaxRedirects(): int
	{
		return $this->_maxRedirects;
	}

	/**
	 * Sets the longest redirect chain followed.
	 * @param int|string $value The maximum redirects; a negative value is 0.
	 * @return static The client.
	 */
	public function setMaxRedirects($value): static
	{
		$this->_maxRedirects = max(0, TPropertyValue::ensureInteger($value));
		return $this;
	}

	/**
	 * Returns whether the TLS peer is verified, or the CA file or directory it is verified with.
	 * @return bool|string The verification setting.
	 */
	public function getVerify(): bool|string
	{
		return $this->_verify;
	}

	/**
	 * Sets whether the TLS peer is verified.  A string other than a boolean word is the CA
	 * file or directory to verify with.
	 * @param bool|string $value The verification setting.
	 * @return static The client.
	 */
	public function setVerify(bool|string $value): static
	{
		if (is_string($value) && in_array(strtolower($value), ['true', 'false', 'on', 'off', 'yes', 'no', '1', '0', ''], true)) {
			$value = TPropertyValue::ensureBoolean($value);
		}
		$this->_verify = $value;
		return $this;
	}

	/**
	 * Returns whether compressed bodies are asked for and decoded.
	 * @return bool Whether content is decoded.
	 */
	public function getDecodeContent(): bool
	{
		return $this->_decodeContent;
	}

	/**
	 * Sets whether compressed bodies are asked for and decoded.
	 * @param bool|string $value Whether content is decoded.
	 * @return static The client.
	 */
	public function setDecodeContent($value): static
	{
		$this->_decodeContent = TPropertyValue::ensureBoolean($value);
		return $this;
	}

	/**
	 * Returns whether {@see send()} and {@see request()} throw {@see THttpResponseException}
	 * for a 4xx or 5xx response.  {@see sendRequest()} never does.
	 * @return bool Whether error responses throw.
	 */
	public function getHttpErrors(): bool
	{
		return $this->_httpErrors;
	}

	/**
	 * Sets whether {@see send()} and {@see request()} throw for a 4xx or 5xx response.
	 * @param bool|string $value Whether error responses throw.
	 * @return static The client.
	 */
	public function setHttpErrors($value): static
	{
		$this->_httpErrors = TPropertyValue::ensureBoolean($value);
		return $this;
	}

	/**
	 * Returns the headers added to a request that lacks them.
	 * @return array<string, string|string[]> The default headers; by default a `User-Agent` of `Prado/<version>`.
	 */
	public function getHeaders(): array
	{
		return $this->_headers ?? ['User-Agent' => 'Prado/' . Prado::getVersion()];
	}

	/**
	 * Sets the headers added to a request that lacks them.
	 * @param array<string, string|string[]> $value The default headers; `[]` for none.
	 * @return static The client.
	 */
	public function setHeaders(array $value): static
	{
		$this->_headers = $value;
		return $this;
	}

	/**
	 * Returns the base of relative {@see request()} URIs.
	 * @return ?UriInterface The base URI, or null.
	 */
	public function getBaseUri(): ?UriInterface
	{
		return $this->_baseUri;
	}

	/**
	 * Sets the base of relative {@see request()} URIs.
	 * @param null|string|UriInterface $value The absolute base URI, or null.
	 * @return static The client.
	 */
	public function setBaseUri(null|string|UriInterface $value): static
	{
		$this->_baseUri = is_string($value) ? ($value === '' ? null : new TResourceUri($value)) : $value;
		return $this;
	}
}
