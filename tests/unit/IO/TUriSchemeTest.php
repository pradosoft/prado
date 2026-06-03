<?php

use Prado\IO\TUriScheme;
use Prado\TEnumerable;

class TUriSchemeTest extends PHPUnit\Framework\TestCase
{
	public function testIsEnumerable()
	{
		self::assertInstanceOf(TEnumerable::class, new TUriScheme());
	}

	public function testConstantsAreLowercaseSchemeStrings()
	{
		self::assertSame('http', TUriScheme::HTTP);
		self::assertSame('https', TUriScheme::HTTPS);
		self::assertSame('wss', TUriScheme::WSS);
		self::assertSame('mailto', TUriScheme::MAILTO);
		self::assertSame('php', TUriScheme::PHP);
	}

	public function testUsableInSchemeComparison()
	{
		$scheme = (new \Prado\Web\TUri('https://host/path'))->getScheme();
		self::assertSame(TUriScheme::HTTPS, $scheme);
		self::assertNotSame(TUriScheme::HTTP, $scheme);
	}

	public function testEnumerableLookups()
	{
		self::assertTrue(TUriScheme::hasConstantValue('https'));
		self::assertFalse(TUriScheme::hasConstantValue('not-a-scheme'));
		self::assertSame('wss', TUriScheme::valueOfConstant('WSS'));
		self::assertSame('FTP', TUriScheme::constantOfValue('ftp'));
	}
}
