<?php

class Ticket191TestCase extends SeleniumTestCase
{
	function test()
	{
		$this->open('tickets/index.php?page=Ticket191');
		$this->type("ctl0\$Content\$TextBox2", "test");
		$this->clickAndWait("name=ctl0\$Content\$ctl0");
		$this->type("ctl0\$Content\$TextBox", "test");
		$this->clickAndWait("name=ctl0\$Content\$ctl1");
		$this->verifyNotVisible('ctl0_Content_ctl2');
	}
}

?>