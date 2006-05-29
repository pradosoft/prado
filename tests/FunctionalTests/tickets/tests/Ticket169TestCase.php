<?php

class Ticket169TestCase extends SeleniumTestCase
{
	function test()
	{
		$this->open('tickets/index.php?page=Ticket169');
		$this->assertNotVisible('ctl0_Content_validator1');
		$this->click('ctl0_Content_ctl0');
		$this->assertVisible('ctl0_Content_validator1');
	}
}

?>