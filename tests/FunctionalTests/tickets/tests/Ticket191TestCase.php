<?php

class Ticket191TestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$this->url('tickets/index.php?page=Ticket191');
		$this->type("ctl0\$Content\$TextBox2", "test");
		$this->clickAndWait("name=ctl0\$Content\$ctl0");
		$this->type("ctl0\$Content\$TextBox", "test");
		$this->clickAndWait("name=ctl0\$Content\$ctl1");
		$this->assertNotVisible('ctl0_Content_ctl2');
	}
}
