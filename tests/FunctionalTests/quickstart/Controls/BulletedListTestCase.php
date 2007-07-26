<?php

class BulletedListTestCase extends SeleniumTestCase
{
	function test()
	{
		$this->open("../../demos/quickstart/index.php?page=Controls.Samples.TBulletedList.Home&amp;notheme=true&amp;lang=en", "");

		// verify if all required texts are present
		$this->verifyTextPresent('item 1','');
		$this->verifyTextPresent('item 2','');
		$this->verifyTextPresent('item 3','');
		$this->verifyTextPresent('item 4','');
		$this->verifyTextPresent('google','');
		$this->verifyTextPresent('yahoo','');
		$this->verifyTextPresent('amazon','');

		// verify order list starting from 5
		$this->verifyElementPresent("//ol[@start='5']");

		// unable to verify styles

		// verify hyperlink list
		$this->verifyElementPresent("//a[@href='http://www.google.com/']");
		$this->verifyElementPresent("//a[@href='http://www.yahoo.com/']");
		$this->verifyElementPresent("//a[@href='http://www.amazon.com/']");

		// verify linkbutton list
		$this->clickAndWait("id=ctl0_body_ctl40", "");
		$this->verifyTextPresent("You clicked google : http://www.google.com/.", "");
		$this->clickAndWait("id=ctl0_body_ctl41", "");
		$this->verifyTextPresent("You clicked yahoo : http://www.yahoo.com/.", "");
		$this->clickAndWait("id=ctl0_body_ctl42", "");
		$this->verifyTextPresent("You clicked amazon : http://www.amazon.com/.", "");
	}
}

?>