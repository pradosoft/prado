<?php

class Ticket507 extends TPage
{
	public function onLoad($s)
	{
		//the following fixed the static declaration on the above
		$this->list1->SelectionMode = "Multiple";
	}

	public function list1_callback($sender, $param)
	{
		$values = $sender->getSelectedValues();
		$this->label1->setText("Selection: " . implode(', ', $values));
	}

	public function enable_list()
	{
		$this->list1->enabled = true;
	}
}
