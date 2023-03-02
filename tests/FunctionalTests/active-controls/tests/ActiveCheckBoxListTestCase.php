<?php

class ActiveCheckBoxListTestCase extends PradoGenericSelenium2Test
{
	public function test()
	{
		$base = 'ctl0_Content_';
		$this->url("active-controls/index.php?page=TActiveCheckBoxListTest");
		$this->assertSourceContains("TActiveCheckBoxList Test Case");

		$this->assertText("{$base}label1", "Label 1");

		$this->byId("{$base}button1")->click();
		$this->pauseFairAmount();
		$this->assertCheckBoxes([1, 2, 3]);

		$this->byId("{$base}button2")->click();
		$this->pauseFairAmount();
		$this->assertCheckBoxes([]);


		$this->byId("{$base}button3")->click();
		$this->pauseFairAmount();
		$this->assertCheckBoxes([0]);


		$this->byId("{$base}button4")->click();
		$this->pauseFairAmount();
		$this->assertCheckBoxes([4]);


		$this->byId("{$base}button5")->click();
		$this->pauseFairAmount();
		$this->assertCheckBoxes([1, 4]);

		$this->byId("{$base}list1_c2")->click();
		$this->pauseFairAmount();
		$this->assertText("{$base}label1", "Selection: value 2, value 3, value 5");

		$this->byId("{$base}list1_c2")->click();
		$this->pauseFairAmount();
		$this->assertText("{$base}label1", "Selection: value 2, value 5");
	}

	public function assertCheckBoxes($checks, $total = 5)
	{
		$base = 'ctl0_Content_';
		for ($i = 0; $i < $total; $i++) {
			if (in_array($i, $checks)) {
				$this->assertTrue($this->byId("{$base}list1_c{$i}")->selected());
			} else {
				$this->assertFalse($this->byId("{$base}list1_c{$i}")->selected());
			}
		}
	}
}
