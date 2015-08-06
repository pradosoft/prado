<?php

class ActiveListBoxMasterTestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$this->url("active-controls/index.php?page=ActiveListBoxMasterTest");
		$this->assertSourceContains('Active List Box Functional Test');

		$base = 'ctl0_body_';

		$this->assertText("{$base}label1", "Label 1");

		$this->byId("{$base}button1")->click();
		$this->pause(800);
		$this->assertEquals($this->getSelectedLabels("{$base}list1"), array('item 2', 'item 3', 'item 4'));

		$this->byId("{$base}button3")->click();
		$this->pause(800);
		$this->assertEquals($this->getSelectedLabels("{$base}list1"), array('item 1'));

		$this->byId("{$base}button4")->click();
		$this->pause(800);
		$this->assertEquals($this->getSelectedLabels("{$base}list1"), array('item 5'));

		$this->byId("{$base}button5")->click();
		$this->pause(800);
		$this->assertEquals($this->getSelectedLabels("{$base}list1"), array('item 2', 'item 5'));

		$this->byId("{$base}button2")->click();
		$this->pause(800);
		$this->assertNotSomethingSelected("{$base}list1");

		$this->byId("{$base}button6")->click();
		$this->pause(800);
		$this->byId("{$base}button1")->click();
		$this->pause(800);
		$this->assertEquals($this->getSelectedLabels("{$base}list1"), array('item 2', 'item 3', 'item 4'));

		$this->select("{$base}list1", "item 1");
		$this->pause(800);
		$this->assertText("{$base}label1", 'Selection: value 1');

		$this->addSelection("{$base}list1", "item 4");
		$this->pause(800);
		$this->assertText("{$base}label1", 'Selection: value 1, value 4');
	}
}