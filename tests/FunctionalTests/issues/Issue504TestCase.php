<?php

use Facebook\WebDriver\WebDriverKeys;

class Issue504TestCase extends \Prado\Tests\PradoGenericSelenium2Test
{
	public function test()
	{
		$this->url('issues/index.php?page=Issue504');
		$this->assertSourceContains('Issue 504 Test');
		$base = 'ctl0_Content_';

		$this->byID("{$base}textbox1")->click();
		$this->keys(WebDriverKeys::ENTER);
		$this->pause(50);

		$this->assertText("{$base}label1", "buttonOkClick");
	}
}
