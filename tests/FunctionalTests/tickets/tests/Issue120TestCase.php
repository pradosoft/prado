<?php

class Issue120TestCase extends SeleniumTestCase
{
	function test()
	{
		$this->open('tickets/index.php?page=Issue120');
		$this->assertTextPresent('TActiveDropDownList PromptValue Test');
		
		$this->assertSelectedIndex("ctl0_Content_ddl1", 0);
		$this->assertSelectedValue("ctl0_Content_ddl1", 'PromptValue');
		
		$this->click("ctl0_Content_btn1");
		$this->pause(800);

		$this->assertSelectedIndex("ctl0_Content_ddl1", 0);
		$this->assertSelectedValue("ctl0_Content_ddl1", 'PromptValue');
	}
}

?>