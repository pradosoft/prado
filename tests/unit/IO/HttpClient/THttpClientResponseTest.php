<?php

use Prado\IO\HttpClient\THttpClientResponse;

/**
 * Tests for THttpClientResponse.
 */
class THttpClientResponseTest extends PHPUnit\Framework\TestCase
{
	public function testBasicAccessors(): void
	{
		$r = new THttpClientResponse(200, ['Content-Type' => 'application/json'], '{"a":1}');
		$this->assertSame(200, $r->getStatusCode());
		$this->assertSame('{"a":1}', $r->getBody());
		$this->assertSame(['Content-Type' => 'application/json'], $r->getHeaders());
	}

	public function testHeaderLookupIsCaseInsensitive(): void
	{
		$r = new THttpClientResponse(200, ['content-type' => 'application/json', 'X-FOO' => 'bar']);
		// Headers are canonicalised on input
		$this->assertSame('application/json', $r->getHeader('Content-Type'));
		$this->assertSame('application/json', $r->getHeader('content-type'));
		$this->assertSame('application/json', $r->getHeader('CONTENT-TYPE'));
		$this->assertSame('bar', $r->getHeader('X-Foo'));
	}

	public function testGetHeaderReturnsDefaultWhenAbsent(): void
	{
		$r = new THttpClientResponse(200, []);
		$this->assertNull($r->getHeader('Missing'));
		$this->assertSame('fallback', $r->getHeader('Missing', 'fallback'));
	}

	public function testGetJsonDecodesBody(): void
	{
		$r = new THttpClientResponse(200, [], '{"name":"Alice","age":30}');
		$this->assertSame(['name' => 'Alice', 'age' => 30], $r->getJson());
	}

	public function testGetJsonReturnsDefaultOnEmptyBody(): void
	{
		$r = new THttpClientResponse(204, [], '');
		$this->assertNull($r->getJson());
		$this->assertSame('def', $r->getJson(true, 'def'));
	}

	public function testGetJsonReturnsDefaultOnInvalidJson(): void
	{
		$r = new THttpClientResponse(200, [], 'not-json');
		$this->assertNull($r->getJson());
	}

	public function testStatusClassifiers(): void
	{
		$this->assertTrue((new THttpClientResponse(200))->isSuccess());
		$this->assertTrue((new THttpClientResponse(204))->isSuccess());
		$this->assertFalse((new THttpClientResponse(300))->isSuccess());

		$this->assertTrue((new THttpClientResponse(301))->isRedirect());
		$this->assertTrue((new THttpClientResponse(404))->isClientError());
		$this->assertTrue((new THttpClientResponse(500))->isServerError());
		$this->assertFalse((new THttpClientResponse(200))->isServerError());
	}
}
