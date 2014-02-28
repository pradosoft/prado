<?php
/*
 * Created on 24/04/2006
 */

class ListControlTestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$base = "ctl0_Content_";
		$this->url("validators/index.php?page=ListControl");
		$this->assertTextPresent("List Control Required Field Validation Test", "");
		$this->click("//input[@type='submit' and @value='Submit!']", "");

		$this->assertVisible("{$base}validator1");
		$this->assertVisible("{$base}validator2");
		$this->assertVisible("{$base}validator3");
		$this->assertVisible("{$base}validator4");

		$this->click("//input[@id='{$base}list1_c1' and @value='Red']", "");
		$this->select("{$base}list2", "label=Red");
		$this->select("{$base}list3", "label=Blue");
		$this->click("{$base}list4_c3", "");
		$this->clickAndWait("//input[@type='submit' and @value='Submit!']", "");

		$this->assertNotVisible("{$base}validator1");
		$this->assertNotVisible("{$base}validator2");
		$this->assertNotVisible("{$base}validator3");
		$this->assertNotVisible("{$base}validator4");

		//invalid selector: Unable to locate an element with the xpath expression .//option[.='Don\'t select this one'] because of the following error: SyntaxError: Failed to execute 'evaluate' on 'Document': The string './/option[.='Don\'t select this one']' is not a valid XPath expression.
		$this->select("{$base}list3", "label=Dont select this one");
		$this->click("{$base}list4_c0");
		$this->select("{$base}list2", "label=--- Select a color ---");
		$this->click("//input[@type='submit' and @value='Submit!']", "");
		$this->click("//input[@id='{$base}list1_c1' and @value='Red']", "");
		$this->click("//input[@id='{$base}list1_c0' and @value='Select a color below']", "");
		$this->click("//input[@type='submit' and @value='Submit!']", "");

		$this->assertVisible("{$base}validator1");
		$this->assertVisible("{$base}validator2");
		$this->assertVisible("{$base}validator3");
		$this->assertVisible("{$base}validator4");

	}

}

