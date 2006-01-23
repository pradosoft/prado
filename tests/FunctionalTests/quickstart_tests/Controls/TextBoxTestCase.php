<?php

class TextBoxTestCase extends SeleniumTestCase
{
	function test ()
	{
		$this->open("../../demos/quickstart/index.php?page=Controls.Samples.TTextBox.Home", "");

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
		$this->verifyAttribute("ctl0\$body\$ctl4@disabled","regexp:true|disabled");

		// a read-only textbox
		$this->verifyAttribute("ctl0\$body\$ctl5@readonly","regexp:true|readonly");

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

		// ------------------multiline textbox----------------------

		// regular textbox
		$this->type("ctl0\$body\$ctl10", "This is a\nmultiline\ntextbox.");
		$this->type("ctl0\$body\$ctl11", "This is a multiline text box.
In HTML, it is displayed as a textarea.
End of message
");

		// a disabled multiline textbox
		$this->verifyAttribute("ctl0\$body\$ctl12@disabled","regexp:true|disabled");

		// a read-only multiline textbox
		$this->verifyAttribute("ctl0\$body\$ctl13@readonly","regexp:true|readonly");
		$this->verifyAttribute("ctl0\$body\$ctl13@wrap","off");

		// auto postback textbox
		$this->verifyValue("ctl0\$body\$ctl14", "change me");
		$this->typeAndWait("ctl0\$body\$ctl14", "change mes");
		$this->verifyValue("ctl0\$body\$ctl14", "text changed");
		$this->verifyValue("ctl0\$body\$ctl10", "This is a\nmultiline\ntextbox.");
		$this->verifyValue("ctl0\$body\$ctl11", "This is a multiline text box.
In HTML, it is displayed as a textarea.
End of message
");

		// textbox associated with a validator
		$this->verifyNotVisible('ctl0_body_ctl15');
		$this->type("ctl0\$body\$MultiTextBox3", "demo");
		$this->pause(1000);
		$this->verifyVisible('ctl0_body_ctl15');
		$this->typeAndWait("ctl0\$body\$MultiTextBox3", "non demo");
		$this->verifyNotVisible('ctl0_body_ctl15');
	}
}

?>