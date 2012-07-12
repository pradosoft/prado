<?php

class Issue216TestCase extends SeleniumTestCase
{
	function test()
	{
		$this->open('tickets/index.php?page=Issue216');
		$this->assertTextPresent('TTabPanel doesn\'t preserve active tab on callback request');
		
		$this->assertVisible('ctl0_Content_tab1');

		$this->click("ctl0_Content_btn1");
		$this->pause(800);

		$this->assertText("ctl0_Content_result", "Tab ActiveIndex is : 0");

		$this->click("ctl0_Content_tab2_0");
		$this->pause(800);

		$this->assertVisible('ctl0_Content_tab2');

		$this->click("ctl0_Content_btn1");
		$this->pause(800);
		$this->assertText("ctl0_Content_result", "Tab ActiveIndex is : 1");
	}
}

?>