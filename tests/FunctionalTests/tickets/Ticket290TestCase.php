<?php

use Facebook\WebDriver\WebDriverKeys;

/**
 *
 */
class Ticket290TestCase extends \Prado\Tests\PradoGenericSelenium2Test
{
	public function test()
	{
		$base = 'ctl0_Content_';
		$this->url('tickets/index.php?page=Ticket290');
		$this->assertTitle("Verifying Ticket 290");

		$this->assertText("{$base}label1", "Label 1");
		$this->assertText("{$base}label2", "Label 2");

		$this->type("{$base}textbox1", "test");

		$this->byId("{$base}textbox1")->click();
		$this->keys(WebDriverKeys::ENTER);

		$this->assertText("{$base}label1", "Doing Validation");
		$this->assertText("{$base}label2", "Button 2 (default) Clicked!");
	}
}
