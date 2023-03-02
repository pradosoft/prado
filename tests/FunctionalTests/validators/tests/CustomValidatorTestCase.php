<?php

//New Test
class CustomValidatorTestCase extends PradoGenericSelenium2Test
{
	public function test()
	{
		$base = "ctl0_Content_";
		$this->url("validators/index.php?page=CustomValidator");
		$this->assertSourceContains("Prado CustomValidator Tests");
		$this->assertNotVisible("{$base}validator1");

		$this->byXPath("//input[@type='submit' and @value='Test']")->click();
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
		$this->byXPath("//input[@type='submit' and @value='Test']")->click();
		$this->assertNotVisible("{$base}validator1");
	}
}
