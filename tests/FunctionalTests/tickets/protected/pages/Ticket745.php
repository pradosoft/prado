<?php

class Ticket745 extends TPage
{
	public function wizardCompleted($sender, $param)
	{
		$this->Result->Text = "Your favorite color is: " . $this->DropDownList1->SelectedValue;
	}
}
