<?php

class Ticket876TestCase extends SeleniumTestCase {

	public function test() {
		$this->open('tickets/index.php?page=Ticket876');
		$this->assertTitle("Verifying Ticket 876");
		$base = 'ctl0_Content_';
		
		$this->assertElementPresent('xpath=//link[@rel="stylesheet"]');
		$this->clickAndWait($base.'Button');
		$this->assertElementNotPresent('xpath=//link[@rel="stylesheet"]');
		
		/*$this->select($base.'Date_month', 10);
		$this->select($base.'Date_day', 22);
		
		$this->clickAndWait($base.'SendButton');
		$this->assertTextPresent('2008-10-22');*/
	}
	
}

?>
