<?php

Prado::using('System.Data.ActiveRecord.Scaffold.TScaffoldBase');
Prado::using('System.Data.ActiveRecord.Scaffold.InputBuilder.TScaffoldInputBase');

class TScaffoldEditView extends TScaffoldBase
{
	private static $_builders=array();
	private $_editRenderer;

	public function onLoad($param)
	{
		if($this->getVisible())
			$this->initializeEditForm();
	}

	public function getEditRenderer()
	{
		return $this->getViewState('EditRenderer', '');
	}

	public function setEditRenderer($value)
	{
		$this->setViewState('EditRenderer', $value, '');
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
		$record = $this->getCurrentRecord();
		$classPath = $this->getEditRenderer();
		if($classPath === '')
		{
			$columns = $this->getTableMetaData()->getColumns();
			$this->getInputRepeater()->setDataSource($columns);
			$this->getInputRepeater()->dataBind();
		}
		else
		{
			if($this->_editRenderer===null)
				$this->createEditRenderer($record, $classPath);
			else
				$this->_editRenderer->setData($record);
		}
	}

	protected function createEditRenderer($record, $classPath)
	{
		$this->_editRenderer = Prado::createComponent($classPath);
		if($this->_editRenderer instanceof IScaffoldEditRenderer)
		{
			$index = $this->getControls()->remove($this->getInputRepeater());
			$this->getControls()->insertAt($index,$this->_editRenderer);
			$this->_editRenderer->setData($record);
		}
		else
		{
			throw new TConfigurationException(
				'scaffold_invalid_edit_renderer', $this->getID(), get_class($record));
		}
	}

	protected function repeaterItemCreated($sender, $param)
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
		if($this->_editRenderer===null)
		{
			$table = $this->getTableMetaData();
			$builder = $this->getScaffoldInputBuilder($record);
			foreach($this->getInputRepeater()->getItems() as $item)
			{
				$column = $table->getColumn($item->getCustomData());
				$builder->loadScaffoldInput($this, $item, $column, $record);
			}
		}
		else
		{
			$this->_editRenderer->updateRecord($record);
		}

		$record->save();
		return true;
	}

	protected function getInputRepeater()
	{
		$this->ensureChildControls();
		return $this->getRegisteredObject('_repeater');
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

interface IScaffoldEditRenderer extends IDataRenderer
{
	public function updateRecord($record);
}

?>