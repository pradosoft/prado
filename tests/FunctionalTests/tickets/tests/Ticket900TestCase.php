<?php

class Ticket900TestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$this->url('tickets/index.php?page=Ticket900');
		$this->assertEquals($this->title(), "Verifying Ticket 900");
		$base = 'ctl0_Content_';

		$this->byName('ctl0$Content$DataGrid$ctl1$ctl3')->click();
		$this->type($base.'DataGrid_ctl1_TextBox', '');
		$this->byId($base.'DataGrid_ctl1_ctl3')->click();
		$this->byName('ctl0$Content$DataGrid$ctl1$ctl4')->click();
		$this->assertText($base.'CommandName', 'cancel');
	}
}

