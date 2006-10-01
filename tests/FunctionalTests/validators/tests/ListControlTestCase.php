<?php
/*
 * Created on 24/04/2006
 */

class ListControlTestCase extends SeleniumTestCase
{
	function test()
	{
		//problem with test runner clicking on radio buttons
		$this->skipBrowsers(self::OPERA);

		$base = "ctl0_Content_";
		$this->open("validators/index.php?page=ListControl", "");
		$this->verifyTextPresent("List Control Required Field Validation Test", "");
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

		$this->select("{$base}list3", "label=Don't select this one");
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

?>
