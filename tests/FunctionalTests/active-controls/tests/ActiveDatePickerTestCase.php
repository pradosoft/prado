<?php
class ActiveDatePickerTestCase extends SeleniumTestCase
{
	function test()
	{
		$this->open("active-controls/index.php?page=ActiveDatePicker");
		$this->verifyTextPresent("TActiveDatePicker test");
		$this->verifyText("status", "");
		$this->verifyValue("datepicker", date('m-d-Y'));
		$this->click("increaseButton");
		$this->pause(800);
		$this->verifyValue("datepicker", date('m-d-Y', strtotime('+ 1 day')));
		$this->verifyText("status", date('m-d-Y', strtotime('+ 1 day')));
		$this->click("increaseButton");
		$this->pause(800);
		$this->verifyValue("datepicker", date('m-d-Y', strtotime('+ 2 day')));
		$this->verifyText("status", date('m-d-Y', strtotime('+ 2 day')));
		$this->click("todayButton");
		$this->pause(800);
		$this->verifyValue("datepicker", date('m-d-Y'));
		$this->verifyText("status", date('m-d-Y'));
		$this->click("decreaseButton");
		$this->pause(800);
		$this->verifyValue("datepicker", date('m-d-Y', strtotime('- 1 day')));
		$this->verifyText("status", date('m-d-Y', strtotime('- 1 day')));
		$this->click("datepicker");
		$this->pause(800);
		$this->click("css=input.todayButton");
		$this->pause(800);
		$this->verifyValue("datepicker", date('m-d-Y'));
		$this->verifyText("status", date('m-d-Y'));
		$this->click("css=input.nextMonthButton");
		$this->pause(800);
		$this->verifyValue("datepicker", date('m-d-Y', strtotime('+ 1 month')));
		$this->verifyText("status", date('m-d-Y', strtotime('+1 month')));
		
		$this->click('toggleButton');
		$this->pause(1000);
		
		$this->click("todayButton");
		$this->pause(800);
		$this->verifySelected("datepicker_month", date('m'));
		$this->verifyText("status", date('m-d-Y'));
		
		$this->click("increaseButton");
		$this->pause(800);
		$dateToCheck=strtotime('+ 1 day');
		$this->verifySelected("datepicker_month", date('m', $dateToCheck));
		$this->verifySelected("datepicker_day", date('d', $dateToCheck));
		$this->verifySelected("datepicker_year", date('Y', $dateToCheck));
		$this->verifyText("status", date('m-d-Y', $dateToCheck));
		
		$this->click("increaseButton");
		$this->pause(800);
		$dateToCheck=strtotime('+ 2 day');
		$this->verifySelected("datepicker_month", date('m', $dateToCheck));
		$this->verifySelected("datepicker_day", date('d', $dateToCheck));
		$this->verifySelected("datepicker_year", date('Y', $dateToCheck));
		$this->verifyText("status", date('m-d-Y', $dateToCheck));
		
		$this->click("todayButton");
		$this->pause(800);
		$dateToCheck=time();
		$this->verifySelected("datepicker_month", date('m', $dateToCheck));
		$this->verifySelected("datepicker_day", date('d', $dateToCheck));
		$this->verifySelected("datepicker_year", date('Y', $dateToCheck));
		$this->verifyText("status", date('m-d-Y', $dateToCheck));
		
		$this->click("decreaseButton");
		$this->pause(800);
		$dateToCheck=strtotime('- 1 day');
		$this->verifySelected("datepicker_month", date('m', $dateToCheck));
		$this->verifySelected("datepicker_day", date('d', $dateToCheck));
		$this->verifySelected("datepicker_year", date('Y', $dateToCheck));
		$this->verifyText("status", date('m-d-Y', $dateToCheck));
		
		$this->click("datepickerbutton");
		$this->pause(800);
		$this->click("css=input.todayButton");
		$this->pause(800);
		$dateToCheck=time();
		$this->verifySelected("datepicker_month", date('m', $dateToCheck));
		$this->verifySelected("datepicker_day", date('d', $dateToCheck));
		$this->verifySelected("datepicker_year", date('Y', $dateToCheck));
		$this->verifyText("status", date('m-d-Y', $dateToCheck));
		
		$this->click("css=input.nextMonthButton");
		$this->pause(800);
		$dateToCheck=strtotime('+ 1 month');
		$this->verifySelected("datepicker_month", date('m', $dateToCheck));
		$this->verifySelected("datepicker_day", date('d', $dateToCheck));
		$this->verifySelected("datepicker_year", date('Y', $dateToCheck));
		$this->verifyText("status", date('m-d-Y', $dateToCheck));
	}
}

?>