<?php

Prado::using('System.Data.ActiveRecord.TActiveRecord');

abstract class TScaffoldBase extends TTemplateControl
{
	private $_record;
	private $_meta;

	protected function getTableMetaData()
	{
		if($this->_meta===null)
		{
			$finder = $this->getRecordFinder();
			$gateway = $finder->getRecordManager()->getRecordGateWay();
			$this->_meta = $gateway->getMetaData($finder);
		}
		return $this->_meta;
	}

	protected function getRecordProperties($record)
	{
		$data = array();
		foreach($this->getTableMetaData()->getColumns() as $name=>$column)
			$data[] = $record->{$name};
		return $data;
	}

	public function getRecordObjectPk($record)
	{
		$pk = array();
		foreach($this->getTableMetaData()->getColumns() as $name=>$column)
		{
			if($column->getIsPrimaryKey())
				$data[] = $record->{$name};
		}
		return $data;
	}

	public function getRecordClass()
	{
		return $this->getViewState('RecordClass');
	}

	public function setRecordClass($value)
	{
		$this->setViewState('RecordClass', $value);
	}

	public function copyFrom(TScaffoldBase $obj)
	{
		$this->_record = $obj->_record;
		$this->_meta = $obj->_meta;
		$this->setRecordClass($obj->getRecordClass());
	}

	protected function clearRecordObject()
	{
		$this->_record=null;
		$this->_meta=null;
	}

	public function getRecordObject($pk=null)
	{
		if($this->_record===null)
		{
			if($pk!==null)
				$this->_record=$this->getRecordFinder()->findByPk($pk);
			else
			{
				$class = $this->getRecordClass();
				if($class!==null)
					$this->_record=Prado::createComponent($class);
				else
					throw new TConfigurationException('scaffold_invalid_record_class', $this->getID());
			}
		}
		return $this->_record;
	}

	public function getRecordFinder()
	{
		return TActiveRecord::getRecordFinder(get_class($this->getRecordObject()));
	}

	public function setRecordObject($value)
	{
		if($value instanceof TActiveRecord)
			$this->_record=$value;
		else
			throw new TConfigurationException('scaffold_object_must_be_tactiverecord', $this->getID());
	}

	public function getDefaultStyle()
	{
		return $this->getViewState('DefaultStyle', 'style');
	}

	public function setDefaultStyle($value)
	{
		$this->setViewState('DefaultStyle', TPropertyValue::ensureString($value), 'style');
	}

	public function onPreRender($param)
	{
		parent::onPreRender($param);
		$url = $this->publishAsset($this->getDefaultStyle().'.css');
		$cs = $this->getPage()->getClientScript();
		$cs->registerStyleSheetFile($url,$url);
	}
}

?>