<?php

class LinkButtonTestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$this->url('validators/index.php?page=LinkButton');

		// verify all error messages are invisible
		$this->assertNotVisible('ctl0_Content_ctl0');
		$this->assertNotVisible('ctl0_Content_ctl2');
		$this->assertNotVisible('ctl0_Content_ctl4');

		// verify the first validator shows the error
		$this->click("ctl0_Content_ctl1");
		$this->assertVisible('ctl0_Content_ctl0');
		$this->assertNotVisible('ctl0_Content_ctl2');
		$this->assertNotVisible('ctl0_Content_ctl4');

		// verify the first validation is passed
		$this->pause(500);
		$this->assertTextNotPresent('Button1 is clicked');
		$this->type("ctl0_Content_TextBox1", "test");
		$this->clickAndWait("ctl0_Content_ctl1");
		$this->assertNotVisible('ctl0_Content_ctl0');
		$this->assertNotVisible('ctl0_Content_ctl2');
		$this->assertNotVisible('ctl0_Content_ctl4');
		$this->assertTextPresent('Button1 is clicked and valid');

		// verify the second validator shows the error
		$this->click("ctl0_Content_ctl3");
		$this->assertNotVisible('ctl0_Content_ctl0');
		$this->assertVisible('ctl0_Content_ctl2');
		$this->assertNotVisible('ctl0_Content_ctl4');

		// verify the second validation is passed
		$this->pause(500);
		$this->assertTextNotPresent('Button2 is clicked');
		$this->type("ctl0_Content_TextBox2", "test");
		$this->clickAndWait("ctl0_Content_ctl3");
		$this->assertNotVisible('ctl0_Content_ctl0');
		$this->assertNotVisible('ctl0_Content_ctl2');
		$this->assertNotVisible('ctl0_Content_ctl4');
		$this->assertTextPresent('Button2 is clicked and valid');

		// verify the third validator shows the error
		$this->clickAndWait("ctl0_Content_ctl5");
		$this->assertNotVisible('ctl0_Content_ctl0');
		$this->assertNotVisible('ctl0_Content_ctl2');
		$this->assertVisible('ctl0_Content_ctl4');

		// verify the third validation is passed
		$this->assertTextPresent('Button3 is clicked');
		$this->assertTextNotPresent('Button3 is clicked and valid');
		$this->type("ctl0_Content_TextBox3", "test");
		$this->clickAndWait("ctl0_Content_ctl5");
		$this->assertNotVisible('ctl0_Content_ctl0');
		$this->assertNotVisible('ctl0_Content_ctl2');
		$this->assertNotVisible('ctl0_Content_ctl4');
		$this->assertTextPresent('Button3 is clicked and valid');
	}
}
