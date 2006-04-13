<?php

class Ticket121TestCase extends SeleniumTestCase
{
	function test()
	{
		$this->open('tickets/index.php?page=Ticket121');
		$this->type("ctl0\$Content\$FooTextBox", "");
		$this->verifyNotVisible('ctl0_Content_ctl1');
		$this->click("//input[@type='image' and @id='ctl0_Content_ctl0']", "");
		$this->verifyVisible('ctl0_Content_ctl1');
		$this->type("ctl0\$Content\$FooTextBox", "content");
		$this->clickAndWait("//input[@type='image' and @id='ctl0_Content_ctl0']", "");
		$this->verifyNotVisible('ctl0_Content_ctl1');
		$this->verifyTextPresent("clicked at", "");
	}
}

?>