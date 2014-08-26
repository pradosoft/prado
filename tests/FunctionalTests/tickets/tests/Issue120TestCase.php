<?php

class Issue120TestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$this->url('tickets/index.php?page=Issue120');
		$this->assertContains('TActiveDropDownList PromptValue Test', $this->source());

		$this->assertSelectedIndex("ctl0_Content_ddl1", 0);
		$this->assertSelectedValue("ctl0_Content_ddl1", 'PromptValue');

		$this->byId("ctl0_Content_btn1")->click();
		$this->pause(800);

		$this->assertSelectedIndex("ctl0_Content_ddl1", 0);
		$this->assertSelectedValue("ctl0_Content_ddl1", 'PromptValue');
	}
}
