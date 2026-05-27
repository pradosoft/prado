<?php

use Prado\IO\HttpClient\TFopenHttpClient;
use Prado\IO\HttpClient\THttpClientException;

require_once __DIR__ . '/HttpServerTestTrait.php';

class ExposedFopenHttpClient extends TFopenHttpClient
{
	public function exposeParseResponseHeaders(array $raw): array
	{
		return $this->parseResponseHeaders($raw);
	}
}

/**
 * Tests for TFopenHttpClient.
 *
 * Pure-unit tests use the header-parser seam directly. End-to-end tests run
 * against PHP's built-in test server; they are skipped automatically if the
 * server fails to start.
 */
class TFopenHttpClientTest extends PHPUnit\Framework\TestCase
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

	// ── parseResponseHeaders unit tests ───────────────────────────────────────

	public function testParseResponseHeadersSimple(): void
	{
		$client = new ExposedFopenHttpClient();
		[$status, $headers] = $client->exposeParseResponseHeaders([
			'HTTP/1.1 200 OK',
			'Content-Type: text/html',
			'X-Powered-By: Prado',
		]);
		$this->assertSame(200, $status);
		$this->assertSame('text/html', $headers['Content-Type']);
		$this->assertSame('Prado', $headers['X-Powered-By']);
	}

	public function testParseResponseHeadersKeepsOnlyFinalBlockAfterRedirect(): void
	{
		$client = new ExposedFopenHttpClient();
		[$status, $headers] = $client->exposeParseResponseHeaders([
			'HTTP/1.1 302 Found',
			'Location: /echo',
			'HTTP/1.1 200 OK',
			'Content-Type: application/json',
		]);
		$this->assertSame(200, $status);
		$this->assertSame(['Content-Type' => 'application/json'], $headers);
	}

	public function testParseResponseHeadersMergesDuplicates(): void
	{
		$client = new ExposedFopenHttpClient();
		[, $headers] = $client->exposeParseResponseHeaders([
			'HTTP/1.1 200 OK',
			'Set-Cookie: a=1',
			'Set-Cookie: b=2',
		]);
		$this->assertSame('a=1, b=2', $headers['Set-Cookie']);
	}

	public function testParseResponseHeadersIgnoresMalformedLines(): void
	{
		$client = new ExposedFopenHttpClient();
		[$status, $headers] = $client->exposeParseResponseHeaders([
			'HTTP/1.1 200 OK',
			'malformed-no-colon',
			'Good-Header: yes',
		]);
		$this->assertSame(200, $status);
		$this->assertSame(['Good-Header' => 'yes'], $headers);
	}

	// ── Integration tests (need the built-in server) ─────────────────────────

	public function testGetReturnsBodyAndStatus(): void
	{
		$this->requireRunningHttpServer();
		$client = new TFopenHttpClient();
		$response = $client->download('GET', self::url('/'));
		$this->assertSame(200, $response->getStatusCode());
		$this->assertSame('hello', $response->getBody());
	}

	public function testGetEchoEndpointReturnsRequestDetails(): void
	{
		$this->requireRunningHttpServer();
		$client = new TFopenHttpClient();
		$response = $client->download('GET', self::url('/echo'));
		$body = $response->getJson();
		$this->assertSame('GET', $body['method']);
		$this->assertSame('/echo', $body['path']);
	}

	public function testPostSendsBody(): void
	{
		$this->requireRunningHttpServer();
		$client = new TFopenHttpClient();
		$response = $client->download('POST', self::url('/echo'), ['Content-Type' => 'text/plain'], 'hello world');
		$body = $response->getJson();
		$this->assertSame('POST', $body['method']);
		$this->assertSame('hello world', $body['body']);
	}

	public function testNon2xxStatusIsReturnedNotThrown(): void
	{
		$this->requireRunningHttpServer();
		$client = new TFopenHttpClient();
		$response = $client->download('GET', self::url('/status/404'));
		$this->assertSame(404, $response->getStatusCode());
		$this->assertSame('status 404', $response->getBody());
	}

	public function testFollowsRedirectByDefault(): void
	{
		$this->requireRunningHttpServer();
		$client = new TFopenHttpClient();
		$response = $client->download('GET', self::url('/redirect'));
		// After following, we land at /echo (200)
		$this->assertSame(200, $response->getStatusCode());
		$body = $response->getJson();
		$this->assertSame('/echo', $body['path']);
	}

	public function testFollowRedirectsOffReturns3xx(): void
	{
		$this->requireRunningHttpServer();
		$client = new TFopenHttpClient();
		$client->setFollowRedirects(false);
		$response = $client->download('GET', self::url('/redirect'));
		$this->assertTrue($response->isRedirect());
		$this->assertSame(302, $response->getStatusCode());
	}

	public function testTransportFailureThrows(): void
	{
		$this->requireRunningHttpServer();
		$client = new TFopenHttpClient();
		// Random unused high port — refused connection
		$this->expectException(THttpClientException::class);
		$client->download('GET', 'http://127.0.0.1:1/should-fail');
	}

	public function testCustomHeadersAreSent(): void
	{
		$this->requireRunningHttpServer();
		$client = new TFopenHttpClient();
		$response = $client->download('GET', self::url('/echo'), ['X-Custom-Token' => 'abc123']);
		$body = $response->getJson();
		// Header keys arrive in capitalized form via getallheaders()
		$found = false;
		foreach ($body['headers'] as $name => $value) {
			if (strcasecmp($name, 'X-Custom-Token') === 0 && $value === 'abc123') {
				$found = true;
				break;
			}
		}
		$this->assertTrue($found, 'X-Custom-Token header was not received by the server.');
	}
}
