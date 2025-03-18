<?php

class QuickstartMultiViewTestCase extends \Prado\Tests\PradoDemosSelenium2Test
{
	public function test()
	{
		$this->url("quickstart/index.php?page=Controls.Samples.TMultiView.Home&amp;notheme=true&amp;lang=en");

		$this->assertTitle("PRADO QuickStart Sample");

		// view 1 : type in a string
		$this->assertElementNotPresent('ctl0_body_Result1');
		$this->assertElementNotPresent('ctl0_body_Result2');
		$this->type('ctl0_body_Memo', 'test');
		$this->byName('ctl0$body$ctl0')->click(); // view 2 to select the dropdown
		$this->pause(50);
		$this->byName('ctl0$body$ctl4')->click();

		// view 3 : check if the output is updated
		$this->assertSourceContains('Your text input is: test');
		$this->assertSourceContains('Your color choice is: Red');
		$this->byName('ctl0$body$ctl7')->click();
		$this->pause(50);

		// view 2 : update dropdownlist
		$this->assertElementNotPresent('ctl0_body_Result1');
		$this->assertElementNotPresent('ctl0_body_Result2');
		$this->select('ctl0$body$DropDownList', "Blue");
		$this->byName('ctl0$body$ctl4')->click();

		// view 3 : check if the output is updated
		$this->assertSourceContains('Your text input is: test');
		$this->assertSourceContains('Your color choice is: Blue');
		$this->byName('ctl0$body$ctl7')->click();

		// view 2 : check if dropdownlist maintains state
		$this->assertSelected('ctl0$body$DropDownList', "Blue");
	}
}
