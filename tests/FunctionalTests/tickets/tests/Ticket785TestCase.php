<?php

class Ticket785TestCase extends \Prado\Tests\PradoGenericSelenium2Test
{
	public function test()
	{
		$year = date('Y') - 1;
		$base = 'ctl0_Content_';
		$this->url('tickets/index.php?page=Ticket785');
		$this->assertTitle("Verifying Ticket 785");

		$this->assertText('selDate', '');
		$this->select($base . "datePicker_year", $year);
		$expectedDate = date("d-m") . '-' . $year;
		$this->assertText('selDate', $expectedDate);

		$this->byId($base . "datePickerbutton")->click();
		$this->byCssSelector("input.todayButton")->click();
		$this->byCssSelector("body")->click(); 		// Hide calendar
		$expectedDate = date("d-m-Y");
		$this->assertText('selDate', $expectedDate);

		$this->assertText('selDate2', '');
		$this->type($base . 'datePicker2', '12/05/2006');
		$this->byCssSelector("body")->click();
		$this->assertText('selDate2', '12/05/2006');
	}
}
