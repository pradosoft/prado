<?php

/**
 * TEtcdCacheTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit\Caching;

use Prado\Caching\TEtcdCache;
use Prado\Exceptions\TConfigurationException;
use Prado\IO\HttpClient\THttpClient;
use Prado\IO\HttpClient\THttpClientException;
use Prado\IO\HttpClient\THttpClientResponse;
use Prado\Test\Unit\Harness\Caching\TTestEtcdCache;

/**
 * Records each etcd request and answers with queued responses.
 */
class TEtcdCacheTestHttpClient extends THttpClient
{
	public array $calls = [];

	/** @var THttpClientResponse[] */
	public array $responses = [];

	public ?\Throwable $throw = null;

	public function download(string $method, string $url, array $headers = [], ?string $body = null): THttpClientResponse
	{
		$this->calls[] = compact('method', 'url', 'headers', 'body');
		if ($this->throw !== null) {
			throw $this->throw;
		}
		return array_shift($this->responses) ?? new THttpClientResponse(200, [], '{"action":"set"}');
	}
}

/**
 * Unit tests for {@see TEtcdCache}, via the {@see TTestEtcdCache} harness. The etcd v2
 * protocol is exercised through a recording {@see THttpClient}; no etcd server is needed.
 */
class TEtcdCacheTest extends \PHPUnit\Framework\TestCase
{
	private function newCache(): TTestEtcdCache
	{
		$cache = new TTestEtcdCache();
		$cache->setPrimaryCache(false);
		return $cache;
	}

	private function newClientCache(?TEtcdCacheTestHttpClient &$client): TTestEtcdCache
	{
		$client = new TEtcdCacheTestHttpClient();
		$cache = $this->newCache();
		$cache->setDownloader($client);
		return $cache;
	}

	public function testIsAvailableReflectsTransport(): void
	{
		$expected = function_exists('curl_init') || filter_var(ini_get('allow_url_fopen'), FILTER_VALIDATE_BOOLEAN);
		$this->assertSame($expected, TEtcdCache::getIsAvailable());
	}

	public function testDefaultDownloaderIsLazyAndDoesNotFollowRedirects(): void
	{
		$cache = $this->newCache();
		$client = $cache->getDownloader();
		$this->assertInstanceOf(THttpClient::class, $client);
		$this->assertFalse($client->getFollowRedirects());
		$this->assertSame($client, $cache->getDownloader());
	}

	public function testDownloaderRoundTrip(): void
	{
		$cache = $this->newClientCache($client);
		$this->assertSame($client, $cache->getDownloader());
	}

	public function testGetSerializedValueHit(): void
	{
		$cache = $this->newClientCache($client);
		$client->responses[] = new THttpClientResponse(200, [], '{"action":"get","node":{"key":"/pradocache/k","value":"abc"}}');
		$this->assertSame('abc', $cache->pubGetSerializedValue('k'));
		$this->assertSame([[
			'method' => 'GET',
			'url' => 'http://localhost:2379/v2/keys/pradocache/k',
			'headers' => [],
			'body' => null,
		]], $client->calls);
	}

	public function testGetSerializedValueMiss(): void
	{
		$cache = $this->newClientCache($client);
		$client->responses[] = new THttpClientResponse(404, [], '{"errorCode":100,"message":"Key not found"}');
		$this->assertFalse($cache->pubGetSerializedValue('k'));
	}

	public function testNonJsonResponseIsAnError(): void
	{
		$cache = $this->newClientCache($client);
		$client->responses[] = new THttpClientResponse(502, [], '<html>Bad Gateway</html>');
		$this->assertFalse($cache->pubGetSerializedValue('k'));
		$client->responses[] = new THttpClientResponse(502, [], '');
		$result = $cache->pubRequest('GET', 'pradocache/k');
		$this->assertSame(502, $result->errorCode);
	}

	public function testSetSerializedValueSendsFormBody(): void
	{
		$cache = $this->newClientCache($client);
		$this->assertTrue($cache->pubSetSerializedValue('k', 'v w', 60));
		$this->assertTrue($cache->pubSetSerializedValue('k', 'v', 0));
		$this->assertSame('PUT', $client->calls[0]['method']);
		$this->assertSame('http://localhost:2379/v2/keys/pradocache/k', $client->calls[0]['url']);
		$this->assertSame(['Content-Type' => 'application/x-www-form-urlencoded'], $client->calls[0]['headers']);
		$this->assertSame('value=v+w&ttl=60', $client->calls[0]['body']);
		$this->assertSame('value=v', $client->calls[1]['body']);
	}

