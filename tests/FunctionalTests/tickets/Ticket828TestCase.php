<?php

class Ticket828TestCase extends \Prado\Tests\PradoGenericSelenium2Test
{
	public function test()
	{
		$base = "ctl0_Content_";
		$this->url("tickets/index.php?page=Ticket828");
		$this->byId("{$base}submit1")->click();
		$this->assertVisible("{$base}validator1");
		$this->assertVisible("{$base}validator2");
		$this->assertVisible("{$base}validator3");
		$this->byId("{$base}list1_c0")->click();
		$this->addSelection("{$base}list2", "One");
		$this->addSelection("{$base}list2", "Two");
		$this->byId("{$base}list3_c3")->click();
		$this->byId("{$base}submit1")->click();
		$this->assertNotVisible("{$base}validator1");
		$this->assertNotVisible("{$base}validator2");
		$this->assertNotVisible("{$base}validator3");
		$this->byId("{$base}list1_c1")->click();
		$this->byId("{$base}list1_c2")->click();
		$this->byId("{$base}list1_c3")->click();
		$this->addSelection("{$base}list2", "Two");
		$this->byId("{$base}list1_c3")->click();
		$this->byId("{$base}submit1")->click();
		$this->assertNotVisible("{$base}validator1");
		$this->assertNotVisible("{$base}validator2");
		$this->assertNotVisible("{$base}validator3");
		$this->byId("{$base}list3_c3")->click();
		$this->byId("{$base}submit1")->click();
		$this->pause(200);
		$this->assertNotVisible("{$base}validator1");
		$this->assertNotVisible("{$base}validator2");
		$this->assertNotVisible("{$base}validator3");
	}
}
