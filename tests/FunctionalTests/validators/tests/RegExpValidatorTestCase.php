<?php

//New Test
class RegExpValidatorTestCase extends PradoGenericSelenium2Test
{
	public function test()
	{
		$base = "ctl0_Content_";
		$this->url("validators/index.php?page=RegularExpressionValidator");
		$this->assertSourceContains("Prado RegularExpressionValidator Tests");
		$this->assertNotVisible("{$base}validator1");
		$this->assertNotVisible("{$base}validator2");
		$this->type("{$base}text1", "1");
		$this->type("{$base}text2", "2");
		$this->byXPath("//input[@type='submit' and @value='Test']")->click();
		$this->assertVisible("{$base}validator1");
		$this->assertVisible("{$base}validator2");
		$this->type("{$base}text1", "asdasd");
		$this->byXPath("//input[@type='submit' and @value='Test']")->click();
		$this->assertVisible("{$base}validator1");
		$this->type("{$base}text1", "12345");
		$this->assertNotVisible("{$base}validator1");
		$this->assertVisible("{$base}validator2");
		$this->type("{$base}text2", "wei@gmail.com");
		$this->assertNotVisible("{$base}validator1");
		$this->assertNotVisible("{$base}validator2");
		$this->byXPath("//input[@type='submit' and @value='Test']")->click();
		$this->assertNotVisible("{$base}validator1");
		$this->assertNotVisible("{$base}validator2");
	}
}
