<?php

class AutoCompleteTestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$base='ctl0_Content_';
		$this->url("active-controls/index.php?page=AutoCompleteTest");
		$this->assertSourceContains("TAutoComplete Test");

		$this->assertText("{$base}label1", "Label 1");

		$this->byId("{$base}textbox3")->click();
		$this->keys('a');
		$this->pause(800);
		$this->assertSourceContains('Andorra');
		$this->assertText("{$base}label1", "suggestion for a");

		$this->keys('u');
		$this->pause(800);
		$this->assertSourceContains('Australia');
		$this->assertText("{$base}label1", "suggestion for au");

		$this->byCssSelector("#{$base}textbox3_result ul li")->click();
		$this->pause(800);
		$this->assertText("{$base}label1", "Label 1: Austria");


		$this->byId("{$base}textbox2")->click();
		$this->keys('cu');
		$this->pause(800);
		$this->byCssSelector("#{$base}textbox2_result ul li")->click();
		$this->pause(800);
		$this->assertText("{$base}label1", "Label 1: Cuba");

		$this->keys(PHPUnit_Extensions_Selenium2TestCase_Keys::END);
		$this->keys(',me');
		$this->pause(800);
		$this->byCssSelector("#{$base}textbox2_result ul li")->click();
		$this->pause(500);
		$this->assertText("{$base}label1", "Label 1: Cuba,Mexico");
	}
}
