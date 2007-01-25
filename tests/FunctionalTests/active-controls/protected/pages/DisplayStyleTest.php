<?php

class DisplayStyleTest extends TPage
{
	function display_button1()
	{
		$this->button1->Display="Dynamic";
	}

	function hide_button1()
	{
		$this->button1->Display="None";
	}

	function show_button2()
	{
		$this->button2->Display="Fixed";
	}

	function hide_button2()
	{
		$this->button2->Display="Hidden";
	}
}

?>