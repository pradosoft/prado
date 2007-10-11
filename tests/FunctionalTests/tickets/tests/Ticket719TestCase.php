<?php

class Ticket719TestCase extends SeleniumTestCase
{
	function test()
	{
		$this->open("tickets/index.php?page=Ticket719");
		$this->verifyTextPresent("Verifying Ticket 719");
		
		$base="ctl0_Content_";
		
		$this->click("${base}ctl2");
		$this->pause(800);
		$this->assertVisible("${base}ctl0", 'Required');
		$this->assertVisible("${base}ctl1", 'Required');
		
		$this->keyPress("${base}autocomplete", 'f');
		$this->pause(1000);
		$this->verifyTextPresent('Finland');
		$this->keyPress("${base}autocomplete", 'r');
		$this->pause(1000);
		$this->verifyTextPresent('French');
		$this->keyPress("${base}autocomplete", 'a');
		$this->pause(1000);
		$this->verifyTextPresent('France');
	
		$this->click("css=#${base}autocomplete_result ul li");
		$this->pause(800);
		$this->assertNotVisible("${base}ctl1");

		$this->type("${base}textbox", "Prado");
		$this->assertNotVisible("${base}ctl0");
		
		$this->click("${base}ctl2");
		$this->pause(800);
		$this->assertText("${base}Result", "TextBox Content : Prado -- Autocomplete Content :France");
	}
}

?>