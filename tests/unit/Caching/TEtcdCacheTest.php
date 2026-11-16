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
use Prado\IO\Http\TRequest;
use Prado\IO\Http\TResponse;
use Prado\IO\HttpClient\THttpClient;
use Prado\IO\HttpClient\THttpNetworkException;
use Prado\Test\Unit\Harness\Caching\TTestEtcdCache;
use Prado\Test\Unit\Harness\IO\TTestHttpClient;
use Psr\Http\Message\RequestInterface;

/**
 * A cache on a PHP with no HTTP handler.
 */
class TTransportlessEtcdCache extends TTestEtcdCache
{
	public static function getIsAvailable(): bool
	{
		return false;
	}
}

/**
 * Unit tests for {@see TEtcdCache}, via the {@see TTestEtcdCache} harness. The etcd v2
 * protocol is exercised through a recording {@see TTestHttpClient}; no etcd server is needed.
 */
class TEtcdCacheTest extends \PHPUnit\Framework\TestCase
{
	private function newCache(): TTestEtcdCache
	{
		$cache = new TTestEtcdCache();
		$cache->setPrimaryCache(false);
		return $cache;
	}

	private function newClientCache(?TTestHttpClient &$client): TTestEtcdCache
	{
		$client = new TTestHttpClient();
		$cache = $this->newCache();
		$cache->setHttpClient($client);
		return $cache;
	}

	public function testIsAvailableAsksTheHttpClientForAHandler(): void
	{
		$this->assertSame(THttpClient::getHasAvailableHandler(), TEtcdCache::getIsAvailable());
	}

	public function testDefaultHttpClientIsLazyAndDoesNotFollowRedirects(): void
	{
		$cache = $this->newCache();
		$client = $cache->getHttpClient();
		$this->assertInstanceOf(THttpClient::class, $client);
		$this->assertFalse($client->getFollowRedirects());
		$this->assertSame($client, $cache->getHttpClient());
	}

	public function testHttpClientRoundTrip(): void
	{
		$cache = $this->newClientCache($client);
		$this->assertSame($client, $cache->getHttpClient());
	}

	public function testGetSerializedValueHit(): void
	{
		$cache = $this->newClientCache($client);
		$client->responses[] = new TResponse(200, [], '{"action":"get","node":{"key":"/pradocache/k","value":"abc"}}');
		$this->assertSame('abc', $cache->pubGetSerializedValue('k'));
		$this->assertCount(1, $client->calls);
		$this->assertSame('GET', $client->calls[0]['method']);
		$this->assertSame('http://localhost:2379/v2/keys/pradocache/k', $client->calls[0]['url']);
		$this->assertSame(['Host' => 'localhost:2379'], $client->calls[0]['headers']);
		$this->assertSame('', $client->calls[0]['body']);
		$this->assertInstanceOf(RequestInterface::class, $client->calls[0]['request']);
	}

	public function testGetSerializedValueMiss(): void
	{
		$cache = $this->newClientCache($client);
		$client->responses[] = new TResponse(404, [], '{"errorCode":100,"message":"Key not found"}');
		$this->assertFalse($cache->pubGetSerializedValue('k'));
	}

	public function testNonJsonResponseIsAnError(): void
	{
		$cache = $this->newClientCache($client);
		$client->responses[] = new TResponse(502, [], '<html>Bad Gateway</html>');
		$this->assertFalse($cache->pubGetSerializedValue('k'));
		$client->responses[] = new TResponse(502, [], '');
		$result = $cache->pubRequest('GET', 'pradocache/k');
		$this->assertSame(502, $result->errorCode);
	}

	public function testNonJsonResponseToAWriteIsAFailure(): void
	{
		$cache = $this->newClientCache($client);
		$client->responses[] = new TResponse(502, [], '<html>Bad Gateway</html>');
		$this->assertFalse($cache->pubSetSerializedValue('k', 'v', 0));
		$this->assertSame('PUT', $client->calls[0]['method']);
	}

