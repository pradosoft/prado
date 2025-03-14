<?php

class ActiveRadioButtonListTestCase extends PradoGenericSelenium2Test
{
	public function test()
	{
		$base = 'ctl0_Content_';
		$this->url("active-controls/index.php?page=ActiveRadioButtonListTest");
		$this->assertSourceContains("TActiveRadioButtonList Test Case");

		$this->assertText("{$base}label1", "Label 1");


		$this->byId("{$base}button3")->click();
		$this->assertCheckBoxes([0]);

		$this->byId("{$base}button2")->click();
		$this->assertCheckBoxes([]);

		$this->byId("{$base}button4")->click();
		$this->assertCheckBoxes([4]);

		$this->byId("{$base}list1_c2")->click();
		$this->assertText("{$base}label1", "Selection: value 3");

		$this->byId("{$base}list1_c3")->click();
		$this->assertText("{$base}label1", "Selection: value 4");
	}

	public function assertCheckBoxes($checks, $total = 5)
	{
		$base = 'ctl0_Content_';
		for ($i = 0; $i < $total; $i++) {
			if (in_array($i, $checks)) {
				$this->assertChecked("{$base}list1_c{$i}");
			} else {
				$this->assertNotChecked("{$base}list1_c{$i}");
			}
		}
	}
}
