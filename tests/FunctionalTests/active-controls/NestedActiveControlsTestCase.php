<?php

class NestedActiveControlsTestCase extends \Prado\Tests\PradoGenericSelenium2Test
{
	public function test()
	{
		$base = 'ctl0_Content_';
		$this->url("active-controls/index.php?page=NestedActiveControls");
		$this->assertSourceContains("Nested Active Controls Test");
		$this->assertText("{$base}label1", "Label 1");
		$this->assertText("{$base}label2", "Label 2");
		$this->assertSourceNotContains("Label 3");

		$this->byId("div1")->click();
		$this->assertSourceContains("Something lalala");
		$this->assertText("{$base}label3", "Label 3");

		$this->byId("{$base}button1")->click();
		$this->assertText("{$base}label1", "Label 1: Button 1 Clicked");
		$this->assertText("{$base}label2", "Label 2: Button 1 Clicked");
		$this->assertText("{$base}label3", "Label 3: Button 1 Clicked");
	}
}
