<?php

class NestedActiveControlsTestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$base='ctl0_Content_';
		$this->url("active-controls/index.php?page=NestedActiveControls");
		$this->assertContains("Nested Active Controls Test", $this->source());
		$this->assertText("{$base}label1", "Label 1");
		$this->assertText("{$base}label2", "Label 2");
		$this->assertNotContains("Label 3", $this->source());

		$this->byId("div1")->click();
		$this->pause(800);
		$this->assertContains("Something lalala", $this->source());
		$this->assertText("{$base}label3", "Label 3");

		$this->byId("{$base}button1")->click();
		$this->pause(800);
		$this->assertText("{$base}label1", "Label 1: Button 1 Clicked");
		$this->assertText("{$base}label2", "Label 2: Button 1 Clicked");
		$this->assertText("{$base}label3", "Label 3: Button 1 Clicked");
	}
}
