<?php

class ActiveImageButtonTestCase extends SeleniumTestCase
{
	function test()
	{
		$this->open("active-controls/index.php?page=ActiveImageButtonTest");
		$this->assertTextPresent("TActiveImageButton Functional Test");
		$this->assertText("label1", "Label 1");
		$this->click("image1");
		$this->pause(800);
		//unable to determine mouse position
		$this->assertTextPresent("Image clicked at x=0, y=0");
	}
}

?>