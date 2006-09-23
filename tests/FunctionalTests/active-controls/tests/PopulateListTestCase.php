<?php

class PopulateListTestCase extends SeleniumTestCase
{
	function test()
	{
		$this->open("active-controls/index.php?page=PopulateActiveList");
		$this->verifyTextPresent("Populate active list controls");
		$this->assertText("label1", "");

		$this->click("button1");
		$this->pause(800);
		$this->select("list1", "World");
		$this->pause(800);
		$this->assertText("label1", "list1: World");

		$this->click("button2");
		$this->pause(800);
		$this->select("list2", "Prado");
		$this->pause(800);
		$this->assertText("label1", "list2: Prado");
	}
}

?>