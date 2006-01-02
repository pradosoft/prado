<?php

class Home extends TPage
{
	public function buttonClicked($sender,$param)
	{
		$index=$this->RadioButtonList->SelectedIndex;
		$value=$this->RadioButtonList->SelectedValue;
		$text=$this->RadioButtonList->SelectedItem->Text;
		$this->SelectionResult->Text="Your selection is (Index: $index, Value: $value, Text: $text).";
	}

	public function selectionChanged($sender,$param)
	{
		$index=$sender->SelectedIndex;
		$value=$sender->SelectedValue;
		$text=$sender->SelectedItem->Text;
		$this->SelectionResult2->Text="Your selection is (Index: $index, Value: $value, Text: $text).";
	}
}

?>