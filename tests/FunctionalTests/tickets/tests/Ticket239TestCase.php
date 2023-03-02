<?php

class Ticket239TestCase extends PradoGenericSelenium2Test
{
	public function test()
	{
		$this->url('tickets/index.php?page=Ticket239');

		// view1
		$this->assertSourceContains('view1 is activated');
		$this->assertSourceNotContains('view1 is deactivated');
		$this->assertSourceNotContains('view2 is activated');
		$this->assertSourceNotContains('view2 is deactivated');
		$this->assertSourceNotContains('view3 is activated');
		$this->assertSourceNotContains('view3 is deactivated');

		// goto view2
		$this->byName('ctl0$Content$ctl1')->click();
		$this->assertSourceNotContains('view1 is activated');
		$this->assertSourceContains('view1 is deactivated');
		$this->assertSourceContains('view2 is activated');
		$this->assertSourceNotContains('view2 is deactivated');
		$this->assertSourceNotContains('view3 is activated');
		$this->assertSourceNotContains('view3 is deactivated');

		// goto view3
		$this->byName('ctl0$Content$ctl3')->click();
		$this->assertSourceNotContains('view1 is activated');
		$this->assertSourceNotContains('view1 is deactivated');
		$this->assertSourceNotContains('view2 is activated');
		$this->assertSourceContains('view2 is deactivated');
		$this->assertSourceContains('view3 is activated');
		$this->assertSourceNotContains('view3 is deactivated');

		// goto view2
		$this->byName('ctl0$Content$ctl4')->click();
		$this->assertSourceNotContains('view1 is activated');
		$this->assertSourceNotContains('view1 is deactivated');
		$this->assertSourceContains('view2 is activated');
		$this->assertSourceNotContains('view2 is deactivated');
		$this->assertSourceNotContains('view3 is activated');
		$this->assertSourceContains('view3 is deactivated');

		// goto view1
		$this->byName('ctl0$Content$ctl2')->click();
		$this->assertSourceContains('view1 is activated');
		$this->assertSourceNotContains('view1 is deactivated');
		$this->assertSourceNotContains('view2 is activated');
		$this->assertSourceContains('view2 is deactivated');
		$this->assertSourceNotContains('view3 is activated');
		$this->assertSourceNotContains('view3 is deactivated');
	}
}
