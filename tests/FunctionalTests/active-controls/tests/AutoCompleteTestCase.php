<?php

class AutoCompleteTestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$base='ctl0_Content_';
		$this->url("active-controls/index.php?page=AutoCompleteTest");
		$this->assertTextPresent("TAutoComplete Test");

		$this->assertText("{$base}label1", "Label 1");

		$this->click("{$base}textbox3");
		$this->keys('a');
		$this->pause(500);
		$this->assertTextPresent('Andorra');
		$this->assertText("{$base}label1", "suggestion for a");

		$this->keys('u');
		$this->pause(500);
		$this->assertTextPresent('Australia');
		$this->assertText("{$base}label1", "suggestion for au");

		$this->click("css=#{$base}textbox3_result ul li");
		$this->pause(500);
		$this->assertText("{$base}label1", "Label 1: Austria");


		$this->click("{$base}textbox2");
		$this->keys('cu');
		$this->pause(500);
		$this->click("css=#{$base}textbox2_result ul li");
		$this->pause(500);
		$this->assertText("{$base}label1", "Label 1: Cuba");

		$this->keys(PHPUnit_Extensions_Selenium2TestCase_Keys::END);
		$this->keys(',me');
		$this->pause(500);
		$this->click("css=#{$base}textbox2_result ul li");
		$this->pause(500);
		$this->assertText("{$base}label1", "Label 1: Cuba,Mexico");
	}
}
