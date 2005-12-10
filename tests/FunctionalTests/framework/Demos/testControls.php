<?php

class testControls extends SeleniumTestCase
{
	function setup()
	{
		$this->open('../../demos/controls/index.php');
	}

	function testIndexPage()
	{
		$this->assertTextPresent("Welcome! Guest");
		$this->clickAndWait('ctl0$header$ctl15');
		$this->assertTextPresent("Login");
	}
}