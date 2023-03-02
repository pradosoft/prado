<?php

//New Test
class QuickstartRadioButtonListTestCase extends PradoDemosSelenium2Test
{
	public function test()
	{
		$this->url("quickstart/index.php?page=Controls.Samples.TRadioButtonList.Home&amp;notheme=true&amp;lang=en");

		// RadioButton list with default settings:
		$this->byXPath("//input[@name='ctl0\$body\$ctl0' and @value='value 3']")->click();

		// RadioButton list with customized cellpadding, cellspacing, color and text alignment:
		$this->byXPath("//input[@name='ctl0\$body\$ctl1' and @value='value 1']")->click();

		// *** Currently unable to test the following cases:
		// RadioButton list with vertical (default) repeat direction
		// RadioButton list with horizontal repeat direction
		// RadioButton list with flow layout and vertical (default) repeat direction
		// RadioButton list with flow layout and horizontal repeat direction:

		// RadioButton list's behavior upon postback
		$this->byXPath("//input[@name='ctl0\$body\$RadioButtonList' and @value='value 3']")->click();
		$this->byXPath("//input[@type='submit' and @value='Submit']")->click();
		$this->assertSourceContains("Your selection is: (Index: 2, Value: value 3, Text: item 3)");

		// Auto postback check box list
		$this->byXPath("//input[@name='ctl0\$body\$ctl7' and @value='value 5']")->click();
		$this->assertSourceContains("Your selection is: (Index: 4, Value: value 5, Text: item 5)");

		// Databind to an integer-indexed array
		$this->byXPath("//input[@name='ctl0\$body\$DBRadioButtonList1' and @value='0']")->click();
		$this->assertSourceContains("Your selection is: (Index: 0, Value: 0, Text: item 1)");

		// Databind to an associative array:
		$this->byXPath("//input[@name='ctl0\$body\$DBRadioButtonList2' and @value='key 2']")->click();
		$this->assertSourceContains("Your selection is: (Index: 1, Value: key 2, Text: item 2)");

		// Databind with DataTextField and DataValueField specified
		$this->byXPath("//input[@name='ctl0\$body\$DBRadioButtonList3' and @value='003']")->click();
		$this->assertSourceContains("Your selection is: (Index: 2, Value: 003, Text: Cary)");

		// RadioButton list causing validation
		$this->assertNotVisible('ctl0_body_ctl8');
		$this->byXPath("//input[@name='ctl0\$body\$ctl9' and @value='Agree']")->click();
//		$this->pause(1000);
		$this->assertVisible('ctl0_body_ctl8');
		$this->type("ctl0\$body\$TextBox", "test");
		$this->byXPath("//input[@name='ctl0\$body\$ctl9' and @value='Disagree']")->click();
		$this->assertNotVisible('ctl0_body_ctl8');
	}
}
