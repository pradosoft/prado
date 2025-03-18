<?php

class Ticket169TestCase extends \Prado\Tests\PradoGenericSelenium2Test
{
	public function test()
	{
		$this->url('tickets/index.php?page=Ticket169');
		$this->assertNotVisible('ctl0_Content_validator1');
		$this->byId('ctl0_Content_ctl0')->click();
		$this->assertVisible('ctl0_Content_validator1');
	}
}
