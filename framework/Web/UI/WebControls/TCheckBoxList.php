<?php

Prado::using('System.Web.UI.WebControls.TRepeatInfo');

class TCheckBoxList extends TListControl implements IRepeatInfoUser, INamingContainer, IPostBackDataHandler
{
	private $_repeatedControl;
	private $_isEnabled;

	public function __construct()
	{
		parent::__construct();
		$this->_repeatedControl=$this->createRepeatedControl();
		$this->_repeatedControl->setEnableViewState(false);
		$this->_repeatedControl->setID('0');
		$this->getControls()->add($this->_repeatedControl);
	}

	protected function createRepeatedControl()
	{
		return new TCheckBox;
	}

	public function findControl($id)
	{
		return $this;
	}

	protected function getIsMultiSelect()
	{
		return true;
	}

	protected function createStyle()
	{
		return new TTableStyle;
	}

	protected function getRepeatInfo()
	{
		if(($repeatInfo=$this->getViewState('RepeatInfo',null))===null)
		{
			$repeatInfo=new TRepeatInfo;
			$this->setViewState('RepeatInfo',$repeatInfo,null);
		}
		return $repeatInfo;
	}

	/**
	 * @return string the alignment of the text caption, defaults to 'Right'.
	 */
	public function getTextAlign()
	{
		return $this->getViewState('TextAlign','Right');
	}

	/**
	 * Sets the text alignment of the checkboxes
	 * @param string either 'Left' or 'Right'
	 */
	public function setTextAlign($value)
	{
		$this->setViewState('TextAlign',TPropertyValue::ensureEnum($value,array('Left','Right')),'Right');
	}

	/**
	 * @return integer the number of columns that the list should be displayed with. Defaults to 0 meaning not set.
	 */
	public function getRepeatColumns()
	{
		return $this->getRepeatInfo()->getRepeatColumns();
	}

	/**
	 * Sets the number of columns that the list should be displayed with.
	 * @param integer the number of columns that the list should be displayed with.
	 */
	public function setRepeatColumns($value)
	{
		$this->getRepeatInfo()->setRepeatColumns($value);
	}

	/**
	 * @return string the direction of traversing the list, defaults to 'Vertical'
	 */
	public function getRepeatDirection()
	{
		return $this->getRepeatInfo()->getRepeatDirection();
	}

	/**
	 * Sets the direction of traversing the list (Vertical, Horizontal)
	 * @param string the direction of traversing the list
	 */
	public function setRepeatDirection($value)
	{
		$this->getRepeatInfo()->setRepeatDirection($value);
	}

	/**
	 * @return string how the list should be displayed, using table or using line breaks. Defaults to 'Table'.
	 */
	public function getRepeatLayout()
	{
		return $this->getRepeatInfo()->getRepeatLayout();
	}

	/**
	 * Sets how the list should be displayed, using table or using line breaks (Table, Flow)
	 * @param string how the list should be displayed, using table or using line breaks (Table, Flow)
	 */
	public function setRepeatLayout($value)
	{
		$this->getRepeatInfo()->setRepeatLayout($value);
	}

	/**
	 * @return integer the cellspacing for the table keeping the checkbox list. Defaults to -1, meaning not set.
	 */
	public function getCellSpacing()
	{
		if($this->getHasStyle())
			return $this->getStyle()->getCellSpacing();
		else
			return -1;
	}

	/**
	 * Sets the cellspacing for the table keeping the checkbox list.
	 * @param integer the cellspacing for the table keeping the checkbox list.
	 */
	public function setCellSpacing($value)
	{
		$this->getStyle()->setCellSpacing($value);
	}

	/**
	 * @return integer the cellpadding for the table keeping the checkbox list. Defaults to -1, meaning not set.
	 */
	public function getCellPadding()
	{
		if($this->getHasStyle())
			return $this->getStyle()->getCellPadding();
		else
			return -1;
	}

	/**
	 * Sets the cellpadding for the table keeping the checkbox list.
	 * @param integer the cellpadding for the table keeping the checkbox list.
	 */
	public function setCellPadding($value)
	{
		$this->getStyle()->setCellPadding($value);
	}

	public function loadPostData($key,$values)
	{
	}

	public function raisePostDataChangedEvent()
	{
	}

	public function getHasHeader()
	{
		return false;
	}

	public function getHasFooter()
	{
		return false;
	}

	public function getHasSeparators()
	{
		return false;
	}

	public function getItemStyle($itemType,$index)
	{
		return null;
	}

	public function getRepeatedItemCount()
	{
		if($this->getHasItems())
			return $this->getItems()->getCount();
		else
			return 0;
	}

	public function renderItem($writer,$repeatInfo,$itemType,$index)
	{
		$item=$this->getItems()->itemAt($index);
		if($item->getHasAttributes())
			$this->_repeatedControl->getAttributes()->copyFrom($item->getAttributes());
		else if($this->_repeatedControl->getHasAttributes())
			$this->_repeatedControl->getAttributes()->clear();
		$this->_repeatedControl->setID("$index");
		$this->_repeatedControl->setText($item->getText());
		$this->_repeatedControl->setChecked($item->getSelected());
		$this->_repeatedControl->setEnabled($this->_isEnabled && $item->getEnabled());
		$this->_repeatedControl->renderControl($writer);
	}

	protected function onPreRender($param)
	{
		parent::onPreRender($param);
		$this->_repeatedControl->setAutoPostBack($this->getAutoPostBack());
		$this->_repeatedControl->setCausesValidation($this->getCausesValidation());
		$this->_repeatedControl->setValidationGroup($this->getValidationGroup());
		$page=$this->getPage();
		$n=$this->getRepeatedItemCount();
		for($i=0;$i<$n;++$i)
		{
			$this->_repeatedControl->setID("$i");
			$page->registerRequiresPostData($this->_repeatedControl);
		}
	}

	protected function render($writer)
	{
		if($this->getRepeatedItemCount()>0)
		{
			$this->_isEnabled=$this->getEnabled(true);
			$repeatInfo=$this->getRepeatInfo();
			$accessKey=$this->getAccessKey();
			$tabIndex=$this->getTabIndex();
			$this->_repeatedControl->setTextAlign($this->getTextAlign());
			$this->_repeatedControl->setAccessKey($accessKey);
			$this->_repeatedControl->setTabIndex($tabIndex);
			$this->setAccessKey('');
			$this->setTabIndex(0);
			$repeatInfo->renderRepeater($writer,$this);
			$this->setAccessKey($accessKey);
			$this->setTabIndex($tabIndex);
		}
	}
}

?>