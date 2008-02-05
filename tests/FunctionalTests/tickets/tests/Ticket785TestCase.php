<?php
class Ticket785TestCase extends SeleniumTestCase
{
	function test()
	{
		$base = 'ctl0_Content_';
		$this->open('tickets/index.php?page=Ticket785');
		$this->assertTitle("Verifying Ticket 785");
		
		$this->assertText('selDate', '');
		$this->select($base."datePicker_year", "2007");
		$this->pause(800);
		$expectedDate=date("d-m").'-2007';
		$this->assertText('selDate', $expectedDate);
		
		$this->click($base."datePickerbutton");
		$this->pause(800);
		$this->click("css=input.todayButton");
		$this->pause(800);
		$this->clickAt("css=body","0,0"); 		// Hide calendar
		$expectedDate=date("d-m-Y");
		$this->assertText('selDate', $expectedDate);
		
		$this->assertText('selDate2', '');
		$this->type($base.'datePicker2', '12/05/2006');
		$this->pause(800);
		$this->clickAt("css=body","0,0");
		$this->assertText('selDate2', '12/05/2006');		
		
	}

}
?>