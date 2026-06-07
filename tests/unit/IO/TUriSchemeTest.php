<?php

use Prado\IO\TUriScheme;
use Prado\TEnumerable;
use Prado\Util\Helpers\TProcessHelper;

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

		// Cross-check each constant against the runtime transport list where its prerequisite
		// holds: tcp/udp are universal, unix/udg need POSIX, ssl/tls need the OpenSSL extension.
		$transports = stream_get_transports();
		self::assertContains(TUriScheme::TCP, $transports);
		self::assertContains(TUriScheme::UDP, $transports);

		if (!TProcessHelper::isSystemWindows()) {
			self::assertContains(TUriScheme::UNIX, $transports, 'unix transport on POSIX');
			self::assertContains(TUriScheme::UDG, $transports, 'udg transport on POSIX');
		}
		if (extension_loaded('openssl')) {
			self::assertContains(TUriScheme::SSL, $transports, 'ssl transport with OpenSSL');
			self::assertContains(TUriScheme::TLS, $transports, 'tls transport with OpenSSL');
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
