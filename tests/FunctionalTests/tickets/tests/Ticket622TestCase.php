<?php

class Ticket622TestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$base="ctl0_Content_";
		$this->url('tickets/index.php?page=Ticket622');
		$this->assertEquals($this->title(), "Verifying Ticket 622");

		$this->click($base.'ctl0');
		$this->pause(800);
        $this->verifyAttribute($base.'ALB@style','');
        $this->verifyAttribute('css=span#acb span@style', '');
        $this->verifyAttribute('css=span#arb span@style', '');
	}
}
