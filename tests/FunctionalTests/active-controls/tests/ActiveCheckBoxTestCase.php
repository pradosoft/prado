<?php

class ActiveCheckBoxTestCase extends \Prado\Tests\PradoGenericSelenium2Test
{
	public function test()
	{
		$base = 'ctl0_Content_';
		$this->url("active-controls/index.php?page=ActiveCheckBoxTest");
		$this->assertSourceContains("Active CheckBox Test");

		$this->assertText("{$base}checkbox1_label", "CheckBox 1");
		$this->assertText("{$base}checkbox2_label", "CheckBox 2");
		$this->assertText("{$base}label1", 'Label 1');

		$this->byId("{$base}change_text1")->click();
		$this->assertText("{$base}checkbox1_label", 'Hello CheckBox 1');

		$this->byId("{$base}change_text2")->click();
		$this->assertText("{$base}checkbox2_label", 'CheckBox 2 World');

		//check box 1
		$this->byId("{$base}change_checked1")->click();
		$this->assertChecked("{$base}checkbox1");

		$this->byId("{$base}change_checked1")->click();
		$this->assertNotChecked("{$base}checkbox1");

		//check box 2
		$this->byId("{$base}change_checked2")->click();
		$this->assertChecked("{$base}checkbox2");

		$this->byId("{$base}change_checked2")->click();
		$this->assertNotChecked("{$base}checkbox2");

		//click checkbox 1
		$this->byId("{$base}checkbox1")->click();
		$this->assertText("{$base}label1", "Label 1:Hello CheckBox 1 Checked");

		$this->byId("{$base}checkbox1")->click();
		$this->assertText("{$base}label1", "Label 1:Hello CheckBox 1 Not Checked");

		//click checkbox 2
		$this->byId("{$base}checkbox2")->click();
		$this->assertText("{$base}label1", "Label 1:CheckBox 2 World Checked");

		$this->byId("{$base}checkbox2")->click();
		$this->assertText("{$base}label1", "Label 1:CheckBox 2 World Not Checked");
	}
}