	public function testSetSerializedValueFailure(): void
	{
		$cache = $this->newClientCache($client);
		$client->responses[] = new THttpClientResponse(500, [], '{"errorCode":300,"message":"Raft Internal Error"}');
		$this->assertFalse($cache->pubSetSerializedValue('k', 'v', 0));
	}

	public function testAddSerializedValueRequiresAbsentKey(): void
	{
		$cache = $this->newClientCache($client);
		$this->assertTrue($cache->pubAddSerializedValue('k', 'v', 30));
		$this->assertSame('value=v&prevExist=false&ttl=30', $client->calls[0]['body']);
		$client->responses[] = new THttpClientResponse(412, [], '{"errorCode":105,"message":"Key already exists"}');
		$this->assertFalse($cache->pubAddSerializedValue('k', 'v', 0));
		$this->assertSame('value=v&prevExist=false', $client->calls[1]['body']);
	}

	public function testDeleteValue(): void
	{
		$cache = $this->newClientCache($client);
		$client->responses[] = new THttpClientResponse(404, [], '{"errorCode":100}');
		$this->assertTrue($cache->pubDeleteValue('k'));
		$this->assertSame('DELETE', $client->calls[0]['method']);
		$this->assertSame('http://localhost:2379/v2/keys/pradocache/k', $client->calls[0]['url']);
		$this->assertNull($client->calls[0]['body']);
	}

	public function testFlushDeletesDirectoryRecursively(): void
	{
		$cache = $this->newClientCache($client);
		$cache->setHost('etcd.example.test');
		$cache->setPort(12379);
		$cache->setDir('myapp');
		$this->assertTrue($cache->flush());
		$this->assertSame('DELETE', $client->calls[0]['method']);
		$this->assertSame('http://etcd.example.test:12379/v2/keys/myapp?recursive=true', $client->calls[0]['url']);
	}

	public function testTransportFailurePropagates(): void
	{
		$cache = $this->newClientCache($client);
		$client->throw = new THttpClientException('httpclient_transport_error', 7, 'Connection refused');
		$this->expectException(THttpClientException::class);
		$cache->pubGetSerializedValue('k');
	}

	public function testSetThenGetRoundTripsThroughSerialization(): void
	{
		if (!TEtcdCache::getIsAvailable()) {
			$this->markTestSkipped('An HTTP transport is required to initialize TEtcdCache.');
		}
		$cache = $this->newClientCache($client);
		$cache->init(null);
		$this->assertTrue($cache->set('key', ['a' => 1]));
		parse_str($client->calls[0]['body'], $params);
		$client->responses[] = new THttpClientResponse(200, [], json_encode(['node' => ['value' => $params['value']]]));
		$this->assertSame(['a' => 1], $cache->get('key'));
		$this->assertSame($client->calls[0]['url'], $client->calls[1]['url']);
	}

	public function testPropertyDefaults(): void
	{
		$cache = $this->newCache();
		$this->assertSame('localhost', $cache->getHost());
		$this->assertSame(2379, $cache->getPort());
		$this->assertSame('pradocache', $cache->getDir());
	}

	public function testPropertyRoundTrip(): void
	{
		$cache = $this->newCache();
		$cache->setHost('etcd.example.test');
		$cache->setPort(12379);
		$cache->setDir('myapp');
		$this->assertSame('etcd.example.test', $cache->getHost());
		$this->assertSame(12379, $cache->getPort());
		$this->assertSame('myapp', $cache->getDir());
	}

	public function testInitThrowsWhenCurlUnavailable(): void
	{
		if (TEtcdCache::getIsAvailable()) {
			$this->markTestSkipped('An HTTP transport is present; cannot exercise the unavailable path.');
		}
		$this->expectException(TConfigurationException::class);
		$this->newCache()->init(null);
	}

	public function testFakeClockSeam(): void
	{
		$cache = $this->newCache();
		$cache->fakeNow = 4242;
		$this->assertSame(4242, $cache->pubTime());
	}

	/**
	 * @dataProvider frozenSetterProvider
	 */
	public function testConfigPropertiesCannotChangeAfterInit(string $setter, mixed $value): void
	{
		if (!TEtcdCache::getIsAvailable()) {
			$this->markTestSkipped('An HTTP transport is required to initialize TEtcdCache.');
		}
		$cache = $this->newCache();
		$cache->init(null);
		$this->expectException(\Prado\Exceptions\TInvalidOperationException::class);
		$cache->$setter($value);
	}

	public static function frozenSetterProvider(): array
	{
		return [
			'Host' => ['setHost', 'other.host'],
			'Port' => ['setPort', 9999],
			'Dir'  => ['setDir', 'otherdir'],
		];
	}
}
