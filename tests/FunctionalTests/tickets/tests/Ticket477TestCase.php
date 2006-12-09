<?php

class Ticket477TestCase extends SeleniumTestCase
{
	function test()
	{
		$base = 'ctl0_Content_';
		$this->open('tickets/index.php?page=Ticket477');
		$this->assertTitle("Verifying Ticket 477");
		$this->assertNotVisible("{$base}validator1");
		$this->assertNotVisible("{$base}validator2");

		$this->clickAndWait("{$base}list1_c1");
		$this->assertVisible("{$base}validator1");
		$this->assertNotVisible("{$base}validator2");


		$this->clickAndWait("{$base}list2_c1");
		$this->assertNotVisible("{$base}validator1");
		$this->assertVisible("{$base}validator2");
	}
}

?>