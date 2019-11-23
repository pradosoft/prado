<?php

class ActiveRadioButtonTestCase extends PradoGenericSelenium2Test
{
	public function test()
	{
		$base = 'ctl0_Content_';
		$this->url("active-controls/index.php?page=ActiveRadioButtonTest");
		$this->assertSourceContains("Active Radio Button Test");
		$this->assertText("{$base}label1", 'Label 1');

		$this->assertFalse($this->byId("{$base}radio1")->selected());
		$this->assertFalse($this->byId("{$base}radio2")->selected());
		$this->assertFalse($this->byId("{$base}radio3")->selected());

		$this->assertText("{$base}radio1_label", 'Radio Button 1');
		$this->assertText("{$base}radio2_label", 'Radio Button 2');
		$this->assertText("{$base}radio3_label", 'Radio Button 3');

		$this->byId("{$base}change_text1")->click();
		$this->pauseFairAmount();
		$this->assertText("{$base}radio1_label", 'Hello Radio Button 1');
		$this->assertText("{$base}radio2_label", 'Radio Button 2');
		$this->assertText("{$base}radio3_label", 'Radio Button 3');

		$this->byId("{$base}change_text2")->click();
		$this->pauseFairAmount();
		$this->assertText("{$base}radio1_label", 'Hello Radio Button 1');
		$this->assertText("{$base}radio2_label", 'Radio Button 2 World');
		$this->assertText("{$base}radio3_label", 'Radio Button 3');

		$this->byId("{$base}change_radio1")->click();
		$this->pauseFairAmount();
		$this->assertTrue($this->byId("{$base}radio1")->selected());
		$this->assertFalse($this->byId("{$base}radio2")->selected());
		$this->assertFalse($this->byId("{$base}radio3")->selected());

		$this->byId("{$base}change_radio2")->click();
		$this->pauseFairAmount();
		$this->assertFalse($this->byId("{$base}radio1")->selected());
		$this->assertTrue($this->byId("{$base}radio2")->selected());
		$this->assertFalse($this->byId("{$base}radio3")->selected());


		$this->byId("{$base}radio3")->click();
		$this->pauseFairAmount();
		$this->assertFalse($this->byId("{$base}radio1")->selected());
		$this->assertTrue($this->byId("{$base}radio2")->selected());
		$this->assertTrue($this->byId("{$base}radio3")->selected());
		$this->assertText("{$base}label1", 'Label 1:Radio Button 3 Checked');
	}
}
