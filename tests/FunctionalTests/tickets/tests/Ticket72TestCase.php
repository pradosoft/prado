<?php

class Ticket72TestCase extends \Prado\Tests\PradoGenericSelenium2Test
{
	public function test()
	{
		$this->url('tickets/index.php?page=Ticket72');
		$this->type("ctl0\$Content\$K1", "abc");
		$this->type("ctl0\$Content\$K2", "efg");
		$this->byXPath("//input[@type='submit' and @value='Send']")->click();
		$this->assertSourceContains("efg");
		$this->assertSourceNotContains("abcefg");
	}
}
