<?php


use Prado\Data\SqlMap\DataMapper\TPropertyAccess;

class TPropertyAccessTest extends PHPUnit\Framework\TestCase
{
	public function testHasPublicVar()
	{
		$testobj = new _PropertyAccessTestHelperPublicVar();
		self::assertEquals(true, TPropertyAccess::has($testobj, 'a'));
		self::assertEquals(true, TPropertyAccess::has($testobj, 'b'));
		self::assertEquals(false, TPropertyAccess::has($testobj, 'c'));

		self::assertEquals(false, TPropertyAccess::has($testobj, 'A'));
		self::assertEquals(false, TPropertyAccess::has($testobj, 'B'));
		self::assertEquals(false, TPropertyAccess::has($testobj, 'C'));
	}

	public function testGetPublicVar()
	{
		$testobj = new _PropertyAccessTestHelperPublicVar();

		self::assertEquals(1, TPropertyAccess::get($testobj, 'a'));
		self::assertEquals(2, TPropertyAccess::get($testobj, 'b'));

		self::expectException('Prado\\Data\SqlMap\DataMapper\\TInvalidPropertyException');
		TPropertyAccess::get($testobj, 'c');
	}

	public function testSetPublicVar()
	{
		$testobj = new _PropertyAccessTestHelperPublicVar();

		TPropertyAccess::set($testobj, 'a', 10);
		self::assertEquals(10, TPropertyAccess::get($testobj, 'a'));

		TPropertyAccess::set($testobj, 'b', 20);
		self::assertEquals(20, TPropertyAccess::get($testobj, 'b'));

		TPropertyAccess::set($testobj, 'c', 30);
		self::assertEquals(30, TPropertyAccess::get($testobj, 'c'));
	}


	public function testHasStaticProperties()
	{
		$testobj = new _PropertyAccessTestHelperStaticProperties();
		self::assertEquals(true, TPropertyAccess::has($testobj, 'a'));
		self::assertEquals(true, TPropertyAccess::has($testobj, 'b'));
		self::assertEquals(false, TPropertyAccess::has($testobj, 'c'));

		self::assertEquals(true, TPropertyAccess::has($testobj, 'A'));
		self::assertEquals(true, TPropertyAccess::has($testobj, 'B'));
		self::assertEquals(false, TPropertyAccess::has($testobj, 'C'));
	}

	public function testGetStaticProperties()
	{
		$testobj = new _PropertyAccessTestHelperStaticProperties();
		self::assertEquals(1, TPropertyAccess::get($testobj, 'a'));
		self::assertEquals(2, TPropertyAccess::get($testobj, 'b'));
		self::assertEquals(1, TPropertyAccess::get($testobj, 'A'));
		self::assertEquals(2, TPropertyAccess::get($testobj, 'B'));

		self::expectException('Prado\\Data\\SqlMap\\DataMapper\\TInvalidPropertyException');
		TPropertyAccess::get($testobj, 'c');

		self::expectException('Prado\\Data\\SqlMap\\DataMapper\\TInvalidPropertyException');
		TPropertyAccess::get($testobj, 'C');
	}

	public function testSetStaticProperties()
	{
		$testobj = new _PropertyAccessTestHelperStaticProperties();
		TPropertyAccess::set($testobj, 'a', 10);
		self::assertEquals(10, TPropertyAccess::get($testobj, 'a'));
		self::assertEquals(10, TPropertyAccess::get($testobj, 'A'));

		TPropertyAccess::set($testobj, 'A', 100);
		self::assertEquals(100, TPropertyAccess::get($testobj, 'a'));
		self::assertEquals(100, TPropertyAccess::get($testobj, 'A'));

		TPropertyAccess::set($testobj, 'b', 10);
		self::assertEquals(10, TPropertyAccess::get($testobj, 'b'));
		self::assertEquals(10, TPropertyAccess::get($testobj, 'B'));

		TPropertyAccess::set($testobj, 'B', 100);
		self::assertEquals(100, TPropertyAccess::get($testobj, 'b'));
		self::assertEquals(100, TPropertyAccess::get($testobj, 'B'));

		TPropertyAccess::set($testobj, 'c', 30);
		self::assertEquals(30, TPropertyAccess::get($testobj, 'c'));

		self::expectException('Prado\\Data\\SqlMap\\DataMapper\\TInvalidPropertyException');
		TPropertyAccess::get($testobj, 'C');
	}


	public function testHasDynamicProperties()
	{
		$testobj = new _PropertyAccessTestHelperDynamicProperties();
		self::assertEquals(true, TPropertyAccess::has($testobj, 'a'));
		self::assertEquals(true, TPropertyAccess::has($testobj, 'b'));
		self::assertEquals(true, TPropertyAccess::has($testobj, 'c'));

		self::assertEquals(true, TPropertyAccess::has($testobj, 'A'));
		self::assertEquals(true, TPropertyAccess::has($testobj, 'B'));
		self::assertEquals(true, TPropertyAccess::has($testobj, 'C'));
	}

