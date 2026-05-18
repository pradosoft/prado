<?php

class EventTriggerTestCase extends \Prado\Tests\PradoGenericSelenium2Test
{
	public function test()
	{
		$base = "ctl0_Content_";
		$this->url("active-controls/index.php?page=EventTriggeredCallback");
		$this->assertSourceContains("Event Triggered Callback Test");

		$this->assertText("{$base}label1", 'Label 1');

		$this->byId("button1")->click();
		$this->assertText("{$base}label1", 'button 1 clicked');

		$this->type("{$base}text1", 'test');
		$this->assertText("{$base}label1", 'text 1 focused');
	}
}
