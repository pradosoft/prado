<?php

class Home extends TPage
{
	public function buttonClicked($sender,$param)
	{
		$indices=$this->CheckBoxList->SelectedIndices;
		$result='';
		foreach($indices as $index)
		{
			$item=$this->CheckBoxList->Items[$index];
			$result.="(Index: $index, Value: $item->Value, Text: $item->Text)\n";
		}
		if($result==='')
			$this->SelectionResult->Text='Your selection is empty.';
		else
			$this->SelectionResult->Text='Your selection is: '.$result;
	}

	public function selectionChanged($sender,$param)
	{
		$indices=$sender->SelectedIndices;
		$result='';
		foreach($indices as $index)
		{
			$item=$sender->Items[$index];
			$result.="(Index: $index, Value: $item->Value, Text: $item->Text)\n";
		}
		if($result==='')
			$this->SelectionResult2->Text='Your selection is empty.';
		else
			$this->SelectionResult2->Text='Your selection is: '.$result;
	}
}

?>