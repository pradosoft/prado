<?php

class Ticket897TestCase extends PradoGenericSeleniumTest
{
	function test()
	{
		$this->open('tickets/index.php?page=Ticket897');
		$this->assertTitle("Verifying Ticket 897");
		$base = 'ctl0_Content_';
		
		$this->select($base.'Date_month', 10);
		$this->select($base.'Date_day', 22);
		
		$this->clickAndWait($base.'SendButton');
		$this->assertTextPresent(date('Y').'-10-22');
	}
}

