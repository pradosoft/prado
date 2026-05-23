<?php

class Ticket849TestCase extends \Prado\Tests\PradoGenericSelenium2Test
{
	public function test()
	{
		$this->url('tickets/index.php?page=Ticket849');
		$this->assertTitle("Verifying Ticket 849");
		$base = 'ctl0_Content_';
		$this->byId($base . 'ctl0')->click();
		$this->byCssSelector('td.date.today.selected')->click();
		$this->pause(1000);
		$this->assertValue($base . 'ctl0', date('m-d-Y'));
	}
}
