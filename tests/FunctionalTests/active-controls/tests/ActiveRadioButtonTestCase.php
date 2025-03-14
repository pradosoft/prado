<?php

class ActiveRadioButtonTestCase extends PradoGenericSelenium2Test
{
	public function test()
	{
		$base = 'ctl0_Content_';
		$this->url("active-controls/index.php?page=ActiveRadioButtonTest");
		$this->assertSourceContains("Active Radio Button Test");
		$this->assertText("{$base}label1", 'Label 1');

		$this->assertNotChecked("{$base}radio1");
		$this->assertNotChecked("{$base}radio2");
		$this->assertNotChecked("{$base}radio3");

		$this->assertText("{$base}radio1_label", 'Radio Button 1');
		$this->assertText("{$base}radio2_label", 'Radio Button 2');
		$this->assertText("{$base}radio3_label", 'Radio Button 3');

		$this->byId("{$base}change_text1")->click();
		$this->assertText("{$base}radio1_label", 'Hello Radio Button 1');
		$this->assertText("{$base}radio2_label", 'Radio Button 2');
		$this->assertText("{$base}radio3_label", 'Radio Button 3');

		$this->byId("{$base}change_text2")->click();
		$this->assertText("{$base}radio1_label", 'Hello Radio Button 1');
		$this->assertText("{$base}radio2_label", 'Radio Button 2 World');
		$this->assertText("{$base}radio3_label", 'Radio Button 3');

		$this->byId("{$base}change_radio1")->click();
		$this->assertChecked("{$base}radio1");
		$this->assertNotChecked("{$base}radio2");
		$this->assertNotChecked("{$base}radio3");

		$this->byId("{$base}change_radio2")->click();
		$this->assertNotChecked("{$base}radio1");
		$this->assertChecked("{$base}radio2");
		$this->assertNotChecked("{$base}radio3");


		$this->byId("{$base}radio3")->click();
		$this->assertNotChecked("{$base}radio1");
		$this->assertChecked("{$base}radio2");
		$this->assertChecked("{$base}radio3");
		$this->assertText("{$base}label1", 'Label 1:Radio Button 3 Checked');
	}
}
