<?php

use Prado\Util\Math\TRational;

class TRationalTest extends PHPUnit\Framework\TestCase
{
	public $obj;
	
	public function getTestClass()
	{
		return TRational::class;
	}
	
	protected function setUp(): void
	{
		$class = $this->getTestClass();
		$this->obj = new $class();
	}

	protected function tearDown(): void
	{
		$this->obj = null;
	}
	
	public function testGetIsUnsigned()
	{
		self::assertFalse($this->obj->getIsUnsigned());
	}

	public function testConstruct()
	{
		$class = $this->getTestClass();
		
		self::assertEquals(0, $this->obj->getNumerator());
		self::assertEquals(1, $this->obj->getDenominator());
		
		$rational = new $class(1.5);
		self::assertEquals(3, $rational->getNumerator());
		self::assertEquals(2, $rational->getDenominator());
		
		$rational = new $class('911/0');
		self::assertEquals(911, $rational->getNumerator());
		self::assertEquals(0, $rational->getDenominator());
		
		$rational = new $class(33, 10);
		self::assertEquals(33, $rational->getNumerator());
		self::assertEquals(10, $rational->getDenominator());
		
		$rational = new $class(11, null);
		self::assertEquals(11, $rational->getNumerator());
		self::assertEquals(1, $rational->getDenominator());
		
		$rational = new $class(13, false);
		self::assertEquals(13, $rational->getNumerator());
		self::assertEquals(1, $rational->getDenominator());
		
		$rational = new $class(NAN);
		self::assertEquals(0, $rational->getNumerator());
		self::assertEquals(0, $rational->getDenominator());
		
		$rational = new $class(null);
		self::assertEquals(0, $rational->getNumerator());
		self::assertEquals(1, $rational->getDenominator());
		
		$rational = new $class(false);
		self::assertEquals(0, $rational->getNumerator());
		self::assertEquals(1, $rational->getDenominator());
		
		$rational = new $class(['55.5']);
		self::assertEquals(111, $rational->getNumerator());
		self::assertEquals(2, $rational->getDenominator());
	}
	
	public function testConstructSpecific()
	{
		$class = $this->getTestClass();
		
		$rational = new $class(-1.5);
		self::assertEquals(-3, $rational->getNumerator());
		self::assertEquals(2, $rational->getDenominator());
		
		$rational = new $class(4294967294.0, 3);
		self::assertEquals(1431655764, $rational->getNumerator());
		self::assertEquals(1, $rational->getDenominator());
		
		$rational = new $class(INF);
		self::assertEquals(-1, $rational->getNumerator());
		self::assertEquals(0, $rational->getDenominator());
		
		$rational = new $class('-21/-13');
		self::assertEquals(-21, $rational->getNumerator());
		self::assertEquals(-13, $rational->getDenominator());
		
		$rational = new $class(['-21', '-13']);
		self::assertEquals(-21, $rational->getNumerator());
		self::assertEquals(-13, $rational->getDenominator());
		
		$rational = new $class([-34, 21]);
		self::assertEquals(-34, $rational->getNumerator());
		self::assertEquals(21, $rational->getDenominator());
		
		$rational = new $class([34, -21]);
		self::assertEquals(34, $rational->getNumerator());
		self::assertEquals(-21, $rational->getDenominator());
	}
	
	public function testNumerator()
	{
		self::assertEquals($this->obj, $this->obj->setNumerator(11));
		self::assertEquals(11, $this->obj->getNumerator());
			
		$this->obj->setNumerator(13.3);
		self::assertEquals(13, $this->obj->getNumerator());
		
		$this->obj->setNumerator(0);
		self::assertEquals(0, $this->obj->getNumerator());
	}
	
	public function testNumeratorSpecific()
	{
		$this->obj->setNumerator(-3.89);
		self::assertEquals(-3, $this->obj->getNumerator());
		
		$this->obj->setNumerator(-1);
		self::assertEquals(-1, $this->obj->getNumerator());
	}
	
	public function testDenominator()
	{
		self::assertEquals($this->obj, $this->obj->setDenominator(11));
		self::assertEquals(11, $this->obj->getDenominator());
			
		$this->obj->setDenominator(13.3);
		self::assertEquals(13, $this->obj->getDenominator());
			
		$this->obj->setNumerator(-3.89);
		self::assertEquals($this->obj::getIsUnsigned() ? 0 : -3, $this->obj->getNumerator());
		
		$this->obj->setDenominator(-1);
		self::assertEquals($this->obj::getIsUnsigned() ? 0 : -1, $this->obj->getDenominator());
		
		$this->obj->setDenominator(0);
		self::assertEquals(0, $this->obj->getDenominator());
	}
	
