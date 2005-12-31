<?php

class Home extends TPage
{
	public function selectionChanged($sender,$param)
	{
		$index=$sender->SelectedIndex;
		$value=$sender->SelectedValue;
		$text=$sender->SelectedItem->Text;
		$this->SelectionResult->Text="Your selection is (Index: $index, Value: $value, Text: $text).";
	}

	public function buttonClicked($sender,$param)
	{
		$index=$this->ListBox1->SelectedIndex;
		$value=$this->ListBox1->SelectedValue;
		$text=$this->ListBox1->SelectedItem->Text;
		$this->SelectionResult2->Text="Your selection is (Index: $index, Value: $value, Text: $text).";
	}
}

?>