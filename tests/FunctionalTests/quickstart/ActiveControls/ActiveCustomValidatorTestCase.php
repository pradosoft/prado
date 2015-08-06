<?php
//$Id: ActiveButtonTestCase.php 1405 2006-09-10 01:03:56Z wei $
class QuickstartActiveCustomValidatorTestCase extends PradoGenericSelenium2Test
{
	function test ()
	{
		$this->url("../../demos/quickstart/index.php?page=ActiveControls.Samples.TActiveCustomValidator.Home&amp;notheme=true&amp;lang=en");

		$this->assertEquals("PRADO QuickStart Sample", $this->title());

		$this->assertSourceContains('TActiveCustomValidator Samples (AJAX)');

		$base = 'ctl0_body_';

		$this->assertNotVisible($base.'validator1');
		$this->byId($base.'button1')->click();
		$this->pause(800);
		$this->assertVisible($base.'validator1');

		$this->type($base.'textbox1', 'hello');
		$this->pause(800);
		$this->assertVisible($base.'validator1');

		$this->type($base.'textbox1', 'Prado');
		$this->pause(800);
		$this->assertVisible($base.'validator1');

		$this->byId($base.'button1')->click();
		$this->pause(800);
		$this->assertNotVisible($base.'validator1');
	}
}
