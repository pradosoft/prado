<?php

//New Test
class RangeValidatorTestCase extends SeleniumTestCase
{
	function testIntegerRange()
	{
		$base = "ctl0_Content_";
		$this->open("validators/index.php?page=RangeValidatorInteger", "");
		$this->verifyTextPresent("Prado RangeValidator Tests Integer", "");
		
		//between 1 and 4
		$this->type("{$base}text1", "ad");
		$this->assertNotVisible("{$base}validator1", "");
		$this->click("//input[@type='submit' and @value='Test']", "");
		$this->assertVisible("{$base}validator1", "");
		$this->type("{$base}text1", "12");
		$this->click("//input[@type='submit' and @value='Test']", "");
		$this->assertVisible("{$base}validator1", "");
		$this->type("{$base}text1", "2");
		$this->assertNotVisible("{$base}validator1", "");
		$this->clickAndWait("//input[@type='submit' and @value='Test']", "");
		$this->assertNotVisible("{$base}validator1", "");
		
		
		// >= 2
		$this->assertNotVisible("{$base}validator2", "");
		$this->type("{$base}text2", "1");
		$this->click("//input[@type='submit' and @value='Test']", "");
		$this->assertVisible("{$base}validator2", "");
		$this->type("{$base}text2", "10");
		$this->clickAndWait("//input[@type='submit' and @value='Test']", "");
		$this->assertNotVisible("{$base}validator2", "");

		// <= 20
		$this->assertNotVisible("{$base}validator3", "");
		$this->type("{$base}text3", "100");
		$this->click("//input[@type='submit' and @value='Test']", "");
		$this->assertVisible("{$base}validator3", "");
		$this->type("{$base}text3", "10");
		$this->clickAndWait("//input[@type='submit' and @value='Test']", "");
		$this->assertNotVisible("{$base}validator3", "");

	}
	
	function testFloatRange()
	{
		$base = "ctl0_Content_";
		$this->open("validators/index.php?page=RangeValidatorFloat", "");
		$this->verifyTextPresent("Prado RangeValidator Tests Float", "");
		
		//between 1 and 4
		$this->type("{$base}text1", "ad");
		$this->assertNotVisible("{$base}validator1", "");
		$this->click("//input[@type='submit' and @value='Test']", "");
		$this->assertVisible("{$base}validator1", "");
		$this->type("{$base}text1", "12");
		$this->click("//input[@type='submit' and @value='Test']", "");
		$this->assertVisible("{$base}validator1", "");
		$this->type("{$base}text1", "2");
		$this->assertNotVisible("{$base}validator1", "");
		$this->clickAndWait("//input[@type='submit' and @value='Test']", "");
		$this->assertNotVisible("{$base}validator1", "");
		
		
		// >= 2
		$this->assertNotVisible("{$base}validator2", "");
		$this->type("{$base}text2", "1");
		$this->click("//input[@type='submit' and @value='Test']", "");
		$this->assertVisible("{$base}validator2", "");
		$this->type("{$base}text2", "10");
		$this->clickAndWait("//input[@type='submit' and @value='Test']", "");
		$this->assertNotVisible("{$base}validator2", "");

		// <= 20
		$this->assertNotVisible("{$base}validator3", "");
		$this->type("{$base}text3", "100");
		$this->click("//input[@type='submit' and @value='Test']", "");
		$this->assertVisible("{$base}validator3", "");
		$this->type("{$base}text3", "10");
		$this->clickAndWait("//input[@type='submit' and @value='Test']", "");
		$this->assertNotVisible("{$base}validator3", "");
	}
	
	function testDateRange()
	{
		$base = "ctl0_Content_";
		$this->open("validators/index.php?page=RangeValidatorDate", "");
		$this->verifyTextPresent("Prado RangeValidator Tests Date", "");
		
		//between 22/1/2005 and 3/2/2005
		$this->type("{$base}text1", "ad");
		$this->assertNotVisible("{$base}validator1", "");
		$this->click("//input[@type='submit' and @value='Test']", "");
		$this->assertVisible("{$base}validator1", "");
		$this->type("{$base}text1", "27/2/2005");
		$this->click("//input[@type='submit' and @value='Test']", "");
		$this->assertVisible("{$base}validator1", "");
		$this->type("{$base}text1", "1/2/2005");
		$this->click("//input[@type='submit' and @value='Test']", "");
		$this->assertNotVisible("{$base}validator1", "");
		$this->clickAndWait("//input[@type='submit' and @value='Test']", "");
		$this->assertNotVisible("{$base}validator1", "");
		
		
		// >= 22/1/2005
		$this->assertNotVisible("{$base}validator2", "");
		$this->type("{$base}text2", "1/1/2005");
		$this->click("//input[@type='submit' and @value='Test']", "");
		$this->pause(250);
		$this->assertVisible("{$base}validator2", "");
		$this->type("{$base}text2", "1/4/2005");
		$this->clickAndWait("//input[@type='submit' and @value='Test']", "");
		$this->assertNotVisible("{$base}validator2", "");

		// <= 3/2/2005
		$this->assertNotVisible("{$base}validator3", "");
		$this->type("{$base}text3", "4/5/2005");
		$this->click("//input[@type='submit' and @value='Test']", "");
		$this->pause(250);
		$this->assertVisible("{$base}validator3", "");
		$this->type("{$base}text3", "1/2/2005");
		$this->clickAndWait("//input[@type='submit' and @value='Test']", "");
		$this->assertNotVisible("{$base}validator3", "");
	}	
	
	function testStringRange()
	{
		$base = "ctl0_Content_";
		$this->open("validators/index.php?page=RangeValidatorString", "");
		$this->verifyTextPresent("Prado RangeValidator Tests String", "");
		
		//between 'd' and 'y'
		$this->type("{$base}text1", "a");
		$this->assertNotVisible("{$base}validator1", "");
		$this->click("//input[@type='submit' and @value='Test']", "");
		$this->assertVisible("{$base}validator1", "");
		$this->type("{$base}text1", "b");
		$this->click("//input[@type='submit' and @value='Test']", "");
		$this->assertVisible("{$base}validator1", "");
		$this->type("{$base}text1", "f");
		$this->assertNotVisible("{$base}validator1", "");
		$this->clickAndWait("//input[@type='submit' and @value='Test']", "");
		$this->assertNotVisible("{$base}validator1", "");
		
		
		// >= 'd'
		$this->assertNotVisible("{$base}validator2", "");
		$this->type("{$base}text2", "a");
		$this->click("//input[@type='submit' and @value='Test']", "");
		$this->assertVisible("{$base}validator2", "");
		$this->type("{$base}text2", "g");
		$this->clickAndWait("//input[@type='submit' and @value='Test']", "");
		$this->assertNotVisible("{$base}validator2", "");

		// <= 'y'
		$this->assertNotVisible("{$base}validator3", "");
		$this->type("{$base}text3", "z");
		$this->click("//input[@type='submit' and @value='Test']", "");
		$this->assertVisible("{$base}validator3", "");
		$this->type("{$base}text3", "t");
		$this->clickAndWait("//input[@type='submit' and @value='Test']", "");
		$this->assertNotVisible("{$base}validator3", "");
	}		
}

?>