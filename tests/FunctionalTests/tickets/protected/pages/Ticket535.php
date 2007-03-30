<?php
Prado::using('System.Web.UI.ActiveControls.*');
class Ticket535 extends TPage
{
	public function noGroup($sender, $param)
	{
		$this->label1->Text = "radio1 checked:{".$this->radio1->getChecked()."}   radio2 checked:{".$this->radio2->getChecked()."} ";
	}

	public function group($sender, $param)
	{
		$this->label1->Text = "bad_radio1 checked:{".$this->bad_radio1->getChecked()."}   bad_radio2 checked:{".$this->bad_radio2->getChecked()."} ";
	}
}

?>