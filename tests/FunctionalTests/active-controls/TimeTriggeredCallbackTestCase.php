<?php

class TimeTriggeredCallbackTestCase extends \Prado\Tests\PradoGenericSelenium2Test
{
	public function test()
	{
		$base = 'ctl0_Content_';
		$this->url("active-controls/index.php?page=TimeTriggeredCallbackTest");
		$this->assertSourceContains("TimeTriggeredCallback + ViewState Tests");

		$this->assertText("{$base}label1", "ViewState Counter :");

		$this->byId("{$base}button1")->click();

		$this->pause(8000);

		$this->assertText("{$base}label1", "ViewState Counter : 1 2 3 4 5 6 7 8 9 10");
	}
}
