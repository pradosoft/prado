<?php

class DropDownListTestCase extends SeleniumTestCase
{
	function test ()
	{
		$this->open("../../demos/quickstart/index.php?page=Controls.Samples.TDropDownList.Home&amp;notheme=true&amp;lang=en", "");

		$this->verifyTitle("PRADO QuickStart Sample", "");

		// dropdown list with default settings
		$this->verifyElementPresent("ctl0\$body\$ctl0");

		// dropdown list with initial options
		$this->verifySelectOptions("ctl0\$body\$ctl1","item 1,item 2,item 3,item 4");
		$this->verifySelected("ctl0\$body\$ctl1","value=value 2");

		// dropdown list with customized styles
		$this->verifySelectOptions("ctl0\$body\$ctl2","item 1,item 2,item 3,item 4");
		$this->verifySelected("ctl0\$body\$ctl2","value=value 2");

		// a disabled dropdown list
		$this->verifyAttribute("ctl0\$body\$ctl3@disabled","regexp:true|disabled");

		// an auto postback dropdown list
		$this->verifyTextNotPresent("Your selection is: (Index: 2, Value: value 3, Text: item 3)", "");
		$this->selectAndWait("ctl0\$body\$ctl4", "label=item 3");
		$this->verifyTextPresent("Your selection is: (Index: 2, Value: value 3, Text: item 3)", "");

		// a single selection list box upon postback
		$this->select("ctl0\$body\$DropDownList1", "label=item 4");
		$this->verifyTextNotPresent("Your selection is: (Index: 3, Value: value 4, Text: item 4)", "");
		$this->clickAndWait("//input[@type='submit' and @value='Submit']", "");
		$this->verifyTextPresent("Your selection is: (Index: 3, Value: value 4, Text: item 4)", "");

		// Databind to an integer-indexed array
		$this->selectAndWait("ctl0\$body\$DBDropDownList1", "label=item 3");
		$this->verifyTextPresent("Your selection is: (Index: 2, Value: 2, Text: item 3)", "");

		// Databind to an associative array
		$this->selectAndWait("ctl0\$body\$DBDropDownList2", "label=item 2");
		$this->verifyTextPresent("Your selection is: (Index: 1, Value: key 2, Text: item 2)", "");

		// Databind with DataTextField and DataValueField specified
		$this->selectAndWait("ctl0\$body\$DBDropDownList3", "label=Cary");
		$this->verifyTextPresent("Your selection is: (Index: 2, Value: 003, Text: Cary)", "");

		// dropdown list is being validated
		$this->verifyNotVisible('ctl0_body_ctl6');
		$this->click("id=ctl0_body_ctl7", "");
		$this->verifyVisible('ctl0_body_ctl6');
		$this->select("ctl0\$body\$VDropDownList1", "label=item 2");
		$this->clickAndWait("id=ctl0_body_ctl7", "");
		$this->verifyNotVisible('ctl0_body_ctl6');

		// dropdown list causing validation
		$this->verifyNotVisible('ctl0_body_ctl8');
		$this->select("ctl0\$body\$VDropDownList2", "label=Disagree");
		$this->pause(1000);
		$this->verifyVisible('ctl0_body_ctl8');
		$this->type("ctl0\$body\$TextBox", "test");
		$this->selectAndWait("ctl0\$body\$VDropDownList2", "label=Agree");
		$this->verifyNotVisible('ctl0_body_ctl8');
	}
}

?>