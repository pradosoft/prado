<?php
/*
 * Created on 25/04/2006
 */

class DataTypeValidatorTestCase extends SeleniumTestCase
{
	function test()
	{
		$base = "ctl0_Content_";
		$this->open("validators/index.php?page=DataTypeValidator", "");
		$this->verifyTextPresent("Data Type Validator Tests", "");
		$this->click("//input[@type='submit' and @value='submit!']", "");
		
		$this->assertNotVisible("{$base}validator1");
		$this->assertNotVisible("{$base}validator2");
		$this->assertNotVisible("{$base}validator3");
		
		$this->type("{$base}textbox1", "a");
		$this->type("{$base}textbox2", "b");
		$this->type("{$base}textbox3", "c");
		$this->click("//input[@type='submit' and @value='submit!']", "");

		$this->assertVisible("{$base}validator1");
		$this->assertVisible("{$base}validator2");
		$this->assertVisible("{$base}validator3");
		
		$this->type("{$base}textbox1", "12");
		$this->type("{$base}textbox2", "12.5");
		$this->type("{$base}textbox3", "2/10/2005");
		$this->clickAndWait("//input[@type='submit' and @value='submit!']", "");
		
		$this->assertNotVisible("{$base}validator1");
		$this->assertNotVisible("{$base}validator2");
		$this->assertNotVisible("{$base}validator3");
		
		$this->type("{$base}textbox1", "12.2");
		$this->type("{$base}textbox2", "-12.5");
		$this->type("{$base}textbox3", "2/13/2005");
		$this->click("//input[@type='submit' and @value='submit!']", "");
		
		$this->assertVisible("{$base}validator1");
		$this->assertNotVisible("{$base}validator2");
		$this->assertVisible("{$base}validator3");
	}
		
} 

?>
