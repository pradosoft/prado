<?php

class Ticket72TestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$this->url('tickets/index.php?page=Ticket72');
		$this->type("ctl0\$Content\$K1", "abc");
		$this->type("ctl0\$Content\$K2", "efg");
		$this->byXPath("//input[@type='submit' and @value='Send']")->click();
		$this->assertContains("efg", $this->source());
		$this->assertNotContains("abcefg", $this->source());
	}
}
