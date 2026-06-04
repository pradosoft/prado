<?php

use Prado\IO\HttpClient\TCachedHttpClient;
use Prado\IO\HttpClient\THttpClient;
use Prado\IO\HttpClient\THttpClientResponse;

/**
 * Minimal in-memory ICache for testing.
 */
class CachedClientTestCache implements \Prado\Caching\ICache
{
	public array $store = [];
	public array $ttls = [];
	public int $getCalls = 0;
	public int $setCalls = 0;
	public int $deleteCalls = 0;

	public static function getIsAvailable(): bool
	{
		return true;
	}
	public function get($id)
	{
		$this->getCalls++;
		return $this->store[$id] ?? false;
	}
	public function set($id, $value, $expire = 0, $dependency = null)
	{
		$this->setCalls++;
		$this->store[$id] = $value;
		$this->ttls[$id] = $expire;
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
		$this->deleteCalls++;
		unset($this->store[$id]);
		return true;
	}
	public function flush()
	{
		$this->store = [];
		return true;
	}
}

/**
 * Recording downloader for cache-wrapper tests.
 */
class RecordingHttpClient extends THttpClient
{
	public array $calls = [];
	/** @var THttpClientResponse[] */
	public array $responses = [];

	public function queue(THttpClientResponse $r): void { $this->responses[] = $r; }

	public function download(string $method, string $url, array $headers = [], ?string $body = null): THttpClientResponse
	{
		$this->calls[] = ['method' => $method, 'url' => $url, 'headers' => $headers, 'body' => $body];
		return array_shift($this->responses) ?? new THttpClientResponse(200, [], '');
	}
}

/**
 * Cache wrapper subclass that exposes cacheKey() for direct testing.
 */
class ExposedCachedHttpClient extends TCachedHttpClient
{
	public function exposeCacheKey(string $verb, string $url, array $headers): string
	{
		return $this->cacheKey($verb, $url, $headers);
	}
}

/**
 * Tests for TCachedHttpClient.
 */
class TCachedHttpClientTest extends PHPUnit\Framework\TestCase
{
	private RecordingHttpClient $inner;
	private CachedClientTestCache $cache;
	private TCachedHttpClient $wrapped;

	protected function setUp(): void
	{
		$this->inner = new RecordingHttpClient();
		$this->cache = new CachedClientTestCache();
		$this->wrapped = new TCachedHttpClient($this->inner, $this->cache, 120);
	}

	public function testConstructorStoresDependencies(): void
	{
		$this->assertSame($this->inner, $this->wrapped->getInner());
		$this->assertSame($this->cache, $this->wrapped->getCache());
		$this->assertSame(120, $this->wrapped->getTtl());
	}

	public function testGetIsCachedAfterFirstSuccess(): void
	{
		$this->inner->queue(new THttpClientResponse(200, [], '{"v":1}'));
		$r1 = $this->wrapped->download('GET', 'https://x.test/a');
		$r2 = $this->wrapped->download('GET', 'https://x.test/a');

		$this->assertSame($r1, $r2);
		$this->assertCount(1, $this->inner->calls);
		$this->assertSame(1, $this->cache->setCalls);
	}

	public function testHeadIsCachedLikeGet(): void
	{
		$this->inner->queue(new THttpClientResponse(200, [], ''));
		$this->wrapped->download('HEAD', 'https://x.test/a');
		$this->wrapped->download('HEAD', 'https://x.test/a');
		$this->assertCount(1, $this->inner->calls);
	}

	public function testMethodCasingIsNormalised(): void
	{
		$this->inner->queue(new THttpClientResponse(200, [], 'X'));
		$this->wrapped->download('get', 'https://x.test/a');
		$this->wrapped->download('GET', 'https://x.test/a');
		$this->assertCount(1, $this->inner->calls);
	}

	public function testNonIdempotentVerbsBypassCache(): void
	{
		foreach (['POST', 'PUT', 'PATCH', 'DELETE'] as $verb) {
			$this->inner->queue(new THttpClientResponse(200, [], '{}'));
			$this->inner->queue(new THttpClientResponse(200, [], '{}'));
			$this->wrapped->download($verb, "https://x.test/{$verb}");
			$this->wrapped->download($verb, "https://x.test/{$verb}");
		}
		// 4 verbs × 2 calls = 8 inner calls, 0 cache writes
		$this->assertCount(8, $this->inner->calls);
		$this->assertSame(0, $this->cache->setCalls);
	}

	public function testUnsuccessfulGetsAreNotCached(): void
	{
		$this->inner->queue(new THttpClientResponse(404, [], ''));
		$this->inner->queue(new THttpClientResponse(404, [], ''));
		$this->wrapped->download('GET', 'https://x.test/a');
		$this->wrapped->download('GET', 'https://x.test/a');
		$this->assertCount(2, $this->inner->calls);
		$this->assertSame(0, $this->cache->setCalls);
	}

	public function testDifferentHeadersProduceDifferentCacheKeys(): void
	{
		$this->inner->queue(new THttpClientResponse(200, [], '{"who":"v1"}'));
		$this->inner->queue(new THttpClientResponse(200, [], '{"who":"v2"}'));

		$r1 = $this->wrapped->download('GET', 'https://x.test/a', ['Accept' => 'application/json']);
		$r2 = $this->wrapped->download('GET', 'https://x.test/a', ['Accept' => 'application/xml']);

		$this->assertNotSame($r1, $r2);
		$this->assertCount(2, $this->inner->calls);
	}

	public function testTtlPassedToCache(): void
	{
		$this->inner->queue(new THttpClientResponse(200, [], 'x'));
		$this->wrapped->download('GET', 'https://x.test/a');
		$ttls = array_values($this->cache->ttls);
		$this->assertSame(120, $ttls[0]);
	}

	public function testInvalidateRemovesEntry(): void
	{
		$this->inner->queue(new THttpClientResponse(200, [], 'first'));
		$this->inner->queue(new THttpClientResponse(200, [], 'second'));

		$r1 = $this->wrapped->download('GET', 'https://x.test/a');
		$this->wrapped->invalidate('GET', 'https://x.test/a');
		$r2 = $this->wrapped->download('GET', 'https://x.test/a');

		$this->assertSame('first', $r1->getBody());
		$this->assertSame('second', $r2->getBody());
		$this->assertSame(1, $this->cache->deleteCalls);
	}

	public function testCacheKeyIsStableAcrossHeaderOrder(): void
	{
		$exposed = new ExposedCachedHttpClient($this->inner, $this->cache);
		$k1 = $exposed->exposeCacheKey('GET', 'https://x.test/a', ['A' => '1', 'B' => '2']);
		$k2 = $exposed->exposeCacheKey('GET', 'https://x.test/a', ['B' => '2', 'A' => '1']);
		$this->assertSame($k1, $k2);
	}

	public function testCacheKeyChangesWithUrl(): void
	{
		$exposed = new ExposedCachedHttpClient($this->inner, $this->cache);
		$k1 = $exposed->exposeCacheKey('GET', 'https://x.test/a', []);
		$k2 = $exposed->exposeCacheKey('GET', 'https://x.test/b', []);
		$this->assertNotSame($k1, $k2);
	}

	public function testCacheKeyChangesWithVerb(): void
	{
		$exposed = new ExposedCachedHttpClient($this->inner, $this->cache);
		$k1 = $exposed->exposeCacheKey('GET', 'https://x.test/a', []);
		$k2 = $exposed->exposeCacheKey('HEAD', 'https://x.test/a', []);
		$this->assertNotSame($k1, $k2);
	}
}
