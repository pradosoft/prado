<?php

class Ticket823TestCase extends SeleniumTestCase {

	public function test() {
		$this->open('tickets/index.php?page=Ticket823');
		$this->assertTitle("Verifying Ticket 823");
		$base = 'ctl0_Content_';
		$this->assertElementPresent('xpath=//option[@value="Choose..."]');		
	}
}

?>
