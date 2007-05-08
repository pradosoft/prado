<?php

Prado::using('System.Web.UI.ActiveControls.*');

class Ticket587_reopened extends TPage
{
	function list1_changed($sender)
	{
		$this->label1->setText("Selection 1: ".$sender->getSelectedValue());
		$this->addOptionsToList2($sender->getSelectedValue(),$sender->getSelectedIndex());
	}

	function suggest($sender, $param)
	{
		$this->list2->Items->clear();
		for($i = 0; $i < 2; $i++)
           $this->list2->Items[$i] = 'asd 3 - item '.($i+1);
	}

	function addOptionsToList2($parent,$index)
	{
		$this->list2->Items->clear();
		for($i = 0; $i < 2+$index; $i++)
			$this->list2->Items[$i] = $parent.' - item '.($i+1);
		$this->list2->setEnabled(true);
	}

	function list2_changed($sender)
	{
		$this->label1->setText("Selection 2: ".$sender->getSelectedValue());
	}

	function select_index_3()
	{
		$this->list1->setSelectedIndex(3);
	}

	function clear_selections()
	{
		$this->list1->clearSelection();
	}

	function select_value_2()
	{
		$this->list1->setSelectedValue("value 2");
	}

	function select_index_3_plus()
	{
		$this->list1->setSelectedValue("value 3");
		$this->list1_changed($this->list1);
		$this->list2->setSelectedValue("value 3 - item 3");
	}

	function do_postback()
	{
		$value = 'List 1: '.$this->list1->selectedValue. ', List 2: '. $this->list2->selectedValue;
		$this->label1->Text = $value;
	}
}

?>