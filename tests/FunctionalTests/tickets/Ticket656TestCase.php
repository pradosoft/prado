<?php

class Ticket656TestCase extends \Prado\Tests\PradoGenericSelenium2Test
{
	public function test()
	{
		$base = 'ctl0_Content_';
		$this->url('tickets/index.php?page=Ticket656');
		$this->assertTitle("Verifying Ticket 656");

		// First test, current date
		$this->byId($base . "btnUpdate")->click();
		$this->assertText($base . "lblStatus", date("d-m-Y"));

		// Then, set another date
		$year = date('Y') - 2;
		$this->select($base . "datePicker_day", 20);
		$this->select($base . "datePicker_month", 10);
		$this->select($base . "datePicker_year", $year);
		$this->byId($base . "btnUpdate")->click();
		$this->assertText($base . "lblStatus", date("d-m-Y", mktime(0, 0, 0, 10, 20, $year)));
	}
}
