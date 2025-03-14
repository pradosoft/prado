<?php

class Ticket823TestCase extends PradoGenericSelenium2Test
{
	public function test()
	{
		$this->url('tickets/index.php?page=Ticket823');
		$this->assertTitle("Verifying Ticket 823");
		$base = 'ctl0_Content_';
		$this->assertElementPresent('//option[@value=""]');
		$this->assertElementPresent('//option[.="Choose..."]');
	}
}
