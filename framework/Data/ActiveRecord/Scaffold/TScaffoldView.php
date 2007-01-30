<?php

Prado::using('System.Data.ActiveRecord.Scaffold.TScaffoldBase');
Prado::using('System.Data.ActiveRecord.Scaffold.TScaffoldListView');
Prado::using('System.Data.ActiveRecord.Scaffold.TScaffoldEditView');

class TScaffoldView extends TScaffoldBase
{
	public function onLoad($param)
	{
		parent::onLoad($param);
		$this->getListView()->copyFrom($this);
		$this->getEditView()->copyFrom($this);
	}

	public function getListView()
	{
		$this->ensureChildControls();
		return $this->getRegisteredObject('_listView');
	}

	public function getEditView()
	{
		$this->ensureChildControls();
		return $this->getRegisteredObject('_editView');
	}

	public function getAddButton()
	{
		$this->ensureChildControls();
		return $this->getRegisteredObject('_newButton');
	}

	public function bubbleEvent($sender,$param)
	{
		switch(strtolower($param->getCommandName()))
		{
			case 'edit':
				return $this->showEditView($sender, $param);
			case 'new':
				return $this->showAddView($sender, $param);
			default:
				return $this->showListView($sender, $param);
		}
		return false;
	}

	protected function showEditView($sender, $param)
	{
		$this->getListView()->setVisible(false);
		$this->getEditView()->setVisible(true);
		$this->getAddButton()->setVisible(false);
		$this->getEditView()->getCancelButton()->setVisible(true);
		$this->getEditView()->getClearButton()->setVisible(false);
	}

	protected function showListView($sender, $param)
	{
		$this->getListView()->setVisible(true);
		$this->getEditView()->setVisible(false);
		$this->getAddButton()->setVisible(true);
	}

	protected function showAddView($sender, $param)
	{
		$this->getEditView()->setRecordPk(null);
		$this->getEditView()->initializeEditForm();
		$this->showEditView($sender, $param);
	}
}

?>