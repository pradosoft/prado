<?php

class Ticket278TestCase extends SeleniumTestCase
{
	function test()
	{
		//problem with test runner clicking on radio buttons
		$this->skipBrowsers(self::OPERA);

		$base = 'ctl0_Content_';
		$this->open('tickets/index.php?page=Ticket278');
		$this->assertTitle('Verifying Ticket 278');
		$this->assertNotVisible($base.'validator1');
		$this->assertNotVisible($base.'validator2');
		$this->assertNotVisible($base.'panel1');

		$this->click($base.'button1');
		$this->assertVisible($base.'validator1');
		$this->assertNotVisible($base.'validator2');

		$this->type($base.'text1', 'asd');
		$this->clickAndWait($base.'button1');
		$this->assertNotVisible($base.'validator1');
		$this->assertNotVisible($base.'validator2');
		$this->assertNotVisible($base.'panel1');

		$this->click($base.'check1');
		$this->click($base.'button1');
		$this->assertNotVisible($base.'validator1');
		$this->assertVisible($base.'validator2');
		$this->assertVisible($base.'panel1');


		$this->type($base.'text1', '');
		$this->type($base.'text2', 'asd');
		$this->click($base.'button1');
		$this->assertVisible($base.'validator1');
		$this->assertNotVisible($base.'validator2');
		$this->assertVisible($base.'panel1');


		$this->type($base.'text1', 'asd');
		$this->clickAndWait($base.'button1');
		$this->assertNotVisible($base.'validator1');
		$this->assertNotVisible($base.'validator2');
		$this->assertVisible($base.'panel1');

		$this->type($base.'text1', '');
		$this->type($base.'text2', '');
		$this->click($base.'button1');
		$this->assertVisible($base.'validator1');
		$this->assertVisible($base.'validator2');
		$this->assertVisible($base.'panel1');
	}
}

?>