<?php

class Ticket239TestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$this->url('tickets/index.php?page=Ticket239');

		// view1
		$this->assertContains('view1 is activated', $this->source());
		$this->assertNotContains('view1 is deactivated', $this->source());
		$this->assertNotContains('view2 is activated', $this->source());
		$this->assertNotContains('view2 is deactivated', $this->source());
		$this->assertNotContains('view3 is activated', $this->source());
		$this->assertNotContains('view3 is deactivated', $this->source());

		// goto view2
		$this->byName('ctl0$Content$ctl1')->click();
		$this->assertNotContains('view1 is activated', $this->source());
		$this->assertContains('view1 is deactivated', $this->source());
		$this->assertContains('view2 is activated', $this->source());
		$this->assertNotContains('view2 is deactivated', $this->source());
		$this->assertNotContains('view3 is activated', $this->source());
		$this->assertNotContains('view3 is deactivated', $this->source());

		// goto view3
		$this->byName('ctl0$Content$ctl3')->click();
		$this->assertNotContains('view1 is activated', $this->source());
		$this->assertNotContains('view1 is deactivated', $this->source());
		$this->assertNotContains('view2 is activated', $this->source());
		$this->assertContains('view2 is deactivated', $this->source());
		$this->assertContains('view3 is activated', $this->source());
		$this->assertNotContains('view3 is deactivated', $this->source());

		// goto view2
		$this->byName('ctl0$Content$ctl4')->click();
		$this->assertNotContains('view1 is activated', $this->source());
		$this->assertNotContains('view1 is deactivated', $this->source());
		$this->assertContains('view2 is activated', $this->source());
		$this->assertNotContains('view2 is deactivated', $this->source());
		$this->assertNotContains('view3 is activated', $this->source());
		$this->assertContains('view3 is deactivated', $this->source());

		// goto view1
		$this->byName('ctl0$Content$ctl2')->click();
		$this->assertContains('view1 is activated', $this->source());
		$this->assertNotContains('view1 is deactivated', $this->source());
		$this->assertNotContains('view2 is activated', $this->source());
		$this->assertContains('view2 is deactivated', $this->source());
		$this->assertNotContains('view3 is activated', $this->source());
		$this->assertNotContains('view3 is deactivated', $this->source());
	}
}
