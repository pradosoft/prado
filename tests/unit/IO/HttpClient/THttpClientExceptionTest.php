<?php

namespace Prado\Test\Unit\IO\HttpClient;

use Prado\Exceptions\TNetworkException;
use Prado\IO\Http\TRequest;
use Prado\IO\Http\TResponse;
use Prado\IO\HttpClient\THttpClientException;
use Prado\IO\HttpClient\THttpNetworkException;
use Prado\IO\HttpClient\THttpRequestException;
use Prado\IO\HttpClient\THttpResponseException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Client\RequestExceptionInterface;

class THttpClientExceptionTest extends \PHPUnit\Framework\TestCase
{
	public function testBaseIsAPsr18ClientException(): void
	{
		$e = new THttpClientException('httpclient_transport_required');
		self::assertInstanceOf(ClientExceptionInterface::class, $e);
		self::assertInstanceOf(TNetworkException::class, $e);
		self::assertNull($e->getRequest());
		self::assertStringContainsString('No HTTP handler is available', $e->getMessage());
	}

	public function testNetworkException(): void
	{
		$request = new TRequest('GET', 'http://a.test/');
		$previous = new \RuntimeException('inner');
		$e = new THttpNetworkException($request, 'httpclient_transport_error', 7, 'Connection refused', $previous);
		self::assertInstanceOf(NetworkExceptionInterface::class, $e);
		self::assertInstanceOf(THttpClientException::class, $e);
		self::assertNotInstanceOf(RequestExceptionInterface::class, $e);
		self::assertSame($request, $e->getRequest());
		self::assertSame($previous, $e->getPrevious());
		self::assertStringContainsString('(code 7): Connection refused', $e->getMessage());
	}

	public function testRequestException(): void
	{
		$request = new TRequest('GET', 'ftp://a.test/');
		$e = new THttpRequestException($request, 'httpclient_scheme_unsupported', 'ftp');
		self::assertInstanceOf(RequestExceptionInterface::class, $e);
		self::assertNotInstanceOf(NetworkExceptionInterface::class, $e);
		self::assertSame($request, $e->getRequest());
		self::assertStringContainsString("'ftp'", $e->getMessage());
	}

	public function testResponseException(): void
	{
		$request = new TRequest('GET', 'http://a.test/');
		$response = new TResponse(503, [], 'down');
		$e = new THttpResponseException($request, $response);
		self::assertInstanceOf(THttpRequestException::class, $e);
		self::assertSame($response, $e->getResponse());
		self::assertSame($request, $e->getRequest());
		self::assertSame(503, $e->getStatusCode());
		self::assertSame(503, $e->getCode());
		self::assertStringContainsString('503 Service Unavailable', $e->getMessage());
	}
}
