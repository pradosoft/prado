<?php

class AutoCompleteTestCase extends PradoGenericSeleniumTest
{
	function test()
	{
		$this->open("active-controls/index.php?page=AutoCompleteTest");
		$this->verifyTextPresent("TAutoComplete Test");

		$this->assertText("label1", "Label 1");

		$this->type("textbox3", 'a');
		$this->runScript('Prado.Registry.get(\'textbox3\').onKeyPress({})');
		$this->pause(500);
		$this->verifyTextPresent('Andorra');
		$this->assertText("label1", "suggestion for a");

		$this->type("textbox3", 'au');
		$this->runScript('Prado.Registry.get(\'textbox3\').onKeyPress({})');
		$this->pause(500);
		$this->verifyTextPresent('Australia');
		$this->assertText("label1", "suggestion for au");

		$this->click("css=#textbox3_result ul li");
		$this->pause(500);
		$this->assertText("label1", "Label 1: Austria");

		$this->type("textbox2", "cu");
		$this->runScript('Prado.Registry.get(\'textbox2\').onKeyPress({})');
		$this->pause(500);
		$this->click('css=#textbox2_result ul li');
		$this->pause(500);
		$this->assertText("label1", "Label 1: Cuba");

		$this->type("textbox2", "Cuba,me");
		$this->runScript('Prado.Registry.get(\'textbox2\').onKeyPress({})');
		$this->pause(500);
		$this->click('css=#textbox2_result ul li');
		$this->pause(500);
		$this->assertText("label1", "Label 1: Cuba,Mexico");
	}
}
