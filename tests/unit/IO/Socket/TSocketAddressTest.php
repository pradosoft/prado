<?php

use Prado\IO\Socket\TSocketAddress;
use Prado\IO\TResourceUri;

class TSocketAddressTest extends PHPUnit\Framework\TestCase
{
	public function testParseHostPort()
	{
		$a = TSocketAddress::parse('tcp://127.0.0.1:8080');
		self::assertSame('tcp', $a->getScheme());
		self::assertSame('127.0.0.1', $a->getHost());
		self::assertSame(8080, $a->getPort());
		self::assertSame('tcp://127.0.0.1:8080', (string) $a);
	}

	public function testParseBareHostPort()
	{
		$a = TSocketAddress::parse('192.168.0.5:443');
		self::assertNull($a->getScheme());
		self::assertSame('192.168.0.5', $a->getHost());
		self::assertSame(443, $a->getPort());
	}

	public function testParseUnix()
	{
		$a = TSocketAddress::parse('unix:///tmp/app.sock');
		self::assertSame('unix', $a->getScheme());
		self::assertSame('/tmp/app.sock', $a->getPath());
		self::assertSame('unix:///tmp/app.sock', (string) $a);
	}

	public function testParseUdg()
	{
		$a = TSocketAddress::parse('udg:///tmp/dg.sock');
		self::assertSame('udg', $a->getScheme());
		self::assertSame('/tmp/dg.sock', $a->getPath());
		self::assertNull($a->getPort());
		self::assertSame('udg:///tmp/dg.sock', (string) $a);
	}

	public function testParseTlsComponents()
	{
		$a = TSocketAddress::parse('tls://example.com:8443');
		self::assertSame('tls', $a->getScheme());
		self::assertSame('example.com', $a->getHost());
		self::assertSame(8443, $a->getPort());
		self::assertNull($a->getPath());
	}

	public function testParseIPv6()
	{
		$a = TSocketAddress::parse('tcp://[::1]:9000');
		self::assertSame('[::1]', $a->getHost());
		self::assertSame(9000, $a->getPort());
		self::assertSame('tcp://[::1]:9000', (string) $a);
	}

	public function testToStringRoundTrips()
	{
		foreach (['tcp://127.0.0.1:8080', 'udp://host:53', 'unix:///tmp/app.sock'] as $uri) {
			self::assertSame($uri, (string) TSocketAddress::parse($uri), "round-trip: {$uri}");
		}
	}

	public function testUriBridgeRoundTrips()
	{
		foreach (['tcp://127.0.0.1:8080', 'unix:///tmp/app.sock', 'udp://[::1]:53', '192.168.0.5:443'] as $s) {
			$address = TSocketAddress::parse($s);
			$uri = $address->getUri();
			self::assertInstanceOf(TResourceUri::class, $uri);
			$back = TSocketAddress::fromUri($uri);
			self::assertSame((string) $address, (string) $back, "URI bridge round-trip: {$s}");
		}
	}

	public function testFromUriReadsComponents()
	{
		$a = TSocketAddress::fromUri(new TResourceUri('tcp://example.com:1234'));
		self::assertSame('tcp', $a->getScheme());
		self::assertSame('example.com', $a->getHost());
		self::assertSame(1234, $a->getPort());
		self::assertNull($a->getPath());
	}
}
