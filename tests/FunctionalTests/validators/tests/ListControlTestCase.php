<?php
/*
 * Created on 24/04/2006
 */

class ListControlTestCase extends PradoGenericSelenium2Test
{
	public function test()
	{
		$base = "ctl0_Content_";
		$this->url("validators/index.php?page=ListControl");
		$this->assertSourceContains("List Control Required Field Validation Test");
		$this->byXPath("//input[@type='submit' and @value='Submit!']")->click();

		$this->assertVisible("{$base}validator1");
		$this->assertVisible("{$base}validator2");
		$this->assertVisible("{$base}validator3");
		$this->assertVisible("{$base}validator4");

		$this->byXPath("//input[@id='{$base}list1_c1' and @value='Red']")->click();
		$this->select("{$base}list2", "Red");
		$this->select("{$base}list3", "Blue");
		$this->byId("{$base}list4_c3")->click();
		$this->byXPath("//input[@type='submit' and @value='Submit!']")->click();

		$this->assertNotVisible("{$base}validator1");
		$this->assertNotVisible("{$base}validator2");
		$this->assertNotVisible("{$base}validator3");
		$this->assertNotVisible("{$base}validator4");

		//invalid selector: Unable to locate an element with the xpath expression .//option[.='Don\'t select this one'] because of the following error: SyntaxError: Failed to execute 'evaluate' on 'Document': The string './/option[.='Don\'t select this one']' is not a valid XPath expression.
		$this->select("{$base}list3", "Dont select this one");
		$this->byId("{$base}list4_c0")->click();
		$this->select("{$base}list2", "--- Select a color ---");
		$this->byXPath("//input[@type='submit' and @value='Submit!']")->click();
		$this->byXPath("//input[@id='{$base}list1_c1' and @value='Red']")->click();
		$this->byXPath("//input[@id='{$base}list1_c0' and @value='Select a color below']")->click();
		$this->byXPath("//input[@type='submit' and @value='Submit!']")->click();

		$this->assertVisible("{$base}validator1");
		$this->assertVisible("{$base}validator2");
		$this->assertVisible("{$base}validator3");
		$this->assertVisible("{$base}validator4");
	}
}