	public function testDenominatorSpecific()
	{
		$this->obj->setDenominator(-3.89);
		self::assertEquals($this->obj::getIsUnsigned() ? 0 : -3, $this->obj->getDenominator());
		
		$this->obj->setDenominator(-1);
		self::assertEquals($this->obj::getIsUnsigned() ? 0 : -1, $this->obj->getDenominator());
	}
	
	public function testValue()
	{
		self::assertEquals(0, $this->obj->getNumerator());
		self::assertEquals(1, $this->obj->getDenominator());
		self::assertEquals(0, $this->obj->getValue());
			
		self::assertEquals($this->obj, $this->obj->setValue(11.0));
		self::assertEquals(11, $this->obj->getNumerator());
		self::assertEquals(1, $this->obj->getDenominator());
		self::assertEquals(11.0, $this->obj->getValue());
		
		$this->obj->setValue(0);
		self::assertEquals(0, $this->obj->getNumerator());
		self::assertEquals(1, $this->obj->getDenominator());
		self::assertEquals(0, $this->obj->getValue());
		
		$this->obj->setValue(1.0/3.0);
		self::assertEquals(1, $this->obj->getNumerator());
		self::assertEquals(3, $this->obj->getDenominator());
		self::assertEquals(0.3333333333333333, $this->obj->getValue());
		
		$phi = (1.0 + sqrt(5)) / 2.0; // The "most irrational" number.  1.61803399....
		$this->obj->setValue($phi);
		self::assertEquals(987, $this->obj->getNumerator());
		self::assertEquals(610, $this->obj->getDenominator());
		self::assertEquals(1.618032786885246, $this->obj->getValue());
			
		$this->obj->setValue($phi, 0);
		self::assertEquals(165580141, $this->obj->getNumerator());
		self::assertEquals(102334155, $this->obj->getDenominator());
		self::assertEquals(1.618033988749895, $this->obj->getValue());
		
		$this->obj->setValue(2147483647.5, 0);
		self::assertEquals(2147483647, $this->obj->getNumerator());
		self::assertEquals(1, $this->obj->getDenominator());
		self::assertEquals(2147483647.0, $this->obj->getValue());
		
		self::assertEquals($this->obj, $this->obj->setValue(['1', '0']));
		self::assertEquals(1, $this->obj->getNumerator());
		self::assertEquals(0, $this->obj->getDenominator());
		self::assertTrue(is_nan($this->obj->getValue()));
		
		$this->obj->setValue([987, 610]);
		self::assertEquals(987, $this->obj->getNumerator());
		self::assertEquals(610, $this->obj->getDenominator());
		self::assertEquals(1.618032786885246, $this->obj->getValue());
		
		$this->obj->setValue([NAN]);
		self::assertEquals(0, $this->obj->getNumerator());
		self::assertEquals(0, $this->obj->getDenominator());
		self::asserttrue(is_nan($this->obj->getValue()));
	}
	
	public function testValueSpecific()
	{
		$this->obj->setValue(-11.5);
		self::assertEquals(-23, $this->obj->getNumerator());
		self::assertEquals(2, $this->obj->getDenominator());
		self::assertEquals(-11.5, $this->obj->getValue());
		
		$this->obj->setValue(2147483646.5, 0);
		self::assertEquals(2147483646, $this->obj->getNumerator());
		self::assertEquals(1, $this->obj->getDenominator());
		self::assertEquals(2147483646.0, $this->obj->getValue());
		
		//  Set infinity
		$this->obj->setValue('-1/0');
		self::assertEquals(-1, $this->obj->getNumerator());
		self::assertEquals(0, $this->obj->getDenominator());
		self::assertEquals(INF, $this->obj->getValue());
		
		$this->obj->setValue(INF);
		self::assertEquals(-1, $this->obj->getNumerator());
		self::assertEquals(0, $this->obj->getDenominator());
		self::assertEquals(INF, $this->obj->getValue());
	}
	
	public function testToString()
	{
		$this->obj->setValue(1.5);
		self::assertEquals('3/2', (string) $this->obj);
		
		$this->obj->setValue(NAN);
		self::assertEquals('0/0', (string) $this->obj);
	}
	
	public function testToStringSpecific()
	{
		$this->obj->setValue(-1.5);
		self::assertEquals('-3/2', (string) $this->obj);
		
		$this->obj->setValue(INF);
		self::assertEquals('-1/0', (string) $this->obj);
	}
	
	public function testToArray()
	{
		$this->obj->setValue(1.5);
		self::assertEquals([3, 2], $this->obj->toArray());
		
		$this->obj->setValue(NAN);
		self::assertEquals([0, 0], $this->obj->toArray());
	}
	
