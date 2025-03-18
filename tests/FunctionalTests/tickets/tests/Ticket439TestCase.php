<?php

class Ticket439TestCase extends \Prado\Tests\PradoGenericSelenium2Test
{
	public function test()
	{
		$base = 'ctl0_Content_';
		$this->url('tickets/index.php?page=Ticket439');
		$this->assertTitle("Verifying Ticket 439");
		$this->byId("{$base}button1")->click();
		$this->assertTitle("Verifying Home");
	}
}
