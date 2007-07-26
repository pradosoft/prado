<?php
//$Id: ActiveButtonTestCase.php 1405 2006-09-10 01:03:56Z wei $
class ActiveCustomValidatorTestCase extends SeleniumTestCase
{
	function test ()
	{
		$this->open("../../demos/quickstart/index.php?page=ActiveControls.Samples.TActiveCustomValidator.Home&amp;notheme=true&amp;lang=en");

		$this->verifyTitle("PRADO QuickStart Sample", "");

		$this->assertTextPresent('TActiveCustomValidator Samples (AJAX)');

		$base = 'ctl0_body_';

		$this->assertNotVisible($base.'validator1');
		$this->click($base.'button1');
		$this->pause(800);
		$this->assertVisible($base.'validator1');

		$this->type($base.'textbox1', 'hello');
		$this->pause(800);
		$this->assertVisible($base.'validator1');

		$this->type($base.'textbox1', 'Prado');
		$this->pause(800);
		$this->assertNotVisible($base.'validator1');

		$this->clickAndWait($base.'button1');
		$this->assertNotVisible($base.'validator1');
	}
}

?>