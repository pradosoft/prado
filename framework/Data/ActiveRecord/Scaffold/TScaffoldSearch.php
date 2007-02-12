<?php

Prado::using('System.Data.ActiveRecord.Scaffold.TScaffoldBase');

class TScaffoldSearch extends TScaffoldBase
{
	private $_list;

	public function getListView()
	{
		if($this->_list===null && ($id = $this->getListViewID()) !== null)
		{
			$this->_list = $this->getParent()->findControl($id);
			if($this->_list ===null)
				throw new TConfigurationException('scaffold_unable_to_find_list_view', $id);
		}
		return $this->_list;
	}

	public function setListView($value)
	{
		$this->_list = $value;
	}

	public function setListViewID($value)
	{
		$this->setViewState('ListViewID', $value);
	}

	public function getListViewID()
	{
		return $this->getViewState('ListViewID');
	}

	public function bubbleEvent($sender, $param)
	{
		if(strtolower($param->getCommandName())==='search')
		{
			if(($list = $this->getListView()) !== null)
			{
				$list->setSearchCondition($this->createSearchCondition());
				$list->setSearchParameters(array());
				return false;
			}
		}
		$this->raiseBubbleEvent($this, $param);
		return true;
	}

	protected function createSearchCondition()
	{
		$table = $this->getTableMetaData();
		if(strlen($str=$this->getSearchText()->getText()) > 0)
			return $table->getSearchRegExpCriteria($this->getFields(), $str);
	}

	protected function getFields()
	{
		if(strlen(trim($str=$this->getSearchableFields()))>0)
			$fields = preg_split('/\s*,\s*/', $str);
		else
			$fields = array_keys($this->getTableMetaData()->getColumns());
		return $fields;
	}

	public function getSearchableFields()
	{
		return $this->getViewState('SearchableFields','');
	}

	public function setSearchableFields($value)
	{
		$this->setViewState('SearchableFields', $value, '');
	}

	public function getSearchButton()
	{
		$this->ensureChildControls();
		return $this->getRegisteredObject('_search');
	}

	public function getSearchText()
	{
		$this->ensureChildControls();
		return $this->getRegisteredObject('_textbox');
	}
}

?>