<?php

class Ticket587TestCase extends PradoGenericSelenium2Test
{
	function testKeyPress()
	{
		$base = 'ctl0_Content_';
		$this->url('tickets/index.php?page=Ticket587_reopened');
		$this->assertEquals($this->title(), "Verifying Ticket 587_reopened");

		$this->assertText($base."label1", "Label 1");
		$this->select($base."list1", "item 3");
		$this->pause(800);
		$this->select($base."list2", "value 3 - item 4");
		$this->pause(800);
		$this->assertText($base."label1", "Selection 2: value 3 - item 4");

		$this->type($base.'text1', 't');
		$this->pause(800);
		$this->select($base."list2", "asd 3 - item 2");
		$this->pause(800);
		$this->assertText($base."label1", "Selection 2: asd 3 - item 2");
	}

	function testButtonClick()
	{
		$base = 'ctl0_Content_';
		$this->url('tickets/index.php?page=Ticket587_reopened');
		$this->assertEquals($this->title(), "Verifying Ticket 587_reopened");

		$this->assertText($base."label1", "Label 1");
		$this->select($base."list1", "item 3");
		$this->pause(800);
		$this->select($base."list2", "value 3 - item 4");
		$this->pause(800);
		$this->assertText($base."label1", "Selection 2: value 3 - item 4");

		$this->byId($base.'button6')->click();
		$this->pause(800);
		$this->select($base."list2", "asd 3 - item 2");
		$this->pause(800);
		$this->assertText($base."label1", "Selection 2: asd 3 - item 2");
	}
}
