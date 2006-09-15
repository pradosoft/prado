<?php

class ActiveListBoxMasterTest extends TPage
{
	function list1_callback($sender, $param)
	{
		$values = $sender->getSelectedValues();
		$this->label1->setText("Selection: ".implode(', ', $values));
	}

	function select_index_123()
	{
		$this->list1->setSelectedIndices(array(1,2,3));
	}

	function select_index_4()
	{
		$this->list1->setSelectedIndex(4);
	}

	function clear_selections()
	{
		$this->list1->clearSelection();
	}

	function select_value_1()
	{
		$this->list1->setSelectedValue("value 1");
	}

	function select_values_25()
	{
		$this->list1->setSelectedValues(array('value 2', 'value 5'));
	}

	function change_to_multiple()
	{
		$this->list1->SelectionMode="Multiple";
	}

	function change_to_single()
	{
		$this->list1->SelectionMode="Single";
	}
}

?>