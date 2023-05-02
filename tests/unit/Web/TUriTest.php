<?php

use Prado\Exceptions\TInvalidDataValueException;
use Prado\Web\TUri;

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

	public function testGetUri()
	{
		self::assertEquals(self::URISTR, $this->uri->getUri());
	}

	public function testGetScheme()
	{
		self::assertEquals('http', $this->uri->getScheme());
	}

	public function testGetHost()
	{
		self::assertEquals('www.pradoframework.net', $this->uri->getHost());
	}

	public function testGetPort()
	{
		self::assertEquals(80, $this->uri->getPort());
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

	public function testGetQuery()
	{
		self::assertEquals('page=test&param1=test2', $this->uri->getQuery());
	}

	public function testGetFragment()
	{
		self::assertEquals('anchor', $this->uri->getFragment());
	}
}
