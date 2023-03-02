<?php

class QuickstartBulletedListTestCase extends PradoDemosSelenium2Test
{
	public function test()
	{
		$this->url("quickstart/index.php?page=Controls.Samples.TBulletedList.Home&amp;notheme=true&amp;lang=en");

		// verify if all required texts are present
		$this->assertSourceContains('item 1');
		$this->assertSourceContains('item 2');
		$this->assertSourceContains('item 3');
		$this->assertSourceContains('item 4');
		$this->assertSourceContains('google');
		$this->assertSourceContains('yahoo');
		$this->assertSourceContains('amazon');

		// verify order list starting from 5
		$this->assertElementPresent("//ol[@start='5']");

		// unable to verify styles

		// verify hyperlink list
		$this->assertElementPresent("//a[@href='http://www.google.com/']");
		$this->assertElementPresent("//a[@href='http://www.yahoo.com/']");
		$this->assertElementPresent("//a[@href='http://www.amazon.com/']");

		// verify linkbutton list
		$this->byId("ctl0_body_ctl40")->click();
		$this->assertSourceContains("You clicked google : http://www.google.com/.");
		$this->byId("ctl0_body_ctl41")->click();
		$this->assertSourceContains("You clicked yahoo : http://www.yahoo.com/.");
		$this->byId("ctl0_body_ctl42")->click();
		$this->assertSourceContains("You clicked amazon : http://www.amazon.com/.");
	}
}
