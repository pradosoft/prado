<?php
class Ticket670TestCase extends SeleniumTestCase
{
	function test()
	{
		$base = 'ctl0_Content_';
		$this->open('tickets/index.php?page=Ticket670');
		$this->assertTitle("Verifying Ticket 670");
		
		$this->type($base."datePicker", '07-07-2003');
		$this->pause(800);
		//$this->assertText($base."datePicker",'07-07-2003');
		$this->click($base."datePickerbutton");
		$this->pause(800);
		$this->click($base."ok");
		$this->pause(800);
		$this->assertText($base."lbl",'07-07-2007');
		
	}

}
?>