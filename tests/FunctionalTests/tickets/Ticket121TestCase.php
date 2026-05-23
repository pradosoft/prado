<?php

class Ticket121TestCase extends \Prado\Tests\PradoGenericSelenium2Test
{
	public function test()
	{
		$this->url('tickets/index.php?page=Ticket121');
		$this->type("ctl0\$Content\$FooTextBox", "");
		$this->assertNotVisible('ctl0_Content_ctl1');
		$this->byXPath("//input[@type='image' and @id='ctl0_Content_ctl0']")->click();
		$this->assertVisible('ctl0_Content_ctl1');
		$this->type("ctl0\$Content\$FooTextBox", "content");
		$this->byXPath("//input[@type='image' and @id='ctl0_Content_ctl0']")->click();
		$this->assertNotVisible('ctl0_Content_ctl1');
		$this->assertSourceContains("clicked at");
	}
}
