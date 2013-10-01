<?php

class Ticket622TestCase extends PradoGenericSeleniumTest
{
	function test()
	{
		$base="ctl0_Content_";
		$this->open('tickets/index.php?page=Ticket622');
		$this->assertTitle("Verifying Ticket 622");
		
		$this->click($base.'ctl0');
		$this->pause(800);
        $this->assertAttribute($base.'ALB@style',';');
        $this->assertAttribute('css=span#acb span@style', ';');
        $this->assertAttribute('css=span#arb span@style', ';');
	}
}
