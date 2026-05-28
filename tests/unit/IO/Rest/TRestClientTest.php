<?php

use Prado\Caching\ICache;
use Prado\IO\HttpClient\TCachedHttpClient;
use Prado\IO\HttpClient\TCurlHttpClient;
use Prado\IO\HttpClient\TFopenHttpClient;
use Prado\IO\HttpClient\THttpClientException;
use Prado\IO\HttpClient\THttpClientResponse;
use Prado\IO\HttpClient\THttpClient;
use Prado\IO\Rest\TRestClient;

// ── Mock downloader that records calls and returns canned responses ───────────

class MockHttpClient extends THttpClient
{
	public array $calls = [];

	/** @var THttpClientResponse[] */
	public array $responses = [];

	/** @var null|\Throwable */
	public ?\Throwable $throwOnNextCall = null;

	public function queue(THttpClientResponse $response): void
	{
		$this->responses[] = $response;
	}

	public function download(string $method, string $url, array $headers = [], ?string $body = null): THttpClientResponse
	{
		$this->calls[] = compact('method', 'url', 'headers', 'body');
		if ($this->throwOnNextCall !== null) {
			$e = $this->throwOnNextCall;
			$this->throwOnNextCall = null;
			throw $e;
		}
		if ($this->responses === []) {
			return new THttpClientResponse(200, [], '{}');
		}
		return array_shift($this->responses);
	}
}

// ── In-memory cache implementing ICache (just what TCachedHttpClient uses) ────

class InMemoryCache implements ICache
{
	public array $store = [];
	public int $getCalls = 0;
	public int $setCalls = 0;

	public function get($id)
	{
		$this->getCalls++;
		return $this->store[$id] ?? false;
	}
	public function set($id, $value, $expire = 0, $dependency = null)
	{
		$this->setCalls++;
		$this->store[$id] = $value;
		return true;
	}
	public function add($id, $value, $expire = 0, $dependency = null)
	{
		if (isset($this->store[$id])) {
			return false;
		}
		return $this->set($id, $value, $expire, $dependency);
	}
	public function delete($id)
	{
		unset($this->store[$id]);
		return true;
	}
	public function flush()
	{
		$this->store = [];
		return true;
	}
}

// ── Test client subclass exercising the dispatch surface ──────────────────────

class TestApiClient extends TRestClient
{
	public function __construct()
	{
		parent::__construct();
		$this->setBaseUrl('https://api.example.test/');
	}

	public function fetchUser(string $id): mixed
	{
		return $this->get('users/{id}', ['id' => $id]);
	}

	public function listUsers(int $page): mixed
	{
		return $this->get('users', [], ['page' => $page]);
	}

	public function createUser(array $data): mixed
	{
		return $this->post('users', [], $data);
	}

	public function updateUser(string $id, array $data): mixed
	{
		return $this->put('users/{id}', ['id' => $id], $data);
	}

	public function deleteUser(string $id): mixed
	{
		return $this->delete('users/{id}', ['id' => $id]);
	}

	public function exposeBuildUrl(string $path, array $params = [], array $query = []): string
	{
		return $this->buildUrl($path, $params, $query);
	}
}

/**
 * Tests for TRestClient.
 */
class TRestClientTest extends PHPUnit\Framework\TestCase
{
	private TestApiClient $client;
	private MockHttpClient $downloader;

	protected function setUp(): void
	{
		$this->downloader = new MockHttpClient();
		$this->client = new TestApiClient();
		$this->client->setDownloader($this->downloader);
	}

	// ── URL building ──────────────────────────────────────────────────────────

	public function testBuildUrlExpandsPlaceholders(): void
	{
		$this->assertSame(
			'https://api.example.test/users/42',
			$this->client->exposeBuildUrl('users/{id}', ['id' => '42'])
		);
	}

	public function testBuildUrlUrlEncodesPlaceholderValues(): void
	{
		$this->assertSame(
			'https://api.example.test/items/a%20b%2Fc',
			$this->client->exposeBuildUrl('items/{slug}', ['slug' => 'a b/c'])
		);
	}

	public function testBuildUrlLeavesUnknownPlaceholdersIntact(): void
	{
		$this->assertSame(
			'https://api.example.test/users/{id}',
			$this->client->exposeBuildUrl('users/{id}', [])
		);
	}

	public function testBuildUrlAppendsQueryString(): void
	{
		$url = $this->client->exposeBuildUrl('search', [], ['q' => 'hello world', 'page' => 2]);
		$this->assertSame('https://api.example.test/search?q=hello+world&page=2', $url);
	}

