<?php
class ActiveDatePickerTestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$base='ctl0_Content_';
		$this->url("active-controls/index.php?page=ActiveDatePicker");
		$this->assertTextPresent("TActiveDatePicker test");
		$this->assertText("{$base}status", "");
		$this->assertValue("{$base}datepicker", date('m-d-Y'));
		$this->click("{$base}increaseButton");
		$this->pause(800);
		$this->assertValue("{$base}datepicker", date('m-d-Y', strtotime('+ 1 day')));
		$this->assertText("{$base}status", date('m-d-Y', strtotime('+ 1 day')));
		$this->click("{$base}increaseButton");
		$this->pause(800);
		$this->assertValue("{$base}datepicker", date('m-d-Y', strtotime('+ 2 day')));
		$this->assertText("{$base}status", date('m-d-Y', strtotime('+ 2 day')));
		$this->click("{$base}todayButton");
		$this->pause(800);
		$this->assertValue("{$base}datepicker", date('m-d-Y'));
		$this->assertText("{$base}status", date('m-d-Y'));
		$this->click("{$base}decreaseButton");
		$this->pause(800);
		$this->assertValue("{$base}datepicker", date('m-d-Y', strtotime('- 1 day')));
		$this->assertText("{$base}status", date('m-d-Y', strtotime('- 1 day')));
		$this->click("{$base}datepicker");
		$this->pause(800);
		$this->click("css=input.todayButton");
		$this->pause(800);
		$this->assertValue("{$base}datepicker", date('m-d-Y'));
		$this->assertText("{$base}status", date('m-d-Y'));
		$this->click("css=input.nextMonthButton");
		$this->pause(800);
		$this->assertValue("{$base}datepicker", date('m-d-Y', strtotime('+ 1 month')));
		$this->assertText("{$base}status", date('m-d-Y', strtotime('+1 month')));

		$this->click("{$base}toggleButton");
		$this->pause(2000);

		$this->click("{$base}todayButton");
		$this->pause(800);
		$this->assertSelected("{$base}datepicker_month", date('m'));
		$this->assertText("{$base}status", date('m-d-Y'));

		$this->click("{$base}increaseButton");
		$this->pause(800);
		$dateToCheck=strtotime('+ 1 day');
		$this->assertSelected("{$base}datepicker_month", date('m', $dateToCheck));
		$this->assertSelected("{$base}datepicker_day", date('d', $dateToCheck));
		$this->assertSelected("{$base}datepicker_year", date('Y', $dateToCheck));
		$this->assertText("{$base}status", date('m-d-Y', $dateToCheck));

		$this->click("{$base}increaseButton");
		$this->pause(800);
		$dateToCheck=strtotime('+ 2 day');
		$this->assertSelected("{$base}datepicker_month", date('m', $dateToCheck));
		$this->assertSelected("{$base}datepicker_day", date('d', $dateToCheck));
		$this->assertSelected("{$base}datepicker_year", date('Y', $dateToCheck));
		$this->assertText("{$base}status", date('m-d-Y', $dateToCheck));

		$this->click("{$base}todayButton");
		$this->pause(800);
		$dateToCheck=time();
		$this->assertSelected("{$base}datepicker_month", date('m', $dateToCheck));
		$this->assertSelected("{$base}datepicker_day", date('d', $dateToCheck));
		$this->assertSelected("{$base}datepicker_year", date('Y', $dateToCheck));
		$this->assertText("{$base}status", date('m-d-Y', $dateToCheck));

		$this->click("{$base}decreaseButton");
		$this->pause(800);
		$dateToCheck=strtotime('- 1 day');
		$this->assertSelected("{$base}datepicker_month", date('m', $dateToCheck));
		$this->assertSelected("{$base}datepicker_day", date('d', $dateToCheck));
		$this->assertSelected("{$base}datepicker_year", date('Y', $dateToCheck));
		$this->assertText("{$base}status", date('m-d-Y', $dateToCheck));

		$this->click("{$base}datepickerbutton");
		$this->pause(800);
		$this->click("css=input.todayButton");
		$this->pause(800);
		$dateToCheck=time();
		$this->assertSelected("{$base}datepicker_month", date('m', $dateToCheck));
		$this->assertSelected("{$base}datepicker_day", date('d', $dateToCheck));
		$this->assertSelected("{$base}datepicker_year", date('Y', $dateToCheck));
		$this->assertText("{$base}status", date('m-d-Y', $dateToCheck));

		$this->click("css=input.nextMonthButton");
		$this->pause(800);
		$dateToCheck=strtotime('+ 1 month');
		$this->assertSelected("{$base}datepicker_month", date('m', $dateToCheck));
		$this->assertSelected("{$base}datepicker_day", date('d', $dateToCheck));
		$this->assertSelected("{$base}datepicker_year", date('Y', $dateToCheck));
		$this->assertText("{$base}status", date('m-d-Y', $dateToCheck));

		$this->click('ctl0_ctl1');
		$this->pause(800);

		$this->assertText("{$base}status2", "");
		$dateToCheck=time();
		$this->assertSelected("{$base}datepicker2_month", date('m', $dateToCheck));
		$this->assertSelected("{$base}datepicker2_day", date('d', $dateToCheck));
		$this->assertSelected("{$base}datepicker2_year", date('Y', $dateToCheck));
		$this->select("{$base}datepicker2_year", date('Y')+1);
		$this->pause(800);
		$dateToCheck=mktime(0,0,0,(int)date('m'),(int)date('d'), date('Y')+1);
		$this->assertText("{$base}status2", date('m-d-Y', $dateToCheck));


		$this->assertText("{$base}status3", "");
		$dateToCheck=time();
		$this->assertSelected("{$base}datepicker3_month", date('F', $dateToCheck));
		$this->assertSelected("{$base}datepicker3_year", date('Y', $dateToCheck));
		$this->select("{$base}datepicker3_year", date('Y')+1);
		$this->pause(800);
		$dateToCheck=mktime(0,0,0,(int)date('m'),(int)date('d'), date('Y')+1);
		$this->assertText("{$base}status3", date('m/Y', $dateToCheck));
	}
}
