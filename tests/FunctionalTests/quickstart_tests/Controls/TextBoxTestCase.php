<?php

class TextBoxTestCase extends SeleniumTestCase
{
	function test ()
	{
		$this->open("../../demos/quickstart/?page=Controls.Samples.TTextBox.Home", "");
		// a read-only multiline textbox
		$this->verifyAttribute("ctl0\$body\$ctl13@readonly","regexp:/(true|readonly)/");
return;
		$this->verifyTitle("PRADO QuickStart Sample", "");

		// a normal textbox
		$this->type("ctl0\$body\$ctl0", "test");

		// textbox with maxlength set
		$this->verifyAttribute("ctl0\$body\$ctl1@size","4");
		$this->verifyAttribute("ctl0\$body\$ctl1@maxlength","6");
		$this->type("ctl0\$body\$ctl1", "textte");

		// vCard textboxes
		$this->verifyAttribute("ctl0\$body\$ctl2@vcard_name","vCard.FirstName");
		$this->type("ctl0\$body\$ctl2", "first");
		$this->verifyAttribute("ctl0\$body\$ctl3@vcard_name","vCard.LastName");
		$this->type("ctl0\$body\$ctl3", "last");

		// a disabled textbox
		$this->verifyAttribute("ctl0\$body\$ctl4@disabled","disabled");

		// a read-only textbox
		$this->verifyAttribute("ctl0\$body\$ctl5@readonly","readonly");

		// auto postback textbox, CausesValidation=false
		$this->verifyValue("ctl0\$body\$ctl6", "change me");
		$this->typeAndWait("ctl0\$body\$ctl6", "change mes");
		$this->verifyValue("ctl0\$body\$ctl6", "text changed");

		// auto postback textbox, CausesValidation=true
		$this->verifyNotVisible('ctl0_body_ctl7');
		$this->type("ctl0\$body\$TextBox3", "test");
		$this->pause(1000);
		$this->verifyVisible('ctl0_body_ctl7');
		$this->typeAndWait("ctl0\$body\$TextBox3", "non test");
		$this->verifyNotVisible('ctl0_body_ctl7');

		// submitting textbox with a button
		$this->type("ctl0\$body\$TextBox1", "texttext");
		$this->clickAndWait("//input[@type='submit' and @value='Submit']", "");
		$this->verifyValue("ctl0\$body\$TextBox1", "You just entered 'texttext'.");

		// SafeText
		$this->verifyText("ctl0_body_Output","test");
		$this->typeAndWait("ctl0\$body\$TextBox2","&lt;a href=javascript:xxx&gt;malicious code&lt;/a&gt;");
		$this->verifyText("ctl0_body_Output","malicious code");

		// password
		$this->verifyAttribute("ctl0\$body\$ctl9@type","password");

		$this->type("ctl0\$body\$TextBox3", "tests");
		$this->clickAndWait("//input[@type='submit' and @value='Submit']", "");
		$this->clickAndWait("//input[@type='submit' and @value='Submit']", "");
		$this->type("ctl0\$body\$ctl9", "test");
		$this->type("ctl0\$body\$ctl10", "test
test
test");
		$this->type("ctl0\$body\$ctl11", "This is a multiline text box.
In HTML, it is displayed as a textarea.
test  ");
		$this->typeAndWait("ctl0\$body\$ctl14", "change med");
		$this->type("ctl0\$body\$MultiTextBox3", "test");
		$this->verifyTextPresent("You must enter a value not equal to 'test'.", "");
		$this->typeAndWait("ctl0\$body\$MultiTextBox3", "testd");


		// a disabled multiline textbox
		$this->verifyAttribute("ctl0\$body\$ctl12@disabled","disabled");

		//$this->verifyElementPresent("//ctl0\$body\$ctl13[@readonly]");
		$this->verifyAttribute("ctl0\$body\$ctl13@wrap","off");

	}
}

?>