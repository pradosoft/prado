<?php

class QuickstartBulletedListTestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$this->url("../../demos/quickstart/index.php?page=Controls.Samples.TBulletedList.Home&amp;notheme=true&amp;lang=en");

		// verify if all required texts are present
		$this->assertContains('item 1', $this->source());
		$this->assertContains('item 2', $this->source());
		$this->assertContains('item 3', $this->source());
		$this->assertContains('item 4', $this->source());
		$this->assertContains('google', $this->source());
		$this->assertContains('yahoo', $this->source());
		$this->assertContains('amazon', $this->source());

		// verify order list starting from 5
		$this->assertElementPresent("//ol[@start='5']");

		// unable to verify styles

		// verify hyperlink list
		$this->assertElementPresent("//a[@href='http://www.google.com/']");
		$this->assertElementPresent("//a[@href='http://www.yahoo.com/']");
		$this->assertElementPresent("//a[@href='http://www.amazon.com/']");

		// verify linkbutton list
		$this->byId("ctl0_body_ctl40")->click();
		$this->assertContains("You clicked google : http://www.google.com/.", $this->source());
		$this->byId("ctl0_body_ctl41")->click();
		$this->assertContains("You clicked yahoo : http://www.yahoo.com/.", $this->source());
		$this->byId("ctl0_body_ctl42")->click();
		$this->assertContains("You clicked amazon : http://www.amazon.com/.", $this->source());
	}
}
