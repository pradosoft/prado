<?php

class ActiveMeterTest extends TPage
{
	public function set_segments()
	{
		$this->meter1->Max = 100;
		$this->meter1->Value = 70;
		$this->meter1->Low = 25;
		$this->meter1->High = 85;
		$this->meter1->Optimum = 10;
	}

	public function clear_segments()
	{
		$this->meter1->Low = null;
		$this->meter1->High = null;
		$this->meter1->Optimum = null;
	}

	public function shift_range()
	{
		$this->meter1->Min = -10;
		$this->meter1->Value = -5;
	}
}
