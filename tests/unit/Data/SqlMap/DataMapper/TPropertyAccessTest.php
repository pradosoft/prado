<?php

namespace Prado\Test\Unit\Data\SqlMap\DataMapper;

use Prado\Exceptions\TPhpErrorException;
use Prado\Data\SqlMap\DataMapper\TPropertyAccess;

class TPropertyAccessTest extends \PHPUnit\Framework\TestCase
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

		self::expectException(\Prado\Data\SqlMap\DataMapper\TInvalidPropertyException::class);
		TPropertyAccess::get($testobj, 'c');
	}

	public function testSetPublicVar()
	{
		$testobj = new _PropertyAccessTestHelperPublicVar();

		TPropertyAccess::set($testobj, 'a', 10);
		self::assertEquals(10, TPropertyAccess::get($testobj, 'a'));

		TPropertyAccess::set($testobj, 'b', 20);
		self::assertEquals(20, TPropertyAccess::get($testobj, 'b'));

		try { // PHP 8.2 throws an error on undefined properties.
			TPropertyAccess::set($testobj, 'c', 30);
			self::assertEquals(30, TPropertyAccess::get($testobj, 'c'));
		} catch(TPhpErrorException $e) {
		}
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

		self::expectException(\Prado\Data\SqlMap\DataMapper\TInvalidPropertyException::class);
		TPropertyAccess::get($testobj, 'c');

		self::expectException(\Prado\Data\SqlMap\DataMapper\TInvalidPropertyException::class);
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

		try {
			TPropertyAccess::set($testobj, 'c', 30);
			self::assertEquals(30, TPropertyAccess::get($testobj, 'c'));
		} catch(TPhpErrorException $e) {
		}
			
		self::expectException(\Prado\Data\SqlMap\DataMapper\TInvalidPropertyException::class);
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

		try { // no public variable $c, error in PHP 8.2
			TPropertyAccess::set($testobj, 'a.c.c', 30);
		} catch (TPhpErrorException $e) {
		}
		try { // no getter methods for $c, error in PHP 8.2
			TPropertyAccess::set($testobj, 'a.d.c', 30);
		} catch (TPhpErrorException $e) {
		}
		// But dynamic magic methods accept $c without error as no error is coded into the test class.
		TPropertyAccess::set($testobj, 'a.e.c', 30);

		self::assertEquals(30, TPropertyAccess::get($testobj, 'a.c.c'));
		self::assertEquals(30, TPropertyAccess::get($testobj, 'a.d.c'));

		self::assertNull(TPropertyAccess::get($testobj, 'a.e.c'));
		self::assertNull(TPropertyAccess::get($testobj, 'a.e.C'));

		self::expectException(\Prado\Data\SqlMap\DataMapper\TInvalidPropertyException::class);
		TPropertyAccess::get($testobj, 'a.c.C');

		self::expectException(\Prado\Data\SqlMap\DataMapper\TInvalidPropertyException::class);
		TPropertyAccess::get($testobj, 'a.d.C');
	}

	public function testArrayAccessObject()
	{
		$map = new \Prado\Collections\TMap(['a' => 'foo', 'n' => null, 'c' => ['d' => 'bar']]);

		self::assertEquals('foo', TPropertyAccess::get($map, 'a'));
		self::assertNull(TPropertyAccess::get($map, 'n'));
		self::assertEquals('bar', TPropertyAccess::get($map, 'c.d'));
		self::assertTrue(TPropertyAccess::has($map, 'a'));
		self::assertTrue(TPropertyAccess::has($map, 'n'));
		self::assertTrue(TPropertyAccess::has($map, 'c.d'));
		self::assertFalse(TPropertyAccess::has($map, 'z'));
		self::assertFalse(TPropertyAccess::has($map, 'c.z'));
		self::assertFalse(TPropertyAccess::has($map, 'n.z'));

		TPropertyAccess::set($map, 'e', 'qux');
		self::assertEquals('qux', $map['e']);

		$testobj = new _PropertyAccessTestHelperPublicVar();
		TPropertyAccess::set($testobj, 'a', $map);
		self::assertEquals('foo', TPropertyAccess::get($testobj, 'a.a'));
		self::assertTrue(TPropertyAccess::has($testobj, 'a.c.d'));
		TPropertyAccess::set($testobj, 'a.b', 'baz');
		self::assertEquals('baz', $map['b']);

		self::expectException(\Prado\Data\SqlMap\DataMapper\TInvalidPropertyException::class);
		TPropertyAccess::get($map, 'z');
	}

	public function testArrayAccessObjectNestedPathMissing()
	{
		$map = new \Prado\Collections\TMap(['c' => ['d' => 'bar']]);

		self::expectException(\Prado\Data\SqlMap\DataMapper\TInvalidPropertyException::class);
		TPropertyAccess::get($map, 'c.z');
	}

	public function testArrayAccessObjectUsesOffsetMethods()
	{
		$object = new _PropertyAccessTestHelperArrayAccess();

		self::assertEquals(['a', 'b'], TPropertyAccess::get($object, 'ab'));
		self::assertEquals('b', TPropertyAccess::get($object, 'ab.1'));
		self::assertTrue(TPropertyAccess::has($object, 'ab'));
		self::assertTrue(TPropertyAccess::has($object, 'ab.1'));
		self::assertFalse(TPropertyAccess::has($object, 'cd'));
		self::assertSame(['ab', 'ab', 'ab', 'ab', 'cd'], $object->existsCalls);
		self::assertSame(['ab', 'ab', 'ab', 'ab'], $object->getCalls);

		self::expectException(\Prado\Data\SqlMap\DataMapper\TInvalidPropertyException::class);
		TPropertyAccess::get($object, 'cd');
	}

	public function testArrayAccessObjectInsideArray()
	{
		$map = new \Prado\Collections\TMap(['k' => 'v']);
		$data = ['map' => $map];

		self::assertEquals('v', TPropertyAccess::get($data, 'map.k'));
		self::assertTrue(TPropertyAccess::has($data, 'map.k'));
		self::assertFalse(TPropertyAccess::has($data, 'map.z'));
	}
}

/**
 * Accepts only keys made of the letters a and b. The value of a key is the
 * array of its letters. Records each offset call.
 */
class _PropertyAccessTestHelperArrayAccess implements \ArrayAccess
{
	public $existsCalls = [];
	public $getCalls = [];

	public function offsetExists($offset): bool
	{
		$this->existsCalls[] = $offset;
		return preg_match('/^[ab]+$/', (string) $offset) === 1;
	}

	public function offsetGet($offset): mixed
	{
		$this->getCalls[] = $offset;
		return str_split((string) $offset);
	}

	public function offsetSet($offset, $value): void
	{
	}

	public function offsetUnset($offset): void
	{
	}
}
