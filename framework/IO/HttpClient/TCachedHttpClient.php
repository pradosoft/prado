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

/**
 * TCachedHttpClient class.
 *
 * TCachedHttpClient is a transparent caching wrapper around any other
 * {@see THttpClient}. It is intentionally a wrapper rather than a base
 * class so that any concrete transport — cURL, fopen, or a third-party
 * downloader provided by application code — can be cached uniformly.
 *
 * ## Behavior
 *
 * Only **idempotent successful** responses are cached: requests whose method
 * is `GET` or `HEAD` and whose response carries a 2xx status. Every other
 * request passes through to the inner downloader unchanged, and unsuccessful
 * responses are returned but not stored.
 *
 * The cache key is derived from the request method, URL, and the set of
 * headers that affect the response (e.g. `Accept`, `Authorization`).
 * Subclasses can override {@see cacheKey()} to alter that policy.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TCachedHttpClient extends THttpClient
{
	/**
	 * @var THttpClient Underlying transport used on cache misses.
	 */
	private THttpClient $_inner;

	/**
	 * @var ICache Cache module used for response storage.
	 */
	private ICache $_cache;

	/**
	 * @var int Cache time-to-live in seconds.
	 */
	private int $_ttl;

	/**
	 * Constructor.
	 * @param THttpClient $inner Downloader to delegate to on cache miss.
	 * @param ICache $cache Cache module to store responses in.
	 * @param int $ttl Cache time-to-live in seconds. Defaults to 300.
	 */
	public function __construct(THttpClient $inner, ICache $cache, int $ttl = 300)
	{
		parent::__construct();
		$this->_inner = $inner;
		$this->_cache = $cache;
		$this->_ttl = $ttl;
	}

	/**
	 * Returns a cached response when available, otherwise delegates and
	 * stores the result on success.
	 *
	 * @param string $method HTTP verb.
	 * @param string $url Absolute URL.
	 * @param array<string,string> $headers Request headers.
	 * @param ?string $body Raw request body.
	 * @throws THttpClientException on transport failure from the inner downloader.
	 * @return THttpClientResponse
	 */
	public function download(string $method, string $url, array $headers = [], ?string $body = null): THttpClientResponse
	{
		$verb = strtoupper($method);
		$idempotent = ($verb === 'GET' || $verb === 'HEAD');

		if (!$idempotent) {
			return $this->getInner()->download($method, $url, $headers, $body);
		}

		$cache = $this->getCache();
		$key = $this->cacheKey($verb, $url, $headers);
		$cached = $cache->get($key);
		if ($cached instanceof THttpClientResponse) {
			return $cached;
		}

		$response = $this->getInner()->download($method, $url, $headers, $body);
		if ($response->isSuccess()) {
			$cache->set($key, $response, $this->getTtl());
		}
		return $response;
	}

	/**
	 * Derives the cache key for a request.
	 *
	 * The default key includes the verb, URL, and a stable hash of all
	 * request headers. Subclasses may override this to ignore certain
	 * headers, namespace the key, or shorten it.
	 *
	 * @param string $verb Uppercase HTTP method.
	 * @param string $url Absolute URL.
	 * @param array<string,string> $headers Request headers.
	 * @return string Cache key.
	 */
	protected function cacheKey(string $verb, string $url, array $headers): string
	{
		ksort($headers);
		return 'httpclient:' . $verb . ':' . $this->computeAuthToken($url, $headers);
	}

	/**
	 * Computes a cache-key token from the request URL and headers.
	 *
	 * The token is derived by serializing the URL and the sorted header map,
	 * then hashing the result via {@see hashToken()}. Subclasses may override
	 * to exclude headers that do not affect the response (e.g. `User-Agent`),
	 * or to normalize the URL before hashing.
	 *
	 * @param string $url Absolute request URL.
	 * @param array<string,string> $headers Request headers (already sorted by caller).
	 * @return string Opaque token suitable for inclusion in a cache key.
	 */
	protected function computeAuthToken(string $url, array $headers): string
	{
		return $this->hashToken($url . '|' . serialize($headers));
	}

	/**
	 * Hashes an arbitrary token string into a fixed-length cache key segment.
	 *
	 * The default implementation uses SHA-1. Subclasses may override to use
	 * a faster algorithm (e.g. `hash('xxh64', $token)`) when cache-key
	 * collision resistance is not a concern.
	 *
	 * @param string $token Input string to hash.
	 * @return string Hex-encoded hash string.
	 */
	protected function hashToken(string $token): string
	{
		return sha1($token);
	}

	/**
	 * Invalidates a single cached response.
	 *
	 * Useful after a mutating request — for example, calling {@see invalidate()}
	 * with the same URL/headers after a `POST` clears any stale `GET` cache.
	 *
	 * @param string $verb HTTP method (typically `'GET'` or `'HEAD'`).
	 * @param string $url Absolute URL.
	 * @param array<string,string> $headers Request headers used at cache time.
	 */
	public function invalidate(string $verb, string $url, array $headers = []): void
	{
		$this->getCache()->delete($this->cacheKey(strtoupper($verb), $url, $headers));
	}

	/**
	 * @return THttpClient Underlying transport.
	 */
	public function getInner(): THttpClient
	{
		return $this->_inner;
	}

	/**
	 * @return ICache Cache module in use.
	 */
	public function getCache(): ICache
	{
		return $this->_cache;
	}

	/**
	 * @return int Cache time-to-live in seconds.
	 */
	public function getTtl(): int
	{
		return $this->_ttl;
	}
}
