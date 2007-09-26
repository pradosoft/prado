<?php
class Ticket671TestCase extends SeleniumTestCase
{
	function test()
	{
		$base = 'ctl0_Content_';
		$this->open('tickets/index.php?page=Ticket671');
		$this->assertTitle("Verifying Ticket 671");
		
		$this->verifyNotVisible($base.'ctl0');
		// Click submit
		$this->click($base.'ctl1');
		$this->pause(800);
		$this->verifyText($base.'ctl0', 'Please Select Test 3');
		$this->verifyVisible($base.'ctl0');
		$this->select($base.'addl', 'Test 2');
		$this->pause(800);
		$this->verifyVisible($base.'ctl0');
		$this->verifyText($base."lblResult", "You have selected 'Test 2'. But this is not valid !");
		$this->select($base.'addl', 'Test 3');
		$this->pause(800);
		$this->verifyNotVisible($base.'ctl0');
		$this->verifyText($base."lblResult", "You have selected 'Test 3'.");
		$this->click($base.'ctl1');
		$this->pause(800);
		$this->verifyText($base."lblResult", "You have successfully validated the form");
		
		$this->type($base.'testTextBox', 'test');
		$this->pause(800);
		$this->click($base.'ctl3');
		$this->pause(800);
		$this->verifyVisible($base.'ctl2');
		$this->type($base.'testTextBox',"Prado");
		$this->pause(800);
		$this->click($base.'ctl3');
		$this->pause(800);
		$this->verifyNotVisible($base.'ctl2');
		$this->verifyText($base.'lblResult2', 'Thanks !');
	}

}
?>