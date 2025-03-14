<?php

class ActiveDropDownListTestCase extends PradoGenericSelenium2Test
{
	public function test()
	{
		$base = 'ctl0_Content_';
		$this->url("active-controls/index.php?page=ActiveDropDownList");
		$this->assertSourceContains('Active Drop Down List Test Case');

		$this->assertText("{$base}label1", "Label 1");

		$this->byId("{$base}button1")->click();
		$this->assertSelected("{$base}list1", "item 4");

		$this->byId("{$base}button2")->click();
		$this->assertSelectedValue("{$base}list1", 'value 1');

		$this->byId("{$base}button3")->click();
		$this->assertSelected("{$base}list1", "item 2");

		$this->assertText("{$base}label1", "Selection 1: value 1");

		$this->select("{$base}list1", "item 1");
		$this->select("{$base}list2", "value 1 - item 4");
		$this->assertText("{$base}label2", "Selection 2: value 1 - item 4");

		$this->select("{$base}list1", "item 3");
		$this->select("{$base}list2", "value 3 - item 5");
		$this->assertText("{$base}label2", "Selection 2: value 3 - item 5");

		$this->byId("{$base}button4")->click();
		$this->assertSelected("{$base}list1", 'item 3');
		$this->pause(300);
		$this->assertSelected("{$base}list2", 'value 3 - item 3');
	}
}
