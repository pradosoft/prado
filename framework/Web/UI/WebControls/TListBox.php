<?php

class TListBox extends TListControl implements IPostBackDataHandler
{
	protected function addAttributesToRender($writer)
	{
		$rows=$this->getRows();
		$writer->addAttribute('size',"$rows");
		if($this->getSelectionMode()==='Multiple')
			$writer->addAttribute('name',$this->getUniqueID().'[]');
		else
			$writer->addAttribute('name',$this->getUniqueID());
		parent::addAttributesToRender($writer);
	}

	protected function onPreRender($param)
	{
		parent::onPreRender($param);
		if($this->getEnabled(true))
			$this->getPage()->registerRequiresPostData($this);
	}

	public function loadPostData($key,$values)
	{
		if(!$this->getEnabled(true))
			return false;
		$selections=isset($values[$key])?$values[$key]:null;
		$this->ensureDataBound();
		if($selections!==null)
		{
			$items=$this->getItems();
			if($this->getSelectionMode()==='Single')
			{
				$selection=is_array($selections)?$selections[0]:$selections;
				$index=$items->findIndexByValue($selection,false);
				if($this->getSelectedIndex()!==$index)
				{
					$this->setSelectedIndex($index);
					return true;
				}
				else
					return false;
			}
			if(!is_array($selections))
				$selections=array($selections);
			$list=array();
			foreach($selections as $selection)
				$list[]=$items->findIndexByValue($selection,false);
			$list2=$this->getSelectedIndices();
			$n=count($list);
			$flag=false;
			if($n===count($list2))
			{
				sort($list,SORT_NUMERIC);
				for($i=0;$i<$n;++$i)
				{
					if($list[$i]!==$list2[$i])
					{
						$flag=true;
						break;
					}
				}
			}
			else
				$flag=true;
			if($flag)
				$this->setSelectedIndices($list);
			return $flag;
		}
		else if($this->getSelectedIndex()!==-1)
		{
			$this->clearSelection();
			return true;
		}
		else
			return false;
	}

	public function raisePostDataChangedEvent()
	{
		$page=$this->getPage();
		if($this->getAutoPostBack() && !$page->getPostBackEventTarget())
		{
			$page->setPostBackEventTarget($this);
			if($this->getCausesValidation())
				$page->validate($this->getValidationGroup());
		}
		$this->onSelectedIndexChanged(null);
	}

	protected function getIsMultiSelect()
	{
		return $this->getSelectionMode()==='Multiple';
	}

	public function getSelectedIndices()
	{
		return parent::getSelectedIndices();
	}

	/**
	 * @return integer the number of rows to be displayed in the component
	 */
	public function getRows()
	{
		return $this->getViewState('Rows', 4);
	}

	/**
	 * Sets the number of rows to be displayed in the component
	 * @param integer the number of rows
	 */
	public function setRows($value)
	{
		$value=TPropertyValue::ensureInteger($value);
		if($value<=0)
			$value=4;
		$this->setViewState('Rows', $value, 4);
	}

	/**
	 * @return string the selection mode (Single, Multiple )
	 */
	public function getSelectionMode()
	{
		return $this->getViewState('SelectionMode', 'Single');
	}

	/**
	 * Sets the selection mode of the component (Single, Multiple)
	 * @param string the selection mode
	 */
	function setSelectionMode($value)
	{
		$this->setViewState('SelectionMode',TPropertyValue::ensureEnum($value,array('Single','Multiple')),'Single');
	}
}
?>