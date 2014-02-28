<?php

class ActiveRadioButtonListTestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$base='ctl0_Content_';
		$this->url("active-controls/index.php?page=ActiveRadioButtonListTest");
		$this->assertTextPresent("TActiveRadioButtonList Test Case");

		$this->assertText("{$base}label1", "Label 1");


		$this->click("{$base}button3");
		$this->pause(800);
		$this->assertCheckBoxes(array(0));

		$this->click("{$base}button2");
		$this->pause(800);
		$this->assertCheckBoxes(array());

		$this->click("{$base}button4");
		$this->pause(800);
		$this->assertCheckBoxes(array(4));

		$this->click("{$base}list1_c2");
		$this->pause(800);
		$this->assertText("{$base}label1", "Selection: value 3");

		$this->click("{$base}list1_c3");
		$this->pause(800);
		$this->assertText("{$base}label1", "Selection: value 4");

	}

	function assertCheckBoxes($checks, $total = 5)
	{
		$base='ctl0_Content_';
		for($i = 0; $i < $total; $i++)
		{
			if(in_array($i, $checks))
				$this->assertChecked("{$base}list1_c{$i}");
			else
				$this->assertNotChecked("{$base}list1_c{$i}");
		}
	}
}
