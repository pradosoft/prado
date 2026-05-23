<?php

class ActiveTextBoxCallback extends TPage
{
	public function textbox1_callback($sender, $param)
	{
		$this->label1->Text = 'Label 1: ' . $sender->Text;
	}
}
