<?php

class MultiViewTestCase extends SeleniumTestCase
{
	function test ()
	{
		$this->open("../../demos/quickstart/index.php?page=Controls.Samples.TMultiView.Home&amp;notheme=true&amp;lang=en", "");

		$this->verifyTitle("PRADO QuickStart Sample", "");

		// view 1 : type in a string
		$this->verifyElementNotPresent('ctl0_body_Result1');
		$this->verifyElementNotPresent('ctl0_body_Result2');
		$this->type('ctl0_body_Memo','test');
		$this->clickAndWait('ctl0$body$ctl0'); // view 2 to select the dropdown
		$this->clickAndWait('ctl0$body$ctl4');

		// view 3 : check if the output is updated
		$this->verifyTextPresent('Your text input is: test');
		$this->verifyTextPresent('Your color choice is: Red');
		$this->clickAndWait('ctl0$body$ctl7');

		// view 2 : update dropdownlist
		$this->verifyElementNotPresent('ctl0_body_Result1');
		$this->verifyElementNotPresent('ctl0_body_Result2');
		$this->select('ctl0$body$DropDownList', "label=Blue");
		$this->clickAndWait('ctl0$body$ctl4');

		// view 3 : check if the output is updated
		$this->verifyTextPresent('Your text input is: test');
		$this->verifyTextPresent('Your color choice is: Blue');
		$this->clickAndWait('ctl0$body$ctl7');

		// view 2 : check if dropdownlist maintains state
		$this->verifySelected('ctl0$body$DropDownList', "label=Blue");
	}
}

?>