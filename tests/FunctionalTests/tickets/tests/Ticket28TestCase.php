<?php

class Ticket28TestCase extends SeleniumTestCase
{
	
	function test()
	{
		$this->open('tickets/index.php?page=Ticket28');
		$this->assertTextPresent('Label 1');
		$this->clickAndWait('link=Click Me');
		$this->assertTextPresent('Link Button 1 Clicked!');
	}
}

?>