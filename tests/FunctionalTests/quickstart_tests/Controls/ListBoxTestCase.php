<?php

//New Test
class ListBoxTestCase extends SeleniumTestCase
{
	function test ()
	{
		$this->open("../../demos/quickstart/index.php?page=Controls.Samples.TListBox.Home&amp;notheme=true", "");

		// a default single selection listbox
		$this->verifyAttribute("ctl0\$body\$ctl0@size","4");

		// single selection list box with initial options
		$this->verifySelectOptions("ctl0\$body\$ctl1","item 1,item 2,item 3,item 4");
		$this->verifySelected("ctl0\$body\$ctl1","value=value 2");

		// a single selection list box with customized style
		$this->verifyAttribute("ctl0\$body\$ctl2@size","3");
		$this->verifySelectOptions("ctl0\$body\$ctl2","item 1,item 2,item 3,item 4");
		$this->verifySelected("ctl0\$body\$ctl2","value=value 2");

		// a disabled list box
		$this->verifyAttribute("ctl0\$body\$ctl3@disabled","regexp:true|disabled");

		// an auto postback single selection list box
		$this->verifyTextNotPresent("Your selection is: (Index: 2, Value: value 3, Text: item 3)", "");
		$this->selectAndWait("ctl0\$body\$ctl4", "label=item 3");
		$this->verifyTextPresent("Your selection is: (Index: 2, Value: value 3, Text: item 3)", "");

		// a single selection list box upon postback
		$this->select("ctl0\$body\$ListBox1", "label=item 4");
		$this->verifyTextNotPresent("Your selection is: (Index: 3, Value: value 4, Text: item 4)", "");
		$this->clickAndWait("//input[@type='submit' and @value='Submit']", "");
		$this->verifyTextPresent("Your selection is: (Index: 3, Value: value 4, Text: item 4)", "");

		// a multiple selection list box
		$this->verifyAttribute("ctl0\$body\$ctl6[]@size","4");
		$this->verifyAttribute("ctl0\$body\$ctl6[]@multiple","regexp:true|multiple");

		// a multiple selection list box with initial options
		$this->verifyAttribute("ctl0\$body\$ctl7[]@multiple","regexp:true|multiple");
		$this->verifySelectOptions("ctl0\$body\$ctl7[]","item 1,item 2,item 3,item 4");

		// multiselection list box's behavior upon postback
		$this->select("ctl0\$body\$ListBox2[]", "label=item 3");
		$this->clickAndWait("name=ctl0\$body\$ctl8", "");
		$this->verifyText("ctl0_body_MultiSelectionResult2","Your selection is: (Index: 1, Value: value 2, Text: item 2)(Index: 2, Value: value 3, Text: item 3)(Index: 3, Value: value 4, Text: item 4)");

		// Auto postback multiselection list box
		$this->selectAndWait("ctl0\$body\$ctl9[]", "label=item 1");
		$this->verifyText("ctl0_body_MultiSelectionResult","Your selection is: (Index: 0, Value: value 1, Text: item 1)(Index: 1, Value: value 2, Text: item 2)(Index: 3, Value: value 4, Text: item 4)");

		// Databind to an integer-indexed array
		$this->selectAndWait("ctl0\$body\$DBListBox1[]", "label=item 3");
		$this->verifyTextPresent("Your selection is: (Index: 2, Value: item 3, Text: item 3)", "");

		// Databind to an associative array
		$this->selectAndWait("ctl0\$body\$DBListBox2[]", "label=item 2");
		$this->verifyTextPresent("Your selection is: (Index: 1, Value: key 2, Text: item 2)", "");

		// Databind with DataTextField and DataValueField specified
		$this->selectAndWait("ctl0\$body\$DBListBox3[]", "label=Cary");
		$this->verifyTextPresent("Your selection is: (Index: 2, Value: 003, Text: Cary)", "");

		// List box is being validated
		$this->verifyNotVisible('ctl0_body_ctl10');
		$this->click("id=ctl0_body_ctl11", "");
		$this->verifyVisible('ctl0_body_ctl10');
		$this->select("ctl0\$body\$VListBox1", "label=item 2");
		$this->clickAndWait("id=ctl0_body_ctl11", "");
		$this->verifyNotVisible('ctl0_body_ctl10');

		// List box causing validation
		$this->verifyNotVisible('ctl0_body_ctl12');
		$this->select("ctl0\$body\$VListBox2", "label=Agree");
		$this->pause(1000);
		$this->verifyVisible('ctl0_body_ctl12');
		$this->type("ctl0\$body\$TextBox", "test");
		$this->selectAndWait("ctl0\$body\$VListBox2", "label=Disagree");
		$this->verifyNotVisible('ctl0_body_ctl12');
	}
}

?>