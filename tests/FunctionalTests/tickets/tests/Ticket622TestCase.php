<?php

class Ticket622TestCase extends PradoGenericSelenium2Test
{
	public function test()
	{
		$base = "ctl0_Content_";
		$this->url('tickets/index.php?page=Ticket622');
		$this->assertTitle("Verifying Ticket 622");

		$this->byId($base . 'ctl0')->click();

		$this->assertEquals('', $this->byId($base . 'ALB')->getAttribute('style'));
		$this->assertEquals('', $this->byCssSelector('span#acb span')->getAttribute('style'));
		$this->assertEquals('', $this->byCssSelector('span#arb span')->getAttribute('style'));
	}
}
