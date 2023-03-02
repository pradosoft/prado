<?php

//New Test
class RangeValidatorTestCase extends PradoGenericSelenium2Test
{
	public function testIntegerRange()
	{
		$base = "ctl0_Content_";
		$this->url("validators/index.php?page=RangeValidatorInteger");
		$this->assertSourceContains("Prado RangeValidator Tests Integer");

		//between 1 and 4
		$this->type("{$base}text1", "ad");
		$this->assertNotVisible("{$base}validator1");
		$this->byXPath("//input[@type='submit' and @value='Test']")->click();
		$this->assertVisible("{$base}validator1");
		$this->type("{$base}text1", "12");
		$this->byXPath("//input[@type='submit' and @value='Test']")->click();
		$this->assertVisible("{$base}validator1");
		$this->type("{$base}text1", "2");
		$this->assertNotVisible("{$base}validator1");
		$this->byXPath("//input[@type='submit' and @value='Test']")->click();
		$this->assertNotVisible("{$base}validator1");


		// >= 2
		$this->assertNotVisible("{$base}validator2");
		$this->type("{$base}text2", "1");
		$this->byXPath("//input[@type='submit' and @value='Test']")->click();
		$this->assertVisible("{$base}validator2");
		$this->type("{$base}text2", "10");
		$this->byXPath("//input[@type='submit' and @value='Test']")->click();
		$this->assertNotVisible("{$base}validator2");

		// <= 20
		$this->assertNotVisible("{$base}validator3");
		$this->type("{$base}text3", "100");
		$this->byXPath("//input[@type='submit' and @value='Test']")->click();
		$this->assertVisible("{$base}validator3");
		$this->type("{$base}text3", "10");
		$this->byXPath("//input[@type='submit' and @value='Test']")->click();
		$this->assertNotVisible("{$base}validator3");
	}

	public function testFloatRange()
	{
		$base = "ctl0_Content_";
		$this->url("validators/index.php?page=RangeValidatorFloat");
		$this->assertSourceContains("Prado RangeValidator Tests Float");

		//between 1 and 4
		$this->type("{$base}text1", "ad");
		$this->assertNotVisible("{$base}validator1");
		$this->byXPath("//input[@type='submit' and @value='Test']")->click();
		$this->assertVisible("{$base}validator1");
		$this->type("{$base}text1", "12");
		$this->byXPath("//input[@type='submit' and @value='Test']")->click();
		$this->assertVisible("{$base}validator1");
		$this->type("{$base}text1", "2");
		$this->assertNotVisible("{$base}validator1");
		$this->byXPath("//input[@type='submit' and @value='Test']")->click();
		$this->assertNotVisible("{$base}validator1");


		// >= 2
		$this->assertNotVisible("{$base}validator2");
		$this->type("{$base}text2", "1");
		$this->byXPath("//input[@type='submit' and @value='Test']")->click();
		$this->assertVisible("{$base}validator2");
		$this->type("{$base}text2", "10");
		$this->byXPath("//input[@type='submit' and @value='Test']")->click();
		$this->assertNotVisible("{$base}validator2");

		// <= 20
		$this->assertNotVisible("{$base}validator3");
		$this->type("{$base}text3", "100");
		$this->byXPath("//input[@type='submit' and @value='Test']")->click();
		$this->assertVisible("{$base}validator3");
		$this->type("{$base}text3", "10");
		$this->byXPath("//input[@type='submit' and @value='Test']")->click();
		$this->assertNotVisible("{$base}validator3");
	}

	public function testDateRange()
	{
		$base = "ctl0_Content_";
		$this->url("validators/index.php?page=RangeValidatorDate");
		$this->assertSourceContains("Prado RangeValidator Tests Date");

		//between 22/1/2005 and 3/2/2005
		$this->type("{$base}text1", "ad");
		$this->assertNotVisible("{$base}validator1");
		$this->byXPath("//input[@type='submit' and @value='Test']")->click();
		$this->assertVisible("{$base}validator1");
		$this->type("{$base}text1", "27/2/2005");
		$this->byXPath("//input[@type='submit' and @value='Test']")->click();
		$this->assertVisible("{$base}validator1");
		$this->type("{$base}text1", "1/2/2005");
		$this->byXPath("//input[@type='submit' and @value='Test']")->click();
		$this->assertNotVisible("{$base}validator1");
		$this->byXPath("//input[@type='submit' and @value='Test']")->click();
		$this->assertNotVisible("{$base}validator1");


		// >= 22/1/2005
		$this->assertNotVisible("{$base}validator2");
		$this->type("{$base}text2", "1/1/2005");
		$this->byXPath("//input[@type='submit' and @value='Test']")->click();
		$this->assertVisible("{$base}validator2");
		$this->type("{$base}text2", "1/4/2005");
		$this->byXPath("//input[@type='submit' and @value='Test']")->click();
		$this->assertNotVisible("{$base}validator2");

		// <= 3/2/2005
		$this->assertNotVisible("{$base}validator3");
		$this->type("{$base}text3", "4/5/2005");
		$this->byXPath("//input[@type='submit' and @value='Test']")->click();
		$this->assertVisible("{$base}validator3");
		$this->type("{$base}text3", "1/2/2005");
		$this->byXPath("//input[@type='submit' and @value='Test']")->click();
		$this->assertNotVisible("{$base}validator3");
	}

	public function testStringRange()
	{
		$base = "ctl0_Content_";
		$this->url("validators/index.php?page=RangeValidatorString");
		$this->assertSourceContains("Prado RangeValidator Tests String");

		//between 'd' and 'y'
		$this->type("{$base}text1", "a");
		$this->assertNotVisible("{$base}validator1");
		$this->byXPath("//input[@type='submit' and @value='Test']")->click();
		$this->assertVisible("{$base}validator1");
		$this->type("{$base}text1", "b");
		$this->byXPath("//input[@type='submit' and @value='Test']")->click();
		$this->assertVisible("{$base}validator1");
		$this->type("{$base}text1", "f");
		$this->assertNotVisible("{$base}validator1");
		$this->pause(50);
		$this->byXPath("//input[@type='submit' and @value='Test']")->click();
		$this->pause(50);
		$this->assertNotVisible("{$base}validator1");


		// >= 'd'
		$this->assertNotVisible("{$base}validator2");
		$this->type("{$base}text2", "a");
		$this->byXPath("//input[@type='submit' and @value='Test']")->click();
		$this->assertVisible("{$base}validator2");
		$this->type("{$base}text2", "g");
		$this->byXPath("//input[@type='submit' and @value='Test']")->click();
		$this->assertNotVisible("{$base}validator2");

		// <= 'y'
		$this->assertNotVisible("{$base}validator3");
		$this->type("{$base}text3", "z");
		$this->byXPath("//input[@type='submit' and @value='Test']")->click();
		$this->assertVisible("{$base}validator3");
		$this->type("{$base}text3", "t");
		$this->byXPath("//input[@type='submit' and @value='Test']")->click();
		$this->assertNotVisible("{$base}validator3");
	}
}
