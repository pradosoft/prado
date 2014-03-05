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
		$this->byId("ctl0_Content_ctl1")->click();
		$this->assertVisible('ctl0_Content_ctl0');
		$this->assertNotVisible('ctl0_Content_ctl2');
		$this->assertNotVisible('ctl0_Content_ctl4');

		// verify the first validation is passed
		$this->pause(500);
		$this->assertNotContains('Button1 is clicked', $this->source());
		$this->type("ctl0_Content_TextBox1", "test");
		$this->byId("ctl0_Content_ctl1")->click();
		$this->assertNotVisible('ctl0_Content_ctl0');
		$this->assertNotVisible('ctl0_Content_ctl2');
		$this->assertNotVisible('ctl0_Content_ctl4');
		$this->assertContains('Button1 is clicked and valid', $this->source());

		// verify the second validator shows the error
		$this->byId("ctl0_Content_ctl3")->click();
		$this->assertNotVisible('ctl0_Content_ctl0');
		$this->assertVisible('ctl0_Content_ctl2');
		$this->assertNotVisible('ctl0_Content_ctl4');

		// verify the second validation is passed
		$this->pause(500);
		$this->assertNotContains('Button2 is clicked', $this->source());
		$this->type("ctl0_Content_TextBox2", "test");
		$this->byId("ctl0_Content_ctl3")->click();
		$this->assertNotVisible('ctl0_Content_ctl0');
		$this->assertNotVisible('ctl0_Content_ctl2');
		$this->assertNotVisible('ctl0_Content_ctl4');
		$this->assertContains('Button2 is clicked and valid', $this->source());

		// verify the third validator shows the error
		$this->byId("ctl0_Content_ctl5")->click();
		$this->assertNotVisible('ctl0_Content_ctl0');
		$this->assertNotVisible('ctl0_Content_ctl2');
		$this->assertVisible('ctl0_Content_ctl4');

		// verify the third validation is passed
		$this->assertContains('Button3 is clicked', $this->source());
		$this->assertNotContains('Button3 is clicked and valid', $this->source());
		$this->type("ctl0_Content_TextBox3", "test");
		$this->byId("ctl0_Content_ctl5")->click();
		$this->assertNotVisible('ctl0_Content_ctl0');
		$this->assertNotVisible('ctl0_Content_ctl2');
		$this->assertNotVisible('ctl0_Content_ctl4');
		$this->assertContains('Button3 is clicked and valid', $this->source());
	}
}
