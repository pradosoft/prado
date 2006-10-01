<?php

class ActiveRadioButtonTestCase extends SeleniumTestCase
{
	function test()
	{
		//problem with test runner clicking on radio buttons
		$this->skipBrowsers(self::OPERA);

		$this->open("active-controls/index.php?page=ActiveRadioButtonTest");
		$this->verifyTextPresent("Active Radio Button Test");
		$this->assertText('label1', 'Label 1');

		$this->assertNotChecked('radio1');
		$this->assertNotChecked('radio2');
		$this->assertNotChecked('radio3');

		$this->assertText('radio1_label', 'Radio Button 1');
		$this->assertText('radio2_label', 'Radio Button 2');
		$this->assertText('radio3_label', 'Radio Button 3');

		$this->click('change_text1');
		$this->pause(800);
		$this->assertText('radio1_label', 'Hello Radio Button 1');
		$this->assertText('radio2_label', 'Radio Button 2');
		$this->assertText('radio3_label', 'Radio Button 3');

		$this->click('change_text2');
		$this->pause(800);
		$this->assertText('radio1_label', 'Hello Radio Button 1');
		$this->assertText('radio2_label', 'Radio Button 2 World');
		$this->assertText('radio3_label', 'Radio Button 3');

		$this->click('change_radio1');
		$this->pause(800);
		$this->assertChecked('radio1');
		$this->assertNotChecked('radio2');
		$this->assertNotChecked('radio3');

		$this->click('change_radio2');
		$this->pause(800);
		$this->assertNotChecked('radio1');
		$this->assertChecked('radio2');
		$this->assertNotChecked('radio3');


		$this->click('radio3');
		$this->pause(800);
		$this->assertNotChecked('radio1');
		$this->assertChecked('radio2');
		$this->assertChecked('radio3');
		$this->assertText('label1', 'Label 1:Radio Button 3 Checked');


	}
}
?>