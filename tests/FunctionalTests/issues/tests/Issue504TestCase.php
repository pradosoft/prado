<?php

class Issue504TestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$this->url('issues/index.php?page=Issue504');
		$this->assertContains('Issue 504 Test', $this->source());
		$base='ctl0_Content_';

		$this->byID("{$base}textbox1")->click();
		$this->keys(PHPUnit_Extensions_Selenium2TestCase_Keys::ENTER);

		$this->assertText("{$base}label1", "buttonOkClick");
	}
}
