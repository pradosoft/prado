<?php

class ActiveRadioButtonListTestCase extends SeleniumTestCase
{
	function test()
	{
		$this->open("active-controls/index.php?page=ActiveRadioButtonListTest");
		$this->verifyTextPresent("TActiveRadioButtonList Test Case");

		$this->assertText("label1", "Label 1");


		$this->click("button3");
		$this->pause(800);
		$this->assertCheckBoxes(array(0));

		$this->click("button2");
		$this->pause(800);
		$this->assertCheckBoxes(array());

		$this->click("button4");
		$this->pause(800);
		$this->assertCheckBoxes(array(4));

		$this->click("list1_c2");
		$this->pause(800);
		$this->assertText("label1", "Selection: value 3");

		$this->click("list1_c3");
		$this->pause(800);
		$this->assertText("label1", "Selection: value 4");

	}

	function assertCheckBoxes($checks, $total = 5)
	{
		for($i = 0; $i < $total; $i++)
		{
			if(in_array($i, $checks))
				$this->assertChecked("list1_c{$i}");
			else
				$this->assertNotChecked("list1_c{$i}");
		}
	}
}

?>