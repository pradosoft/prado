<?php

use Prado\IO\HttpClient\TCurlHttpClient;
use Prado\IO\HttpClient\THttpClientException;

require_once __DIR__ . '/HttpServerTestTrait.php';

/**
 * Tests for TCurlHttpClient.
 *
 * Run against PHP's built-in test server. The whole class is skipped when the
 * cURL extension is unavailable, or when the test server fails to start.
 *
 * @requires extension curl
 */
class TCurlHttpClientTest extends PHPUnit\Framework\TestCase
{
	use HttpServerTestTrait;

	public static function setUpBeforeClass(): void
	{
		self::startHttpServer();
	}

	public static function tearDownAfterClass(): void
	{
		self::stopHttpServer();
	}

	public function testGetReturnsBodyAndStatus(): void
	{
		$this->requireRunningHttpServer();
		$client = new TCurlHttpClient();
		$response = $client->download('GET', self::url('/'));
		$this->assertSame(200, $response->getStatusCode());
		$this->assertSame('hello', $response->getBody());
	}

	public function testPostSendsJsonBody(): void
	{
		$this->requireRunningHttpServer();
		$client = new TCurlHttpClient();
		$response = $client->download(
			'POST',
			self::url('/echo'),
			['Content-Type' => 'application/json'],
			'{"name":"Alice"}'
		);
		$body = $response->getJson();
		$this->assertSame('POST', $body['method']);
		$this->assertSame('{"name":"Alice"}', $body['body']);
	}

	public function testPutAndDeleteVerbs(): void
	{
		$this->requireRunningHttpServer();
		$client = new TCurlHttpClient();

		$put = $client->download('PUT', self::url('/echo'), [], 'put-body');
		$this->assertSame('PUT', $put->getJson()['method']);
		$this->assertSame('put-body', $put->getJson()['body']);

		$del = $client->download('DELETE', self::url('/echo'));
		$this->assertSame('DELETE', $del->getJson()['method']);
	}

	public function testHeadSetsNoBody(): void
	{
		$this->requireRunningHttpServer();
		$client = new TCurlHttpClient();
		$response = $client->download('HEAD', self::url('/'));
		$this->assertSame(200, $response->getStatusCode());
		$this->assertSame('', $response->getBody());
	}

	public function testNon2xxStatusIsReturnedNotThrown(): void
	{
		$this->requireRunningHttpServer();
		$client = new TCurlHttpClient();
		$response = $client->download('GET', self::url('/status/500'));
		$this->assertSame(500, $response->getStatusCode());
		$this->assertTrue($response->isServerError());
	}

	public function testResponseHeadersAreParsed(): void
	{
		$this->requireRunningHttpServer();
		$client = new TCurlHttpClient();
		$response = $client->download('GET', self::url('/headers'));
		$this->assertSame('alpha', $response->getHeader('X-Custom-A'));
		$this->assertSame('bravo', $response->getHeader('X-Custom-B'));
	}

	public function testFollowsRedirectByDefault(): void
	{
		$this->requireRunningHttpServer();
		$client = new TCurlHttpClient();
		$response = $client->download('GET', self::url('/redirect'));
		$this->assertSame(200, $response->getStatusCode());
	}

	public function testFollowRedirectsOffReturns3xx(): void
	{
		$this->requireRunningHttpServer();
		$client = new TCurlHttpClient();
		$client->setFollowRedirects(false);
		$response = $client->download('GET', self::url('/redirect'));
		$this->assertSame(302, $response->getStatusCode());
	}

	public function testTransportFailureThrows(): void
	{
		$this->expectException(THttpClientException::class);
		$client = new TCurlHttpClient();
		$client->setTimeout(1);
		$client->download('GET', 'http://127.0.0.1:1/should-fail');
	}

	public function testTimeoutHonoured(): void
	{
		$this->requireRunningHttpServer();
		$client = new TCurlHttpClient();
		$client->setTimeout(1);
		try {
			$client->download('GET', self::url('/slow?delay=2'));
			$this->fail('expected timeout exception');
		} catch (THttpClientException $e) {
			$this->assertTrue(true);
		}
	}
}
