<?php

class Issue120TestCase extends \Prado\Tests\PradoGenericSelenium2Test
{
	public function test()
	{
		$this->url('tickets/index.php?page=Issue120');
		$this->assertSourceContains('TActiveDropDownList PromptValue Test');

		$this->assertSelectedIndex("ctl0_Content_ddl1", 0);
		$this->assertSelectedValue("ctl0_Content_ddl1", 'PromptValue');

		$this->byId("ctl0_Content_btn1")->click();

		$this->assertSelectedIndex("ctl0_Content_ddl1", 0);
		$this->assertSelectedValue("ctl0_Content_ddl1", 'PromptValue');
	}
}
