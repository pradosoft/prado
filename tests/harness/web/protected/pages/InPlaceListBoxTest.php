<?php

class InPlaceListBoxTest extends TPage
{
	public function selection_changed($sender, $param)
	{
		$this->status->Text = 'changed: ' . implode(',', $sender->getSelectedValues());
	}

	public function server_select($sender, $param)
	{
		$this->lb->setSelectedValues(['green', 'blue']);
		$this->status->Text = 'server selected';
	}

	public function server_clear($sender, $param)
	{
		$this->lb->clearSelection();
		$this->status->Text = 'cleared';
	}
}
