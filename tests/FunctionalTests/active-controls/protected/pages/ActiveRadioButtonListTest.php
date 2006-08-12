<?php

class ActiveRadioButtonListTest extends TPage
{
	function list1_callback($sender, $param)
	{
		$values = $sender->getSelectedValues();
		$this->label1->setText("Selection: ".implode(', ', $values));
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
}

?>