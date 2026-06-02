<?php

use Prado\Exceptions\TInvalidDataValueException;
use Prado\IO\TResourceUri;
use Prado\Web\TUri;
use Psr\Http\Message\UriInterface;

class TUriTest extends PHPUnit\Framework\TestCase
{
	const URISTR = 'http://login:p@ssw0rd:compl3x@www.pradoframework.net:80/demos/quickstart/index.php?page=test&param1=test2#anchor';

	protected $uri;

	protected function setUp(): void
	{
		$this->uri = new TUri(self::URISTR);
	}

	protected function tearDown(): void
	{
		$this->uri = null;
	}

	public function testConstruct()
	{
		$url = "http://www.pradoframework.net/";
		$uri = new TUri($url);
		self::assertEquals($url, $uri->getUri());
		// Bad uri test
		$url = "http://www.pradoframework.net:badport/test";
		try {
			$url = new TUri($url);
			self::fail('exception not raised with an invalid URL');
		} catch (TInvalidDataValueException $e) {
		}
	}

	public function testIsPsr7Uri()
	{
		self::assertInstanceOf(UriInterface::class, $this->uri);
		self::assertInstanceOf(TResourceUri::class, $this->uri);
	}

	public function testGetUri()
	{
		// 4.4.0: getUri() returns the recomposed (normalized) URI — default port
		// suppressed, user-info percent-encoded.
		$expected = 'http://login:p%40ssw0rd%3Acompl3x@www.pradoframework.net/demos/quickstart/index.php?page=test&param1=test2#anchor';
		self::assertSame($expected, $this->uri->getUri());
		self::assertSame((string) $this->uri, $this->uri->getUri());
	}

	public function testGetScheme()
	{
		self::assertEquals('http', $this->uri->getScheme());
	}

	public function testSchemeAndHostNormalizedToLowerCase()
	{
		$u = new TUri('HTTP://Example.COM/');
		self::assertSame('http', $u->getScheme());
		self::assertSame('example.com', $u->getHost());
	}

	public function testGetHost()
	{
		self::assertEquals('www.pradoframework.net', $this->uri->getHost());
	}

	public function testGetPortSuppressesDefault()
	{
		// 4.4.0: PSR-7 default-port suppression — http:80 → null.
		self::assertNull($this->uri->getPort());
		self::assertNull((new TUri('http://h/'))->getPort());
	}

	public function testGetPortNonDefault()
	{
		self::assertSame(8080, (new TUri('http://h:8080/'))->getPort());
	}

	public function testGetUser()
	{
		self::assertEquals('login', $this->uri->getUser());
	}

	public function testGetPassword()
	{
		self::assertEquals('p@ssw0rd:compl3x', $this->uri->getPassword());
	}

	public function testGetPath()
	{
		self::assertEquals('/demos/quickstart/index.php', $this->uri->getPath());
	}

	public function testPathPercentEncoded()
	{
		self::assertSame('/a%20b', (new TUri('http://h/'))->withPath('/a b')->getPath());
	}

	public function testGetQuery()
	{
		self::assertEquals('page=test&param1=test2', $this->uri->getQuery());
	}

	public function testGetFragment()
	{
		self::assertEquals('anchor', $this->uri->getFragment());
	}

	public function testAuthorityAndUserInfo()
	{
		$u = new TUri('http://bob:pw@host:8080/');
		self::assertSame('bob:pw@host:8080', $u->getAuthority());
		self::assertSame('bob:pw', $u->getUserInfo());
		self::assertSame('bob', $u->getUser());
		self::assertSame('pw', $u->getPassword());
	}

	public function testGettersLiveAfterWith()
	{
		// Full PSR-7: getters reflect with* changes (no staleness).
		$u = new TUri('http://host/');
		$u2 = $u->withScheme('https')->withHost('Other.Test')->withPort(8443);
		self::assertInstanceOf(TUri::class, $u2);
		self::assertSame('http', $u->getScheme(), 'original unchanged');
		self::assertSame('https', $u2->getScheme());
		self::assertSame('other.test', $u2->getHost());
		self::assertSame(8443, $u2->getPort());
		self::assertSame('https://other.test:8443/', (string) $u2);
	}

	public function testImmutableNoSetters()
	{
		// TUri has remained immutable: it exposes no public setters.
		self::assertFalse(method_exists($this->uri, 'setScheme'));
		self::assertFalse(method_exists($this->uri, 'setHost'));
	}

	public function testUserPasswordDecodedViaWithUserInfo()
	{
		$u = (new TUri('http://h/'))->withUserInfo('bob', 'p@ss');
		self::assertSame('bob', $u->getUser());
		self::assertSame('p@ss', $u->getPassword());      // decoded
		self::assertSame('bob:p%40ss', $u->getUserInfo()); // encoded
	}

	public function testInvalidPortThrows()
	{
		self::expectException(\InvalidArgumentException::class);
		(new TUri('http://h/'))->withPort(70000);
	}
}
