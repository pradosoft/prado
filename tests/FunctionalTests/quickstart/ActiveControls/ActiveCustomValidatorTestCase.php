<?php

class QuickstartActiveCustomValidatorTestCase extends PradoDemosSelenium2Test
{
	public function test()
	{
		$this->url("quickstart/index.php?page=ActiveControls.Samples.TActiveCustomValidator.Home&amp;notheme=true&amp;lang=en");

		$this->assertEquals("PRADO QuickStart Sample", $this->title());

		$this->assertSourceContains('TActiveCustomValidator Samples (AJAX)');

		$base = 'ctl0_body_';

		$this->assertNotVisible($base . 'validator1');
		$this->byId($base . 'button1')->click();
		$this->pauseFairAmount();
		$this->assertVisible($base . 'validator1');

		$this->type($base . 'textbox1', 'hello');
		$this->pauseFairAmount();
		$this->assertVisible($base . 'validator1');

		$this->type($base . 'textbox1', 'Prado');
		$this->pauseFairAmount();
		$this->assertVisible($base . 'validator1');

		$this->byId($base . 'button1')->click();
		$this->pauseFairAmount();
		$this->assertNotVisible($base . 'validator1');
	}
}
