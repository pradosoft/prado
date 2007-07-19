<?php

class Ticket592TestCase extends SeleniumTestCase
{
	function test()
	{
		$base = 'ctl0_Content_';
		$this->open('tickets/index.php?page=Ticket592');
		$this->assertTitle("Verifying Ticket 592");

		$this->assertText("{$base}label1", "Label 1");

		$this->click("{$base}radio1");
		$this->click("{$base}button1");
		$this->pause(800);
		$this->assertText("{$base}label1", 'radio1 checked:{1} radio2 checked:{}');

		$this->click("{$base}radio2");
		$this->click("{$base}button1");
		$this->pause(800);
		$this->assertText("{$base}label1", 'radio1 checked:{1} radio2 checked:{1}');

		$this->click("{$base}bad_radio1");
		$this->click("{$base}button2");
		$this->pause(800);
		$this->assertText("{$base}label1", 'bad_radio1 checked:{1} bad_radio2 checked:{}');

		$this->click("{$base}bad_radio2");
		$this->click("{$base}button2");
		$this->pause(800);
		$this->assertText("{$base}label1", 'bad_radio1 checked:{} bad_radio2 checked:{1}');
		
		$this->click("{$base}bad_radio3");
		$this->click("{$base}button3");
		$this->pause(800);
		$this->assertText("{$base}label1", 'bad_radio3 checked:{1} bad_radio4 checked:{}');

		$this->click("{$base}bad_radio4");
		$this->click("{$base}button3");
		$this->pause(800);
		$this->assertText("{$base}label1", 'bad_radio3 checked:{} bad_radio4 checked:{1}');
		
	}

}

?>