<?php

class ActiveListBoxMasterTest extends TPage
{
	public function list1_callback($sender, $param)
	{
		$values = $sender->getSelectedValues();
		$this->label1->setText("Selection: " . implode(', ', $values));
	}

	public function select_index_123()
	{
		$this->list1->setSelectedIndices([1, 2, 3]);
	}

	public function select_index_4()
	{
		$this->list1->setSelectedIndex(4);
	}

	public function clear_selections()
	{
		$this->list1->clearSelection();
	}

	public function select_value_1()
	{
		$this->list1->setSelectedValue("value 1");
	}

	public function select_values_25()
	{
		$this->list1->setSelectedValues(['value 2', 'value 5']);
	}

	public function change_to_multiple()
	{
		$this->list1->SelectionMode = "Multiple";
	}

	public function change_to_single()
	{
		$this->list1->SelectionMode = "Single";
	}
}
