<?php

class QuickstartActiveCustomValidatorTestCase extends \Prado\Tests\PradoDemosSelenium2Test
{
	public function test()
	{
		$this->url("quickstart/index.php?page=ActiveControls.Samples.TActiveCustomValidator.Home&amp;notheme=true&amp;lang=en");

		$this->assertTitle("PRADO QuickStart Sample");

		$this->assertSourceContains('TActiveCustomValidator Samples (AJAX)');

		$base = 'ctl0_body_';

		$this->assertNotVisible($base . 'validator1');
		$this->byId($base . 'button1')->click();
		$this->assertVisible($base . 'validator1');

		$this->type($base . 'textbox1', 'hello');
		$this->assertVisible($base . 'validator1');

		$this->type($base . 'textbox1', 'Prado');
		$this->assertVisible($base . 'validator1');

		$this->byId($base . 'button1')->click();
		$this->assertNotVisible($base . 'validator1');
	}
}
