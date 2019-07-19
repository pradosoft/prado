<?php

class Ticket278TestCase extends PradoGenericSelenium2Test
{
	public function test()
	{
		$base = 'ctl0_Content_';
		$this->url('tickets/index.php?page=Ticket278');
		$this->assertEquals($this->title(), 'Verifying Ticket 278');
		$this->assertNotVisible($base . 'validator1');
		$this->assertNotVisible($base . 'validator2');
		$this->assertNotVisible($base . 'panel1');

		$this->byId($base . 'button1')->click();
		$this->assertVisible($base . 'validator1');
		$this->assertNotVisible($base . 'validator2');

		$this->type($base . 'text1', 'asd');
		$this->byId($base . 'button1')->click();
		$this->assertNotVisible($base . 'validator1');
		$this->assertNotVisible($base . 'validator2');
		$this->assertNotVisible($base . 'panel1');

		$this->byId($base . 'check1')->click();
		$this->byId($base . 'button1')->click();
		$this->assertNotVisible($base . 'validator1');
		$this->assertVisible($base . 'validator2');
		$this->assertVisible($base . 'panel1');


		$this->type($base . 'text1', '');
		$this->type($base . 'text2', 'asd');
		$this->byId($base . 'button1')->click();
		$this->assertVisible($base . 'validator1');
		$this->assertNotVisible($base . 'validator2');
		$this->assertVisible($base . 'panel1');


		$this->type($base . 'text1', 'asd');
		$this->byId($base . 'button1')->click();
		$this->assertNotVisible($base . 'validator1');
		$this->assertNotVisible($base . 'validator2');
		$this->assertVisible($base . 'panel1');

		$this->type($base . 'text1', '');
		$this->type($base . 'text2', '');
		$this->byId($base . 'button1')->click();
		$this->assertVisible($base . 'validator1');
		$this->assertVisible($base . 'validator2');
		$this->assertVisible($base . 'panel1');
	}
}
