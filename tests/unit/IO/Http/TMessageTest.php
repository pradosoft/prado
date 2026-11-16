<?php

namespace Prado\Test\Unit\IO\Http;

use Prado\IO\Http\TResponse;
use Prado\IO\TStream;

/**
 * Tests the header, protocol, and body handling TMessage gives its subclasses, through TResponse.
 */
class TMessageTest extends \PHPUnit\Framework\TestCase
{
	public function testHeadersKeepCaseAndLookUpCaseInsensitively()
	{
		$message = new TResponse(200, ['X-Foo' => 'a', 'content-type' => 'text/plain']);
		self::assertSame(['X-Foo' => ['a'], 'content-type' => ['text/plain']], $message->getHeaders());
		self::assertTrue($message->hasHeader('x-foo'));
		self::assertSame(['text/plain'], $message->getHeader('Content-Type'));
		self::assertSame([], $message->getHeader('Missing'));
		self::assertSame('', $message->getHeaderLine('Missing'));
	}

	public function testConstructorMergesSameNameInDifferentCase()
	{
		$message = new TResponse(200, ['Set-Cookie' => 'a=1', 'set-cookie' => ['b=2', 'c=3']]);
		self::assertSame(['Set-Cookie' => ['a=1', 'b=2', 'c=3']], $message->getHeaders());
	}

	public function testMultipleValuesAndHeaderLine()
	{
		$message = (new TResponse())->withHeader('Accept', ['a/b', 'c/d']);
		self::assertSame(['a/b', 'c/d'], $message->getHeader('accept'));
		self::assertSame('a/b, c/d', $message->getHeaderLine('ACCEPT'));
	}

	public function testWithHeaderReplacesAnyCase()
	{
		$message = new TResponse(200, ['x-foo' => 'a']);
		$changed = $message->withHeader('X-FOO', 'b');
		self::assertSame(['X-FOO' => ['b']], $changed->getHeaders());
		self::assertSame(['x-foo' => ['a']], $message->getHeaders());
	}

	public function testWithAddedHeader()
	{
		$message = new TResponse(200, ['Vary' => 'Accept']);
		$added = $message->withAddedHeader('vary', ['Cookie']);
		self::assertSame(['Vary' => ['Accept', 'Cookie']], $added->getHeaders());
		$new = $message->withAddedHeader('X-New', 'v');
		self::assertSame(['v'], $new->getHeader('x-new'));
		self::assertSame(['Accept'], $message->getHeader('Vary'));
	}

	public function testWithoutHeader()
	{
		$message = new TResponse(200, ['X-A' => '1', 'X-B' => '2']);
		self::assertSame($message, $message->withoutHeader('X-Missing'));
		$removed = $message->withoutHeader('x-a');
		self::assertSame(['X-B' => ['2']], $removed->getHeaders());
		self::assertTrue($message->hasHeader('X-A'));
	}

	public function testValuesAreTrimmedAndNumbersAccepted()
	{
		$message = (new TResponse())->withHeader('X-N', [" \tvalue\t ", 42, 1.5]);
		self::assertSame(['value', '42', '1.5'], $message->getHeader('X-N'));
	}

	public static function provideInvalidHeaderNames(): array
	{
		return [[''], ['Bad Name'], ["X-\r\nInjected"], ['X:Y'], ['Ünicode']];
	}

	/**
	 * @dataProvider provideInvalidHeaderNames
	 */
	public function testInvalidHeaderNameThrows(string $name)
	{
		$this->expectException(\InvalidArgumentException::class);
		(new TResponse())->withHeader($name, 'v');
	}

	public static function provideInvalidHeaderValues(): array
	{
		return [["a\r\nX-Injected: 1"], ["a\nb"], ["a\0b"], [[]], [[true]], [[null]]];
	}

	/**
	 * @dataProvider provideInvalidHeaderValues
	 */
	public function testInvalidHeaderValueThrows(mixed $value)
	{
		$this->expectException(\InvalidArgumentException::class);
		(new TResponse())->withHeader('X-A', $value);
	}

	public function testInvalidHeaderInConstructorThrows()
	{
		$this->expectException(\InvalidArgumentException::class);
		new TResponse(200, ['X-A' => "bad\r\nvalue"]);
	}

	public function testProtocolVersion()
	{
		$message = new TResponse();
		self::assertSame('1.1', $message->getProtocolVersion());
		self::assertSame($message, $message->withProtocolVersion('1.1'));
		$two = $message->withProtocolVersion('2');
		self::assertSame('2', $two->getProtocolVersion());
		self::assertSame('1.1', $message->getProtocolVersion());
		$this->expectException(\InvalidArgumentException::class);
		$message->withProtocolVersion('two');
	}

	public function testBodyDefaultsToEmptyStream()
	{
		$message = new TResponse();
		$body = $message->getBody();
		self::assertSame('', (string) $body);
		self::assertSame($body, $message->getBody());
	}

	public function testWithBody()
	{
		$message = new TResponse();
		$stream = TStream::fromString('x');
		$with = $message->withBody($stream);
		self::assertSame($stream, $with->getBody());
		self::assertSame($with, $with->withBody($stream));
		self::assertNotSame($stream, $message->getBody());
	}

	public function testCloneSharesBody()
	{
		$message = new TResponse(200, [], 'shared');
		$clone = $message->withHeader('X-A', '1');
		self::assertSame($message->getBody(), $clone->getBody());
	}
}
