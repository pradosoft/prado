<?php

class ActiveDropDownListTestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$base='ctl0_Content_';
		$this->url("active-controls/index.php?page=ActiveDropDownList");
		$this->assertTextPresent('Active Drop Down List Test Case');

		$this->assertText("{$base}label1", "Label 1");

		$this->click("{$base}button1");
		$this->pause(800);
		$this->assertSelected("{$base}list1", "item 4");

		$this->click("{$base}button2");
		$this->pause(800);
		$this->assertSelectedValue("{$base}list1", 'value 1');

		$this->click("{$base}button3");
		$this->pause(800);
		$this->assertSelected("{$base}list1", "item 2");

		$this->assertText("{$base}label1", "Selection 1: value 1");

		$this->select("{$base}list1", "item 1");
		$this->pause(800);
		$this->select("{$base}list2", "value 1 - item 4");
		$this->pause(800);
		$this->assertText("{$base}label2", "Selection 2: value 1 - item 4");

		$this->select("{$base}list1", "item 3");
		$this->pause(800);
		$this->select("{$base}list2", "value 3 - item 5");
		$this->pause(800);
		$this->assertText("{$base}label2", "Selection 2: value 3 - item 5");

		$this->click("{$base}button4");
		$this->pause(800);
		$this->assertSelected("{$base}list1", 'item 3');
		$this->pause(300);
		$this->assertSelected("{$base}list2", 'value 3 - item 3');

	}
}
