<?php

class InPlaceDropDownListTest extends TPage
{
	public function selection_changed($sender, $param)
	{
		$this->status->Text = 'changed: ' . $sender->getSelectedValue();
	}

	public function server_select($sender, $param)
	{
		$this->ddl->setSelectedValue('blue');
		$this->status->Text = 'server selected';
	}

	public function rename_item($sender, $param)
	{
		// Replaces the selected item with one carrying the same value and a new
		// text. The selection does not change, so only the item list update
		// refreshes the label.
		$items = $this->ddl->getItems();
		$index = $this->ddl->getSelectedIndex();
		$items->removeAt($index);
		$items->insertAt($index, new TListItem('Renamed', 'red', true, true));
		$this->status->Text = 'renamed';
	}

	public function change_empty_text($sender, $param)
	{
		$this->lazy->getItems()->clear();
		$this->lazy->setEmptyDisplayText('(nothing left)');
		$this->lazyStatus->Text = 'empty text changed';
	}

	public function make_readonly($sender, $param)
	{
		$this->ddl->setReadOnly(true);
		$this->status->Text = 'readonly';
	}

	public function load_items($sender, $param)
	{
		$items = $this->lazy->getItems();
		$items->clear();
		$items->add('Fresh A');
		$items->itemAt(0)->setValue('a');
		$items->add('Fresh B');
		$items->itemAt(1)->setValue('b');
		$this->lazy->setSelectedValue('a');
	}

	public function lazy_changed($sender, $param)
	{
		$this->lazyStatus->Text = 'changed: ' . $sender->getSelectedValue();
	}
}
