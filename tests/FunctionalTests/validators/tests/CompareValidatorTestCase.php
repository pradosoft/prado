<?php

//New Test
class CompareValidatorTestCase extends PradoGenericSelenium2Test
{
	public function test()
	{
		$base = "ctl0_Content_";

		$this->url("validators/index.php?page=CompareValidator");
		$this->assertSourceContains("Prado CompareValidator Tests");

		$this->type("{$base}text1", "qwe");
		$this->assertNotVisible("{$base}validator1");
		$this->assertNotVisible("{$base}validator2");

		$this->byXPath("//input[@type='submit' and @value='Test']")->click();

		$this->type("{$base}text2", "1234");
		$this->byXPath("//input[@type='submit' and @value='Test']")->click();
		$this->assertVisible("{$base}validator1");

		$this->type("{$base}text2", "qwe");
		$this->byXPath("//input[@type='submit' and @value='Test']")->click();
		$this->assertNotVisible("{$base}validator1");
		$this->assertNotVisible("{$base}validator2");


		$this->type("{$base}text3", "12312");
		$this->byXPath("//input[@type='submit' and @value='Test']")->click();
		$this->pause(500);
		$this->assertVisible("{$base}validator2");

		$this->type("{$base}text3", "13/1/2005");
		$this->assertVisible("{$base}validator2");


		$this->type("{$base}text3", "12/1/2005");
		$this->byXPath("//input[@type='submit' and @value='Test']")->click();

		$this->assertNotVisible("{$base}validator1");
		$this->assertNotVisible("{$base}validator2");
	}
}
