<?php

//New Test
class QuickstartCheckBoxListTestCase extends PradoDemosSelenium2Test
{
	public function test()
	{
		$this->url("quickstart/index.php?page=Controls.Samples.TCheckBoxList.Home&amp;notheme=true&amp;lang=en");

		// Check box list with default settings:
		$this->byXPath("//input[@name='ctl0\$body\$ctl0\$c0' and @value='value 1']")->click();

		// Check box list with customized cellpadding, cellspacing, color and text alignment:
		$this->byXPath("//input[@name='ctl0\$body\$ctl1\$c1' and @value='value 2']")->click();

		// *** Currently unable to test the following cases:
		// Check box list with vertical (default) repeat direction
		// Check box list with horizontal repeat direction
		// Check box list with flow layout and vertical (default) repeat direction
		// Check box list with flow layout and horizontal repeat direction:

		// Check box list's behavior upon postback
		$this->byXPath("//input[@name='ctl0\$body\$CheckBoxList\$c2' and @value='value 3']")->click();
		$this->byXPath("//input[@type='submit' and @value='Submit']")->click();
		$this->pause(50);
		$this->assertStringContainsString("Your selection is: (Index: 1, Value: value 2, Text: item 2)(Index: 2, Value: value 3, Text: item 3)(Index: 4, Value: value 5, Text: item 5)", $this->source());

		// Auto postback check box list
		$this->byXPath("//input[@name='ctl0\$body\$ctl7\$c1' and @value='value 2']")->click();
		$this->pause(50);
		$this->assertStringContainsString("Your selection is: (Index: 4, Value: value 5, Text: item 5)", $this->source());

		// Databind to an integer-indexed array
		$this->byXPath("//input[@name='ctl0\$body\$DBCheckBoxList1\$c1' and @value='1']")->click();
		$this->pause(50);
		$this->assertStringContainsString("Your selection is: (Index: 1, Value: 1, Text: item 2)", $this->source());

		// Databind to an associative array:
		$this->byXPath("//input[@name='ctl0\$body\$DBCheckBoxList2\$c1' and @value='key 2']")->click();
		$this->pause(50);
		$this->assertStringContainsString("Your selection is: (Index: 1, Value: key 2, Text: item 2)", $this->source());

		// Databind with DataTextField and DataValueField specified
		$this->byXPath("//input[@name='ctl0\$body\$DBCheckBoxList3\$c2' and @value='003']")->click();
		$this->pause(50);
		$this->assertStringContainsString("Your selection is: (Index: 2, Value: 003, Text: Cary)", $this->source());

		// CheckBox list causing validation
		$this->assertNotVisible('ctl0_body_ctl8');
		$this->byXPath("//input[@name='ctl0\$body\$ctl9\$c0' and @value='Agree']")->click();
		$this->assertVisible('ctl0_body_ctl8');
		$this->type("ctl0\$body\$TextBox", "test");
		$this->byXPath("//input[@name='ctl0\$body\$ctl9\$c0' and @value='Agree']")->click();
		$this->assertNotVisible('ctl0_body_ctl8');
	}
}
