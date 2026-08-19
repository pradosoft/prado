<?php

use Prado\Exceptions\TInvalidDataValueException;
use Prado\IO\Stream\TMultipartStream;

class TMultipartStreamTest extends PHPUnit\Framework\TestCase
{
	public function testBuildsAFieldBody()
	{
		$body = new TMultipartStream([['name' => 'title', 'contents' => 'Sunset']], 'B');
		$expected = "--B\r\n"
			. "Content-Disposition: form-data; name=\"title\"\r\n"
			. "Content-Length: 6\r\n"
			. "\r\n"
			. "Sunset\r\n"
			. "--B--\r\n";
		self::assertSame($expected, (string) $body);
		self::assertSame('B', $body->getBoundary());
	}

	public function testBuildsAFileUploadWithFilenameAndHeaders()
	{
		$body = new TMultipartStream([
			['name' => 'photo', 'contents' => 'DATA', 'filename' => 'a.jpg', 'headers' => ['Content-Type' => 'image/jpeg']],
		], 'B');
		$expected = "--B\r\n"
			. "Content-Disposition: form-data; name=\"photo\"; filename=\"a.jpg\"\r\n"
			. "Content-Type: image/jpeg\r\n"
			. "Content-Length: 4\r\n"
			. "\r\n"
			. "DATA\r\n"
			. "--B--\r\n";
		self::assertSame($expected, (string) $body);
	}

	public function testGeneratesARandomBoundary()
	{
		$a = new TMultipartStream();
		$b = new TMultipartStream();
		self::assertNotSame('', $a->getBoundary());
		self::assertNotSame($a->getBoundary(), $b->getBoundary(), 'Each instance gets its own boundary.');
		self::assertSame("--{$a->getBoundary()}--\r\n", (string) $a, 'An empty body is just the closing boundary.');
	}

	public function testMissingNameThrows()
	{
		$this->expectException(TInvalidDataValueException::class);
		new TMultipartStream([['contents' => 'x']]);
	}

	public function testMissingContentsThrows()
	{
		$this->expectException(TInvalidDataValueException::class);
		new TMultipartStream([['name' => 'x']]);
	}

	public function testNameAndFilenameAreSanitizedAgainstHeaderInjection()
	{
		$body = (string) new TMultipartStream([[
			'name' => "field\"\r\nX-Injected: 1\r\n\r\nevil",
			'contents' => 'v',
			'filename' => "a\".jpg\r\nX-Also: 1",
		]]);
		self::assertStringNotContainsString("\r\nX-Injected", $body, 'A line break in the name cannot start a header line.');
		self::assertStringNotContainsString("\r\nX-Also", $body, 'A line break in the filename cannot start a header line.');
		self::assertStringContainsString('name="field\\"X-Injected: 1evil"', $body, 'The quote is escaped and line breaks removed.');
	}

	public function testExtraHeaderValuesAreStrippedOfLineBreaks()
	{
		$body = (string) new TMultipartStream([[
			'name' => 'f',
			'contents' => 'v',
			'headers' => ["X-Meta" => "one\r\nX-Smuggled: 2"],
		]]);
		self::assertStringNotContainsString("\r\nX-Smuggled", $body);
		self::assertStringContainsString('X-Meta: oneX-Smuggled: 2', $body, 'The value survives with its line breaks removed.');
	}
}
