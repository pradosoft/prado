<?php

namespace Prado\Test\Unit\IO\Http;

use Prado\IO\Http\THttpFactory;
use Prado\IO\Http\TRequest;
use Prado\IO\Http\TResponse;
use Prado\IO\TResourceUri;
use Prado\IO\TStream;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;

class THttpFactoryTestRequest extends TRequest
{
}

class THttpFactoryTestResponse extends TResponse
{
}

class THttpFactoryTestUri extends TResourceUri
{
}

class THttpFactoryTestFactory extends THttpFactory
{
	public const REQUEST_CLASS = THttpFactoryTestRequest::class;
	public const RESPONSE_CLASS = THttpFactoryTestResponse::class;
	public const URI_CLASS = THttpFactoryTestUri::class;
}

class THttpFactoryTest extends \PHPUnit\Framework\TestCase
{
	public function testImplementsPsr17()
	{
		$factory = new THttpFactory();
		self::assertInstanceOf(RequestFactoryInterface::class, $factory);
		self::assertInstanceOf(ResponseFactoryInterface::class, $factory);
		self::assertInstanceOf(UriFactoryInterface::class, $factory);
		self::assertInstanceOf(StreamFactoryInterface::class, $factory);
	}

	public function testCreateRequest()
	{
		$factory = new THttpFactory();
		$request = $factory->createRequest('PUT', 'https://example.com/x');
		self::assertInstanceOf(TRequest::class, $request);
		self::assertSame('PUT', $request->getMethod());
		self::assertSame('https://example.com/x', (string) $request->getUri());
		self::assertSame('', (string) $request->getBody());

		$uri = new TResourceUri('http://a.test/');
		self::assertSame($uri, $factory->createRequest('GET', $uri)->getUri());
	}

	public function testCreateResponse()
	{
		$factory = new THttpFactory();
		$response = $factory->createResponse();
		self::assertInstanceOf(TResponse::class, $response);
		self::assertSame(200, $response->getStatusCode());
		self::assertSame('OK', $response->getReasonPhrase());
		self::assertSame('Teapot', $factory->createResponse(418, 'Teapot')->getReasonPhrase());
		self::assertSame('Not Found', $factory->createResponse(404)->getReasonPhrase());
	}

	public function testCreateUri()
	{
		$factory = new THttpFactory();
		$uri = $factory->createUri('HTTP://Example.COM/a');
		self::assertInstanceOf(TResourceUri::class, $uri);
		self::assertSame('http://example.com/a', (string) $uri);
		self::assertSame('', (string) $factory->createUri());
	}

	public function testCreateStream()
	{
		$stream = (new THttpFactory())->createStream('abc');
		self::assertInstanceOf(TStream::class, $stream);
		self::assertSame('abc', (string) $stream);
	}

	public function testSubclassOverridesClasses()
	{
		$factory = new THttpFactoryTestFactory();

		$request = $factory->createRequest('POST', 'https://example.com/a');
		self::assertInstanceOf(THttpFactoryTestRequest::class, $request);
		self::assertSame('POST', $request->getMethod());

		$response = $factory->createResponse(404);
		self::assertInstanceOf(THttpFactoryTestResponse::class, $response);
		self::assertSame('Not Found', $response->getReasonPhrase());

		$uri = $factory->createUri('https://example.com/b');
		self::assertInstanceOf(THttpFactoryTestUri::class, $uri);
		self::assertSame('https://example.com/b', (string) $uri);
	}
}
