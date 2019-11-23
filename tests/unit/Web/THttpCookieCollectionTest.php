<?php

use Prado\Exceptions\TInvalidDataTypeException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Web\THttpCookie;
use Prado\Web\THttpCookieCollection;

class THttpCookieCollectionTest extends PHPUnit\Framework\TestCase
{
	public function testConstruct()
	{
		$coll = new THttpCookieCollection();
		self::assertInstanceOf('Prado\\Web\\THttpCookieCollection', $coll);
	}

	public function testInsertAt()
	{
		$coll = new THttpCookieCollection();
		$coll->insertAt(0, new THttpCookie('name', 'value'));
		self::assertEquals('value', $coll->itemAt(0)->getValue());
		try {
			$coll->insertAt(1, "bad parameter");
			self::fail('Invalid data type exception not raised');
		} catch (TInvalidDataTypeException $e) {
		}
	}

	public function testRemoveAt()
	{
		$coll = new THttpCookieCollection();
		try {
			$coll->removeAt(0);
			self::fail('Invalid Value exception not raised');
		} catch (TInvalidDataValueException $e) {
		}

		$coll->insertAt(0, new THttpCookie('name', 'value'));
		self::assertEquals('value', $coll->removeAt(0)->getValue());
	}

	public function testItemAt()
	{
		$coll = new THttpCookieCollection();
		$coll->insertAt(0, new THttpCookie('name', 'value'));
		self::assertEquals('value', $coll->itemAt(0)->getValue());
		self::assertEquals('value', $coll->itemAt('name')->getValue());
	}

	public function testFindCookieByName()
	{
		$coll = new THttpCookieCollection();
		$coll->insertAt(0, new THttpCookie('name', 'value'));
		self::assertEquals('value', $coll->findCookieByName('name')->getValue());
		self::assertNull($coll->findCookieByName('invalid'));
	}
}
