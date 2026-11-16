<?php

/**
 * TCachedHttpClient class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\HttpClient;

use Prado\Caching\ICache;
use Prado\IO\Http\TResponse;
use Prado\TComponent;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * TCachedHttpClient class.
 *
 * TCachedHttpClient is a PSR-18 {@see ClientInterface} that caches the responses of any
 * other PSR-18 client in an {@see ICache}.  It acts as a private cache:
 * ```php
 * $client = new TCachedHttpClient(new THttpClient(), $application->getCache(), 300);
 * $response = $client->sendRequest(new TRequest('GET', 'https://api.example.com/items'));
 * ```
 *
 * | Case | Result |
 * |---|---|
 * | Method other than `GET` or `HEAD` | Sent to the inner client; nothing is stored. |
 * | Request `Cache-Control: no-store` or `no-cache` | Sent to the inner client; nothing is stored. |
 * | Response status other than 2xx | Returned; not stored. |
 * | Response `Cache-Control: no-store` or `no-cache`, or `Vary: *` | Returned; not stored. |
 * | Response `Cache-Control: max-age` | Stored for the lesser of it and {@see getTtl Ttl}; `max-age=0` is not stored. |
 *
 * A stored response is found by the method, the URI, the values of the request headers
 * its `Vary` names, and always the `Authorization` and `Cookie` headers, so responses
 * are never shared across credentials.  The body is stored as a string and a cached
 * response has a new body stream each time.  {@see invalidate()} forgets the responses
 * of a URI.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TCachedHttpClient extends TComponent implements ClientInterface
{
	/** The request headers every cache key includes. */
	public const KEY_HEADERS = ['authorization', 'cookie'];

	/**
	 * Builds the caching client.
	 * @param ClientInterface $_inner The client that sends requests on a cache miss.
	 * @param ICache $_cache The cache the responses are stored in.
	 * @param int $_ttl The longest seconds a response is stored.
	 */
	public function __construct(private ClientInterface $_inner, private ICache $_cache, private int $_ttl = 300)
	{
		parent::__construct();
	}

	/**
	 * Returns a stored response when one matches, otherwise sends the request through the
	 * inner client and stores a cacheable response.
	 * @param RequestInterface $request The request.
	 * @throws \Psr\Http\Client\ClientExceptionInterface When the inner client fails.
	 * @return ResponseInterface The response.
	 */
	public function sendRequest(RequestInterface $request): ResponseInterface
	{
		$method = strtoupper($request->getMethod());
		if (($method !== 'GET' && $method !== 'HEAD') || $this->hasDirective($request->getHeaderLine('Cache-Control'), ['no-store', 'no-cache'])) {
			return $this->getInner()->sendRequest($request);
		}
		$baseKey = $this->getBaseKey($method, (string) $request->getUri());
		$vary = $this->getCache()->get($baseKey);
		if (is_array($vary)) {
			$entry = $this->getCache()->get($this->getVariantKey($baseKey, $request, $vary));
			if (is_array($entry)) {
				return $this->createResponse($entry);
			}
		}
		$response = $this->getInner()->sendRequest($request);
		$ttl = $this->getStorableTtl($response);
		if ($ttl === null) {
			return $response;
		}
		$entry = [
			'status' => $response->getStatusCode(),
			'reason' => $response->getReasonPhrase(),
			'version' => $response->getProtocolVersion(),
			'headers' => $response->getHeaders(),
			'body' => $this->readBody($response),
		];
		$vary = $this->getVaryNames($response);
		$this->getCache()->set($baseKey, $vary, $this->getTtl());
		$this->getCache()->set($this->getVariantKey($baseKey, $request, $vary), $entry, $ttl);
		return $this->createResponse($entry);
	}

	/**
	 * Forgets the stored responses of a method and URI, for every variant.
	 * @param string $method The method, typically `GET`.
	 * @param string $uri The absolute URI.
	 */
	public function invalidate(string $method, string $uri): void
	{
		$this->getCache()->delete($this->getBaseKey(strtoupper($method), $uri));
	}

	/**
	 * Returns the client that sends requests on a cache miss.
	 * @return ClientInterface The inner client.
	 */
	public function getInner(): ClientInterface
	{
		return $this->_inner;
	}

	/**
	 * Returns the cache the responses are stored in.
	 * @return ICache The cache.
	 */
	public function getCache(): ICache
	{
		return $this->_cache;
	}

	/**
	 * Returns the longest seconds a response is stored.
	 * @return int The time to live.
	 */
	public function getTtl(): int
	{
		return $this->_ttl;
	}

	/**
	 * Returns the seconds a response may be stored, or null when it may not be.
	 * @param ResponseInterface $response The response.
	 * @return ?int The time to live, or null.
	 */
	protected function getStorableTtl(ResponseInterface $response): ?int
	{
		$status = $response->getStatusCode();
		if ($status < 200 || $status >= 300 || in_array('*', $this->getVaryNames($response), true)) {
			return null;
		}
		$control = $response->getHeaderLine('Cache-Control');
		if ($this->hasDirective($control, ['no-store', 'no-cache'])) {
			return null;
		}
		$ttl = $this->getTtl();
		if (preg_match('/(?:^|,)\s*max-age\s*=\s*"?(\d+)"?/i', $control, $m) === 1) {
			$ttl = min($ttl, (int) $m[1]);
		}
		return $ttl > 0 ? $ttl : null;
	}

	/**
	 * Reports whether a `Cache-Control` value holds any of the given directives.
	 * @param string $control The header value.
	 * @param string[] $directives The lower-case directive names.
	 * @return bool Whether one is present.
	 */
	protected function hasDirective(string $control, array $directives): bool
	{
		foreach (explode(',', strtolower($control)) as $part) {
			$name = trim(explode('=', $part, 2)[0]);
			if (in_array($name, $directives, true)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Returns the lower-case request header names a response's `Vary` lists.
	 * @param ResponseInterface $response The response.
	 * @return string[] The names, sorted; `*` when the response varies on everything.
	 */
	protected function getVaryNames(ResponseInterface $response): array
	{
		$names = [];
		foreach ($response->getHeader('Vary') as $value) {
			foreach (explode(',', $value) as $name) {
				$name = strtolower(trim($name));
				if ($name !== '') {
					$names[$name] = $name;
				}
			}
		}
		sort($names);
		return array_values($names);
	}

	/**
	 * Returns the cache key of a method and URI, which holds the `Vary` names.
	 * @param string $method The upper-case method.
	 * @param string $uri The URI.
	 * @return string The key.
	 */
	protected function getBaseKey(string $method, string $uri): string
	{
		return 'httpclient:' . $method . ':' . sha1($uri);
	}

	/**
	 * Returns the cache key of a stored response: the base key and the values of the
	 * `Vary` headers and {@see KEY_HEADERS} of the request.
	 * @param string $baseKey The base key.
	 * @param RequestInterface $request The request.
	 * @param string[] $vary The `Vary` names.
	 * @return string The key.
	 */
	protected function getVariantKey(string $baseKey, RequestInterface $request, array $vary): string
	{
		$values = [];
		foreach (array_unique(array_merge(static::KEY_HEADERS, $vary)) as $name) {
			$values[$name] = $request->getHeaderLine($name);
		}
		ksort($values);
		return $baseKey . ':' . sha1(serialize($values));
	}

	/**
	 * Reads the whole body of a response, from the start when the body is seekable.
	 * @param ResponseInterface $response The response.
	 * @return string The body.
	 */
	protected function readBody(ResponseInterface $response): string
	{
		$body = $response->getBody();
		if ($body->isSeekable()) {
			$body->rewind();
		}
		return $body->getContents();
	}

	/**
	 * Builds a response from a stored entry.
	 * @param array{status: int, reason: string, version: string, headers: array<string, string[]>, body: string} $entry The entry.
	 * @return ResponseInterface The response, with a new body stream.
	 */
	protected function createResponse(array $entry): ResponseInterface
	{
		return new TResponse($entry['status'], $entry['headers'], $entry['body'], $entry['version'], $entry['reason']);
	}
}
