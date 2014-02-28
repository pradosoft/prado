<?php

class Ticket239TestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$this->url('tickets/index.php?page=Ticket239');

		// view1
		$this->assertTextPresent('view1 is activated','');
		$this->assertTextNotPresent('view1 is deactivated','');
		$this->assertTextNotPresent('view2 is activated','');
		$this->assertTextNotPresent('view2 is deactivated','');
		$this->assertTextNotPresent('view3 is activated','');
		$this->assertTextNotPresent('view3 is deactivated','');

		// goto view2
		$this->clickAndWait('name=ctl0$Content$ctl1');
		$this->assertTextNotPresent('view1 is activated','');
		$this->assertTextPresent('view1 is deactivated','');
		$this->assertTextPresent('view2 is activated','');
		$this->assertTextNotPresent('view2 is deactivated','');
		$this->assertTextNotPresent('view3 is activated','');
		$this->assertTextNotPresent('view3 is deactivated','');

		// goto view3
		$this->clickAndWait('name=ctl0$Content$ctl3');
		$this->assertTextNotPresent('view1 is activated','');
		$this->assertTextNotPresent('view1 is deactivated','');
		$this->assertTextNotPresent('view2 is activated','');
		$this->assertTextPresent('view2 is deactivated','');
		$this->assertTextPresent('view3 is activated','');
		$this->assertTextNotPresent('view3 is deactivated','');

		// goto view2
		$this->clickAndWait('name=ctl0$Content$ctl4');
		$this->assertTextNotPresent('view1 is activated','');
		$this->assertTextNotPresent('view1 is deactivated','');
		$this->assertTextPresent('view2 is activated','');
		$this->assertTextNotPresent('view2 is deactivated','');
		$this->assertTextNotPresent('view3 is activated','');
		$this->assertTextPresent('view3 is deactivated','');

		// goto view1
		$this->clickAndWait('name=ctl0$Content$ctl2');
		$this->assertTextPresent('view1 is activated','');
		$this->assertTextNotPresent('view1 is deactivated','');
		$this->assertTextNotPresent('view2 is activated','');
		$this->assertTextPresent('view2 is deactivated','');
		$this->assertTextNotPresent('view3 is activated','');
		$this->assertTextNotPresent('view3 is deactivated','');
	}
}
