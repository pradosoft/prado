<?php

namespace Prado\Test\Unit\IO\Http;

use Prado\IO\Http\TRequest;
use Prado\IO\TResourceUri;
use Prado\IO\TStream;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;

class TRequestTest extends \PHPUnit\Framework\TestCase
{
	public function testImplementsPsr7()
	{
		$request = new TRequest('GET', 'https://example.com/');
		self::assertInstanceOf(RequestInterface::class, $request);
		self::assertInstanceOf(MessageInterface::class, $request);
	}

	public function testConstructorDefaults()
	{
		$request = new TRequest('get', 'https://example.com/a/b?x=1');
		self::assertSame('get', $request->getMethod());
		self::assertSame('1.1', $request->getProtocolVersion());
		self::assertSame('/a/b?x=1', $request->getRequestTarget());
		self::assertSame('', (string) $request->getBody());
		self::assertInstanceOf(TResourceUri::class, $request->getUri());
	}

	public function testHostHeaderFromUriIsFirst()
	{
		$request = new TRequest('GET', 'http://example.com:8080/', ['Accept' => 'text/html']);
		self::assertSame(['Host', 'Accept'], array_keys($request->getHeaders()));
		self::assertSame('example.com:8080', $request->getHeaderLine('host'));
	}

	public function testDefaultPortOmittedFromHost()
	{
		$request = new TRequest('GET', 'https://example.com:443/');
		self::assertSame('example.com', $request->getHeaderLine('Host'));
	}

	public function testExplicitHostHeaderKept()
	{
		$request = new TRequest('GET', 'http://example.com/', ['host' => 'other.test']);
		self::assertSame(['other.test'], $request->getHeader('Host'));
		self::assertSame(['host'], array_keys($request->getHeaders()));
	}

	public function testNoHostWithoutUriHost()
	{
		$request = new TRequest('GET', '/relative');
		self::assertFalse($request->hasHeader('Host'));
		self::assertSame('/relative', $request->getRequestTarget());
	}

	public function testRequestTargetForEmptyAndRelativePaths()
	{
		self::assertSame('/', (new TRequest('GET', 'http://example.com'))->getRequestTarget());
		self::assertSame('/?q=1', (new TRequest('GET', 'http://example.com?q=1'))->getRequestTarget());
		self::assertSame('/rel', (new TRequest('GET', 'rel'))->getRequestTarget());
	}

	public function testWithRequestTarget()
	{
		$request = new TRequest('OPTIONS', 'http://example.com/');
		$star = $request->withRequestTarget('*');
		self::assertNotSame($request, $star);
		self::assertSame('*', $star->getRequestTarget());
		self::assertSame('/', $request->getRequestTarget());
		$this->expectException(\InvalidArgumentException::class);
		$request->withRequestTarget('/a b');
	}

	public function testWithMethod()
	{
		$request = new TRequest('GET', 'http://example.com/');
		self::assertSame($request, $request->withMethod('GET'));
		$post = $request->withMethod('post');
		self::assertSame('post', $post->getMethod());
		self::assertSame('GET', $request->getMethod());
		$this->expectException(\InvalidArgumentException::class);
		$request->withMethod('BAD METHOD');
	}

	public function testInvalidMethodInConstructor()
	{
		$this->expectException(\InvalidArgumentException::class);
		new TRequest('', 'http://example.com/');
	}

	public function testWithUriUpdatesHost()
	{
		$request = new TRequest('GET', 'http://a.test/');
		$moved = $request->withUri(new TResourceUri('http://b.test:81/x'));
		self::assertSame('b.test:81', $moved->getHeaderLine('Host'));
		self::assertSame('/x', $moved->getRequestTarget());
		self::assertSame('a.test', $request->getHeaderLine('Host'));
	}

	public function testWithUriPreserveHost()
	{
		$request = new TRequest('GET', 'http://a.test/');
		$moved = $request->withUri(new TResourceUri('http://b.test/'), true);
		self::assertSame('a.test', $moved->getHeaderLine('Host'));

		$bare = new TRequest('GET', '/x');
		$withHost = $bare->withUri(new TResourceUri('http://c.test/'), true);
		self::assertSame('c.test', $withHost->getHeaderLine('Host'));
	}

	public function testWithUriWithoutHostKeepsHeader()
	{
		$request = new TRequest('GET', 'http://a.test/');
		$moved = $request->withUri(new TResourceUri('/only-path'));
		self::assertSame('a.test', $moved->getHeaderLine('Host'));
	}

	public function testWithUriSameInstance()
	{
		$request = new TRequest('GET', 'http://a.test/');
		self::assertSame($request, $request->withUri($request->getUri()));
	}

	public function testBodyFromString()
	{
		$request = new TRequest('POST', 'http://a.test/', [], 'payload');
		self::assertSame('payload', (string) $request->getBody());
	}

	public function testBodyFromStream()
	{
		$stream = TStream::fromString('abc');
		$request = new TRequest('POST', 'http://a.test/', [], $stream);
		self::assertSame($stream, $request->getBody());
	}

	public function testProtocolVersionInConstructor()
	{
		self::assertSame('2', (new TRequest('GET', 'http://a.test/', [], null, '2'))->getProtocolVersion());
		$this->expectException(\InvalidArgumentException::class);
		new TRequest('GET', 'http://a.test/', [], null, 'HTTP/1.1');
	}
}
