<?php
class Ticket656TestCase extends PradoGenericSeleniumTest
{
	function test()
	{
		$base = 'ctl0_Content_';
		$this->open('tickets/index.php?page=Ticket656');
		$this->assertTitle("Verifying Ticket 656");

		// First test, current date
		$this->click($base."btnUpdate");
		$this->pause(800);
		$this->assertText($base."lblStatus",date("d-m-Y"));

		// Then, set another date
		$year=date('Y')-2;
		$this->select($base."datePicker_day",20);
		$this->select($base."datePicker_month", 10);
		$this->select($base."datePicker_year", $year);
		$this->click($base."btnUpdate");
		$this->pause(800);
		$this->assertText($base."lblStatus",date("d-m-Y", mktime(0,0,0,10,20,$year)));
	}

}