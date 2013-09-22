<?php

class Ticket54TestCase extends PradoGenericSeleniumTest
{
	function test()
	{
		$this->open('tickets/index.php?page=Ticket54');
		$this->verifyTextPresent("|A|a|B|b|C|", "");
	}
}
