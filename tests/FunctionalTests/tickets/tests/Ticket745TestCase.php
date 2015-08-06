<?php
class Ticket745TestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$base = 'ctl0_Content_';
		$this->url('tickets/index.php?page=Ticket745');
		$this->assertEquals($this->title(), "Verifying Ticket 745");

		$this->select($base.'Wizard1_DropDownList1', 'Green');
		$this->byId($base.'Wizard1_ctl4_ctl1')->click();
		$this->pause(800);
		$this->assertSourceContains('Step 3 of 3');
	}

}