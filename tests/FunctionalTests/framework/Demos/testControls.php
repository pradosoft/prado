<?php

class testControls extends SeleniumTestCase
{
	function setup()
	{
		$this->open('../../demos/controls/index.php');
	}

	function testControlSamples()
	{
		$this->assertTextPresent("Welcome! Guest");
		$this->clickAndWait('//input[@value="Toggle Button"]');
		$this->assertTextPresent("Login");
	}
}