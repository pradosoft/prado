<?php

class LinkButtonTestCase extends SeleniumTestCase
{
	function test()
	{
		$this->open('validators/index.php?page=LinkButton');

		// verify all error messages are invisible
		$this->verifyNotVisible('ctl0_Content_ctl0');
		$this->verifyNotVisible('ctl0_Content_ctl2');
		$this->verifyNotVisible('ctl0_Content_ctl4');

		// verify the first validator shows the error
		$this->click("ctl0_Content_ctl1");
		$this->verifyVisible('ctl0_Content_ctl0');
		$this->verifyNotVisible('ctl0_Content_ctl2');
		$this->verifyNotVisible('ctl0_Content_ctl4');

		// verify the first validation is passed
		$this->pause(500);
		$this->verifyTextNotPresent('Button1 is clicked');
		$this->type("ctl0_Content_TextBox1", "test");
		$this->clickAndWait("ctl0_Content_ctl1");
		$this->verifyNotVisible('ctl0_Content_ctl0');
		$this->verifyNotVisible('ctl0_Content_ctl2');
		$this->verifyNotVisible('ctl0_Content_ctl4');
		$this->verifyTextPresent('Button1 is clicked and valid');

		// verify the second validator shows the error
		$this->click("ctl0_Content_ctl3");
		$this->verifyNotVisible('ctl0_Content_ctl0');
		$this->verifyVisible('ctl0_Content_ctl2');
		$this->verifyNotVisible('ctl0_Content_ctl4');

		// verify the second validation is passed
		$this->pause(500);
		$this->verifyTextNotPresent('Button2 is clicked');
		$this->type("ctl0_Content_TextBox2", "test");
		$this->clickAndWait("ctl0_Content_ctl3");
		$this->verifyNotVisible('ctl0_Content_ctl0');
		$this->verifyNotVisible('ctl0_Content_ctl2');
		$this->verifyNotVisible('ctl0_Content_ctl4');
		$this->verifyTextPresent('Button2 is clicked and valid');

		// verify the third validator shows the error
		$this->clickAndWait("ctl0_Content_ctl5");
		$this->verifyNotVisible('ctl0_Content_ctl0');
		$this->verifyNotVisible('ctl0_Content_ctl2');
		$this->verifyVisible('ctl0_Content_ctl4');

		// verify the third validation is passed
		$this->verifyTextPresent('Button3 is clicked');
		$this->verifyTextNotPresent('Button3 is clicked and valid');
		$this->type("ctl0_Content_TextBox3", "test");
		$this->clickAndWait("ctl0_Content_ctl5");
		$this->verifyNotVisible('ctl0_Content_ctl0');
		$this->verifyNotVisible('ctl0_Content_ctl2');
		$this->verifyNotVisible('ctl0_Content_ctl4');
		$this->verifyTextPresent('Button3 is clicked and valid');
	}
}

?>