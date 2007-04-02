<?php 

class Ticket290 extends TPage
{
	function customValidate($sender, $param)
	{
		$this->label1->Text = "Doing Validation";
	}
	
	function button_clicked($sender, $param)
	{
		$this->label2->Text = $sender->Text . " Clicked!";
	}
}

?>