<?php

class Ticket274TestCase extends PradoGenericSelenium2Test
{
	public function test()
	{
		$base = 'ctl0_Content_';
		$this->url('tickets/index.php?page=Ticket274');
		$this->assertEquals($this->title(), 'Verifying Ticket 274');
		$this->assertNotVisible($base . 'validator1');
		$this->assertNotVisible($base . 'validator2');

		$this->byId($base . 'button1')->click();
		$this->assertVisible($base . 'validator1');
		$this->assertNotVisible($base . 'validator2');

		$this->type($base . 'MyDate', 'asd');
		$this->byId($base . 'button1')->click();
		$this->assertNotVisible($base . 'validator1');
		$this->assertVisible($base . 'validator2');
	}
}
