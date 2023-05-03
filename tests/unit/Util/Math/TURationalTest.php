<?php

use Prado\Util\Math\TURational;

class TURationalTest extends TRationalTest
{
	public function getTestClass()
	{
		return TURational::class;
	}
	
	public function testGetIsUnsigned()
	{
		self::assertTrue($this->obj->getIsUnsigned());
	}
	
	public function testConstructSpecific()
	{
		$class = $this->getTestClass();
		
		$rational = new $class(-1.5);
		self::assertEquals(0, $rational->getNumerator());
		self::assertEquals(1, $rational->getDenominator());
		
		$rational = new $class(4294967294.0, 3);
		self::assertEquals(4294967294, $rational->getNumerator());
		self::assertEquals(3, $rational->getDenominator());
		
		$rational = new $class(INF);
		self::assertEquals(4294967295, $rational->getNumerator());
		self::assertEquals(0, $rational->getDenominator());
		
		$rational = new $class('-21/-13');
		self::assertEquals(21, $rational->getNumerator());
		self::assertEquals(13, $rational->getDenominator());
	
		$rational = new $class(['-21', '-13']);
		self::assertEquals(21, $rational->getNumerator());
		self::assertEquals(13, $rational->getDenominator());
		
		$rational = new $class([-34, 21]);
		self::assertEquals(0, $rational->getNumerator());
		self::assertEquals(1, $rational->getDenominator());
		
		$rational = new $class([34, -21]);
		self::assertEquals(0, $rational->getNumerator());
		self::assertEquals(1, $rational->getDenominator());
	}
	
	public function testNumeratorSpecific()
	{
		$this->obj->setNumerator(-3.89);
		self::assertEquals(0, $this->obj->getNumerator());
		
		$this->obj->setNumerator(-1);
		self::assertEquals(0, $this->obj->getNumerator());
	}
	
	public function testDenominatorSpecific()
	{
		$this->obj->setDenominator(-3.89);
		self::assertEquals(0, $this->obj->getDenominator());
		
		$this->obj->setDenominator(-1);
		self::assertEquals(0, $this->obj->getDenominator());
	}
	
	
	public function testValueSpecific()
	{
		$this->obj->setValue(-11.5);
		self::assertEquals(0, $this->obj->getNumerator());
		self::assertEquals(1, $this->obj->getDenominator());
		self::assertEquals(0, $this->obj->getValue());
		
		$this->obj->setValue(2147483646.5, 0);
		self::assertEquals(4294967293, $this->obj->getNumerator());
		self::assertEquals(2, $this->obj->getDenominator());
		self::assertEquals(2147483646.5, $this->obj->getValue());
		
		//  Set infinity
		$this->obj->setValue('4294967295/0');
		self::assertEquals(4294967295, $this->obj->getNumerator());
		self::assertEquals(0, $this->obj->getDenominator());
		self::assertEquals(INF, $this->obj->getValue());
		
		$this->obj->setValue(INF);
		self::assertEquals(4294967295, $this->obj->getNumerator());
		self::assertEquals(0, $this->obj->getDenominator());
		self::assertEquals(INF, $this->obj->getValue());
	}
	
	public function testToStringSpecific()
	{
		$this->obj->setValue(INF);
		self::assertEquals('4294967295/0', (string) $this->obj);
	}
	
	public function testToArraySpecific()
	{
		$this->obj->setValue(INF);
		self::assertEquals([4294967295, 0], $this->obj->toArray());
	}
}
