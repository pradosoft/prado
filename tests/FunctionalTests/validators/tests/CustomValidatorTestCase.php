<?php

//New Test
class CustomValidatorTestCase extends SeleniumTestCase
{
	function test()
	{
		$base = "ctl0_Content_";
		$this->open("validators/index.php?page=CustomValidator", "");
		$this->assertTextPresent("Prado CustomValidator Tests", "");
		$this->assertNotVisible("{$base}validator1");

		$this->click("//input[@type='submit' and @value='Test']", "");
		$this->assertVisible("{$base}validator1");
		
		$this->type("{$base}text1", "Prado");
		$this->pause(250);
		$this->assertNotVisible("{$base}validator1");
		$this->type("{$base}text1", "Testing");
		$this->pause(250);
		$this->assertVisible("{$base}validator1");
		$this->type("{$base}text1", "Prado");
		$this->pause(250);
		$this->assertNotVisible("{$base}validator1");
		$this->clickAndWait("//input[@type='submit' and @value='Test']", "");
		$this->assertNotVisible("{$base}validator1");

	}
}

?>