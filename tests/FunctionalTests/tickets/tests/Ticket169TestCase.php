<?php

class Ticket169TestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$this->url('tickets/index.php?page=Ticket169');
		$this->assertNotVisible('ctl0_Content_validator1');
		$this->click('ctl0_Content_ctl0');
		$this->assertVisible('ctl0_Content_validator1');
	}
}
