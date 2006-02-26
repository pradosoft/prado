<?php

class Ticket72TestCase extends SeleniumTestCase
{
	function test()
	{
		$this->open('tickets/index.php?page=Ticket72');
		$this->type("ctl0\$Content\$K1", "abc");
		$this->type("ctl0\$Content\$K2", "efg");
		$this->clickAndWait("//input[@type='submit' and @value='Send']", "");
		$this->verifyTextPresent("efg", "");
		$this->verifyTextNotPresent("abcefg", "");
	}
}

?>