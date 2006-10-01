<?php

class RequiredFieldTestCase extends SeleniumTestCase
{
	function test()
	{
		//problem with test runner clicking on radio buttons
		$this->skipBrowsers(self::OPERA);

		$base = "ctl0_Content_";
		$this->open("validators/index.php?page=RequiredFieldValidator");
		$this->assertTextPresent("RequiredFieldValidator Tests");
		$this->assertNotVisible("{$base}validator1");
		$this->assertNotVisible("{$base}validator2");
		$this->click("{$base}submit1");
		$this->assertVisible("{$base}validator1");
		$this->assertVisible("{$base}validator2");
		$this->type("{$base}text1", "testing");
		$this->click("{$base}submit1");
		$this->assertNotVisible("{$base}validator1");
		$this->click("{$base}submit2");
		$this->assertNotVisible("{$base}validator1");
		$this->assertNotVisible("{$base}validator2");
		$this->assertVisible("{$base}validator3");
		$this->assertVisible("{$base}validator4");
		$this->type("{$base}text2", "testing2");
		$this->click("{$base}submit2");
		$this->assertNotVisible("{$base}validator3");
		$this->click("{$base}submit3");
		$this->assertVisible("{$base}summary3");
		$this->clickAndWait("{$base}submit4");
		$this->assertNotVisible("{$base}validator1");
		$this->assertNotVisible("{$base}validator2");
		$this->assertNotVisible("{$base}validator3");
		$this->assertNotVisible("{$base}validator4");
		$this->click("{$base}submit1");
		$this->assertVisible("{$base}validator2");
		$this->click("{$base}check1");
		$this->click("{$base}submit2");
		$this->assertVisible("{$base}validator4");
		$this->clickAndWait("{$base}submit1");
		$this->assertNotVisible("{$base}validator1");
		$this->assertNotVisible("{$base}validator2");
		$this->type("{$base}text1");
		$this->click("{$base}check1");
		$this->click("{$base}submit1");
		$this->assertVisible("{$base}validator1");
		$this->assertVisible("{$base}validator2");
		$this->click("{$base}check2");
		$this->clickAndWait("{$base}submit2");

		$this->type("{$base}text1", "Hello");
		$this->click("{$base}check1");
		$this->click("{$base}submit2");

		$this->assertNotVisible("{$base}validator5");
		$this->assertNotVisible("{$base}validator6");
		$this->assertNotVisible("{$base}validator7");
		$this->assertNotVisible("{$base}validator8");
		$this->type("{$base}text1");
		$this->type("{$base}text2");
		$this->click("{$base}check1");
		$this->click("{$base}check2");
		$this->click("{$base}submit3");
		$this->assertVisible("{$base}validator5");
		$this->assertVisible("{$base}validator6");
		$this->assertVisible("{$base}validator7");
		$this->assertVisible("{$base}validator8");
		$this->clickAndWait("{$base}submit4");
		$this->assertNotVisible("{$base}validator5");
		$this->assertNotVisible("{$base}validator6");
		$this->assertNotVisible("{$base}validator7");
		$this->assertNotVisible("{$base}validator8");
	}

	function testInitialValue()
	{
		$base = "ctl0_Content_";
		$this->open("validators/index.php?page=RequiredFieldValidator");
		$this->assertTextPresent("InitialValue Test");
		$this->assertNotVisible("{$base}validator9");
		$this->click("{$base}submit5");
		$this->pause(250);
		$this->assertVisible("{$base}validator9");
		$this->type("{$base}text5", "adasd");
		$this->pause(250);
		$this->assertNotVisible("{$base}validator9");
	}
}
?>