<?php

class RequiredFieldTestCase extends \Prado\Tests\PradoGenericSelenium2Test
{
	public function test()
	{
		$base = "ctl0_Content_";
		$this->url("validators/index.php?page=RequiredFieldValidator");
		$this->assertSourceContains("RequiredFieldValidator Tests");
		$this->assertNotVisible("{$base}validator1");
		$this->assertNotVisible("{$base}validator2");
		$this->byId("{$base}submit1")->click();
		$this->assertVisible("{$base}validator1");
		$this->assertVisible("{$base}validator2");
		$this->type("{$base}text1", "testing");
		$this->byId("{$base}submit1")->click();
		$this->assertNotVisible("{$base}validator1");
		$this->byId("{$base}submit2")->click();
		$this->assertNotVisible("{$base}validator1");
		$this->assertVisible("{$base}validator2");
		$this->assertVisible("{$base}validator3");
		$this->assertVisible("{$base}validator4");
		$this->type("{$base}text2", "testing2");
		$this->byId("{$base}submit2")->click();
		$this->assertNotVisible("{$base}validator3");
		$this->byId("{$base}submit3")->click();
		$this->assertVisible("{$base}summary3");
		$this->byId("{$base}submit4")->click();
		$this->assertNotVisible("{$base}validator1");
		$this->assertNotVisible("{$base}validator2");
		$this->assertNotVisible("{$base}validator3");
		$this->assertNotVisible("{$base}validator4");
		$this->byId("{$base}submit1")->click();
		$this->assertVisible("{$base}validator2");
		$this->byId("{$base}check1")->click();
		$this->byId("{$base}submit2")->click();
		$this->assertVisible("{$base}validator4");
		$this->byId("{$base}submit1")->click();
		$this->assertNotVisible("{$base}validator1");
		$this->assertNotVisible("{$base}validator2");
		$this->type("{$base}text1");
		$this->byId("{$base}check1")->click();
		$this->byId("{$base}submit1")->click();
		$this->assertVisible("{$base}validator1");
		$this->assertVisible("{$base}validator2");
		$this->byId("{$base}check2")->click();
		$this->byId("{$base}submit2")->click();
		$this->pause(50);

		$this->type("{$base}text1", "Hello");
		$this->byId("{$base}check1")->click();
		$this->byId("{$base}submit2")->click();

		$this->assertNotVisible("{$base}validator5");
		$this->assertNotVisible("{$base}validator6");
		$this->assertNotVisible("{$base}validator7");
		$this->assertNotVisible("{$base}validator8");
		$this->type("{$base}text1");
		$this->type("{$base}text2");
		$this->byId("{$base}check1")->click();
		$this->byId("{$base}check2")->click();
		$this->byId("{$base}submit3")->click();
		$this->assertVisible("{$base}validator5");
		$this->assertVisible("{$base}validator6");
		$this->assertVisible("{$base}validator7");
		$this->assertVisible("{$base}validator8");
		$this->byId("{$base}submit4")->click();
		$this->assertNotVisible("{$base}validator5");
		$this->assertNotVisible("{$base}validator6");
		$this->assertNotVisible("{$base}validator7");
		$this->assertNotVisible("{$base}validator8");
	}

	public function testInitialValue()
	{
		$base = "ctl0_Content_";
		$this->url("validators/index.php?page=RequiredFieldValidator");
		$this->assertSourceContains("InitialValue Test");
		$this->assertNotVisible("{$base}validator9");
		$this->byId("{$base}submit5")->click();
		$this->pause(250);
		$this->assertVisible("{$base}validator9");
		$this->type("{$base}text5", "adasd");
		$this->pause(250);
		$this->assertNotVisible("{$base}validator9");
	}
}
