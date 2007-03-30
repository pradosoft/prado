<?php

class Ticket538TestCase extends SeleniumTestCase
{
	function test()
	{
		$base="ctl0_Content_";
		$this->open('tickets/index.php?page=Ticket538');
		$this->verifyTitle("Verifying Ticket 538", "");

		$this->assertText("{$base}ALLog", 'waiting for response...');

		$this->select("{$base}DataViewer", "empty :(");
		$this->click("{$base}selectBtn");
		$this->pause(800);

		$this->assertText("{$base}ALLog", '0,');

		$this->select("{$base}DataSelector", "select data set 2");
		$this->pause(800);
		$this->select("{$base}DataViewer", "G1: Steven=>10");
		$this->addSelection("{$base}DataViewer", "G2: Kevin=>65");

		$this->click("{$base}selectBtn");
		$this->pause(800);
		$this->assertText("{$base}ALLog", '4- "test1", 10- "test2",');
	}
}

?>