	public function testSetSerializedValueSendsFormBody(): void
	{
		$cache = $this->newClientCache($client);
		$this->assertTrue($cache->pubSetSerializedValue('k', 'v w', 60));
		$this->assertTrue($cache->pubSetSerializedValue('k', 'v', 0));
		$this->assertSame('PUT', $client->calls[0]['method']);
		$this->assertSame('http://localhost:2379/v2/keys/pradocache/k', $client->calls[0]['url']);
		$this->assertSame(['Host' => 'localhost:2379', 'Content-Type' => 'application/x-www-form-urlencoded'], $client->calls[0]['headers']);
		$this->assertSame('value=v+w&ttl=60', $client->calls[0]['body']);
		$this->assertSame('value=v', $client->calls[1]['body']);
	}

	public function testSetSerializedValueFailure(): void
	{
		$cache = $this->newClientCache($client);
		$client->responses[] = new TResponse(500, [], '{"errorCode":300,"message":"Raft Internal Error"}');
		$this->assertFalse($cache->pubSetSerializedValue('k', 'v', 0));
	}

	public function testAddSerializedValueRequiresAbsentKey(): void
	{
		$cache = $this->newClientCache($client);
		$this->assertTrue($cache->pubAddSerializedValue('k', 'v', 30));
		$this->assertSame('value=v&prevExist=false&ttl=30', $client->calls[0]['body']);
		$client->responses[] = new TResponse(412, [], '{"errorCode":105,"message":"Key already exists"}');
		$this->assertFalse($cache->pubAddSerializedValue('k', 'v', 0));
		$this->assertSame('value=v&prevExist=false', $client->calls[1]['body']);
	}

	public function testDeleteValue(): void
	{
		$cache = $this->newClientCache($client);
		$client->responses[] = new TResponse(404, [], '{"errorCode":100}');
		$this->assertTrue($cache->pubDeleteValue('k'));
		$this->assertSame('DELETE', $client->calls[0]['method']);
		$this->assertSame('http://localhost:2379/v2/keys/pradocache/k', $client->calls[0]['url']);
		$this->assertSame('', $client->calls[0]['body']);
		$this->assertArrayNotHasKey('Content-Type', $client->calls[0]['headers']);
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
		$client->throw = $failure = new THttpNetworkException(new TRequest('GET', 'http://localhost:2379/v2/keys/pradocache/k'), 'httpclient_transport_error', 7, 'Connection refused');
		try {
			$cache->pubGetSerializedValue('k');
			$this->fail('expected exception');
		} catch (THttpNetworkException $e) {
			$this->assertSame($failure, $e);
		}
	}

	public function testSetThenGetRoundTripsThroughSerialization(): void
	{
		if (!TEtcdCache::getIsAvailable()) {
			$this->markTestSkipped('An HTTP handler is required to initialize TEtcdCache.');
		}
		$cache = $this->newClientCache($client);
		$cache->init(null);
		$this->assertTrue($cache->set('key', ['a' => 1]));
		parse_str($client->calls[0]['body'], $params);
		$client->responses[] = new TResponse(200, [], json_encode(['node' => ['value' => $params['value']]]));
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

	public function testInitThrowsWithoutAHandlerOrAnHttpClient(): void
	{
		$cache = new TTransportlessEtcdCache();
		$cache->setPrimaryCache(false);

		$this->expectException(TConfigurationException::class);
		$cache->init(null);
	}

	public function testInitAcceptsAConfiguredHttpClientWithoutAHandler(): void
	{
		$cache = new TTransportlessEtcdCache();
		$cache->setPrimaryCache(false);
		$cache->setHttpClient($client = new TTestHttpClient());

		$cache->init(null);

		$this->assertSame($client, $cache->getHttpClient());
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
			$this->markTestSkipped('An HTTP handler is required to initialize TEtcdCache.');
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
