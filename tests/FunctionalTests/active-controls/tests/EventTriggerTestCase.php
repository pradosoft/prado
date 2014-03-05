<?php

class EventTriggerTestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$base = "ctl0_Content_";
		$this->url("active-controls/index.php?page=EventTriggeredCallback");
		$this->assertContains("Event Triggered Callback Test", $this->source());

		$this->assertText("{$base}label1", 'Label 1');

		$this->byId("button1")->click();
		$this->pause(800);
		$this->assertText("{$base}label1", 'button 1 clicked');

		$this->type("{$base}text1", 'test');
		$this->pause(800);
		$this->assertText("{$base}label1", 'text 1 focused');
	}
}
