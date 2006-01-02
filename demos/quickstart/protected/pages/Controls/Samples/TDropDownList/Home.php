<?php

class Home extends TPage
{
	public function selectionChanged($sender,$param)
	{
		if(($index=$sender->SelectedIndex)>=0)
		{
			$value=$sender->SelectedValue;
			$text=$sender->SelectedItem->Text;
			$this->SelectionResult->Text="Your selection is (Index: $index, Value: $value, Text: $text).";
		}
		else
			$this->SelectionResult->Text="Your selection is empty.";
	}

	public function buttonClicked($sender,$param)
	{
		if(($index=$this->ListBox1->SelectedIndex)>=0)
		{
			$value=$this->ListBox1->SelectedValue;
			$text=$this->ListBox1->SelectedItem->Text;
			$this->SelectionResult2->Text="Your selection is (Index: $index, Value: $value, Text: $text).";
		}
		else
			$this->SelectionResult2->Text="Your selection is empty.";
	}
}

?>