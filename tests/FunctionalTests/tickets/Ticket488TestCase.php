<?php

class Ticket488TestCase extends \Prado\Tests\PradoGenericSelenium2Test
{
	public function test()
	{
		$base = 'ctl0_Content_';
		$this->url('active-controls/index.php?page=CustomValidatorByPass');
		$this->assertSourceContains('Custom Login');
		$this->assertNotVisible('loginBox');
		$this->byId("showLogin")->click();
		$this->assertVisible("loginBox");
		$this->assertNotVisible("{$base}validator1");
		$this->assertNotVisible("{$base}validator2");

		$this->byId("{$base}checkLogin")->click();
		$this->assertVisible("{$base}validator1");
		$this->assertNotVisible("{$base}validator2");

		$this->type("{$base}Username", 'tea');
		$this->type("{$base}Password", 'mmama');

		$this->byId("{$base}checkLogin")->click();
		$this->assertNotVisible("{$base}validator1");
		$this->assertVisible("{$base}validator2");

		$this->type("{$base}Password", 'test');
		$this->byId("{$base}checkLogin")->click();
		$this->assertNotVisible("{$base}validator1");
		$this->assertNotVisible("{$base}validator2");
	}
}
