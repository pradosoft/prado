<?php

use Prado\IO\HttpClient\THttpClientException;
use Prado\IO\HttpClient\THttpClientResponse;

/**
 * Tests for THttpClientException.
 */
class THttpClientExceptionTest extends PHPUnit\Framework\TestCase
{
	public function testTransportFailureHasNullResponseAndZeroStatus(): void
	{
		$e = new THttpClientException('httpclient_transport_error', 7, 'DNS failed');
		$this->assertNull($e->getResponse());
		$this->assertSame(0, $e->getStatusCode());
	}

	public function testFromResponseCarriesStatusAndResponse(): void
	{
		$response = new THttpClientResponse(404, ['Content-Type' => 'text/plain'], 'not found');
		$e = THttpClientException::fromResponse($response);
		$this->assertSame(404, $e->getStatusCode());
		$this->assertSame(404, $e->getCode());
		$this->assertSame($response, $e->getResponse());
	}

	public function testFromResponseFor500(): void
	{
		$response = new THttpClientResponse(500, [], '{"error":"boom"}');
		$e = THttpClientException::fromResponse($response);
		$this->assertSame(500, $e->getStatusCode());
		$this->assertSame(['error' => 'boom'], $e->getResponse()->getJson());
	}

	public function testExceptionIsThrowable(): void
	{
		$this->expectException(THttpClientException::class);
		throw new THttpClientException('httpclient_transport_error', 1, 'oops');
	}
}