	public function testToArraySpecific()
	{
		$this->obj->setValue(INF);
		self::assertEquals([-1, 0], $this->obj->toArray());
	}
	
	public function testOffsetExists()
	{
		self::assertFalse($this->obj->offsetExists(-1));
		self::assertTrue($this->obj->offsetExists(0));
		self::assertTrue($this->obj->offsetExists(1));
		self::assertFalse($this->obj->offsetExists(2));
		self::assertTrue($this->obj->offsetExists('numerator'));
		self::assertTrue($this->obj->offsetExists('denominator'));
		self::assertFalse($this->obj->offsetExists('not_a_value'));
	}
	
	public function testOffsetGet()
	{
		$this->obj->setNumerator(3);
		$this->obj->setDenominator(2);
		self::assertEquals(3, $this->obj->offsetGet(0));
		self::assertEquals(2, $this->obj->offsetGet(1));
		self::assertEquals(3, $this->obj->offsetGet('numerator'));
		self::assertEquals(2, $this->obj->offsetGet('denominator'));
		
		self::assertEquals(1.5, $this->obj->offsetGet(null));
		self::assertEquals(1.5, $this->obj[null]);
		
		self::expectException(TInvalidDataValueException::class);
		self::assertFalse($this->obj->offsetGet(2));
	}
	
	public function testOffsetSet()
	{
		$this->obj->offsetSet(0, 3);
		self::assertEquals(3, $this->obj->getNumerator());
		$this->obj->offsetSet(1, 2);
		self::assertEquals(2, $this->obj->getDenominator());
		$this->obj->offsetSet('numerator', 8);
		self::assertEquals(8, $this->obj->getNumerator());
		$this->obj->offsetSet('denominator', 5);
		self::assertEquals(5, $this->obj->getDenominator());
		
		$this->obj[0] = 13;
		self::assertEquals(13, $this->obj->getNumerator());
		
		$this->obj[] = 1.5;
		self::assertEquals(3, $this->obj->getNumerator());
		self::assertEquals(2, $this->obj->getDenominator());
		
		self::expectException(TInvalidDataValueException::class);
		self::assertFalse($this->obj->offsetSet(2, 8));
	}
	
	public function testOffsetUnset()
	{
		$this->obj->setValue([3, 2]);
		self::assertEquals(3, $this->obj->getNumerator(), "Numerator was not initialized properly");
		self::assertEquals(2, $this->obj->getDenominator(), "Denominator was not initialized properly");
			
		$this->obj->offsetUnset(0);
		self::assertEquals(0, $this->obj->getNumerator());
		$this->obj->offsetUnset(1);
		self::assertEquals(1, $this->obj->getDenominator());
			
		$this->obj->setValue([3, 2]);
		$this->obj->offsetUnset('numerator');
		self::assertEquals(0, $this->obj->getNumerator());
		$this->obj->offsetUnset('denominator');
		self::assertEquals(1, $this->obj->getDenominator());
		
		self::expectException(TInvalidDataValueException::class);
		$this->obj->offsetUnset(2);
	}
	
	public function testFloat2rational()
	{// main algorithm works.  Boundary Conditions
		$class = $this->getTestClass();
		
		self::assertEquals([-1, 0], $class::float2rational(INF));
		self::assertEquals([0, 0], $class::float2rational(NAN));
		self::assertEquals([0, 1], $class::float2rational(0.0));
		self::assertEquals([0, 1], $class::float2rational(0.5 / 2147483647.5));
		self::assertEquals([2147483647, 1], $class::float2rational(2147483648.0));
		self::assertEquals([-2147483648, 1], $class::float2rational(-2147483649.0));
		self::assertEquals([1, 2147483647], $class::float2rational(0.5 / 2147483647.0));
		self::assertEquals([1, 2147483647], $class::float2rational(1.0 / 2147483647.0));
		self::assertEquals([1, 2147483647], $class::float2rational(1.499 / 2147483647.0));
		
		$maxValue = PHP_INT_SIZE > 4 ? 4294967295 : 4294967295.0;
		self::assertEquals([$maxValue, 0], $class::float2rational(INF, null, true));
		self::assertEquals([0, 1], $class::float2rational(0.5 / 4294967295.5, null, true));
		self::assertEquals([1, $maxValue], $class::float2rational(0.5 / 4294967295.0, null, true));
		self::assertEquals([1, $maxValue], $class::float2rational(1.0 / 4294967295.0, null, true));
		self::assertEquals([1, $maxValue], $class::float2rational(1.49999 / 4294967295.0, null, true));
	}
}
