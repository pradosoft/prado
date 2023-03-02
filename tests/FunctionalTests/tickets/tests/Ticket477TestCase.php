<?php

class Ticket477TestCase extends PradoGenericSelenium2Test
{
	public function test()
	{
		$base = 'ctl0_Content_';
		$this->url('tickets/index.php?page=Ticket477');
		$this->assertEquals($this->title(), "Verifying Ticket 477");
		$this->assertNotVisible("{$base}validator1");
		$this->assertNotVisible("{$base}validator2");

		$this->byId("{$base}list1_c1")->click();
		$this->assertNotVisible("{$base}validator2");
		$this->assertVisible("{$base}validator1");


		$this->byId("{$base}list2_c1")->click();
		$this->assertNotVisible("{$base}validator1");
		$this->assertVisible("{$base}validator2");
	}
}
