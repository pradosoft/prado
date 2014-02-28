<?php

class ActiveListBoxMasterTestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$this->url("active-controls/index.php?page=ActiveListBoxMasterTest");
		$this->assertTextPresent('Active List Box Functional Test');

		$base = 'ctl0_body_';

		$this->assertText("{$base}label1", "Label 1");

		$this->click("{$base}button1");
		$this->pause(800);
		$this->assertEquals($this->getSelectedLabels("{$base}list1"), array('item 2', 'item 3', 'item 4'));

		$this->click("{$base}button3");
		$this->pause(800);
		$this->assertEquals($this->getSelectedLabels("{$base}list1"), array('item 1'));

		$this->click("{$base}button4");
		$this->pause(800);
		$this->assertEquals($this->getSelectedLabels("{$base}list1"), array('item 5'));

		$this->click("{$base}button5");
		$this->pause(800);
		$this->assertEquals($this->getSelectedLabels("{$base}list1"), array('item 2', 'item 5'));

		$this->click("{$base}button2");
		$this->pause(800);
		$this->assertNotSomethingSelected("{$base}list1");

		$this->click("{$base}button6");
		$this->pause(800);
		$this->click("{$base}button1");
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