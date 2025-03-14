<?php

class QuickstartTextBoxTestCase extends PradoDemosSelenium2Test
{
	public function test()
	{
		$this->url("quickstart/index.php?page=Controls.Samples.TTextBox.Home&amp;notheme=true&amp;lang=en");

		$this->assertTitle("PRADO QuickStart Sample");

		// a normal textbox
		$this->type("ctl0\$body\$ctl0", "test");

		// textbox with maxlength set
		$this->assertAttribute("ctl0\$body\$ctl1@size", "4");
		$this->assertAttribute("ctl0\$body\$ctl1@maxlength", "6");
		$this->type("ctl0\$body\$ctl1", "textte");

		// vCard textboxes
		$this->assertAttribute("ctl0\$body\$ctl2@autocomplete", "off");
		$this->type("ctl0\$body\$ctl2", "first");
		$this->assertAttribute("ctl0\$body\$ctl3@autocomplete", "on");
		$this->type("ctl0\$body\$ctl3", "last");

		// a disabled textbox
		$this->assertAttribute("ctl0\$body\$ctl4@disabled", "regexp:true|disabled");

		// a read-only textbox
		$this->assertAttribute("ctl0\$body\$ctl5@readonly", "regexp:true|readonly");

		// auto postback textbox, CausesValidation=false
		$this->assertValue("ctl0\$body\$ctl6", "change me");
		$this->typeSpecial("ctl0\$body\$ctl6", "change mes");
		$this->assertValue("ctl0\$body\$ctl6", "text changed");

		// auto postback textbox, CausesValidation=true
		$this->assertNotVisible('ctl0_body_ctl7');
		$this->typeSpecial("ctl0\$body\$TextBox3", "test");
//		$this->pause(1000);
		$this->assertVisible('ctl0_body_ctl7');
		$this->typeSpecial("ctl0\$body\$TextBox3", "non test");
		$this->assertNotVisible('ctl0_body_ctl7');

		// submitting textbox with a button
		$this->type("ctl0\$body\$TextBox1", "texttext");
		$this->byXPath("//input[@type='submit' and @value='Submit']")->click();
		$this->assertValue("ctl0\$body\$TextBox1", "You just entered 'texttext'.");

		// SafeText
		$this->assertText("ctl0_body_Output", "test");
		$this->typeSpecial("ctl0\$body\$TextBox2", "<a href=javascript:xxx>malicious code</a>");
		$this->pause(50);
		$this->assertText("ctl0_body_Output", "malicious code");

		// password
		$this->assertAttribute("ctl0\$body\$ctl9@type", "password");

		// ------------------multiline textbox----------------------

		// regular textbox
		$this->type("ctl0\$body\$ctl10", "This is a\nmultiline\ntextbox.");
		$this->type("ctl0\$body\$ctl11", "This is a multiline text box.
In HTML, it is displayed as a textarea.
End of message
");

		// a disabled multiline textbox
		$this->assertAttribute("ctl0\$body\$ctl12@disabled", "regexp:true|disabled");

		// a read-only multiline textbox
		$this->assertAttribute("ctl0\$body\$ctl13@readonly", "regexp:true|readonly");
		$this->assertAttribute("ctl0\$body\$ctl13@wrap", "off");

		// auto postback textbox
		$this->assertValue("ctl0\$body\$ctl14", "change me");
		$this->typeSpecial("ctl0\$body\$ctl14", "change mes");
		$this->assertValue("ctl0\$body\$ctl14", "text changed");
		$this->assertValue("ctl0\$body\$ctl10", "This is a\nmultiline\ntextbox.");
		$this->assertValue("ctl0\$body\$ctl11", "This is a multiline text box.
In HTML, it is displayed as a textarea.
End of message
");

		// textbox associated with a validator
		$this->assertNotVisible('ctl0_body_ctl15');
		$this->typeSpecial("ctl0\$body\$MultiTextBox3", "demo");
		$this->assertVisible('ctl0_body_ctl15');
		$this->typeSpecial("ctl0\$body\$MultiTextBox3", "non demo");
		$this->assertNotVisible('ctl0_body_ctl15');
	}
}
