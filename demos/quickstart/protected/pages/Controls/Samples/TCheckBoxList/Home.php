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

	public function multiSelectionChanged($sender,$param)
	{
		$indices=$sender->SelectedIndices;
		$result='';
		foreach($indices as $index)
		{
			$item=$sender->Items[$index];
			$result.="(Index: $index, Value: $item->Value, Text: $item->Text)\n";
		}
		if($result==='')
			$this->MultiSelectionResult->Text='Your selection is empty.';
		else
			$this->MultiSelectionResult->Text='Your selection is: '.$result;
	}

	public function buttonClicked2($sender,$param)
	{
		$indices=$this->ListBox2->SelectedIndices;
		$result='';
		foreach($indices as $index)
		{
			$item=$this->ListBox2->Items[$index];
			$result.="(Index: $index, Value: $item->Value, Text: $item->Text)\n";
		}
		if($result==='')
			$this->MultiSelectionResult2->Text='Your selection is empty.';
		else
			$this->MultiSelectionResult2->Text='Your selection is: '.$result;
	}
}

?>