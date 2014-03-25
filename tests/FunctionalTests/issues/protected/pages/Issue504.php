<?php

class Issue504 extends TPage
{
	function buttonOkClick($sender, $param)
	{
		$this->label1->Text="buttonOkClick";
	}

	function buttonDummyClick($sender, $param)
	{
		$this->label1->Text="buttonDummyClick";
	}
}
