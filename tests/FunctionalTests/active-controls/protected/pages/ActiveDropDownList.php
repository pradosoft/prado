<?php

class ActiveDropDownList extends TPage
{
	function list1_changed($sender)
	{
		$this->label1->setText("Selection 1: ".$sender->getSelectedValue());		
		$this->addOptionsToList2($sender->getSelectedValue());
	}

	function addOptionsToList2($parent)
	{
		for($i = 0; $i < 5; $i++)
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
}

?>