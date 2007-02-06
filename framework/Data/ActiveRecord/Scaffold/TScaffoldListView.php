<?php

Prado::using('System.Data.ActiveRecord.Scaffold.TScaffoldBase');

class TScaffoldListView extends TScaffoldBase
{
	public function onLoad($param)
	{
		parent::onLoad($param);
		if(!$this->getPage()->getIsPostBack())
			$this->initializeSort();
	}

	protected function initializeSort()
	{
		$table = $this->getTableMetaData();
		$sorts = array('Sort By', str_repeat('-',15));
		$headers = array();
		foreach($table->getColumns() as $name=>$colum)
		{
			$fname = ucwords(str_replace('_', ' ', $name));
			$sorts[$name.' ASC'] = $fname .' Ascending';
			$sorts[$name.' DESC'] = $fname .' Descending';
			$headers[] = $fname ;
		}
		$this->_sort->setDataSource($sorts);
		$this->_sort->dataBind();
		$this->_header->setDataSource($headers);
		$this->_header->dataBind();
	}

	public function onPreRender($param)
	{
		parent::onPreRender($param);
		$this->initializeItemCount();
		$this->loadRecordData();
	}

	protected function initializeItemCount()
	{
		$this->_list->setVirtualItemCount($this->getRecordFinder()->count());
	}

	protected function loadRecordData()
	{
		$finder = $this->getRecordFinder();
		$criteria = $this->getPagingCriteria();
		$this->_list->setDataSource($finder->findAll($criteria));
		$this->_list->dataBind();
	}

	protected function getPagingCriteria()
	{
		$total = $this->_list->getVirtualItemCount();
		$limit = $this->_list->getPageSize();
		$offset = $this->_list->getCurrentPageIndex()*$limit;
		if($offset + $limit > $total)
			$limit = $total - $offset;
		$criteria = new TActiveRecordCriteria;
		$criteria->setLimit($limit);
		$criteria->setOffset($offset);
		$order = explode(' ',$this->_sort->getSelectedValue(), 2);
		if(is_array($order) && count($order) === 2)
			$criteria->OrdersBy[$order[0]] = $order[1];
		return $criteria;
	}

	public function bubbleEvent($sender, $param)
	{
		switch(strtolower($param->getCommandName()))
		{
			case 'delete':
				return $this->deleteRecord($sender, $param);
			case 'edit':
				if(($ctrl=$this->getEditViewControl())!==null)
				{
					if($param instanceof TRepeaterCommandEventParameter)
					{
						$pk = $param->getItem()->getCustomData();
						$ctrl->setRecordPk($pk);
						$ctrl->initializeEditForm();
					}
				}
		}
		$this->raiseBubbleEvent($this, $param);
		return true;
	}

	public function deleteRecord($sender, $param)
	{
		if($param instanceof TRepeaterCommandEventParameter)
		{
			$pk = $param->getItem()->getCustomData();
			$this->getRecordFinder()->deleteByPk($pk);
		}
	}

	protected function listItemCreated($sender, $param)
	{
		$item = $param->getItem();
		if($item instanceof IItemDataRenderer)
		{
			$type = $item->getItemType();
			if($type==TListItemType::Item || $type==TListItemType::AlternatingItem)
				$this->populateField($sender, $param);
		}
	}

	protected function populateField($sender, $param)
	{
		$item = $param->getItem();
		if(($data = $item->getData()) !== null)
		{
			$item->setCustomData($this->getRecordObjectPk($data));
			if(($prop = $item->findControl('_properties'))!==null)
			{
				$item->_properties->setDataSource($this->getRecordProperties($data));
				$item->_properties->dataBind();
			}
		}
	}

	protected function pageChanged($sender, $param)
	{
		$this->_list->setCurrentPageIndex($param->getNewPageIndex());
	}

	public function getList()
	{
		$this->ensureChildControls();
		return $this->getRegisteredObject('_list');
	}

	public function getPager()
	{
		$this->ensureChildControls();
		return $this->getRegisteredObject('_pager');
	}

	public function getSort()
	{
		$this->ensureChildControls();
		return $this->getRegisteredObject('_sort');
	}

	public function getHeader()
	{
		$this->ensureChildControls();
		return $this->getRegisteredObject('_header');
	}

	public function getEditViewID()
	{
		return $this->getViewState('EditViewID');
	}

	public function setEditViewID($value)
	{
		$this->setViewState('EditViewID', $value);
	}

	protected function getEditViewControl()
	{
		if(($id=$this->getEditViewID())!==null)
		{
			$ctrl = $this->getParent()->findControl($id);
			if($ctrl===null)
				throw new TConfigurationException('scaffold_unable_to_find_edit_view', $id);
			return $ctrl;
		}
	}
}

?>