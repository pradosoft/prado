<?php

class AutoCompleteTestCase extends SeleniumTestCase
{
	function test()
	{
		$this->open("active-controls/index.php?page=AutoCompleteTest");
		$this->verifyTextPresent("TAutoComplete Test");
		
		$this->assertText("label1", "Label 1");

		$this->type("textbox3", "Australia");
		$this->pause(500);
		$this->click("heading"); //click somewhere else.
		$this->pause(500);
		$this->assertText("label1", "Label 1: Australia");
		
	}
}

?>