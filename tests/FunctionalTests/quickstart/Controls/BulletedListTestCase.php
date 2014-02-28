<?php

class QuickstartBulletedListTestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$this->url("../../demos/quickstart/index.php?page=Controls.Samples.TBulletedList.Home&amp;notheme=true&amp;lang=en");

		// verify if all required texts are present
		$this->assertTextPresent('item 1','');
		$this->assertTextPresent('item 2','');
		$this->assertTextPresent('item 3','');
		$this->assertTextPresent('item 4','');
		$this->assertTextPresent('google','');
		$this->assertTextPresent('yahoo','');
		$this->assertTextPresent('amazon','');

		// verify order list starting from 5
		$this->assertElementPresent("//ol[@start='5']");

		// unable to verify styles

		// verify hyperlink list
		$this->assertElementPresent("//a[@href='http://www.google.com/']");
		$this->assertElementPresent("//a[@href='http://www.yahoo.com/']");
		$this->assertElementPresent("//a[@href='http://www.amazon.com/']");

		// verify linkbutton list
		$this->clickAndWait("id=ctl0_body_ctl40", "");
		$this->assertTextPresent("You clicked google : http://www.google.com/.", "");
		$this->clickAndWait("id=ctl0_body_ctl41", "");
		$this->assertTextPresent("You clicked yahoo : http://www.yahoo.com/.", "");
		$this->clickAndWait("id=ctl0_body_ctl42", "");
		$this->assertTextPresent("You clicked amazon : http://www.amazon.com/.", "");
	}
}
