<?php
require_once(dirname(__FILE__).'/BaseTest.php');

/**
 * @package System.DataAccess.SQLMap
 */
class ResultClassTest extends BaseTest
{
	function __construct()
	{
		parent::__construct();
		$this->initSqlMap();
	}

	/**
	 * Test a boolean resultClass
	 */
	function testBoolean() 
	{
		$bit = $this->sqlmap->queryForObject("GetBoolean", 1);

		$this->assertEquals(true, $bit);
	}

	/**
	 * Test a boolean implicit resultClass
	 */
	function testBooleanWithoutResultClass() 
	{
		$bit = (boolean)$this->sqlmap->queryForObject("GetBooleanWithoutResultClass", 1);

		$this->assertEquals(true, $bit);
	}

	/**
	 * Test a byte resultClass
	 */
	function testByte() 
	{
		$letter = $this->sqlmap->queryForObject("GetByte", 1);

		$this->assertEquals(155, (int)$letter);
	}

	/**
	 * Test a byte implicit resultClass
	 */
	function testByteWithoutResultClass() 
	{
		$letter = $this->sqlmap->queryForObject("GetByteWithoutResultClass", 1);

		$this->assertEquals(155, (int)$letter);
	}

	/**
	 * Test a char resultClass
	 */
	function testChar() 
	{
		$letter = $this->sqlmap->queryForObject("GetChar", 1);

		$this->assertEquals('a', trim($letter));
	}

	/**
	 * Test a char implicit resultClass
	 */
	function testCharWithoutResultClass() 
	{
		$letter = $this->sqlmap->queryForObject("GetCharWithoutResultClass", 1);

		$this->assertEquals('a', trim($letter));
	}

	/**
	 * Test a DateTime resultClass
	 */
	function testDateTime() 
	{
		$orderDate = $this->sqlmap->queryForObject("GetDate", 1);

		$date = @mktime(8, 15, 00, 2, 15, 2003);

		$this->assertEquals($date, $orderDate->getTimeStamp());
	}

	/**
	 * Test a DateTime implicit resultClass
	 */
	function testDateTimeWithoutResultClass() 
	{
		$date = $this->sqlmap->queryForObject("GetDateWithoutResultClass", 1);
		$orderDate = new TDateTime;
		$orderDate->setDateTime($date);
		$date = @mktime(8, 15, 00, 2, 15, 2003);

		$this->assertEquals($date, $orderDate->getTimeStamp());
	}

	/**
	 * Test a decimal resultClass
	 */
	function testDecimal() 
	{
		$price = $this->sqlmap->queryForObject("GetDecimal", 1);

		$this->assertEquals(1.56, $price);
	}

	/**
	 * Test a decimal implicit resultClass
	 */
	function testDecimalWithoutResultClass() 
	{
		$price = $this->sqlmap->queryForObject("GetDecimalWithoutResultClass", 1);

		$this->assertEquals(1.56, (float)$price);
	}

	/**
	 * Test a double resultClass
	 */
	function testDouble() 
	{
		$price = $this->sqlmap->queryForObject("GetDouble", 1);

		$this->assertEquals(99.5, $price);
	}

	/**
	 * Test a double implicit resultClass
	 */
	 
	function testDoubleWithoutResultClass() 
	{
		$price = $this->sqlmap->queryForObject("GetDoubleWithoutResultClass", 1);

		$this->assertEquals(99.5, (float)$price);
	}

	/**
	 * IBATISNET-25 Error applying ResultMap when using 'Guid' in resultClass
	 */
/*	function testGuid() 
	{
		Guid newGuid = new Guid("CD5ABF17-4BBC-4C86-92F1-257735414CF4");

		Guid guid = (Guid) $this->sqlmap->queryForObject("GetGuid", 1);

		$this->assertEquals(newGuid, guid);
	}
*/

	/**
	 * Test a Guid implicit resultClass
	 */ 
/*	function testGuidWithoutResultClass()
	{
		Guid newGuid = new Guid("CD5ABF17-4BBC-4C86-92F1-257735414CF4");

		string guidString = Convert.ToString($this->sqlmap->queryForObject("GetGuidWithoutResultClass", 1));

		Guid guid = new Guid(guidString);

		$this->assertEquals(newGuid, guid);
	}
*/
	/**
	 * Test a int16 resultClass (integer in PHP)
	 */
	 
	function testInt16() 
	{
		$integer = $this->sqlmap->queryForObject("GetInt16", 1);

		$this->assertEquals(32111, $integer);
	}

	/**
	 * Test a int16 implicit resultClass (integer in PHP)
	 */
	 
	function testInt16WithoutResultClass() 
	{
		$integer = $this->sqlmap->queryForObject("GetInt16WithoutResultClass", 1);

		$this->assertEquals(32111, (int)$integer);
	}

	/**
	 * Test a int 32 resultClass (integer in PHP)
	 */
	 

	function testInt32() 
	{
		$integer = $this->sqlmap->queryForObject("GetInt32", 1);

		$this->assertEquals(999999, $integer);
	}

	/**
	 * Test a int 32 implicit resultClass (integer in PHP)
	 */
	 

	function testInt32WithoutResultClass() 
	{
		$integer = $this->sqlmap->queryForObject("GetInt32WithoutResultClass", 1);

		$this->assertEquals(999999, (int)$integer);
	}

	/**
	 * Test a int64 resultClass (float in PHP)
	 */
	 
	function testInt64() 
	{
		$bigInt = $this->sqlmap->queryForObject("GetInt64", 1);

		$this->assertEquals(9223372036854775800, $bigInt);
	}

	/**
	 * Test a int64 implicit resultClass (float in PHP)
	 */
	 
	function testInt64WithoutResultClass() 
	{
		$bigInt = $this->sqlmap->queryForObject("GetInt64WithoutResultClass", 1);

		$this->assertEquals(9223372036854775800, (double)$bigInt);
	}

	/**
	 * Test a single/float resultClass
	 */
	 
	function testSingle() 
	{
		$price = (float)$this->sqlmap->queryForObject("GetSingle", 1);

		$this->assertEquals(92233.5, $price);
	}

	/**
	 * Test a single/float implicit resultClass
	 */
	 
	function testSingleWithoutResultClass() 
	{
		$price = $this->sqlmap->queryForObject("GetSingleWithoutResultClass", 1);

		$this->assertEquals(92233.5, (float)$price);
	}

	/**
	 * Test a string resultClass
	 */
	 
	function testString() 
	{
		$cardType = $this->sqlmap->queryForObject("GetString", 1);

		$this->assertEquals("VISA", $cardType);
	}

	/**
	 * Test a string implicit resultClass
	 */
	 
	function testStringWithoutResultClass() 
	{
		$cardType = $this->sqlmap->queryForObject("GetStringWithoutResultClass", 1);

		$this->assertEquals("VISA", $cardType);
	}
/**/
}

?>