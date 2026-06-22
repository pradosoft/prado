<?php

use Prado\IO\HttpClient\TCachedHttpClient;
use Prado\IO\HttpClient\TCurlHttpClient;
use Prado\IO\HttpClient\TFopenHttpClient;
use Prado\IO\HttpClient\THttpClient;
use Prado\IO\HttpClient\THttpClientResponse;

/**
 * Test stub exposing THttpClient's protected utility methods.
 */
class TestableHttpClient extends THttpClient
{
	public ?THttpClientResponse $next = null;

	public function download(string $method, string $url, array $headers = [], ?string $body = null): THttpClientResponse
	{
		return $this->next ?? new THttpClientResponse(200, [], '');
	}

	public function exposeParseStatusLine(string $line): int
	{
		return $this->parseStatusLine($line);
	}

	public function exposeFormatHeaderLines(array $headers): array
	{
		return $this->formatHeaderLines($headers);
	}
}

/**
 * Tests for THttpClient (abstract base) — factory, utilities, properties.
 */
class THttpClientTest extends PHPUnit\Framework\TestCase
{
	private TestableHttpClient $client;

	protected function setUp(): void
	{
		$this->client = new TestableHttpClient();
	}

	// ── Static factory ────────────────────────────────────────────────────────

	public function testCreatePicksCurlWhenAvailable(): void
	{
		$d = THttpClient::create();
		if (function_exists('curl_init')) {
			$this->assertInstanceOf(TCurlHttpClient::class, $d);
		} else {
			$this->assertInstanceOf(TFopenHttpClient::class, $d);
		}
	}

	public function testNewCacheWrapperWrapsAnyDownloader(): void
	{
		$inner = new TestableHttpClient();
		$cache = $this->createMock(\Prado\Caching\ICache::class);
		$wrapped = THttpClient::newCacheWrapper($inner, $cache, 90);
		$this->assertInstanceOf(TCachedHttpClient::class, $wrapped);
		$this->assertSame($inner, $wrapped->getInner());
		$this->assertSame($cache, $wrapped->getCache());
		$this->assertSame(90, $wrapped->getTtl());
	}

	// ── parseStatusLine ───────────────────────────────────────────────────────

	public function testParseStatusLineReadsHttp11(): void
	{
		$this->assertSame(200, $this->client->exposeParseStatusLine('HTTP/1.1 200 OK'));
	}

	public function testParseStatusLineReadsHttp2(): void
	{
		$this->assertSame(404, $this->client->exposeParseStatusLine('HTTP/2 404 Not Found'));
	}

	public function testParseStatusLineReadsHttp10(): void
	{
		$this->assertSame(301, $this->client->exposeParseStatusLine('HTTP/1.0 301 Moved Permanently'));
	}

	public function testParseStatusLineReturnsZeroForGarbage(): void
	{
		$this->assertSame(0, $this->client->exposeParseStatusLine('not a status line'));
		$this->assertSame(0, $this->client->exposeParseStatusLine(''));
		$this->assertSame(0, $this->client->exposeParseStatusLine('HTTP/1.1 abc OK')); // non-numeric
	}

	// ── formatHeaderLines ─────────────────────────────────────────────────────

	public function testFormatHeaderLinesProducesNameColonValue(): void
	{
		$lines = $this->client->exposeFormatHeaderLines([
			'Accept' => 'application/json',
			'Authorization' => 'Bearer xyz',
		]);
		$this->assertSame(
			['Accept: application/json', 'Authorization: Bearer xyz'],
			$lines
		);
	}

	public function testFormatHeaderLinesEmptyArray(): void
	{
		$this->assertSame([], $this->client->exposeFormatHeaderLines([]));
	}

	public function testFormatHeaderLinesStripsCrlfFromValues(): void
	{
		// A request-splitting payload in a value collapses onto one line with the CR/LF removed.
		$lines = $this->client->exposeFormatHeaderLines([
			'Content-Type' => "application/json\r\nX-Injected: evil",
		]);
		$this->assertSame(['Content-Type: application/jsonX-Injected: evil'], $lines);
	}

	public function testFormatHeaderLinesStripsCrlfFromNames(): void
	{
		$lines = $this->client->exposeFormatHeaderLines([
			"X-Test\r\nX-Injected" => 'value',
		]);
		$this->assertSame(['X-TestX-Injected: value'], $lines);
	}

	public function testFormatHeaderLinesStripsBareCrAndLf(): void
	{
		$lines = $this->client->exposeFormatHeaderLines([
			'A' => "1\r2",   // a bare carriage return
			'B' => "3\n4",   // a bare line feed
		]);
		$this->assertSame(['A: 12', 'B: 34'], $lines);
	}

	public function testFormatHeaderLinesNeverEmitsCrOrLf(): void
	{
		// No produced line may carry a CR or LF, so a second header cannot reach the wire.
		$lines = $this->client->exposeFormatHeaderLines([
			'Set-Cookie' => "a=1\r\nSet-Cookie: b=2",
			"Bad\nName" => "x\ry",
			'Normal' => 'ok',
		]);
		$this->assertCount(3, $lines, 'The split attempt does not add a header line.');
		foreach ($lines as $line) {
			$this->assertDoesNotMatchRegularExpression('/[\r\n]/', $line, 'A flattened header line must not contain CR or LF.');
		}
		$this->assertContains('Set-Cookie: a=1Set-Cookie: b=2', $lines, 'The injected header folds into the original line.');
	}

	// ── Property accessors ────────────────────────────────────────────────────

	public function testTimeoutAccessor(): void
	{
		$this->assertSame(30, $this->client->getTimeout());
		$this->client->setTimeout(60);
		$this->assertSame(60, $this->client->getTimeout());
		$this->client->setTimeout('15'); // string coercion
		$this->assertSame(15, $this->client->getTimeout());
	}

	public function testFollowRedirectsAccessor(): void
	{
		$this->assertTrue($this->client->getFollowRedirects());
		$this->client->setFollowRedirects(false);
		$this->assertFalse($this->client->getFollowRedirects());
		$this->client->setFollowRedirects('true');
		$this->assertTrue($this->client->getFollowRedirects());
	}

	public function testMaxRedirectsAccessor(): void
	{
		$this->assertSame(5, $this->client->getMaxRedirects());
		$this->client->setMaxRedirects(10);
		$this->assertSame(10, $this->client->getMaxRedirects());
		$this->client->setMaxRedirects('3');
		$this->assertSame(3, $this->client->getMaxRedirects());
	}
}
