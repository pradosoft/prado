<?php

class Ticket54TestCase extends SeleniumTestCase
{
	function test()
	{
		$this->open('tickets/index.php?page=Ticket54');
		$this->verifyTextPresent("|A||B||C|", "");
	}
}

?>