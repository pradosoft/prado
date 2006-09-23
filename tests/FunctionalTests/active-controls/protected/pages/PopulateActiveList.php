<?php

class PopulateActiveList extends TPage
{
	public function populate_list1($sender, $param)
	{
		$data = array('Hello', 'World', 'Prado');
		$this->list1->Items->clear();
		for($i = 0,$k=count($data); $i<$k; $i++)
		{
			$item = new TListItem($data[$i], $i);
			$this->list1->Items[] = $item;
		}
	}

	public function populate_list2($sender, $param)
	{
		$data = array('Hello', 'World', 'Prado');
		$this->list2->Items->clear();
		for($i = 0,$k=count($data); $i<$k; $i++)
		{
			$item = new TListItem($data[$i], $i);
			$this->list2->Items[] = $item;
		}
	}

	public function list_changed($sender, $param)
	{
		$text = $sender->SelectedItem ? $sender->SelectedItem->Text : 'Not selected';
		$this->label1->Text = $sender->ID . ': '.$text;
	}
}

?>