<?php

class ActiveImageButtonTestCase extends PradoGenericSelenium2Test
{
	public function test()
	{
		$base = 'ctl0_Content_';
		$this->url("active-controls/index.php?page=ActiveImageButtonTest");
		$this->assertSourceContains("TActiveImageButton Functional Test");
		$this->assertText("{$base}label1", "Label 1");
		$this->byId("{$base}image1")->click();
		$this->pauseFairAmount();
		//unable to determine mouse position
		$this->assertMatchesRegularExpression('/Image clicked at x=\d+, y=\d+/', $this->source());
	}
}
