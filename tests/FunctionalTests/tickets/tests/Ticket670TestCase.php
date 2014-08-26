<?php
class Ticket670TestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$base = 'ctl0_Content_';
		$this->url('tickets/index.php?page=Ticket670');
		$this->assertEquals($this->title(), "Verifying Ticket 670");

		$this->type($base."datePicker", '07-07-2003');
		$this->pause(800);
		//$this->assertText($base."datePicker",'07-07-2003');
		$this->byId($base."datePickerbutton")->click();
		$this->pause(800);
		$this->byId($base."ok")->click();
		$this->pause(800);
		$this->assertText($base."lbl",'07-07-2007');

	}

}