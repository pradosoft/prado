<?php

class ActiveDatePickerTestCase extends \Prado\Tests\PradoGenericSelenium2Test
{
	public function days_in_month($month, $year)
	{
		// calculate number of days in a month
		return $month == 2 ? ($year % 4 ? 28 : ($year % 100 ? 29 : ($year % 400 ? 28 : 29))) : (($month - 1) % 7 % 2 ? 30 : 31);
	}
	public function test()
	{
		$base = 'ctl0_Content_';
		$this->url("active-controls/index.php?page=ActiveDatePicker");
		$this->assertSourceContains("TActiveDatePicker test");
		$this->assertText("{$base}status", "");
		$this->assertValue("{$base}datepicker", date('m-d-Y'));
		$this->byId("{$base}increaseButton")->click();
		$this->assertValue("{$base}datepicker", date('m-d-Y', strtotime('+ 1 day')));
		$this->assertText("{$base}status", date('m-d-Y', strtotime('+ 1 day')));
		$this->byId("{$base}increaseButton")->click();
		$this->assertValue("{$base}datepicker", date('m-d-Y', strtotime('+ 2 day')));
		$this->assertText("{$base}status", date('m-d-Y', strtotime('+ 2 day')));
		$this->byId("{$base}todayButton")->click();
		$this->assertValue("{$base}datepicker", date('m-d-Y'));
		$this->assertText("{$base}status", date('m-d-Y'));
		$this->byId("{$base}decreaseButton")->click();
		$this->assertValue("{$base}datepicker", date('m-d-Y', strtotime('- 1 day')));
		$this->assertText("{$base}status", date('m-d-Y', strtotime('- 1 day')));
		$this->byId("{$base}datepicker")->click();
		$this->byCssSelector("input.todayButton")->click();
		$this->assertValue("{$base}datepicker", date('m-d-Y'));
		$this->assertText("{$base}status", date('m-d-Y'));
		$this->byCssSelector("input.nextMonthButton")->click();
		$nextMonthDate = (function () {	// nextMonth - datepicker.js:L532
				$d = new DateTime(); // now
				$currentDay = (int)$d->format('j');
		
				$d->modify('first day of next month');
				$daysInMonth = (int)$d->format('t');
		
				$d->setDate(
					(int)$d->format('Y'),
					(int)$d->format('n'),
					min($currentDay, $daysInMonth)
				);
		
				return $d->getTimestamp();
			})();
		$this->assertValue("{$base}datepicker", date('m-d-Y', $nextMonthDate));
		$this->assertText("{$base}status", date('m-d-Y', $nextMonthDate));

		$this->byId("{$base}toggleButton")->click();
		$this->pause(2000);

		$this->byId("{$base}todayButton")->click();
		$this->assertSelected("{$base}datepicker_month", date('m'));
		$this->assertText("{$base}status", date('m-d-Y'));

		$this->byId("{$base}increaseButton")->click();
		$dateToCheck = strtotime('+ 1 day');
		$this->assertSelected("{$base}datepicker_month", date('m', $dateToCheck));
		$this->assertSelected("{$base}datepicker_day", date('d', $dateToCheck));
		$this->assertSelected("{$base}datepicker_year", date('Y', $dateToCheck));
		$this->assertText("{$base}status", date('m-d-Y', $dateToCheck));

		$this->byId("{$base}increaseButton")->click();
		$dateToCheck = strtotime('+ 2 day');
		$this->assertSelected("{$base}datepicker_month", date('m', $dateToCheck));
		$this->assertSelected("{$base}datepicker_day", date('d', $dateToCheck));
		$this->assertSelected("{$base}datepicker_year", date('Y', $dateToCheck));
		$this->assertText("{$base}status", date('m-d-Y', $dateToCheck));

		$this->byId("{$base}todayButton")->click();
		$dateToCheck = time();
		$this->assertSelected("{$base}datepicker_month", date('m', $dateToCheck));
		$this->assertSelected("{$base}datepicker_day", date('d', $dateToCheck));
		$this->assertSelected("{$base}datepicker_year", date('Y', $dateToCheck));
		$this->assertText("{$base}status", date('m-d-Y', $dateToCheck));

		$this->byId("{$base}decreaseButton")->click();
		$dateToCheck = strtotime('- 1 day');
		$this->assertSelected("{$base}datepicker_month", date('m', $dateToCheck));
		$this->assertSelected("{$base}datepicker_day", date('d', $dateToCheck));
		$this->assertSelected("{$base}datepicker_year", date('Y', $dateToCheck));
		$this->assertText("{$base}status", date('m-d-Y', $dateToCheck));

		$this->byId("{$base}datepickerbutton")->click();
		$this->byCssSelector("input.todayButton")->click();
		$dateToCheck = time();
		$this->assertSelected("{$base}datepicker_month", date('m', $dateToCheck));
		$this->assertSelected("{$base}datepicker_day", date('d', $dateToCheck));
		$this->assertSelected("{$base}datepicker_year", date('Y', $dateToCheck));
		$this->assertText("{$base}status", date('m-d-Y', $dateToCheck));

		$this->byCssSelector("input.nextMonthButton")->click();
		$dateToCheck = strtotime('+ 1 month');
		$this->assertSelected("{$base}datepicker_month", date('m', $dateToCheck));
		$this->assertSelected("{$base}datepicker_day", date('d', $dateToCheck));
		$this->assertSelected("{$base}datepicker_year", date('Y', $dateToCheck));
		$this->assertText("{$base}status", date('m-d-Y', $dateToCheck));

		$this->byId('ctl0_ctl1')->click();

		$this->assertText("{$base}status2", "");
		$dateToCheck = time();
		$this->assertSelected("{$base}datepicker2_month", date('m', $dateToCheck));
		$this->assertSelected("{$base}datepicker2_day", date('d', $dateToCheck));
		$this->assertSelected("{$base}datepicker2_year", date('Y', $dateToCheck));
		$this->select("{$base}datepicker2_year", date('Y') + 1);
		$dateToCheck = mktime(0, 0, 0, (int) date('m'), (int) date('d'), date('Y') + 1);
		$this->assertText("{$base}status2", date('m-d-Y', $dateToCheck));


		$this->assertText("{$base}status3", "");
		$dateToCheck = time();
		$this->assertSelected("{$base}datepicker3_month", date('F', $dateToCheck));
		$this->assertSelected("{$base}datepicker3_year", date('Y', $dateToCheck));
		$this->select("{$base}datepicker3_year", date('Y') + 1);
		$dateToCheck = mktime(0, 0, 0, (int) date('m'), (int) date('d'), date('Y') + 1);
		$this->assertText("{$base}status3", date('m/Y', $dateToCheck));
	}
}
