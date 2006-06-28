<?php

class Ticket239TestCase extends SeleniumTestCase
{
	function test()
	{
		$this->open('tickets/index.php?page=Ticket239');

		// view1
		$this->verifyTextPresent('view1 is activated','');
		$this->verifyTextNotPresent('view1 is deactivated','');
		$this->verifyTextNotPresent('view2 is activated','');
		$this->verifyTextNotPresent('view2 is deactivated','');
		$this->verifyTextNotPresent('view3 is activated','');
		$this->verifyTextNotPresent('view3 is deactivated','');

		// goto view2
		$this->clickAndWait('name=ctl0$Content$ctl1');
		$this->verifyTextNotPresent('view1 is activated','');
		$this->verifyTextPresent('view1 is deactivated','');
		$this->verifyTextPresent('view2 is activated','');
		$this->verifyTextNotPresent('view2 is deactivated','');
		$this->verifyTextNotPresent('view3 is activated','');
		$this->verifyTextNotPresent('view3 is deactivated','');

		// goto view3
		$this->clickAndWait('name=ctl0$Content$ctl3');
		$this->verifyTextNotPresent('view1 is activated','');
		$this->verifyTextNotPresent('view1 is deactivated','');
		$this->verifyTextNotPresent('view2 is activated','');
		$this->verifyTextPresent('view2 is deactivated','');
		$this->verifyTextPresent('view3 is activated','');
		$this->verifyTextNotPresent('view3 is deactivated','');

		// goto view2
		$this->clickAndWait('name=ctl0$Content$ctl4');
		$this->verifyTextNotPresent('view1 is activated','');
		$this->verifyTextNotPresent('view1 is deactivated','');
		$this->verifyTextPresent('view2 is activated','');
		$this->verifyTextNotPresent('view2 is deactivated','');
		$this->verifyTextNotPresent('view3 is activated','');
		$this->verifyTextPresent('view3 is deactivated','');

		// goto view1
		$this->clickAndWait('name=ctl0$Content$ctl2');
		$this->verifyTextPresent('view1 is activated','');
		$this->verifyTextNotPresent('view1 is deactivated','');
		$this->verifyTextNotPresent('view2 is activated','');
		$this->verifyTextPresent('view2 is deactivated','');
		$this->verifyTextNotPresent('view3 is activated','');
		$this->verifyTextNotPresent('view3 is deactivated','');
	}
}

?>