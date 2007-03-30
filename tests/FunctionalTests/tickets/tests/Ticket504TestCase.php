<?php

class Ticket504TestCase extends SeleniumTestCase
{
	function test()
	{
		$base = 'ctl0_Content_';
		$this->open('tickets/index.php?page=Ticket504');
		$this->verifyTitle("Verifying Ticket 504", "");

		$this->assertText("status", "");

		$this->assertVisible("{$base}panelA");
		$this->assertVisible("{$base}panelB");
		$this->assertVisible("{$base}panelC");
		$this->assertVisible("{$base}panelD");

		$this->click("{$base}linka");
		$this->pause(800);
		$this->assertVisible("{$base}panelA");
		$this->assertNotVisible("{$base}panelB");
		$this->assertNotVisible("{$base}panelC");
		$this->assertNotVisible("{$base}panelD");
		$this->assertText("status", "panelA updated");

		$this->click("{$base}linkb");
		$this->pause(800);
		$this->assertNotVisible("{$base}panelA");
		$this->assertVisible("{$base}panelB");
		$this->assertNotVisible("{$base}panelC");
		$this->assertNotVisible("{$base}panelD");
		$this->assertText("status", "panelB updated");

		$this->click("{$base}linkc");
		$this->pause(800);
		$this->assertNotVisible("{$base}panelA");
		$this->assertNotVisible("{$base}panelB");
		$this->assertVisible("{$base}panelC");
		$this->assertNotVisible("{$base}panelD");
		$this->assertText("status", "panelC updated");

		$this->click("{$base}linkd");
		$this->pause(800);
		$this->assertNotVisible("{$base}panelA");
		$this->assertNotVisible("{$base}panelB");
		$this->assertNotVisible("{$base}panelC");
		$this->assertVisible("{$base}panelD");
		$this->assertText("status", "panelD updated");

	}
}

?>