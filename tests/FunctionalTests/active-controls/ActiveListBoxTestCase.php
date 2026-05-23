<?php

class ActiveListBoxTestCase extends \Prado\Tests\PradoGenericSelenium2Test
{
	public function test()
	{
		$base = 'ctl0_Content_';
		$this->url("active-controls/index.php?page=ActiveListBoxTest");
		$this->assertSourceContains('Active List Box Functional Test');

		$this->assertText("{$base}label1", "Label 1");

		$this->byId("{$base}button1")->click();
		$this->assertEquals($this->getSelectedLabels("{$base}list1"), ['item 2', 'item 3', 'item 4']);

		$this->byId("{$base}button3")->click();
		$this->assertEquals($this->getSelectedLabels("{$base}list1"), ['item 1']);

		$this->byId("{$base}button4")->click();
		$this->assertEquals($this->getSelectedLabels("{$base}list1"), ['item 5']);

		$this->byId("{$base}button5")->click();
		$this->assertEquals($this->getSelectedLabels("{$base}list1"), ['item 2', 'item 5']);

		$this->byId("{$base}button2")->click();
		$this->assertNotSomethingSelected("{$base}list1");

		$this->byId("{$base}button6")->click();
		$this->byId("{$base}button1")->click();
		$this->assertEquals($this->getSelectedLabels("{$base}list1"), ['item 2', 'item 3', 'item 4']);

		$this->select("{$base}list1", "item 1");
		$this->assertText("{$base}label1", 'Selection: value 1');

		$this->addSelection("{$base}list1", "item 4");
		$this->assertText("{$base}label1", 'Selection: value 1, value 4');
	}
}
