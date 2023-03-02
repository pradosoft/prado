<?php

class Ticket622TestCase extends PradoGenericSelenium2Test
{
	public function test()
	{
		$base = "ctl0_Content_";
		$this->url('tickets/index.php?page=Ticket622');
		$this->assertEquals($this->title(), "Verifying Ticket 622");

		$this->byId($base . 'ctl0')->click();
		$this->pauseFairAmount();

		$this->assertEquals('', $this->byId($base . 'ALB')->attribute('style'));
		$this->assertEquals('', $this->byCssSelector('span#acb span')->attribute('style'));
		$this->assertEquals('', $this->byCssSelector('span#arb span')->attribute('style'));
	}
}
