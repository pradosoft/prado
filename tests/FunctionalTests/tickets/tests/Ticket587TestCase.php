<?php

class Ticket587TestCase extends SeleniumTestCase
{
	function testKeyPress()
	{
		$this->skipBrowsers(self::INTERNET_EXPLORER);
		$base = 'ctl0_Content_';
		$this->open('tickets/index.php?page=Ticket587_reopened');
		$this->assertTitle("Verifying Ticket 587_reopened");

		$this->assertText($base."label1", "Label 1");
		$this->select($base."list1", "item 3");
		$this->pause(800);
		$this->select($base."list2", "value 3 - item 4");
		$this->pause(800);
		$this->assertText($base."label1", "Selection 2: value 3 - item 4");

		$this->keyPress($base.'text1', 't');
		$this->pause(800);
		$this->select($base."list2", "asd 3 - item 2");
		$this->pause(800);
		$this->assertText($base."label1", "Selection 2: asd 3 - item 2");
	}

	function testButtonClick()
	{
		$base = 'ctl0_Content_';
		$this->open('tickets/index.php?page=Ticket587_reopened');
		$this->assertTitle("Verifying Ticket 587_reopened");

		$this->assertText($base."label1", "Label 1");
		$this->select($base."list1", "item 3");
		$this->pause(800);
		$this->select($base."list2", "value 3 - item 4");
		$this->pause(800);
		$this->assertText($base."label1", "Selection 2: value 3 - item 4");

		$this->click($base.'button6');
		$this->pause(800);
		$this->select($base."list2", "asd 3 - item 2");
		$this->pause(800);
		$this->assertText($base."label1", "Selection 2: asd 3 - item 2");
	}
}

?>