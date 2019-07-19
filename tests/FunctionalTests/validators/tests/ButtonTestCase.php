<?php

class ButtonTestCase extends PradoGenericSelenium2Test
{
	public function test()
	{
		$this->url('validators/index.php?page=Button');


		// verify all error messages are invisible
		$this->assertNotVisible('ctl0_Content_ctl0');
		$this->assertNotVisible('ctl0_Content_ctl2');
		$this->assertNotVisible('ctl0_Content_ctl4');

		// verify the first validator shows the error
		$this->byId("ctl0_Content_ctl1")->click();
		$this->assertVisible('ctl0_Content_ctl0');
		$this->assertNotVisible('ctl0_Content_ctl2');
		$this->assertNotVisible('ctl0_Content_ctl4');

		// verify the first validation is passed
		$this->pause(500);
		$this->assertSourceNotContains('Button1 is clicked');
		$this->type("ctl0_Content_TextBox1", "test");
		$this->byId("ctl0_Content_ctl1")->click();
		$this->assertNotVisible('ctl0_Content_ctl0');
		$this->assertNotVisible('ctl0_Content_ctl2');
		$this->assertNotVisible('ctl0_Content_ctl4');
		$this->assertSourceContains('Button1 is clicked and valid');

		// verify the second validator shows the error
		$this->byId("ctl0_Content_ctl3")->click();
		$this->assertNotVisible('ctl0_Content_ctl0');
		$this->assertVisible('ctl0_Content_ctl2');
		$this->assertNotVisible('ctl0_Content_ctl4');

		// verify the second validation is passed
		$this->pause(500);
		$this->assertSourceNotContains('Button2 is clicked');
		$this->type("ctl0_Content_TextBox2", "test");
		$this->byId("ctl0_Content_ctl3")->click();
		$this->assertNotVisible('ctl0_Content_ctl0');
		$this->assertNotVisible('ctl0_Content_ctl2');
		$this->assertNotVisible('ctl0_Content_ctl4');
		$this->assertSourceContains('Button2 is clicked and valid');

		// verify the third validator shows the error
		$this->byId("ctl0_Content_ctl5")->click();
		$this->assertNotVisible('ctl0_Content_ctl0');
		$this->assertNotVisible('ctl0_Content_ctl2');
		$this->assertVisible('ctl0_Content_ctl4');

		// verify the third validation is passed
		$this->assertSourceContains('Button3 is clicked');
		$this->assertSourceNotContains('Button3 is clicked and valid');
		$this->type("ctl0_Content_TextBox3", "test");
		$this->byId("ctl0_Content_ctl5")->click();
		$this->assertNotVisible('ctl0_Content_ctl0');
		$this->assertNotVisible('ctl0_Content_ctl2');
		$this->assertNotVisible('ctl0_Content_ctl4');
		$this->assertSourceContains('Button3 is clicked and valid');
	}
}
