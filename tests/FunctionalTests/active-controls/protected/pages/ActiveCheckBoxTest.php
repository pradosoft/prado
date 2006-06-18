<?php

class ActiveCheckBoxTest extends TPage
{
	function change_checkbox1_text()
	{
		$this->checkbox1->Text = "Hello CheckBox 1";
	}
	
	function change_checkbox1_checked()
	{
		$this->checkbox1->Checked = !$this->checkbox1->Checked;
	}

	function change_checkbox2_text()
	{
		$this->checkbox2->Text = "CheckBox 2 World";
	}
	
	function change_checkbox2_checked()
	{
		$this->checkbox2->Checked = !$this->checkbox2->Checked;
	}
	
	function checkbox_requested($sender, $param)
	{
		$this->label1->Text = "Label 1:".$sender->Text. 
			($sender->checked ? ' Checked ' : ' Not Checked');
	}
}

?>