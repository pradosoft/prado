<?php

class Ticket205TestCase extends PradoGenericSelenium2Test
{
	public function test()
	{
		$base = 'ctl0_Content_';
		$this->url("tickets/index.php?page=Ticket205");
		$this->assertEquals($this->title(), "Verifying Ticket 205");

		$validator = $this->byId("{$base}validator1");
		$this->assertFalse($validator->displayed());

		$this->type("{$base}textbox1", "test");
		$this->byId("{$base}button1")->click();
		$this->pause(100);

		$this->assertEquals("error", $this->alertText());
		$this->acceptAlert();

		$this->assertTrue($validator->displayed());

		// type() calls clear() that triggers a focus change and thus a second alert
		$this->typeSpecial("{$base}textbox1", "Prado");

		$this->byId("{$base}button1")->click();
		$this->assertNotVisible("${base}validator1");
	}
}
