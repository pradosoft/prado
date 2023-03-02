<?php


/**
 * Testcase for TJuiAutoComplete
 */
class JuiAutoCompleteTestCase extends PradoDemosSelenium2Test
{
	public function test()
	{
		$this->url("quickstart/index.php?page=JuiControls.Samples.TJuiAutoComplete.Home&amp;notheme=true&amp;lang=en");

		$this->assertEquals("PRADO QuickStart Sample", $this->title());

		$this->assertSourceContains('TJuiAutoComplete Samples');

		$base = 'ctl0_body_';


		$this->assertText("{$base}Selection1", "");

		$this->byId("{$base}AutoComplete")->click();
		$this->keys('J');
		$this->pause(500);
		$this->assertSourceContains('John');

		$this->byCssSelector("#{$base}AutoComplete_result ul li")->click();
		$this->pauseFairAmount();
		$this->assertValue("{$base}AutoComplete", "John");
		$this->assertText("{$base}Selection1", "Selected ID: 1");


		$this->byId("{$base}AutoComplete2")->click();
		$this->keys('Joh');
		$this->pause(500);
		$this->byCssSelector("#{$base}AutoComplete2_result ul li")->click();
		$this->pauseFairAmount();
		$this->assertValue("{$base}AutoComplete2", "John");
		$this->assertText("{$base}Selection2", "Selected ID: 1");

		//$this->keys(\PHPUnit\Extensions\Selenium2TestCase\Keys::END);
		$this->keys(',Ge');
		$this->pause(500);
		$this->byCssSelector("#{$base}AutoComplete2_result ul li")->click();
		$this->pause(500);
		$this->assertValue("{$base}AutoComplete2", "John,George");
		$this->assertText("{$base}Selection2", "Selected ID: 3");
	}
}
