<?php

class Ticket488TestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$base='ctl0_Content_';
		$this->url('active-controls/index.php?page=CustomValidatorByPass');
		$this->assertTextPresent('Custom Login');
		$this->assertNotVisible('loginBox');
		$this->click("showLogin");
		$this->assertVisible("loginBox");
		$this->assertNotVisible("{$base}validator1");
		$this->assertNotVisible("{$base}validator2");

		$this->click("{$base}checkLogin");
		$this->pause(800);
		$this->assertVisible("{$base}validator1");
		$this->assertNotVisible("{$base}validator2");

		$this->type("{$base}Username", 'tea');
		$this->type("{$base}Password", 'mmama');

		$this->click("{$base}checkLogin");
		$this->pause(800);
		$this->assertNotVisible("{$base}validator1");
		$this->assertVisible("{$base}validator2");

		$this->type("{$base}Password", 'test');
		$this->click("{$base}checkLogin");
		$this->pause(800);
		$this->assertNotVisible("{$base}validator1");
		$this->assertNotVisible("{$base}validator2");
	}

	function test_more()
	{
		$this->url('tickets/index.php?page=Ticket488');
		//add test assertions here.
	}
}
