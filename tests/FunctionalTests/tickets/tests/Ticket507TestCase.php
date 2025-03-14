<?php

class Ticket507TestCase extends PradoGenericSelenium2Test
{
	public function test()
	{
		$base = 'ctl0_Content_';
		$this->url('tickets/index.php?page=Ticket507');
		$this->assertTitle("Verifying Ticket 507");

		$this->assertText("{$base}label1", "Label 1");

		$this->byId("{$base}button1")->click();

		$this->select("{$base}list1", "item 1");
		$this->assertText("{$base}label1", "Selection: value 1");

		$this->addSelection("{$base}list1", "item 3");

		$this->assertText("{$base}label1", "Selection: value 1, value 3");
	}
}
