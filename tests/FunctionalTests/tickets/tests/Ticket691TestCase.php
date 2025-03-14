<?php

class Ticket691TestCase extends PradoGenericSelenium2Test
{
	public function test()
	{
		$base = 'ctl0_Content_';
		$this->url('tickets/index.php?page=Ticket691');
		$this->assertTitle("Verifying Ticket 691");

		$this->byXPath("//input[@id='{$base}List_c2']/../..")->click();
		$this->assertText("{$base}Title", "Thanks");
		$this->assertText("{$base}Result", "You vote 3");
	}
}
