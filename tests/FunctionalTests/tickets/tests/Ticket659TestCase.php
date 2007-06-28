<?php

class Ticket659TestCase extends SeleniumTestCase
{
	function test()
	{
		$base = 'ctl0_Content_';
		// Normal component (working)
		$this->open('tickets/index.php?page=ToggleTest');
		$this->assertText("${base}lbl", "Down");
		$this->click("${base}btn", "");
		$this->pause(800);
		$this->assertText("${base}lbl", "Up");
		// Extended component (not working)
		$this->open('tickets/index.php?page=Ticket659');
		$this->assertText("${base}lbl", "Down");
		$this->click("${base}btn", "");
		$this->pause(800);
		$this->assertText("${base}lbl", "Up");
	}
}

?>