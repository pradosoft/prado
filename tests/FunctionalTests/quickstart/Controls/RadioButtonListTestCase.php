<?php

//New Test
class RadioButtonListTestCase extends SeleniumTestCase
{
	function test ()
	{
		$this->open("../../demos/quickstart/index.php?page=Controls.Samples.TRadioButtonList.Home&amp;notheme=true&amp;lang=en", "");

		// RadioButton list with default settings:
		$this->click("//input[@name='ctl0\$body\$ctl0' and @value='value 3']", "");

		// RadioButton list with customized cellpadding, cellspacing, color and text alignment:
		$this->click("//input[@name='ctl0\$body\$ctl1' and @value='value 1']", "");

		// *** Currently unable to test the following cases:
		// RadioButton list with vertical (default) repeat direction
		// RadioButton list with horizontal repeat direction
		// RadioButton list with flow layout and vertical (default) repeat direction
		// RadioButton list with flow layout and horizontal repeat direction:

		// RadioButton list's behavior upon postback
		$this->click("//input[@name='ctl0\$body\$RadioButtonList' and @value='value 3']", "");
		$this->clickAndWait("//input[@type='submit' and @value='Submit']", "");
		$this->verifyTextPresent("Your selection is: (Index: 2, Value: value 3, Text: item 3)", "");

		// Auto postback check box list
		$this->clickAndWait("//input[@name='ctl0\$body\$ctl7' and @value='value 5']", "");
		$this->verifyTextPresent("Your selection is: (Index: 4, Value: value 5, Text: item 5)", "");

		// Databind to an integer-indexed array
		$this->clickAndWait("//input[@name='ctl0\$body\$DBRadioButtonList1' and @value='0']", "");
		$this->verifyTextPresent("Your selection is: (Index: 0, Value: 0, Text: item 1)", "");

		// Databind to an associative array:
		$this->clickAndWait("//input[@name='ctl0\$body\$DBRadioButtonList2' and @value='key 2']", "");
		$this->verifyTextPresent("Your selection is: (Index: 1, Value: key 2, Text: item 2)", "");

		// Databind with DataTextField and DataValueField specified
		$this->clickAndWait("//input[@name='ctl0\$body\$DBRadioButtonList3' and @value='003']", "");
		$this->verifyTextPresent("Your selection is: (Index: 2, Value: 003, Text: Cary)", "");

		// RadioButton list causing validation
		$this->verifyNotVisible('ctl0_body_ctl8');
		$this->click("//input[@name='ctl0\$body\$ctl9' and @value='Agree']", "");
//		$this->pause(1000);
		$this->verifyVisible('ctl0_body_ctl8');
		$this->type("ctl0\$body\$TextBox", "test");
		$this->clickAndWait("//input[@name='ctl0\$body\$ctl9' and @value='Disagree']", "");
		$this->verifyNotVisible('ctl0_body_ctl8');
	}
}

?>