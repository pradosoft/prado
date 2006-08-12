<?php

class ActiveCheckBoxTestCase extends SeleniumTestCase
{
	function test()
	{
		$this->open("active-controls/index.php?page=ActiveCheckBoxTest");
		$this->verifyTextPresent("Active CheckBox Test");

		$this->assertText("checkbox1_label", "CheckBox 1");
		$this->assertText("checkbox2_label", "CheckBox 2");
		$this->assertText('label1', 'Label 1');

		$this->click("change_text1");
		$this->pause(800);
		$this->assertText('checkbox1_label', 'Hello CheckBox 1');

		$this->click("change_text2");
		$this->pause(800);
		$this->assertText('checkbox2_label', 'CheckBox 2 World');

		//check box 1
		$this->click('change_checked1');
		$this->pause(800);
		$this->assertChecked('checkbox1');

		$this->click('change_checked1');
		$this->pause(800);
		$this->assertNotChecked('checkbox1');

		//check box 2
		$this->click('change_checked2');
		$this->pause(800);
		$this->assertChecked('checkbox2');

		$this->click('change_checked2');
		$this->pause(800);
		$this->assertNotChecked('checkbox2');

		//click checkbox 1
		$this->click("checkbox1");
		$this->pause(800);
		$this->assertText("label1", "Label 1:Hello CheckBox 1 Checked");

		$this->click("checkbox1");
		$this->pause(800);
		$this->assertText("label1", "Label 1:Hello CheckBox 1 Not Checked");

		//click checkbox 2
		$this->click("checkbox2");
		$this->pause(800);
		$this->assertText("label1", "Label 1:CheckBox 2 World Checked");

		$this->click("checkbox2");
		$this->pause(800);
		$this->assertText("label1", "Label 1:CheckBox 2 World Not Checked");

	}
}

?>