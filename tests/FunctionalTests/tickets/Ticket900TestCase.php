<?php

class Ticket900TestCase extends \Prado\Tests\PradoGenericSelenium2Test
{
	public function test()
	{
		$this->url('tickets/index.php?page=Ticket900');
		$this->assertTitle("Verifying Ticket 900");
		$base = 'ctl0_Content_';

		$this->byName('ctl0$Content$DataGrid$ctl1$ctl3')->click();
		$this->pause(50);
		$this->type($base . 'DataGrid_ctl1_TextBox', '');
		$this->byId($base . 'DataGrid_ctl1_ctl3')->click();
		$this->pause(50);
		$this->byName('ctl0$Content$DataGrid$ctl1$ctl4')->click();
		$this->pause(50);
		$this->assertText($base . 'CommandName', 'cancel');
	}
}
