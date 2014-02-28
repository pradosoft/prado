<?php

class Ticket72TestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$this->url('tickets/index.php?page=Ticket72');
		$this->type("ctl0\$Content\$K1", "abc");
		$this->type("ctl0\$Content\$K2", "efg");
		$this->clickAndWait("//input[@type='submit' and @value='Send']", "");
		$this->assertTextPresent("efg", "");
		$this->assertTextNotPresent("abcefg", "");
	}
}
