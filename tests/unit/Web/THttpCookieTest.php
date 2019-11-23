<?php

use Prado\Web\THttpCookie;

class THttpCookieTest extends PHPUnit\Framework\TestCase
{
	public function testConstruct()
	{
		$cookie = new THttpCookie('name', 'value');
		self::assertEquals('name', $cookie->getName());
		self::assertEquals('value', $cookie->getValue());
	}

	public function testSetDomain()
	{
		$cookie = new THttpCookie('name', 'value');
		$cookie->setDomain('pradoframework.net');
		self::assertEquals('pradoframework.net', $cookie->getdomain());
	}

	public function testSetExpire()
	{
		$cookie = new THttpCookie('name', 'value');
		$exp = time() + 3600;
		$cookie->setExpire($exp);
		self::assertEquals($exp, $cookie->getExpire());
	}

	public function testSetName()
	{
		$cookie = new THttpCookie('name', 'value');
		$cookie->setName('newName');
		self::assertEquals('newName', $cookie->getName());
	}

	public function testSetValue()
	{
		$cookie = new THttpCookie('name', 'value');
		$cookie->setValue('newValue');
		self::assertEquals('newValue', $cookie->getValue());
	}

	public function testSetPath()
	{
		$cookie = new THttpCookie('name', 'value');
		$cookie->setPath('/admin');
		self::assertEquals('/admin', $cookie->getPath());
	}

	public function testSetSecure()
	{
		$cookie = new THttpCookie('name', 'value');
		$cookie->setSecure(true);
		self::assertTrue($cookie->getSecure());
	}
}
