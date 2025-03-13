<?php

require_once(__DIR__ . '/BaseCase.php');

class ResultClassTest extends BaseCase
{
	public static function setUpBeforeClass(): void
	{
		parent::setUpBeforeClass();
		self::initSqlMap();
	}

	/**
	 * Test a boolean resultClass
	 */
	public function testBoolean()
	{
		$bit = self::$sqlmap->queryForObject("GetBoolean", 1);
		$this->assertSame(true, $bit);
	}

	/**
	 * Test a boolean implicit resultClass
	 */
	public function testBooleanWithoutResultClass()
	{
		$bit = (boolean) self::$sqlmap->queryForObject("GetBooleanWithoutResultClass", 1);
		$this->assertSame(true, $bit);
	}

	/**
	 * Test a byte resultClass
	 */
	public function testByte()
	{
		$letter = self::$sqlmap->queryForObject("GetByte", 1);
		$this->assertSame(155, (int) $letter);
	}

	/**
	 * Test a byte implicit resultClass
	 */
	public function testByteWithoutResultClass()
	{
		$letter = self::$sqlmap->queryForObject("GetByteWithoutResultClass", 1);
		$this->assertSame(155, (int) $letter);
	}

	/**
	 * Test a char resultClass
	 */
	public function testChar()
	{
		$letter = self::$sqlmap->queryForObject("GetChar", 1);
		$this->assertSame('a', trim($letter));
	}

	/**
	 * Test a char implicit resultClass
	 */
	public function testCharWithoutResultClass()
	{
		$letter = self::$sqlmap->queryForObject("GetCharWithoutResultClass", 1);
		$this->assertSame('a', trim($letter));
	}

	/**
	 * Test a DateTime resultClass
	 */
	public function testDateTime()
	{
		$orderDate = self::$sqlmap->queryForObject("GetDate", 1);
		$date = @mktime(8, 15, 00, 2, 15, 2003);
		$this->assertSame($date, $orderDate->getTimeStamp());
	}

	/**
	 * Test a DateTime implicit resultClass
	 */
	public function testDateTimeWithoutResultClass()
	{
		$date = self::$sqlmap->queryForObject("GetDateWithoutResultClass", 1);
		$orderDate = new TDateTime;
		$orderDate->setDateTime($date);
		$date = @mktime(8, 15, 00, 2, 15, 2003);

		$this->assertSame($date, $orderDate->getTimeStamp());
	}

	/**
	 * Test a decimal resultClass
	 */
	public function testDecimal()
	{
		$price = self::$sqlmap->queryForObject("GetDecimal", 1);
		$this->assertSame(1.56, $price);
	}

	/**
	 * Test a decimal implicit resultClass
	 */
	public function testDecimalWithoutResultClass()
	{
		$price = self::$sqlmap->queryForObject("GetDecimalWithoutResultClass", 1);
		$this->assertSame(1.56, (float) $price);
	}

	/**
	 * Test a double resultClass
	 */
	public function testDouble()
	{
		$price = self::$sqlmap->queryForObject("GetDouble", 1);
		$this->assertSame(99.5, $price);
	}

	/**
	 * Test a double implicit resultClass
	 */
	public function testDoubleWithoutResultClass()
	{
		$price = self::$sqlmap->queryForObject("GetDoubleWithoutResultClass", 1);
		$this->assertSame(99.5, (float) $price);
	}

	/**
	 * IBATISNET-25 Error applying ResultMap when using 'Guid' in resultClass
	 */
	/*	function testGuid()
		{
			Guid newGuid = new Guid("CD5ABF17-4BBC-4C86-92F1-257735414CF4");

			Guid guid = (Guid) self::$sqlmap->queryForObject("GetGuid", 1);

			$this->assertSame(newGuid, guid);
		}
	*/

	/**
	 * Test a Guid implicit resultClass
	 */
	/*	function testGuidWithoutResultClass()
		{
			Guid newGuid = new Guid("CD5ABF17-4BBC-4C86-92F1-257735414CF4");

			string guidString = Convert.ToString(self::$sqlmap->queryForObject("GetGuidWithoutResultClass", 1));

			Guid guid = new Guid(guidString);

			$this->assertSame(newGuid, guid);
		}
	*/
	/**
	 * Test a int16 resultClass (integer in PHP)
	 */
	public function testInt16()
	{
		$integer = self::$sqlmap->queryForObject("GetInt16", 1);

		$this->assertSame(32111, $integer);
	}

	/**
	 * Test a int16 implicit resultClass (integer in PHP)
	 */
	public function testInt16WithoutResultClass()
	{
		$integer = self::$sqlmap->queryForObject("GetInt16WithoutResultClass", 1);
		$this->assertSame(32111, (int) $integer);
	}

	/**
	 * Test a int 32 resultClass (integer in PHP)
	 */
	public function testInt32()
	{
		$integer = self::$sqlmap->queryForObject("GetInt32", 1);
		$this->assertSame(999999, $integer);
	}

	/**
	 * Test a int 32 implicit resultClass (integer in PHP)
	 */
	public function testInt32WithoutResultClass()
	{
		$integer = self::$sqlmap->queryForObject("GetInt32WithoutResultClass", 1);
		$this->assertSame(999999, (int) $integer);
	}

	/**
	 * Test a int64 resultClass (float in PHP)
	 */
	public function testInt64()
	{
		$bigInt = self::$sqlmap->queryForObject("GetInt64", 1);
		$this->assertEquals(9223372036854775800, $bigInt);
	}

	/**
	 * Test a int64 implicit resultClass (float in PHP)
	 */
	public function testInt64WithoutResultClass()
	{
		$bigInt = self::$sqlmap->queryForObject("GetInt64WithoutResultClass", 1);
		$this->assertEquals(9223372036854775800, (double) $bigInt);
	}

	/**
	 * Test a single/float resultClass
	 */
	public function testSingle()
	{
		$price = (float) self::$sqlmap->queryForObject("GetSingle", 1);
		$this->assertSame(92233.5, $price);
	}

	/**
	 * Test a single/float implicit resultClass
	 */
	public function testSingleWithoutResultClass()
	{
		$price = self::$sqlmap->queryForObject("GetSingleWithoutResultClass", 1);
		$this->assertSame(92233.5, (float) $price);
	}

	/**
	 * Test a string resultClass
	 */
	public function testString()
	{
		$cardType = self::$sqlmap->queryForObject("GetString", 1);
		$this->assertSame("VISA", $cardType);
	}

	/**
	 * Test a string implicit resultClass
	 */
	public function testStringWithoutResultClass()
	{
		$cardType = self::$sqlmap->queryForObject("GetStringWithoutResultClass", 1);
		$this->assertSame("VISA", $cardType);
	}
	/**/
}
