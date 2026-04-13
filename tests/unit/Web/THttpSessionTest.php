<?php

use Prado\Web\THttpSession;
use Prado\Web\THttpSessionCookieMode;

class THttpSessionTest extends PHPUnit\Framework\TestCase
{
	public function testInit()
	{
		$session = new THttpSession();
		$this->assertFalse($session->getIsStarted());
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testOpen()
	{
		$session = new THttpSession();
		$this->assertFalse($session->getIsStarted());
		$session->open();
		$this->assertTrue($session->getIsStarted());
		$session->close();
		$this->assertFalse($session->getIsStarted());
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testClose()
	{
		$session = new THttpSession();
		$session->open();
		$this->assertTrue($session->getIsStarted());
		$session->close();
		$this->assertFalse($session->getIsStarted());
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testDestroy()
	{
		$session = new THttpSession();
		$session->open();
		$this->assertTrue($session->getIsStarted());
		$session->destroy();
		$this->assertFalse($session->getIsStarted());
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testGetIsStarted()
	{
		$session = new THttpSession();
		$this->assertFalse($session->getIsStarted());
		$session->open();
		$this->assertTrue($session->getIsStarted());
		$session->close();
		$this->assertFalse($session->getIsStarted());
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testSetSessionID()
	{
		$session = new THttpSession();
		$session->setSessionID('testsessionid123');
		$session->open();
		$this->assertEquals('testsessionid123', $session->getSessionID());
		$session->close();
		
		$session2 = new THttpSession();
		$session2->open();
		$this->expectException(\Prado\Exceptions\TInvalidOperationException::class);
		$session2->setSessionID('anotherid');
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testSetSessionName()
	{
		$session = new THttpSession();
		$session->setSessionName('CUSTOM');
		$session->open();
		$this->assertEquals('CUSTOM', $session->getSessionName());
		$session->close();
		
		$session2 = new THttpSession();
		$session2->open();
		$this->expectException(\Prado\Exceptions\TInvalidOperationException::class);
		$session2->setSessionName('ANOTHER');
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testSetSavePath()
	{
		$session = new THttpSession();
		$path = sys_get_temp_dir();
		$session->setSavePath($path);
		$this->assertEquals(realpath($path), $session->getSavePath());
		
		$session2 = new THttpSession();
		$session2->open();
		$this->expectException(\Prado\Exceptions\TInvalidOperationException::class);
		$session2->setSavePath($path);
	}

	public function testSetUseCustomStorage()
	{
		$session = new THttpSession();
		$this->assertFalse($session->getUseCustomStorage());
		$session->setUseCustomStorage(true);
		$this->assertTrue($session->getUseCustomStorage());
		$session->setUseCustomStorage(false);
		$this->assertFalse($session->getUseCustomStorage());
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
		$session = new THttpSession();
		$this->assertFalse($session->getAutoStart());
		$session->setAutoStart(true);
		$this->assertTrue($session->getAutoStart());
		$session->setAutoStart(false);
		$this->assertFalse($session->getAutoStart());
		
		$session2 = new THttpSession();
		$session2->init(null);
		$this->expectException(\Prado\Exceptions\TInvalidOperationException::class);
		$session2->setAutoStart(true);
	}

	public function testSetGProbability()
	{
		$session = new class() extends THttpSession {
			public static $ini = [];
			
			public static function getSessionIniConfig(string $key): string|false
			{
				if (isset(static::$ini[$key])) {
					return static::$ini[$key];
				}
				return false;
			}
			
			protected static function setSessionIniConfig(string $key, mixed $value): string|bool
			{
				$oldValue = false;
				if (isset(static::$ini[$key])) {
					$oldValue = static::$ini[$key];
				}
				static::$ini[$key] = $value;
				return $oldValue;
			}
		};
		$session->setGCProbability(50);
		$this->assertEquals(50, $session->getGCProbability());
		$session->setGCProbability(0);
		$this->assertEquals(0, $session->getGCProbability());
		$session->setGCProbability(100);
		$this->assertEquals(100, $session->getGCProbability());
		
		$session2  = new class() extends THttpSession {
			protected function sessionStart(): bool
			{
				return true;
			}
		};
		$session2->open();
		$this->expectException(\Prado\Exceptions\TInvalidOperationException::class);
		$session2->setGCProbability(50);
		
		$session3 = new THttpSession();
		$this->expectException(\Prado\Exceptions\TInvalidDataValueException::class);
		$session3->setGCProbability(150);
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testSetUseTransparentSessionID()
	{
		$session = new THttpSession();
		$session->CookieMode = THttpSessionCookieMode::Allow;
		$this->assertFalse($session->getUseTransparentSessionID());
		$session->setUseTransparentSessionID(true);
		$this->assertTrue($session->getUseTransparentSessionID());
		$session->setUseTransparentSessionID(false);
		$this->assertFalse($session->getUseTransparentSessionID());
		
		$session2 = new THttpSession();
		$session2->open();
		$this->expectException(\Prado\Exceptions\TInvalidOperationException::class);
		$session2->setUseTransparentSessionID(true);
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testSetTimeout()
	{
		$session = new THttpSession();
		$session->setTimeout(3600);
		$this->assertEquals(3600, $session->getTimeout());
		$session->setTimeout(7200);
		$this->assertEquals(7200, $session->getTimeout());
		
		$session2 = new THttpSession();
		$session2->open();
		$this->expectException(\Prado\Exceptions\TInvalidOperationException::class);
		$session2->setTimeout(3600);
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testGetIterator()
	{
		$session = new THttpSession();
		$session->open();
		$session['key1'] = 'value1';
		$session['key2'] = 'value2';
		
		$iter = $session->getIterator();
		$this->assertInstanceOf(\Iterator::class, $iter);
		
		$values = [];
		foreach ($session as $key => $value) {
			$values[$key] = $value;
		}
		$this->assertEquals('value1', $values['key1']);
		$this->assertEquals('value2', $values['key2']);
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testGetCount()
	{
		$session = new THttpSession();
		$session->open();
		$session->clear();
		$this->assertEquals(0, $session->getCount());
		$this->assertEquals(0, $session->count());
		
		$session['key1'] = 'value1';
		$this->assertEquals(1, $session->getCount());
		
		$session['key2'] = 'value2';
		$this->assertEquals(2, $session->count());
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testGetKeys()
	{
		$session = new THttpSession();
		$session->open();
		$session->clear();
		$this->assertEquals([], $session->getKeys());
		
		$session['key1'] = 'value1';
		$session['key2'] = 'value2';
		$keys = $session->getKeys();
		$this->assertContains('key1', $keys);
		$this->assertContains('key2', $keys);
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testItemAt()
	{
		$session = new THttpSession();
		$session->open();
		$session->clear();
		
		$this->assertNull($session->itemAt('nonexistent'));
		
		$session['key1'] = 'value1';
		$this->assertEquals('value1', $session->itemAt('key1'));
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testAdd()
	{
		$session = new THttpSession();
		$session->open();
		$session->clear();
		
		$session->add('key1', 'value1');
		$this->assertEquals('value1', $session['key1']);
		
		$session->add('key1', 'newvalue');
		$this->assertEquals('newvalue', $session['key1']);
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testRemove()
	{
		$session = new THttpSession();
		$session->open();
		$session->clear();
		
		$session['key1'] = 'value1';
		$this->assertEquals('value1', $session->remove('key1'));
		$this->assertNull($session->itemAt('key1'));
		
		$this->assertNull($session->remove('nonexistent'));
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testContains()
	{
		$session = new THttpSession();
		$session->open();
		$session->clear();
		
		$this->assertFalse($session->contains('key1'));
		
		$session['key1'] = 'value1';
		$this->assertTrue($session->contains('key1'));
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testToArray()
	{
		$session = new THttpSession();
		$session->open();
		$session->clear();
		
		$this->assertEquals([], $session->toArray());
		
		$session['key1'] = 'value1';
		$session['key2'] = 'value2';
		$arr = $session->toArray();
		$this->assertEquals('value1', $arr['key1']);
		$this->assertEquals('value2', $arr['key2']);
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testOffsetExists()
	{
		$session = new THttpSession();
		$session->open();
		$session->clear();
		
		$this->assertFalse(isset($session['key1']));
		
		$session['key1'] = 'value1';
		$this->assertTrue(isset($session['key1']));
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testOffsetGet()
	{
		$session = new THttpSession();
		$session->open();
		$session->clear();
		
		$this->assertNull($session['key1']);
		
		$session['key1'] = 'value1';
		$this->assertEquals('value1', $session['key1']);
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testOffsetSet()
	{
		$session = new THttpSession();
		$session->open();
		$session->clear();
		
		$session['key1'] = 'value1';
		$this->assertEquals('value1', $session['key1']);
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testOffsetUnset()
	{
		$session = new THttpSession();
		$session->open();
		$session->clear();
		
		$session['key1'] = 'value1';
		unset($session['key1']);
		$this->assertFalse(isset($session['key1']));
	}
}
