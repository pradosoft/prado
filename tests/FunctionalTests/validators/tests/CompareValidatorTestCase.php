<?php

//New Test
class CompareValidatorTestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$base = "ctl0_Content_";

		$this->url("validators/index.php?page=CompareValidator");
		$this->assertTextPresent("Prado CompareValidator Tests", "");

		$this->type("{$base}text1", "qwe");
		$this->assertNotVisible("{$base}validator1");
		$this->assertNotVisible("{$base}validator2");

		$this->click("//input[@type='submit' and @value='Test']", "");

		$this->type("{$base}text2", "1234");
		$this->click("//input[@type='submit' and @value='Test']", "");
		$this->assertVisible("{$base}validator1");

		$this->type("{$base}text2", "qwe");
		$this->clickAndWait("//input[@type='submit' and @value='Test']", "");
		$this->assertNotVisible("{$base}validator1");
		$this->assertNotVisible("{$base}validator2");


		$this->type("{$base}text3", "12312");
		$this->click("//input[@type='submit' and @value='Test']", "");
		$this->pause(500);
		$this->assertVisible("{$base}validator2");

		$this->type("{$base}text3", "13/1/2005");
		$this->assertVisible("{$base}validator2");


		$this->type("{$base}text3", "12/1/2005");
		$this->clickAndWait("//input[@type='submit' and @value='Test']", "");

		$this->assertNotVisible("{$base}validator1");
		$this->assertNotVisible("{$base}validator2");

	}
}
