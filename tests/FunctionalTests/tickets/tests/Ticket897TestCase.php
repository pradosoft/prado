<?php

class Ticket897TestCase extends SeleniumTestCase
{
	function test()
	{
		$this->open('tickets/index.php?page=Ticket897');
		$this->assertTitle("Verifying Ticket 897");
		$base = 'ctl0_Content_';
		
		$this->select($base.'Date_month', 10);
		$this->select($base.'Date_day', 22);
		
		$this->clickAndWait($base.'SendButton');
		$this->assertTextPresent('2008-10-22');
	}
}

?>
