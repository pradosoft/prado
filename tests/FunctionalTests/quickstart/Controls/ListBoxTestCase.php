<?php

class QuickstartListBoxTestCase extends \Prado\Tests\PradoDemosSelenium2Test
{
	public function test()
	{
		$this->url("quickstart/index.php?page=Controls.Samples.TListBox.Home&amp;notheme=true&amp;lang=en");

		// a default single selection listbox
		$this->assertAttribute("ctl0\$body\$ctl0@size", "4");

		// single selection list box with initial options
		$this->assertEquals($this->getSelectOptions("ctl0\$body\$ctl1"), ['item 1', 'item 2', 'item 3', 'item 4']);
		$this->assertSelected("ctl0\$body\$ctl1", "item 2");

		// a single selection list box with customized style
		$this->assertAttribute("ctl0\$body\$ctl2@size", "3");
		$this->assertEquals($this->getSelectOptions("ctl0\$body\$ctl2"), ['item 1', 'item 2', 'item 3', 'item 4']);
		$this->assertSelected("ctl0\$body\$ctl2", "item 2");

		// a disabled list box
		$this->assertAttribute("ctl0\$body\$ctl3@disabled", "regexp:true|disabled");

		// an auto postback single selection list box
		$this->assertStringNotContainsString("Your selection is: (Index: 2, Value: value 3, Text: item 3)", $this->source());
		$this->select("ctl0\$body\$ctl4", "item 3");
		$this->assertSourceContains("Your selection is: (Index: 2, Value: value 3, Text: item 3)");

		// a single selection list box upon postback
		$this->select("ctl0\$body\$ListBox1", "item 4");
		$this->assertStringNotContainsString("Your selection is: (Index: 3, Value: value 4, Text: item 4)", $this->source());
		$this->byXPath("//input[@type='submit' and @value='Submit']")->click();
		$this->assertSourceContains("Your selection is: (Index: 3, Value: value 4, Text: item 4)");

		// a multiple selection list box
		$this->assertAttribute("ctl0\$body\$ctl6[]@size", "4");
		$this->assertAttribute("ctl0\$body\$ctl6[]@multiple", "regexp:true|multiple");

		// a multiple selection list box with initial options
		$this->assertAttribute("ctl0\$body\$ctl7[]@multiple", "regexp:true|multiple");
		$this->assertEquals($this->getSelectOptions("ctl0\$body\$ctl7[]"), ['item 1', 'item 2', 'item 3', 'item 4']);

		// multiselection list box's behavior upon postback
		$this->addSelection("ctl0\$body\$ListBox2[]", "item 3");
		$this->byName("ctl0\$body\$ctl8")->click();
		$this->pause(50);
		$this->assertText("ctl0_body_MultiSelectionResult2", "Your selection is: (Index: 1, Value: value 2, Text: item 2)(Index: 2, Value: value 3, Text: item 3)(Index: 3, Value: value 4, Text: item 4)");

		// Auto postback multiselection list box
		$this->addSelection("ctl0\$body\$ctl9[]", "item 1");
		$this->assertText("ctl0_body_MultiSelectionResult", "Your selection is: (Index: 0, Value: value 1, Text: item 1)(Index: 1, Value: value 2, Text: item 2)(Index: 3, Value: value 4, Text: item 4)");

		// Databind to an integer-indexed array
		$this->select("ctl0\$body\$DBListBox1[]", "item 3");
		$this->assertSourceContains("Your selection is: (Index: 2, Value: 2, Text: item 3)");

		// Databind to an associative array
		$this->select("ctl0\$body\$DBListBox2[]", "item 2");
		$this->assertSourceContains("Your selection is: (Index: 1, Value: key 2, Text: item 2)");

		// Databind with DataTextField and DataValueField specified
		$this->select("ctl0\$body\$DBListBox3[]", "Cary");
		$this->assertSourceContains("Your selection is: (Index: 2, Value: 003, Text: Cary)");

		// List box is being validated
		$this->assertNotVisible('ctl0_body_ctl10');
		$this->byId("ctl0_body_ctl11")->click();
		$this->assertVisible('ctl0_body_ctl10');
		$this->select("ctl0\$body\$VListBox1", "item 2");
		$this->byId("ctl0_body_ctl11")->click();
		$this->assertNotVisible('ctl0_body_ctl10');

		// List box causing validation
		$this->assertNotVisible('ctl0_body_ctl12');
		$this->select("ctl0\$body\$VListBox2", "Agree");
		$this->assertVisible('ctl0_body_ctl12');
		$this->type("ctl0\$body\$TextBox", "test");
		$this->select("ctl0\$body\$VListBox2", "Disagree");
		$this->assertNotVisible('ctl0_body_ctl12');
	}
}
