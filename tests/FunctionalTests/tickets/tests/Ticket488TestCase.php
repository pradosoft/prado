<?php

class Ticket488TestCase extends SeleniumTestCase
{
	function test()
	{
		$this->open('active-controls/index.php?page=CustomValidatorByPass');
		$this->assertTextPresent('Custom Login');
		$this->assertNotVisible('loginBox');
		$this->click("showLogin");
		$this->assertVisible("loginBox");
		$this->assertNotVisible("validator1");
		$this->assertNotVisible("validator2");

		$this->click("checkLogin");
		$this->pause(800);
		$this->assertVisible("validator1");
		$this->assertVisible("validator2");

		$this->type('Username', 'tea');
		$this->type('Password', 'mmama');

		$this->click("checkLogin");
		$this->pause(800);
		$this->assertNotVisible("validator1");
		$this->assertVisible("validator2");

		$this->type('Password', 'test');
		$this->pause(800);
		$this->assertNotVisible("validator1");
		$this->assertNotVisible("validator2");
		$this->assertNotVisible('loginBox');
	}

}

?>