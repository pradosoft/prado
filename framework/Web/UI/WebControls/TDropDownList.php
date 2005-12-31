<?php

class TDropDownList extends TListControl implements IPostBackDataHandler
{
	protected function addAttributesToRender($writer)
	{
		$writer->addAttribute('name',$this->getUniqueID());
		parent::addAttributesToRender($writer);
	}

	public function loadPostData($key,$values)
	{
		//TODO: Need to doublecheck!!@!
		if(!$this->getEnabled(true))
			return false;
		$selections=isset($values[$key])?$values[$key]:null;
		$this->ensureDataBound();
		if($selections!==null)
		{
			$items=$this->getItems();
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

	public function getSelectedIndex()
	{
		$index=parent::getSelectedIndex();
		if($index<0 && $this->getItems()->getCount()>0)
		{
			$this->setSelectedIndex(0);
			return 0;
		}
		else
			return $index;
	}
}
?>