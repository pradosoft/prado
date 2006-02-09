<?php

//New Test
class CheckBoxListTestCase extends SeleniumTestCase
{
	function test ()
	{
		$this->open("../../demos/quickstart/index.php?page=Controls.Samples.TCheckBoxList.Home&amp;notheme=true", "");

		// Check box list with default settings:
		$this->click("//input[@name='ctl0\$body\$ctl0\$0' and @value='value 1']", "");

		// Check box list with customized cellpadding, cellspacing, color and text alignment:
		$this->click("//input[@name='ctl0\$body\$ctl1\$1' and @value='value 2']", "");

		// *** Currently unable to test the following cases:
		// Check box list with vertical (default) repeat direction
		// Check box list with horizontal repeat direction
		// Check box list with flow layout and vertical (default) repeat direction
		// Check box list with flow layout and horizontal repeat direction:

		// Check box list's behavior upon postback
		$this->click("//input[@name='ctl0\$body\$CheckBoxList\$2' and @value='value 3']", "");
		$this->clickAndWait("//input[@type='submit' and @value='Submit']", "");
		$this->verifyTextPresent("Your selection is: (Index: 1, Value: value 2, Text: item 2)(Index: 2, Value: value 3, Text: item 3)(Index: 4, Value: value 5, Text: item 5)", "");

		// Auto postback check box list
		$this->clickAndWait("//input[@name='ctl0\$body\$ctl7\$1' and @value='value 2']", "");
		$this->verifyTextPresent("Your selection is: (Index: 4, Value: value 5, Text: item 5)", "");

		// Databind to an integer-indexed array
		$this->clickAndWait("//input[@name='ctl0\$body\$DBCheckBoxList1\$1' and @value='item 2']", "");
		$this->verifyTextPresent("Your selection is: (Index: 1, Value: item 2, Text: item 2)", "");

		// Databind to an associative array:
		$this->clickAndWait("//input[@name='ctl0\$body\$DBCheckBoxList2\$1' and @value='key 2']", "");
		$this->verifyTextPresent("Your selection is: (Index: 1, Value: key 2, Text: item 2)", "");

		// Databind with DataTextField and DataValueField specified
		$this->clickAndWait("//input[@name='ctl0\$body\$DBCheckBoxList3\$2' and @value='003']", "");
		$this->verifyTextPresent("Your selection is: (Index: 2, Value: 003, Text: Cary)", "");

		// CheckBox list causing validation
		$this->verifyNotVisible('ctl0_body_ctl8');
		$this->click("//input[@name='ctl0\$body\$ctl9$0' and @value='Agree']", "");
		$this->pause(1000);
		$this->verifyVisible('ctl0_body_ctl8');
		$this->type("ctl0\$body\$TextBox", "test");
		$this->clickAndWait("//input[@name='ctl0\$body\$ctl9$0' and @value='Agree']", "");
		$this->verifyNotVisible('ctl0_body_ctl8');
	}
}

?>