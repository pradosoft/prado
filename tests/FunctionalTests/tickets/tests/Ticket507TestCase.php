<?php

class Ticket507TestCase extends SeleniumTestCase
{
	function test()
	{
		$base='ctl0_Content_';
		$this->open('tickets/index.php?page=Ticket507');
		$this->verifyTitle("Verifying Ticket 507", "");

		$this->assertText("{$base}label1", "Label 1");

		$this->click("{$base}button1");
		$this->pause(800);

		$this->select("{$base}list1", "item 1");
		$this->pause(800);
		$this->assertText("{$base}label1", "Selection: value 1");

		$this->addSelection("{$base}list1", "item 3");

		$this->pause(800);
		$this->assertText("{$base}label1", "Selection: value 1, value 3");
	}
}

?>