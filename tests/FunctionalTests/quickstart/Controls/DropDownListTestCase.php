<?php

class QuickstartDropDownListTestCase extends \Prado\Tests\PradoDemosSelenium2Test
{
	public function test()
	{
		$this->url("quickstart/index.php?page=Controls.Samples.TDropDownList.Home&amp;notheme=true&amp;lang=en");

		$this->assertTitle("PRADO QuickStart Sample");

		// dropdown list with default settings
		$this->assertElementPresent("ctl0\$body\$ctl0");

		// dropdown list with initial options
		$this->assertEquals($this->getSelectOptions("ctl0\$body\$ctl1"), ['item 1', 'item 2', 'item 3', 'item 4']);
		$this->assertSelected("ctl0\$body\$ctl1", "item 2");

		// dropdown list with customized styles
		$this->assertEquals($this->getSelectOptions("ctl0\$body\$ctl2"), ['item 1', 'item 2', 'item 3', 'item 4']);
		$this->assertSelected("ctl0\$body\$ctl2", "item 2");

		// a disabled dropdown list
		$this->assertAttribute("ctl0\$body\$ctl3@disabled", "regexp:true|disabled");

		// an auto postback dropdown list
		$this->assertStringNotContainsString("Your selection is: (Index: 2, Value: value 3, Text: item 3)", $this->source());
		$this->selectAndWait("ctl0\$body\$ctl4", "item 3");
		$this->assertSourceContains("Your selection is: (Index: 2, Value: value 3, Text: item 3)");

		// a single selection list box upon postback
		$this->select("ctl0\$body\$DropDownList1", "item 4");
		$this->assertStringNotContainsString("Your selection is: (Index: 3, Value: value 4, Text: item 4)", $this->source());
		$this->byXPath("//input[@type='submit' and @value='Submit']")->click();
		$this->assertSourceContains("Your selection is: (Index: 3, Value: value 4, Text: item 4)");

		// Databind to an integer-indexed array
		$this->selectAndWait("ctl0\$body\$DBDropDownList1", "item 3");
		$this->assertSourceContains("Your selection is: (Index: 2, Value: 2, Text: item 3)");

		// Databind to an associative array
		$this->selectAndWait("ctl0\$body\$DBDropDownList2", "item 2");
		$this->assertSourceContains("Your selection is: (Index: 1, Value: key 2, Text: item 2)");

		// Databind with DataTextField and DataValueField specified
		$this->selectAndWait("ctl0\$body\$DBDropDownList3", "Cary");
		$this->assertSourceContains("Your selection is: (Index: 2, Value: 003, Text: Cary)");

		// dropdown list is being validated
		$this->assertNotVisible('ctl0_body_ctl6');
		$this->byId("ctl0_body_ctl7")->click();
		$this->assertVisible('ctl0_body_ctl6');
		$this->select("ctl0\$body\$VDropDownList1", "item 2");
		$this->byId("ctl0_body_ctl7")->click();
		$this->assertNotVisible('ctl0_body_ctl6');

		// dropdown list causing validation
		$this->assertNotVisible('ctl0_body_ctl8');
		$this->select("ctl0\$body\$VDropDownList2", "Disagree");
		$this->pause(1000);
		$this->assertVisible('ctl0_body_ctl8');
		$this->type("ctl0\$body\$TextBox", "test");
		$this->selectAndWait("ctl0\$body\$VDropDownList2", "Agree");
		$this->assertNotVisible('ctl0_body_ctl8');
	}
}
