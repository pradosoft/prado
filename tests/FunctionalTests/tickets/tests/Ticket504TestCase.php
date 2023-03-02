<?php

class Ticket504TestCase extends PradoGenericSelenium2Test
{
	public function test()
	{
		$base = 'ctl0_Content_';
		$this->url('tickets/index.php?page=Ticket504');
		$this->assertEquals("Verifying Ticket 504", $this->title());

		$this->assertText("status", "");

		$this->assertVisible("{$base}panelA");
		$this->assertVisible("{$base}panelB");
		$this->assertVisible("{$base}panelC");
		$this->assertVisible("{$base}panelD");

		$this->byId("{$base}linka")->click();
		$this->pauseFairAmount();
		$this->assertVisible("{$base}panelA");
		$this->assertNotVisible("{$base}panelB");
		$this->assertNotVisible("{$base}panelC");
		$this->assertNotVisible("{$base}panelD");
		$this->assertText("status", "panelA updated");

		$this->byId("{$base}linkb")->click();
		$this->pauseFairAmount();
		$this->assertNotVisible("{$base}panelA");
		$this->assertVisible("{$base}panelB");
		$this->assertNotVisible("{$base}panelC");
		$this->assertNotVisible("{$base}panelD");
		$this->assertText("status", "panelB updated");

		$this->byId("{$base}linkc")->click();
		$this->pauseFairAmount();
		$this->assertNotVisible("{$base}panelA");
		$this->assertNotVisible("{$base}panelB");
		$this->assertVisible("{$base}panelC");
		$this->assertNotVisible("{$base}panelD");
		$this->assertText("status", "panelC updated");

		$this->byId("{$base}linkd")->click();
		$this->pauseFairAmount();
		$this->assertNotVisible("{$base}panelA");
		$this->assertNotVisible("{$base}panelB");
		$this->assertNotVisible("{$base}panelC");
		$this->assertVisible("{$base}panelD");
		$this->assertText("status", "panelD updated");
	}
}
