<?php

class Ticket719TestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$this->url("tickets/index.php?page=Ticket719");
		$this->assertContains("Verifying Ticket 719", $this->source());

		$base="ctl0_Content_";

		$this->byId("${base}ctl2")->click();
		$this->pause(800);
		$this->assertVisible("${base}ctl0", 'Required');
		$this->assertVisible("${base}ctl1", 'Required');

		$this->type("${base}autocomplete", 'f');
		$this->runScript("Prado.Registry['${base}autocomplete'].onKeyPress({})");
		$this->pause(500);
		$this->assertContains('Finland', $this->source());

		$this->type("${base}autocomplete", 'fr');
		$this->runScript("Prado.Registry['${base}autocomplete'].onKeyPress({})");
		$this->pause(500);
		$this->assertContains('French', $this->source());

		$this->type("${base}autocomplete", 'fra');
		$this->runScript("Prado.Registry['${base}autocomplete'].onKeyPress({})");
		$this->pause(500);
		$this->assertContains('France', $this->source());

		$this->byCssSelector("#${base}autocomplete_result ul li")->click();
		$this->pause(800);
		$this->assertNotVisible("${base}ctl1");

		$this->type("${base}textbox", "Prado");
		$this->assertNotVisible("${base}ctl0");

		$this->byId("${base}ctl2")->click();
		$this->pause(800);
		$this->assertText("${base}Result", "TextBox Content : Prado -- Autocomplete Content :France");
	}
}