	public function testBuildUrlAppendsQueryWithExistingQueryString(): void
	{
		$url = $this->client->exposeBuildUrl('search?lang=en', [], ['q' => 'x']);
		$this->assertStringContainsString('?lang=en', $url);
		$this->assertStringContainsString('&q=x', $url);
	}

	// ── Verb dispatch ─────────────────────────────────────────────────────────

	public function testGetDispatch(): void
	{
		$this->downloader->queue(new THttpClientResponse(200, [], '{"id":"42","name":"Alice"}'));
		$user = $this->client->fetchUser('42');
		$this->assertSame(['id' => '42', 'name' => 'Alice'], $user);
		$this->assertSame('GET', $this->downloader->calls[0]['method']);
		$this->assertSame('https://api.example.test/users/42', $this->downloader->calls[0]['url']);
	}

	public function testGetWithQueryString(): void
	{
		$this->downloader->queue(new THttpClientResponse(200, [], '[]'));
		$this->client->listUsers(3);
		$this->assertSame('https://api.example.test/users?page=3', $this->downloader->calls[0]['url']);
	}

	public function testPostJsonEncodesBodyAndSetsContentType(): void
	{
		$this->downloader->queue(new THttpClientResponse(201, [], '{"id":"new"}'));
		$result = $this->client->createUser(['name' => 'Bob']);
		$call = $this->downloader->calls[0];
		$this->assertSame('POST', $call['method']);
		$this->assertSame('{"name":"Bob"}', $call['body']);
		$this->assertSame('application/json', $call['headers']['Content-Type']);
		$this->assertSame(['id' => 'new'], $result);
	}

	public function testPutDispatchesWithPathParams(): void
	{
		$this->downloader->queue(new THttpClientResponse(200, [], '{"ok":true}'));
		$this->client->updateUser('7', ['name' => 'C']);
		$this->assertSame('PUT', $this->downloader->calls[0]['method']);
		$this->assertSame('https://api.example.test/users/7', $this->downloader->calls[0]['url']);
	}

	public function testDeleteDispatch(): void
	{
		$this->downloader->queue(new THttpClientResponse(204, [], ''));
		$result = $this->client->deleteUser('9');
		$this->assertSame('DELETE', $this->downloader->calls[0]['method']);
		$this->assertNull($result); // empty body → null
	}

	// ── Headers ───────────────────────────────────────────────────────────────

	public function testDefaultHeadersAreSentOnEveryCall(): void
	{
		$this->client->setDefaultHeader('Authorization', 'Bearer xyz');
		$this->client->setDefaultHeader('Accept', 'application/json');
		$this->downloader->queue(new THttpClientResponse(200, [], '{}'));
		$this->client->fetchUser('1');
		$headers = $this->downloader->calls[0]['headers'];
		$this->assertSame('Bearer xyz', $headers['Authorization']);
		$this->assertSame('application/json', $headers['Accept']);
	}

	public function testPerCallHeadersOverrideDefaults(): void
	{
		$this->client->setDefaultHeader('Accept', 'application/json');
		$this->downloader->queue(new THttpClientResponse(200, [], '{}'));
		// Reach the generic request via the get() verb helper with per-call header.
		$this->client->get('users/{id}', ['id' => '1'], [], ['Accept' => 'text/plain']);
		$this->assertSame('text/plain', $this->downloader->calls[0]['headers']['Accept']);
	}

	public function testExplicitContentTypeIsRespected(): void
	{
		$this->downloader->queue(new THttpClientResponse(200, [], '{}'));
		$this->client->post('upload', [], 'raw-string-body', [], ['Content-Type' => 'text/plain']);
		$this->assertSame('raw-string-body', $this->downloader->calls[0]['body']);
		$this->assertSame('text/plain', $this->downloader->calls[0]['headers']['Content-Type']);
	}

	// ── Error handling ────────────────────────────────────────────────────────

	public function testThrowsOnHttpErrorByDefault(): void
	{
		$this->downloader->queue(new THttpClientResponse(404, [], '{"error":"not found"}'));
		try {
			$this->client->fetchUser('missing');
			$this->fail('expected exception');
		} catch (THttpClientException $e) {
			$this->assertSame(404, $e->getStatusCode());
			$this->assertNotNull($e->getResponse());
			$this->assertSame(['error' => 'not found'], $e->getResponse()->getJson());
		}
	}

	public function testReturnsResponseWhenThrowOnErrorIsOff(): void
	{
		$this->client->setThrowOnError(false);
		$this->downloader->queue(new THttpClientResponse(500, [], '{"oops":true}'));
		$result = $this->client->fetchUser('boom');
		$this->assertInstanceOf(THttpClientResponse::class, $result);
		$this->assertSame(500, $result->getStatusCode());
	}

