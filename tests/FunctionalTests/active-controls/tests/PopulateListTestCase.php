<?php

class PopulateListTestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$base='ctl0_Content_';
		$this->url("active-controls/index.php?page=PopulateActiveList");
		$this->assertSourceContains("Populate active list controls");
		$this->assertText("{$base}label1", "");

		$this->byId("{$base}button1")->click();
		$this->pause(800);
		$this->select("{$base}list1", "World");
		$this->pause(800);
		$this->assertText("{$base}label1", "list1: World");

		$this->byId("{$base}button2")->click();
		$this->pause(800);
		$this->select("{$base}list2", "Prado");
		$this->pause(800);
		$this->assertText("{$base}label1", "list2: Prado");
	}
}
