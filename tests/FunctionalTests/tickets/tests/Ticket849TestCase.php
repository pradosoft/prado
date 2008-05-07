<?php

class Ticket849TestCase extends SeleniumTestCase
{
	function test()
	{
		$this->open('tickets/index.php?page=Ticket849');
		$this->assertTitle("Verifying Ticket 849");
		$base='ctl0_Content_';
		$this->click($base.'ctl0');
		$this->pause(800);
		$this->click('css=td.date.today.selected');
		$this->pause(1000);
		$this->assertValue($base.'ctl0', date('m-d-Y'));
	}
}

?>