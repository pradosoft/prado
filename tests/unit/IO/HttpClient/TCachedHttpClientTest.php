<?php

namespace Prado\Test\Unit\IO\HttpClient;

use Prado\Caching\TCache;
use Prado\IO\Http\TRequest;
use Prado\IO\Http\TResponse;
use Prado\IO\HttpClient\TCachedHttpClient;
use Prado\Test\Unit\Harness\IO\TTestHttpClient;
use Psr\Http\Client\ClientInterface;

/**
 * An in-memory cache that records each stored time to live.
 */
class TCachedHttpClientTestCache extends TCache
{
	public array $store = [];

	public array $ttls = [];

	protected function getValue($key)
	{
		return $this->store[$key] ?? false;
	}

	protected function setValue($key, $value, $expire)
	{
		$this->store[$key] = $value;
		$this->ttls[$key] = $expire;
		return true;
	}

	protected function addValue($key, $value, $expire)
	{
		return isset($this->store[$key]) ? false : $this->setValue($key, $value, $expire);
	}

	protected function deleteValue($key)
	{
		unset($this->store[$key]);
		return true;
	}

	protected function flushValues()
	{
		$this->store = [];
		return true;
	}
}

class TCachedHttpClientTest extends \PHPUnit\Framework\TestCase
{
	private TTestHttpClient $inner;

	private TCachedHttpClientTestCache $cache;

	private TCachedHttpClient $client;

	protected function setUp(): void
	{
		$this->inner = new TTestHttpClient();
		$this->cache = new TCachedHttpClientTestCache();
		$this->client = new TCachedHttpClient($this->inner, $this->cache, 300);
	}

	private function get(string $uri = 'http://a.test/r', array $headers = []): string
	{
		return (string) $this->client->sendRequest(new TRequest('GET', $uri, $headers))->getBody();
	}

	public function testImplementsPsr18AndExposesItsParts(): void
	{
		self::assertInstanceOf(ClientInterface::class, $this->client);
		self::assertSame($this->inner, $this->client->getInner());
		self::assertSame($this->cache, $this->client->getCache());
		self::assertSame(300, $this->client->getTtl());
	}

	public function testCachesASuccessfulGet(): void
	{
		$this->inner->responses[] = new TResponse(200, ['X-A' => 'a'], 'first');
		self::assertSame('first', $this->get());
		$cached = $this->client->sendRequest(new TRequest('GET', 'http://a.test/r'));
		self::assertSame('first', (string) $cached->getBody());
		self::assertSame(['a'], $cached->getHeader('X-A'));
		self::assertCount(1, $this->inner->calls);
		self::assertNotSame($cached->getBody(), $this->client->sendRequest(new TRequest('GET', 'http://a.test/r'))->getBody(), 'Each hit has its own body stream');
	}

	public function testOtherMethodsPassThrough(): void
	{
		$this->client->sendRequest(new TRequest('POST', 'http://a.test/r', [], 'x'));
		$this->client->sendRequest(new TRequest('POST', 'http://a.test/r', [], 'x'));
		self::assertCount(2, $this->inner->calls);
		self::assertSame([], $this->cache->store);
	}

	public function testErrorResponsesAreNotStored(): void
	{
		$this->inner->responses = [new TResponse(500, [], 'err'), new TResponse(200, [], 'ok')];
		self::assertSame('err', $this->get());
		self::assertSame('ok', $this->get());
	}

	public static function provideUncacheableResponses(): array
	{
		return [
			'no-store' => [['Cache-Control' => 'no-store']],
			'no-cache' => [['Cache-Control' => 'public, no-cache']],
			'max-age 0' => [['Cache-Control' => 'max-age=0']],
			'vary star' => [['Vary' => '*']],
		];
	}

	/**
	 * @dataProvider provideUncacheableResponses
	 */
	public function testUncacheableResponsesAreNotStored(array $headers): void
	{
		$this->inner->responses = [new TResponse(200, $headers, 'one'), new TResponse(200, [], 'two')];
		self::assertSame('one', $this->get());
		self::assertSame('two', $this->get());
	}

	public function testRequestNoCacheBypassesTheCache(): void
	{
		$this->inner->responses = [new TResponse(200, [], 'one'), new TResponse(200, [], 'two')];
		$this->get();
		self::assertSame('two', $this->get('http://a.test/r', ['Cache-Control' => 'no-cache']));
	}

	public function testMaxAgeShortensTheTtl(): void
	{
		$this->inner->responses[] = new TResponse(200, ['Cache-Control' => 'public, max-age=60'], 'x');
		$this->get();
		self::assertContains(60, $this->cache->ttls);
		$this->inner->responses[] = new TResponse(200, ['Cache-Control' => 'max-age=9999'], 'y');
		$this->get('http://a.test/other');
		self::assertContains(300, $this->cache->ttls);
	}

	public function testVaryHeadersSeparateVariants(): void
	{
		$this->inner->responses = [
			new TResponse(200, ['Vary' => 'Accept-Language'], 'english'),
			new TResponse(200, ['Vary' => 'Accept-Language'], 'french'),
		];
		self::assertSame('english', $this->get('http://a.test/r', ['Accept-Language' => 'en']));
		self::assertSame('french', $this->get('http://a.test/r', ['Accept-Language' => 'fr']));
		self::assertSame('english', $this->get('http://a.test/r', ['Accept-Language' => 'en']));
		self::assertCount(2, $this->inner->calls);
	}

	public function testCredentialsAlwaysSeparateVariants(): void
	{
		$this->inner->responses = [new TResponse(200, [], 'alice'), new TResponse(200, [], 'bob')];
		self::assertSame('alice', $this->get('http://a.test/me', ['Authorization' => 'Bearer a']));
		self::assertSame('bob', $this->get('http://a.test/me', ['Authorization' => 'Bearer b']));
		self::assertSame('alice', $this->get('http://a.test/me', ['Authorization' => 'Bearer a']));
	}

	public function testInvalidate(): void
	{
		$this->inner->responses = [new TResponse(200, [], 'one'), new TResponse(200, [], 'two')];
		$this->get();
		$this->client->invalidate('get', 'http://a.test/r');
		self::assertSame('two', $this->get());
	}
}
