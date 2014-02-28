<?php

class ActiveImageButtonTestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$base='ctl0_Content_';
		$this->url("active-controls/index.php?page=ActiveImageButtonTest");
		$this->assertTextPresent("TActiveImageButton Functional Test");
		$this->assertText("{$base}label1", "Label 1");
		$this->click("{$base}image1");
		$this->pause(800);
		//unable to determine mouse position
		$this->assertTextPresent("regexp:Image clicked at x=\d+, y=\d+");
	}
}
