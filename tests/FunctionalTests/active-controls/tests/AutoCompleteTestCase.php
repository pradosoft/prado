<?php

class AutoCompleteTestCase extends SeleniumTestCase
{
	function test()
	{
		$this->open("active-controls/index.php?page=AutoCompleteTest");
		$this->verifyTextPresent("TAutoComplete Test");

		$this->assertText("label1", "Label 1");

		$this->keyPress("textbox3", 'a');
		$this->pause(1000);
		$this->verifyTextPresent('Andorra');
		$this->keyPress("textbox3", 'u');
		$this->pause(1000);
		$this->verifyTextPresent('Australia');
		$this->click("heading"); //click somewhere else.
		$this->waitForText("label1", "suggestion for au");
		$this->assertText("label1", "suggestion for au");
		$this->click("css=#textbox3_result ul li");
		$this->pause(800);
		$this->assertText("label1", "Label 1: Austria");

		$this->keyPress("textbox2", "c");
		$this->pause(800);
		$this->keyPress("textbox2", "u");
		$this->pause(800);
		$this->click('css=#textbox2_result ul li');
		$this->pause(800);
		$this->assertText("label1", "Label 1: Cuba");

		$this->keyPress("textbox2", ",");

		$this->keyPress("textbox2", "m");
		$this->pause(800);

		$this->keyPress("textbox2", "e");
		$this->pause(800);
		$this->click('css=#textbox2_result ul li');
		$this->pause(800);
		$this->assertText("label1", "Label 1: Cuba,Mexico");
	}
}

?>