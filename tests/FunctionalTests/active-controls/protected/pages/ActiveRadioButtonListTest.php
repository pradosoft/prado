<?php

class ActiveRadioButtonListTest extends TPage
{
	public function list1_callback($sender, $param)
	{
		$values = $sender->getSelectedValues();
		$this->label1->setText("Selection: " . implode(', ', $values));
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
}