	public function testTransportFailurePropagatesAsException(): void
	{
		$this->downloader->throwOnNextCall = new THttpClientException('httpclient_transport_error', 0, 'DNS failed');
		try {
			$this->client->fetchUser('x');
			$this->fail('expected exception');
		} catch (THttpClientException $e) {
			$this->assertNull($e->getResponse());
			$this->assertSame(0, $e->getStatusCode());
		}
	}

	// ── Downloader factory + cache wrapper ────────────────────────────────────

	public function testCreatePicksCurlWhenAvailable(): void
	{
		$d = THttpClient::create();
		if (function_exists('curl_init')) {
			$this->assertInstanceOf(TCurlHttpClient::class, $d);
		} else {
			$this->assertInstanceOf(TFopenHttpClient::class, $d);
		}
	}

	public function testNewCacheWrapperReturnsTCachedHttpClient(): void
	{
		$inner = new MockHttpClient();
		$cache = new InMemoryCache();
		$wrapped = THttpClient::newCacheWrapper($inner, $cache, 60);
		$this->assertInstanceOf(TCachedHttpClient::class, $wrapped);
		$this->assertSame($inner, $wrapped->getInner());
		$this->assertSame($cache, $wrapped->getCache());
		$this->assertSame(60, $wrapped->getTtl());
	}

	public function testCacheWrapperServesRepeatedGetsFromCache(): void
	{
		$inner = new MockHttpClient();
		$inner->queue(new THttpClientResponse(200, [], '{"v":1}'));
		$cache = new InMemoryCache();
		$wrapped = THttpClient::newCacheWrapper($inner, $cache);

		$r1 = $wrapped->download('GET', 'https://x.test/a', ['Accept' => 'application/json']);
		$r2 = $wrapped->download('GET', 'https://x.test/a', ['Accept' => 'application/json']);

		$this->assertSame($r1, $r2); // same instance from cache
		$this->assertCount(1, $inner->calls); // inner only called once
		$this->assertSame(1, $cache->setCalls);
	}

	public function testCacheWrapperBypassesNonIdempotentMethods(): void
	{
		$inner = new MockHttpClient();
		$inner->queue(new THttpClientResponse(200, [], '{}'));
		$inner->queue(new THttpClientResponse(200, [], '{}'));
		$cache = new InMemoryCache();
		$wrapped = THttpClient::newCacheWrapper($inner, $cache);

		$wrapped->download('POST', 'https://x.test/a');
		$wrapped->download('POST', 'https://x.test/a');

		$this->assertCount(2, $inner->calls); // each POST hits inner
		$this->assertSame(0, $cache->setCalls);
	}

	public function testCacheWrapperDoesNotCacheErrors(): void
	{
		$inner = new MockHttpClient();
		$inner->queue(new THttpClientResponse(500, [], 'oops'));
		$cache = new InMemoryCache();
		$wrapped = THttpClient::newCacheWrapper($inner, $cache);

		$wrapped->download('GET', 'https://x.test/a');
		$this->assertSame(0, $cache->setCalls);
	}

	public function testCacheWrapperInvalidateRemovesEntry(): void
	{
		$inner = new MockHttpClient();
		$inner->queue(new THttpClientResponse(200, [], '{"v":1}'));
		$inner->queue(new THttpClientResponse(200, [], '{"v":2}'));
		$cache = new InMemoryCache();
		$wrapped = THttpClient::newCacheWrapper($inner, $cache);

		$r1 = $wrapped->download('GET', 'https://x.test/a');
		$wrapped->invalidate('GET', 'https://x.test/a');
		$r2 = $wrapped->download('GET', 'https://x.test/a');

		$this->assertSame('{"v":1}', $r1->getBody());
		$this->assertSame('{"v":2}', $r2->getBody());
		$this->assertCount(2, $inner->calls);
	}

	// ── Downloader properties (accessors) ─────────────────────────────────────

	public function testDownloaderPropertiesUseUapSe(): void
	{
		$d = new MockHttpClient();
		$d->setTimeout(45);
		$this->assertSame(45, $d->getTimeout());
		$d->setFollowRedirects(false);
		$this->assertFalse($d->getFollowRedirects());
		$d->setMaxRedirects(3);
		$this->assertSame(3, $d->getMaxRedirects());
	}

	public function testClientLazilyInstantiatesDefaultDownloader(): void
	{
		$c = new TestApiClient();
		$d = $c->getDownloader();
		$this->assertInstanceOf(THttpClient::class, $d);
		// Subsequent access returns the same instance
		$this->assertSame($d, $c->getDownloader());
	}
}