	public function testGetDynamicProperties()
	{
		$testobj = new _PropertyAccessTestHelperDynamicProperties();
		self::assertEquals(1, TPropertyAccess::get($testobj, 'a'));
		self::assertEquals(2, TPropertyAccess::get($testobj, 'b'));
		self::assertEquals(1, TPropertyAccess::get($testobj, 'A'));
		self::assertEquals(2, TPropertyAccess::get($testobj, 'B'));

		self::assertNull(TPropertyAccess::get($testobj, 'c'));
		self::assertNull(TPropertyAccess::get($testobj, 'C'));
	}

	public function testSetDynamicProperties()
	{
		$testobj = new _PropertyAccessTestHelperDynamicProperties();
		TPropertyAccess::set($testobj, 'a', 10);
		self::assertEquals(10, TPropertyAccess::get($testobj, 'a'));
		self::assertEquals(10, TPropertyAccess::get($testobj, 'A'));

		TPropertyAccess::set($testobj, 'A', 100);
		self::assertEquals(100, TPropertyAccess::get($testobj, 'a'));
		self::assertEquals(100, TPropertyAccess::get($testobj, 'A'));

		TPropertyAccess::set($testobj, 'b', 10);
		self::assertEquals(10, TPropertyAccess::get($testobj, 'b'));
		self::assertEquals(10, TPropertyAccess::get($testobj, 'B'));

		TPropertyAccess::set($testobj, 'B', 100);
		self::assertEquals(100, TPropertyAccess::get($testobj, 'b'));
		self::assertEquals(100, TPropertyAccess::get($testobj, 'B'));

		TPropertyAccess::set($testobj, 'c', 30);
		self::assertNull(TPropertyAccess::get($testobj, 'c'));
		self::assertNull(TPropertyAccess::get($testobj, 'C'));
	}

	public function testArrayAccess()
	{
		$thingamajig = [
			'a' => 'foo',
			'b' => 'bar',
			'c' => new _PropertyAccessTestHelperPublicVar(),
			'd' => new _PropertyAccessTestHelperStaticProperties(),
			'e' => new _PropertyAccessTestHelperDynamicProperties(),
		];

		$testobj = new _PropertyAccessTestHelperPublicVar();
		TPropertyAccess::set($testobj, 'a', $thingamajig);

		$tmp = TPropertyAccess::get($testobj, 'a');
		self::assertTrue(is_array($tmp));
		self::assertEquals($thingamajig, $tmp);

		self::assertEquals('foo', TPropertyAccess::get($testobj, 'a.a'));
		self::assertEquals('bar', TPropertyAccess::get($testobj, 'a.b'));
		self::assertTrue(TPropertyAccess::get($testobj, 'a.c') instanceof _PropertyAccessTestHelperPublicVar);
		self::assertTrue(TPropertyAccess::get($testobj, 'a.d') instanceof _PropertyAccessTestHelperStaticProperties);
		self::assertTrue(TPropertyAccess::get($testobj, 'a.e') instanceof _PropertyAccessTestHelperDynamicProperties);

		TPropertyAccess::set($testobj, 'a.c.a', 10);
		TPropertyAccess::set($testobj, 'a.d.a', 10);
		TPropertyAccess::set($testobj, 'a.e.a', 10);
		self::assertEquals(10, TPropertyAccess::get($testobj, 'a.c.a'));
		self::assertEquals(10, TPropertyAccess::get($testobj, 'a.d.a'));
		self::assertEquals(10, TPropertyAccess::get($testobj, 'a.e.a'));

		TPropertyAccess::set($testobj, 'a.c.c', 30);
		TPropertyAccess::set($testobj, 'a.d.c', 30);
		TPropertyAccess::set($testobj, 'a.e.c', 30);

		self::assertEquals(30, TPropertyAccess::get($testobj, 'a.c.c'));
		self::assertEquals(30, TPropertyAccess::get($testobj, 'a.d.c'));

		self::assertNull(TPropertyAccess::get($testobj, 'a.e.c'));
		self::assertNull(TPropertyAccess::get($testobj, 'a.e.C'));

		self::expectException('Prado\\Data\\SqlMap\\DataMapper\\TInvalidPropertyException');
		TPropertyAccess::get($testobj, 'a.c.C');

		self::expectException('Prado\\Data\\SqlMap\\DataMapper\\TInvalidPropertyException');
		TPropertyAccess::get($testobj, 'a.d.C');
	}
}



class _PropertyAccessTestHelperPublicVar
{
	public $a = 1;
	public $b = 2;
}

class _PropertyAccessTestHelperStaticProperties
{
	private $_a = 1;
	private $_b = 2;

	public function getA()
	{
		return $this -> _a;
	}

	public function setA($value)
	{
		$this -> _a = $value;
	}

	public function getB()
	{
		return $this -> _b;
	}

	public function setB($value)
	{
		$this -> _b = $value;
	}
}

class _PropertyAccessTestHelperDynamicProperties
{
	private $_a = 1;
	private $_b = 2;

	public function __set($name, $value)
	{
		switch (strToLower($name)) {
			case 'a':
				$this -> _a = $value;
			break;
			case 'b':
				$this -> _b = $value;
			break;
		}
	}

	public function __get($name)
	{
		switch (strToLower($name)) {
			case 'a':
				return $this -> _a;
			break;
			case 'b':
				return $this -> _b;
			break;
			default:
				return null;
			break;
		}
	}
}
