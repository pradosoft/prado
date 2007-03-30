<?php

class Ticket567TestCase extends SeleniumTestCase
{
	function test()
	{
		$this->open('tickets/index.php?page=Ticket567.Home');
		$this->verifyTitle("Verifying Ticket 567", "");
		$this->assertText("text1", "Cool Earth?");
		$this->assertText("text2", "Hot Menu.");


		$this->open('tickets/index.php?page=Ticket567.Home2');
		$this->verifyTitle("Verifying Ticket 567", "");
		$this->assertText("text1", "WOWOW");
		$this->assertText("text2", "Hot Menu.");
	}
}

?>