<?php

class Issue516TestCase extends PradoGenericSelenium2Test
{
	public function test()
	{
		$this->url('issues/index.php?page=Issue516');
		$this->assertSourceContains('Issue 516 Test');
		$base = 'ctl0_Content_';
		$row1 = 'DataGrid_ctl1_';
		$row2 = 'DataGrid_ctl2_';

		// click "edit" and check for textbox
		$this->byID("{$base}{$row1}ctl3")->click();
		$this->assertElementPresent("{$base}{$row1}TextBox");
		// click "save" and check for validator
		$this->byID("{$base}{$row1}ctl3")->click();
		$this->assertText("{$base}{$row1}ctl1", 'Please provide a title.');
		// click "cancel" and ensure validator has disappeared
		$this->byID("{$base}{$row1}ctl4")->click();
		$this->assertElementNotPresent("{$base}{$row1}ctl1");

		// click "edit" and check for textbox on the second row
		$this->byID("{$base}{$row2}ctl3")->click();
		$this->assertTrue($this->getElement("{$base}{$row2}TextBox") !== null);
		// click "save" and ensure validation has been successfull
		$this->byID("{$base}{$row2}ctl3")->click();
		$this->assertElementNotPresent("{$base}{$row2}ctl1");
		$this->assertElementNotPresent("{$base}{$row2}TextBox");
		$this->assertText("{$base}{$row2}ctl3", 'Edit');
	}
}
