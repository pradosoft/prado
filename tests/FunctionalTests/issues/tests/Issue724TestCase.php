<?php

/**
 * Testcase for Issue 724
 * Multiple callbacks can be queued, each one being run when the previous one returns.
 * If the previous one changed the state of controls, the following callbacks must use the
 * newly refreshed data from controls instead of the stale, old one.
 **/
class Issue724TestCase extends PradoGenericSelenium2Test
{
	public function test()
	{
		$this->url('issues/index.php?page=Issue724');
		$this->assertSourceContains('Issue 724 Test');
		$base = 'ctl0_Content_';

		$this->byID("{$base}cmdA")->click();
		$this->byID("{$base}cmdB")->click();
		sleep(6);
		$this->assertText("{$base}labelA", "Button A Pressed");
		$this->assertText("{$base}labelB", "When button has been B pressed, the text of label A was: Button A Pressed");
	}
}
