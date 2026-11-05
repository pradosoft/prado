<?php

class ActiveProgressTest extends TPage
{
	public function set_half()
	{
		$this->progress1->Value = 0.5;
	}

	public function set_scale()
	{
		$this->progress1->Max = 100;
		$this->progress1->Value = 75;
	}

	public function set_indeterminate()
	{
		$this->progress1->Value = null;
	}

	public function start_second()
	{
		$this->progress2->Value = 0.4;
	}
}
