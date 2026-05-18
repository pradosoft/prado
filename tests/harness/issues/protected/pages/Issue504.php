<?php

class Issue504 extends TPage
{
	public function buttonOkClick($sender, $param)
	{
		$this->label1->Text = "buttonOkClick";
	}

	public function buttonDummyClick($sender, $param)
	{
		$this->label1->Text = "buttonDummyClick";
	}
}
