<?php

class DisplayStyleTest extends TPage
{
	public function display_button1()
	{
		$this->button1->Display = "Dynamic";
	}

	public function hide_button1()
	{
		$this->button1->Display = "None";
	}

	public function show_button2()
	{
		$this->button2->Display = "Fixed";
	}

	public function hide_button2()
	{
		$this->button2->Display = "Hidden";
	}
}
