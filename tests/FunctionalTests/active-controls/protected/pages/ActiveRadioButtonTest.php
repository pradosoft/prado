<?php

class ActiveRadioButtonTest extends TPage
{
	public function change_radio1_text()
	{
		$this->radio1->Text = "Hello Radio Button 1";
	}

	public function change_radio1_checked()
	{
		$this->radio1->Checked = !$this->radio1->Checked;
	}

	public function change_radio2_text()
	{
		$this->radio2->Text = "Radio Button 2 World";
	}

	public function change_radio2_checked()
	{
		$this->radio2->Checked = !$this->radio2->Checked;
	}

	public function radiobutton_requested($sender, $param)
	{
		$this->label1->Text = "Label 1:" . $sender->Text .
			($sender->checked ? ' Checked ' : ' Not Checked');
	}
}
