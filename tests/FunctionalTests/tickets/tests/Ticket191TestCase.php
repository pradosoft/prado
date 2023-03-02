<?php

class Ticket191TestCase extends PradoGenericSelenium2Test
{
	public function test()
	{
		$this->url('tickets/index.php?page=Ticket191');
		$this->type("ctl0\$Content\$TextBox2", "test");
		$this->byName("ctl0\$Content\$ctl0")->click();
		$this->pause(50);
		$this->type("ctl0\$Content\$TextBox", "test");
		$this->byName("ctl0\$Content\$ctl1")->click();
		$this->assertNotVisible('ctl0_Content_ctl2');
	}
}
