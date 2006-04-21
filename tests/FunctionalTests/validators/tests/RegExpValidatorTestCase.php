<?php

//New Test
class RegExpValidatorTestCase extends SeleniumTestCase
{
	function test()
	{
		$base = "ctl0_Content_";
		$this->open("validators/index.php?page=RegularExpressionValidator", "");
		$this->verifyTextPresent("Prado RegularExpressionValidator Tests", "");
		$this->assertNotVisible("{$base}validator1", "");
		$this->assertNotVisible("{$base}validator2", "");
		$this->type("{$base}text1", "1");
		$this->type("{$base}text2", "2");
		$this->click("//input[@type='submit' and @value='Test']", "");
		$this->assertVisible("{$base}validator1", "");
		$this->assertVisible("{$base}validator2", "");
		$this->type("{$base}text1", "asdasd");
		$this->click("//input[@type='submit' and @value='Test']", "");
		$this->assertVisible("{$base}validator1", "");
		$this->type("{$base}text1", "12345");
		$this->assertNotVisible("{$base}validator1", "");
		$this->assertVisible("{$base}validator2", "");
		$this->type("{$base}text2", "wei@gmail.com");
		$this->assertNotVisible("{$base}validator1", "");
		$this->assertNotVisible("{$base}validator2", "");
		$this->clickAndWait("//input[@type='submit' and @value='Test']", "");
		$this->assertNotVisible("{$base}validator1", "");
		$this->assertNotVisible("{$base}validator2", "");

	}
}

?>