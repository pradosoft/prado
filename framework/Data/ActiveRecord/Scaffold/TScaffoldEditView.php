<?php

Prado::using('System.Data.ActiveRecord.Scaffold.TScaffoldBase');
Prado::using('System.Data.ActiveRecord.Scaffold.InputBuilder.TScaffoldInputBase');

class TScaffoldEditView extends TScaffoldBase
{
	private static $_builders=array();

	public function onLoad($param)
	{
		$this->initializeEditForm();
	}

	public function setRecordPk($value)
	{
		$this->clearRecordObject();
		$val = TPropertyValue::ensureArray($value);
		$this->setViewState('PK', count($val) > 0 ? $val : null);
	}

	public function getRecordPk()
	{
		return $this->getViewState('PK');
	}

	protected function getCurrentRecord()
	{
		return $this->getRecordObject($this->getRecordPk());
	}

	public function initializeEditForm()
	{
		$this->getCurrentRecord();
		$columns = $this->getTableMetaData()->getColumns();
		$this->_repeater->setDataSource($columns);
		$this->_repeater->dataBind();
	}

	public function repeaterItemCreated($sender, $param)
	{
		$type = $param->getItem()->getItemType();
		if($type==TListItemType::Item || $type==TListItemType::AlternatingItem)
		{
			$item = $param->getItem();
			$column = $item->getDataItem();
			if($column===null)
				return;

			$record = $this->getCurrentRecord();
			$builder = $this->getScaffoldInputBuilder($record);
			$builder->createScaffoldInput($this, $item, $column, $record);
		}
	}

	public function bubbleEvent($sender, $param)
	{
		switch(strtolower($param->getCommandName()))
		{
			case 'save':
				if($this->getPage()->getIsValid())
					return $this->doSave() === true ? false : true;
				return true;
			case 'clear':
				$this->setRecordPk(null);
				$this->initializeEditForm();
				return false;
			default:
				return false;
		}
	}

	protected function doSave()
	{
		$record = $this->getCurrentRecord();
		$table = $this->getTableMetaData();
		$builder = $this->getScaffoldInputBuilder($record);
		foreach($this->_repeater->getItems() as $item)
		{
			$column = $table->getColumn($item->getCustomData());
			$builder->loadScaffoldInput($this, $item, $column, $record);
		}
		$record->save();
		return true;
	}

	public function getSaveButton()
	{
		$this->ensureChildControls();
		return $this->getRegisteredObject('_save');
	}

	public function getClearButton()
	{
		$this->ensureChildControls();
		return $this->getRegisteredObject('_clear');
	}

	public function getCancelButton()
	{
		$this->ensureChildControls();
		return $this->getRegisteredObject('_cancel');
	}

	protected function getScaffoldInputBuilder($record)
	{
		$class = get_class($record);
		if(!isset(self::$_builders[$class]))
			self::$_builders[$class] = TScaffoldInputBase::createInputBuilder($record);
		return self::$_builders[$class];
	}

	public function getValidationGroup()
	{
		return 'group_'.$this->getUniqueID();
	}
}

?>