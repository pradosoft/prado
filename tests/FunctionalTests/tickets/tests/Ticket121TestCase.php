<?php

class Ticket121TestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$this->url('tickets/index.php?page=Ticket121');
		$this->type("ctl0\$Content\$FooTextBox", "");
		$this->assertNotVisible('ctl0_Content_ctl1');
		$this->click("//input[@type='image' and @id='ctl0_Content_ctl0']", "");
		$this->assertVisible('ctl0_Content_ctl1');
		$this->type("ctl0\$Content\$FooTextBox", "content");
		$this->clickAndWait("//input[@type='image' and @id='ctl0_Content_ctl0']", "");
		$this->assertNotVisible('ctl0_Content_ctl1');
		$this->assertTextPresent("clicked at", "");
	}
}
