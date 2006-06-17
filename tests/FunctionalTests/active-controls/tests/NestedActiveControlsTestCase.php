<?php

class NestedActiveControlsTestCase extends SeleniumTestCase
{
	function test()
	{
		$this->open("active-controls/index.php?page=NestedActiveControls");
		$this->verifyTextPresent("Nested Active Controls Test");
		$this->assertText("label1", "Label 1");
		$this->assertText("label2", "Label 2");
		$this->assertTextNotPresent("Label 3");
		
		$this->click("div1");
		$this->pause(500);
		$this->assertTextPresent("Something lalala");
		$this->assertText("label3", "Label 3");
		
		$this->click("button1");
		$this->pause(500);
		$this->assertText("label1", "Label 1: Button 1 Clicked");
		$this->assertText("label2", "Label 2: Button 1 Clicked");
		$this->assertText("label3", "Label 3: Button 1 Clicked");
	}	
}

?>