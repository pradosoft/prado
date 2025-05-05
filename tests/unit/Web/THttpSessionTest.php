<?php

use Prado\Web\THttpSession;
use Prado\Web\THttpSessionCookieMode;

class THttpSessionTest extends PHPUnit\Framework\TestCase
{
	public function testInit()
	{
		throw new PHPUnit\Framework\IncompleteTestError();
	}

	public function testOpen()
	{
		throw new PHPUnit\Framework\IncompleteTestError();
	}

	public function testClose()
	{
		throw new PHPUnit\Framework\IncompleteTestError();
	}

	public function testDestroy()
	{
		throw new PHPUnit\Framework\IncompleteTestError();
	}

	public function testGetIsStarted()
	{
		throw new PHPUnit\Framework\IncompleteTestError();
	}

	public function testSetSessionID()
	{
		throw new PHPUnit\Framework\IncompleteTestError();
	}

	public function testSetSessionName()
	{
		throw new PHPUnit\Framework\IncompleteTestError();
	}

	public function testSetSavePath()
	{
		throw new PHPUnit\Framework\IncompleteTestError();
	}

	public function testSetUseCustomStorage()
	{
		throw new PHPUnit\Framework\IncompleteTestError();
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testSetCookieModeNone()
	{
		if(PHP_VERSION_ID >= 80400) {
			$this->markTestSkipped('Disabling session.use_only_cookies INI setting is deprecated on PHP => 8.4 ');
			return;
		}
		$session = new THttpSession();
		$session->CookieMode = THttpSessionCookieMode::None;

		$this->assertEquals(0, ini_get('session.use_cookies_only'));
		$this->assertEquals(0, ini_get('session.use_cookies'));
		$this->assertEquals(THttpSessionCookieMode::None, $session->CookieMode);
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testSetCookieModeAllow()
	{
		if(PHP_VERSION_ID >= 80400) {
			$this->markTestSkipped('Disabling session.use_only_cookies INI setting is deprecated on PHP => 8.4 ');
			return;
		}
		$session = new THttpSession();
		$session->CookieMode = THttpSessionCookieMode::Allow;

		$this->assertEquals(0, ini_get('session.use_only_cookies'));
		$this->assertEquals(1, ini_get('session.use_cookies'));
		$this->assertEquals(THttpSessionCookieMode::Allow, $session->CookieMode);
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testSetCookieModeAlways()
	{
		$session = new THttpSession();
		$session->CookieMode = THttpSessionCookieMode::Only;

		$this->assertEquals(1, ini_get('session.use_only_cookies'));
		$this->assertEquals(1, ini_get('session.use_cookies'));
		$this->assertEquals(0, ini_get('session.use_trans_sid'));
		$this->assertEquals(THttpSessionCookieMode::Only, $session->CookieMode);
	}

	public function testSetAutoStart()
	{
		throw new PHPUnit\Framework\IncompleteTestError();
	}

	public function testSetGProbability()
	{
		throw new PHPUnit\Framework\IncompleteTestError();
	}

	public function testSetUseTransparentSessionID()
	{
		throw new PHPUnit\Framework\IncompleteTestError();
	}

	public function testSetTimeout()
	{
		throw new PHPUnit\Framework\IncompleteTestError();
	}

	public function testGetIterator()
	{
		throw new PHPUnit\Framework\IncompleteTestError();
	}

	public function testGetCount()
	{
		throw new PHPUnit\Framework\IncompleteTestError();
	}

	public function testGetKeys()
	{
		throw new PHPUnit\Framework\IncompleteTestError();
	}

	public function testItemAt()
	{
		throw new PHPUnit\Framework\IncompleteTestError();
	}

	public function testAdd()
	{
		throw new PHPUnit\Framework\IncompleteTestError();
	}

	public function testRemove()
	{
		throw new PHPUnit\Framework\IncompleteTestError();
	}

	public function testContains()
	{
		throw new PHPUnit\Framework\IncompleteTestError();
	}

	public function testToArray()
	{
		throw new PHPUnit\Framework\IncompleteTestError();
	}

	public function testOffsetExists()
	{
		throw new PHPUnit\Framework\IncompleteTestError();
	}

	public function testOffsetGet()
	{
		throw new PHPUnit\Framework\IncompleteTestError();
	}

	public function testOffsetSet()
	{
		throw new PHPUnit\Framework\IncompleteTestError();
	}

	public function testOffsetUnset()
	{
		throw new PHPUnit\Framework\IncompleteTestError();
	}
}
