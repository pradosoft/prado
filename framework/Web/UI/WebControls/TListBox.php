<?php

class TListBox extends TListControl implements IPostBackDataHandler
{
	protected function addAttributesToRender($writer)
	{
		$rows=$this->getRows();
		$writer->addAttribute('size',"$rows");
		$writer->addAttribute('name',$this->getUniqueID());
		parent::addAttributesToRender($writer);
	}

	protected function onPreRender($param)
	{
		parent::onPreRender($param);
		if($this->getSelectionMode()==='Multiple' && $this->getEnabled(true))
			$this->getPage()->registerRequiresPostData($this);
	}

	public function loadPostData($key,$values)
	{
		if(!$this->getEnabled(true))
			return false;
		// ensure DataBound???
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