<?php

class ActiveListBoxTestCase extends PradoGenericSeleniumTest
{
	function test()
	{
		$this->open("active-controls/index.php?page=ActiveListBoxTest");
		$this->assertTextPresent('Active List Box Functional Test');

		$this->assertText("label1", "Label 1");

		$this->click("button1");
		$this->pause(800);
		$this->assertEquals($this->getSelectedLabels('list1'), array('item 2', 'item 3', 'item 4')); 

		$this->click('button3');
		$this->pause(800);
		$this->assertEquals($this->getSelectedLabels('list1'), array('item 1')); 

		$this->click('button4');
		$this->pause(800);
		$this->assertEquals($this->getSelectedLabels('list1'), array('item 5')); 

		$this->click('button5');
		$this->pause(800);
		$this->assertEquals($this->getSelectedLabels('list1'), array('item 2', 'item 5')); 

		$this->click('button2');
		$this->pause(800);
		$this->assertNotSomethingSelected("list1");

		$this->click('button6');
		$this->pause(800);
		$this->click("button1");
		$this->pause(800);
		$this->assertEquals($this->getSelectedLabels('list1'), array('item 2', 'item 3', 'item 4')); 

		$this->select("list1", "item 1");
		$this->pause(800);
		$this->assertText('label1', 'Selection: value 1');

		$this->addSelection("list1", "item 4");
		$this->pause(800);
		$this->assertText('label1', 'Selection: value 1, value 4');
	}
}