<?php

class EventTriggerTestCase extends SeleniumTestCase
{
	function test()
	{
		$this->open("active-controls/index.php?page=EventTriggeredCallback");
		$this->verifyTextPresent("Event Triggered Callback Test");

		$this->assertText('label1', 'Label 1');

		$this->click('button1');
		$this->pause(800);
		$this->assertText('label1', 'button 1 clicked');

		$this->type('text1', 'test');
		$this->pause(800);
		$this->assertText('label1', 'text 1 focused');
	}
}

?>