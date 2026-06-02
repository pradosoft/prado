<?php

use Prado\IO\TResourceUri;
use Psr\Http\Message\UriInterface;

class TResourceUriTest extends PHPUnit\Framework\TestCase
{
	public function testParseFullUri()
	{
		$u = new TResourceUri('https://user:pass@Example.COM:8443/a/b?x=1&y=2#frag');
		self::assertInstanceOf(UriInterface::class, $u);
		self::assertSame('https', $u->getScheme());
		self::assertSame('example.com', $u->getHost());        // lowercased
		self::assertSame(8443, $u->getPort());
		self::assertSame('user:pass', $u->getUserInfo());
		self::assertSame('user:pass@example.com:8443', $u->getAuthority());
		self::assertSame('/a/b', $u->getPath());
		self::assertSame('x=1&y=2', $u->getQuery());
		self::assertSame('frag', $u->getFragment());
	}

	public function testDefaultPortSuppressed()
	{
		self::assertNull((new TResourceUri('http://h:80/'))->getPort());
		self::assertNull((new TResourceUri('https://h:443/'))->getPort());
		self::assertSame(8080, (new TResourceUri('http://h:8080/'))->getPort());
		self::assertSame('h', (new TResourceUri('http://h:80/'))->getAuthority());
	}

	public function testDefaultPortSuppressedForPradoSchemes()
	{
		// Suppression now sources from TUriDefaultPort (databases, AI, …).
		self::assertNull((new TResourceUri('oci://h:1521/'))->getPort());
		self::assertNull((new TResourceUri('sqlsrv://h:1433/'))->getPort());
		self::assertNull((new TResourceUri('firebird://h:3050/'))->getPort());
		self::assertNull((new TResourceUri('ollama://h:11434/'))->getPort());
		// non-default ports are reported as-is
		self::assertSame(1522, (new TResourceUri('oci://h:1522/'))->getPort());
	}

	public function testRoundTripToString()
	{
		$s = 'https://example.com/path?q=1#f';
		self::assertSame($s, (string) new TResourceUri($s));
	}

	public function testImmutabilityWithMethodsReturnClones()
	{
		$u = new TResourceUri('http://example.com/');
		$u2 = $u->withScheme('https')->withHost('other.test')->withPort(8000)->withPath('/x')->withQuery('a=b')->withFragment('z');
		self::assertNotSame($u, $u2);
		self::assertSame('http://example.com/', (string) $u, 'Original unchanged.');
		self::assertSame('https://other.test:8000/x?a=b#z', (string) $u2);
	}

	public function testWithSameValueReturnsSameInstance()
	{
		$u = new TResourceUri('http://example.com/');
		self::assertSame($u, $u->withScheme('http'));   // no-op returns $this
		self::assertSame($u, $u->withScheme('HTTP'));    // case-normalized no-op
	}

	public function testWithUserInfo()
	{
		$u = (new TResourceUri('http://h/'))->withUserInfo('bob', 'secret');
		self::assertSame('bob:secret', $u->getUserInfo());
		self::assertSame('bob:secret@h', $u->getAuthority());
		$u2 = $u->withUserInfo('');
		self::assertSame('', $u2->getUserInfo());
		self::assertSame('h', $u2->getAuthority());
	}

	public function testInvalidPortThrows()
	{
		self::expectException(\InvalidArgumentException::class);
		(new TResourceUri('http://h/'))->withPort(70000);
	}

	public function testInvalidUriThrows()
	{
		self::expectException(\InvalidArgumentException::class);
		new TResourceUri('http://');
	}

	public function testPercentEncodingNotDoubled()
	{
		$u = (new TResourceUri(''))->withPath('/a b/%41/c');
		// space encoded, existing %41 kept, slashes kept
		self::assertSame('/a%20b/%41/c', $u->getPath());
	}

	public function testQueryEncoding()
	{
		$u = (new TResourceUri(''))->withQuery('a=hello world&b=x');
		self::assertSame('a=hello%20world&b=x', $u->getQuery());
	}

	public function testWithSchemeStripsTrailingColonAndSlashes()
	{
		$u = new TResourceUri('http://h/');
		self::assertSame('https', $u->withScheme('HTTPS://')->getScheme());
		self::assertSame('https', $u->withScheme('https:')->getScheme());
		self::assertSame('https://h/', (string) $u->withScheme('https://'));
	}

	public function testWithSchemeInvalidThrows()
	{
		self::expectException(\InvalidArgumentException::class);
		(new TResourceUri('http://h/'))->withScheme('ht!tp');
	}

	public function testWithEmptySchemeRemovesIt()
	{
		$u = (new TResourceUri('http://h/p'))->withScheme('');
		self::assertSame('', $u->getScheme());
		self::assertSame('//h/p', (string) $u);
	}

	public function testUserInfoIsPercentEncoded()
	{
		$u = (new TResourceUri('http://h/'))->withUserInfo('a b', 'p@ss');
		self::assertSame('a%20b:p%40ss', $u->getUserInfo());
		self::assertSame('a%20b:p%40ss@h', $u->getAuthority());
	}

	public function testIPv6Host()
	{
		$u = new TResourceUri('http://[::1]:8080/p');
		self::assertSame('[::1]', $u->getHost());
		self::assertSame(8080, $u->getPort());
		self::assertSame('[::1]:8080', $u->getAuthority());
		self::assertSame('http://[::1]:8080/p', (string) $u);
	}

	public function testFileUriRoundTrips()
	{
		self::assertSame('file:///etc/hosts', (string) new TResourceUri('file:///etc/hosts'));
		self::assertSame('file:foo/bar', (string) new TResourceUri('file:foo/bar'));
	}

	public function testReservedCharsEncodedPerComponent()
	{
		// '?' is reserved in a path; '#' reserved in a query.
		self::assertSame('/a%3Fb', (new TResourceUri(''))->withPath('/a?b')->getPath());
		self::assertSame('a%23b', (new TResourceUri(''))->withQuery('a#b')->getQuery());
	}

	public function testQueryEncodingNotDoubled()
	{
		// existing %2F preserved, space encoded
		self::assertSame('p=%2F%20x', (new TResourceUri(''))->withQuery('p=%2F x')->getQuery());
	}

	public function testWithNoOpsReturnSameInstance()
	{
		$u = new TResourceUri('http://h:8080/p?q=1#f');
		self::assertSame($u, $u->withHost('h'));
		self::assertSame($u, $u->withPort(8080));
		self::assertSame($u, $u->withPath('/p'));
		self::assertSame($u, $u->withQuery('q=1'));
		self::assertSame($u, $u->withFragment('f'));
	}

	public function testPortZeroAllowed()
	{
		self::assertSame(0, (new TResourceUri('http://h/'))->withPort(0)->getPort());
	}

	public function testWebTUriIsNowAPsr7Uri()
	{
		// Consolidation payoff: Prado\Web\TUri extends TResourceUri, so it IS a PSR-7 URI,
		// while keeping its legacy (BC) accessors.
		$u = new \Prado\Web\TUri('http://h.example:8080/p?q=1');
		self::assertInstanceOf(UriInterface::class, $u);
		self::assertInstanceOf(TResourceUri::class, $u);
		// inherited PSR-7 machinery works:
		self::assertSame('h.example:8080', $u->getAuthority());
		self::assertSame('https://h.example:8080/p?q=1', (string) $u->withScheme('https'));
		// legacy accessors preserved (no default-port suppression, raw values):
		self::assertSame(8080, $u->getPort());
		self::assertSame('http://h.example:8080/p?q=1', $u->getUri());
	}
}
