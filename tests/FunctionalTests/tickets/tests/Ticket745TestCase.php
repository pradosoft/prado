<?php
class Ticket745TestCase extends SeleniumTestCase
{
	function test()
	{
		$base = 'ctl0_Content_';
		$this->open('tickets/index.php?page=Ticket745');
		$this->assertTitle("Verifying Ticket 745");
		
		$this->select($base.'Wizard1_DropDownList1', 'Green');
		$this->click($base.'Wizard1_ctl4_ctl1');
		$this->pause(800);
		$this->assertTextPresent ('Step 3 of 3');
		
	}

}
?>