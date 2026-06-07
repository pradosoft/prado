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

	public function testSocketTransportConstants()
	{
		self::assertSame('tcp', TUriScheme::TCP);
		self::assertSame('udp', TUriScheme::UDP);
		self::assertSame('unix', TUriScheme::UNIX);
		self::assertSame('udg', TUriScheme::UDG);
		self::assertSame('ssl', TUriScheme::SSL);
		self::assertSame('tls', TUriScheme::TLS);

		// Each constant names a real PHP socket transport.
		$transports = stream_get_transports();
		foreach ([TUriScheme::TCP, TUriScheme::UDP, TUriScheme::UNIX, TUriScheme::UDG, TUriScheme::SSL, TUriScheme::TLS] as $transport) {
			self::assertContains($transport, $transports, "{$transport} is a PHP socket transport");
		}
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
