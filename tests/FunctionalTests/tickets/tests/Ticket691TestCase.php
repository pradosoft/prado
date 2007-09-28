<?php
class Ticket691TestCase extends SeleniumTestCase
{
	function test()
	{
		$base = 'ctl0_Content_';
		$this->open('tickets/index.php?page=Ticket691');
		$this->assertTitle("Verifying Ticket 691");
		
		$this->click($base."List_c2");
		$this->pause(800);
		$this->assertText($base."Title", "Thanks");
		$this->assertText($base."Result", "You vote 3");
	}

}
?>