<?php

namespace Prado\Test\Unit\Web;

use Prado\Exceptions\TInvalidDataTypeException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Web\THttpCookie;
use Prado\Web\THttpCookieCollection;
use Prado\Test\Unit\Harness\TTestApplication;
use Prado\Test\Unit\Harness\Web\TTestCookieResponse;

class THttpCookieCollectionTest extends \PHPUnit\Framework\TestCase
{
	public function testConstruct()
	{
		$coll = new THttpCookieCollection();
		self::assertInstanceOf(THttpCookieCollection::class, $coll);
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

		$coll = new THttpCookieCollection();
		self::assertNull($coll->findCookieByName('name'));
		$coll->add(new THttpCookie('first', '1'));
		$coll->add(new THttpCookie('second', '2'));
		self::assertSame('2', $coll->findCookieByName('second')->getValue());
	}

	public function testResponseOwnerSendsAndDeletesCookies()
	{
		$app = new TTestApplication(__DIR__ . '/app');
		try {
			$response = new TTestCookieResponse();
			$coll = new THttpCookieCollection($response);
			$coll->add(new THttpCookie('name', 'value'));
			self::assertSame(['name', 'value'], array_slice($response->setCookies[0], 0, 2));
			self::assertSame('value', $coll->removeAt(0)->getValue());
			self::assertSame(['name', null], array_slice($response->setCookies[1], 0, 2));
			self::assertSame(0, $response->setCookies[1][2]['expires']);
			self::assertSame(0, $coll->getCount());
		} finally {
			$app->restoreApplication();
		}
	}
}
