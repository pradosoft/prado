<?php

class ActiveCheckBoxTestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$base='ctl0_Content_';
		$this->url("active-controls/index.php?page=ActiveCheckBoxTest");
		$this->assertTextPresent("Active CheckBox Test");

		$this->assertText("{$base}checkbox1_label", "CheckBox 1");
		$this->assertText("{$base}checkbox2_label", "CheckBox 2");
		$this->assertText("{$base}label1", 'Label 1');

		$this->click("{$base}change_text1");
		$this->pause(800);
		$this->assertText("{$base}checkbox1_label", 'Hello CheckBox 1');

		$this->click("{$base}change_text2");
		$this->pause(800);
		$this->assertText("{$base}checkbox2_label", 'CheckBox 2 World');

		//check box 1
		$this->click("{$base}change_checked1");
		$this->pause(800);
		$this->assertChecked("{$base}checkbox1");

		$this->click("{$base}change_checked1");
		$this->pause(800);
		$this->assertNotChecked("{$base}checkbox1");

		//check box 2
		$this->click("{$base}change_checked2");
		$this->pause(800);
		$this->assertChecked("{$base}checkbox2");

		$this->click("{$base}change_checked2");
		$this->pause(800);
		$this->assertNotChecked("{$base}checkbox2");

		//click checkbox 1
		$this->click("{$base}checkbox1");
		$this->pause(800);
		$this->assertText("{$base}label1", "Label 1:Hello CheckBox 1 Checked");

		$this->click("{$base}checkbox1");
		$this->pause(800);
		$this->assertText("{$base}label1", "Label 1:Hello CheckBox 1 Not Checked");

		//click checkbox 2
		$this->click("{$base}checkbox2");
		$this->pause(800);
		$this->assertText("{$base}label1", "Label 1:CheckBox 2 World Checked");

		$this->click("{$base}checkbox2");
		$this->pause(800);
		$this->assertText("{$base}label1", "Label 1:CheckBox 2 World Not Checked");

	}
}
