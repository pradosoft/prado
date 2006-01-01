<?php

interface IDataSource
{
	public function getView($viewName);
	public function getViewNames();
	public function onDataSourceChanged($param);
}

abstract class TDataSourceControl extends TControl implements IDataSource
{
	public function getView($viewName);

	public function getViewNames()
	{
		return array();
	}

	protected function onDataSourceChanged($param)
	{
		$this->raiseEvent('DataSourceChanged',$this,$param);
	}

	public function focus()
	{
		throw new TNotSupportedException('datasourcecontrol_focus_unsupported');
	}

	public function getEnableTheming()
	{
		return false;
	}

	public function setEnableTheming($value)
	{
		throw new TNotSupportedException('datasourcecontrol_enabletheming_unsupported');
	}

	public function getSkinID()
	{
		return '';
	}

	public function setSkinID($value)
	{
		throw new TNotSupportedException('datasourcecontrol_skinid_unsupported');
	}

	public function getVisible()
	{
		return false;
	}

	public function setVisible($value)
	{
		throw new TNotSupportedException('datasourcecontrol_visible_unsupported');
	}
}

class TReadOnlyDataSource extends TDataSourceControl
{
	private $_dataSource;
	private $_dataMember;

	public function __construct($dataSource,$dataMember)
	{
		if(!is_array($dataSource) && !($dataSource instanceof IDataSource) && !($dataSource instanceof Traversable))
			throw new TInvalidDataTypeException('readonlydatasource_datasource_invalid');
		$this->_dataSource=$dataSource;
		$this->_dataMember=$dataMember;
	}

	public function getView($viewName)
	{
		if($this->_dataSource instanceof IDataSource)
			return $this->_dataSource->getView($viewName);
		else
			return new TReadOnlyDataSourceView($this,$this->_dataMember,$this->_dataSource);
	}
}

?>