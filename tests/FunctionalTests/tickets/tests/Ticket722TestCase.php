<?php

use Facebook\WebDriver\WebDriverKeys;

class Ticket722TestCase extends PradoGenericSelenium2Test
{
	public function test()
	{
		$base = 'ctl0_Content_';
		$this->url('tickets/index.php?page=Ticket722');
		$this->assertTitle("Verifying Ticket 722");

		$this->assertText("{$base}InPlaceTextBox__label", 'Editable Text');
		$this->byID("{$base}InPlaceTextBox__label")->click();

		$this->assertVisible("{$base}InPlaceTextBox");

		// calling clear() would trigger an onBlur event on the textbox
		// so we empty the textbox one char at a time
		$this->byID("{$base}InPlaceTextBox")->click();

		$this->keys(WebDriverKeys::END);
		for ($i = 0; $i < 13; ++$i) {
			$this->keys(WebDriverKeys::BACKSPACE);
		}

		$this->type($base . 'InPlaceTextBox', "Prado");
		$this->assertNotVisible("{$base}InPlaceTextBox");
		$this->assertText("{$base}InPlaceTextBox__label", 'Prado');

		$this->byId("{$base}ctl0")->click();
		$this->assertText("{$base}InPlaceTextBox__label", 'Prado [Read Only]');

		$this->byID("{$base}InPlaceTextBox__label")->click();
		$this->assertNotVisible("{$base}InPlaceTextBox");
	}
}
