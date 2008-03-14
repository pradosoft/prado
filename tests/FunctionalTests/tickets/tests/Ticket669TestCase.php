<?php
class Ticket669TestCase extends SeleniumTestCase
{
	function test()
	{
		$base = 'ctl0_Content_';
		$this->open('tickets/index.php?page=Ticket669');
		$this->assertTitle("Verifying Ticket 669");
		
		$this->assertTextPresent('1 - Test without callback');
		$this->assertValue($base.'tb1', 'ActiveTextBox');
		$this->assertValue($base.'tb2', 'TextBox in ActivePanel');
		
		$this->click($base.'ctl4');
		$this->pause(800);
		$this->assertValue($base.'tb1', 'ActiveTextBox +1');
		$this->assertValue($base.'tb2', 'TextBox in ActivePanel +1');
		
		$this->click($base.'ctl1');
		$this->pause(800);
		$this->assertTextPresent('2 - Test callback with 2nd ActivePanel');
		$this->assertValue($base.'tb3', 'ActiveTextBox');
		$this->assertValue($base.'tb4', 'TextBox in ActivePanel');
		$this->assertValue($base.'tb5', 'TextBox in ActivePanel');
		
		$this->click($base.'ctl6');
		$this->pause(800);
		
		$this->assertValue($base.'tb3', 'ActiveTextBox +1');
		$this->assertValue($base.'tb4', 'TextBox in ActivePanel +1');
		$this->assertValue($base.'tb5', 'TextBox in ActivePanel +1');
		
		$this->click($base.'ctl2');
		$this->pause(800);
		$this->assertTextPresent('3 - Test callback without 2nd ActivePanel');
		$this->assertValue($base.'tb6', 'ActiveTextBox');
		$this->assertValue($base.'tb7', 'TextBox in Panel');
		
		$this->click($base.'ctl8');
		$this->pause(800);
		
		$this->assertValue($base.'tb6', 'ActiveTextBox +1');
		$this->assertValue($base.'tb7', 'TextBox in Panel +1');
		
	}

}
?>