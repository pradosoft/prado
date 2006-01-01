<?php

abstract class TListControl extends TDataBoundControl
{
	private $_items=null;

	/**
	 * @return string tag name of the textbox
	 */
	protected function getTagName()
	{
		return 'select';
	}

	/**
	 * Adds attributes to renderer.
	 * @param THtmlWriter the renderer
	 */
	protected function addAttributesToRender($writer)
	{
		$page=$this->getPage();
		$page->ensureRenderInForm($this);
		if($this->getIsMultiSelect())
			$writer->addAttribute('multiple','multiple');
		if($this->getAutoPostBack() && $page->getClientSupportsJavaScript())
		{
			$writer->addAttribute('id',$this->getClientID());
			$options = $this->getAutoPostBackOptions();
			$scripts = $this->getPage()->getClientScript();
			$postback = $scripts->getPostBackEventReference($this,'',$options,false);
			$scripts->registerClientEvent($this, "change", $postback);
		}
		if($this->getEnabled(true) && !$this->getEnabled())
			$writer->addAttribute('disabled','disabled');
		parent::addAttributesToRender($writer);
	}

	protected function getAutoPostBackOptions()
	{
		$option=new TPostBackOptions();
		$group = $this->getValidationGroup();
		$hasValidators = $this->getPage()->getValidators($group)->getCount()>0;
		if($this->getCausesValidation() && $hasValidators)
		{
			$option->setPerformValidation(true);
			$option->setValidationGroup($group);
		}
		$option->setAutoPostBack(true);
		return $option;
	}

	public function addParsedObject($object)
	{
		if($object instanceof TListItem)
			$this->getItems()->add($object);
	}

	protected function validateDataSource($value)
	{
		if(is_string($value))
		{
			$list=new TList;
			foreach(TPropertyValue::ensureArray($value) as $key=>$value)
				$list->add(array($value,is_string($key)?$key:$value));
			return $list;
		}
		else
			return parent::validateDataSource($value);
		return $value;
	}

	protected function performDataBinding($data)
	{
		if($data instanceof Traversable)
		{
			$textField=$this->getDataTextField();
			if($textField==='')
				$textField=0;
			$valueField=$this->getDataValueField();
			if($valueField==='')
				$valueField=1;
			$textFormat=$this->getDataTextFormatString();
			$items=$this->getItems();
			if(!$this->getAppendDataBoundItems())
				$items->clear();
			foreach($data as $object)
			{
				$item=new TListItem;
				if(isset($object[$textField]))
					$text=$object[$textField];
				else
					$text=TPropertyValue::ensureString($object);
				$item->setText($textFormat===''?$text:sprintf($textFormat,$text));
				if(isset($object[$valueField]))
					$item->setValue($object[$valueField]);
				$items->add($item);
			}
		}
	}

	protected function onSaveState($param)
	{
		if($this->_items)
			$this->setViewState('Items',$this->_items->saveState(),null);
		else
			$this->clearViewState('Items');
	}

	protected function onLoadState($param)
	{
		$this->_items=new TListItemCollection;
		$this->_items->loadState($this->getViewState('Items',null));
	}

	protected function getIsMultiSelect()
	{
		return false;
	}

	public function getAppendDataBoundItems()
	{
		return $this->getViewState('AppendDataBoundItems',false);
	}

	public function setAppendDataBoundItems($value)
	{
		$this->setViewState('AppendDataBoundItems',TPropertyValue::ensureBoolean($value),false);
	}

	/**
	 * @return boolean a value indicating whether an automatic postback to the server
     * will occur whenever the user modifies the text in the TTextBox control and
     * then tabs out of the component. Defaults to false.
	 */
	public function getAutoPostBack()
	{
		return $this->getViewState('AutoPostBack',false);
	}

	/**
	 * Sets the value indicating if postback automatically.
	 * An automatic postback to the server will occur whenever the user
	 * modifies the text in the TTextBox control and then tabs out of the component.
	 * @param boolean the value indicating if postback automatically
	 */
	public function setAutoPostBack($value)
	{
		$this->setViewState('AutoPostBack',TPropertyValue::ensureBoolean($value),false);
	}

	/**
	 * @return boolean whether postback event trigger by this text box will cause input validation, default is true.
	 */
	public function getCausesValidation()
	{
		return $this->getViewState('CausesValidation',true);
	}

	/**
	 * Sets the value indicating whether postback event trigger by this text box will cause input validation.
	 * @param boolean whether postback event trigger by this button will cause input validation.
	 */
	public function setCausesValidation($value)
	{
		$this->setViewState('CausesValidation',TPropertyValue::ensureBoolean($value),true);
	}

	/**
	 * @return string the field of the data source that provides the text content of the list items.
	 */
	public function getDataTextField()
	{
		return $this->getViewState('DataTextField','');
	}

	/**
	 * @param string the field of the data source that provides the text content of the list items.
	 */
	public function setDataTextField($value)
	{
		$this->setViewState('DataTextField',$value,'');
	}

	/**
	 * @return string the formatting string used to control how data bound to the list control is displayed.
	 */
	public function getDataTextFormatString()
	{
		return $this->getViewState('DataTextFormatString','');
	}

	/**
	 * @param string the formatting string used to control how data bound to the list control is displayed.
	 */
	public function setDataTextFormatString($value)
	{
		$this->setViewState('DataTextFormatString',$value,'');
	}

	/**
	 * @return string the field of the data source that provides the value of each list item.
	 */
	public function getDataValueField()
	{
		return $this->getViewState('DataValueField','');
	}

	/**
	 * @param string the field of the data source that provides the value of each list item.
	 */
	public function setDataValueField($value)
	{
		$this->setViewState('DataValueField',$value,'');
	}

	public function getHasItems()
	{
		return ($this->_items && $this->_items->getCount()>0);
	}

	public function getItems()
	{
		if(!$this->_items)
			$this->_items=new TListItemCollection;
		return $this->_items;
	}

	/**
	 * @return integer the index of the item being selected, -1 if no selection
	 */
	public function getSelectedIndex()
	{
		if($this->_items)
		{
			$n=$this->_items->getCount();
			for($i=0;$i<$n;++$i)
				if($this->_items->itemAt($i)->getSelected())
					return $i;
		}
		return -1;
	}

	/**
	 * @param integer the index of the item to be selected
	 */
	public function setSelectedIndex($index)
	{
		$index=TPropertyValue::ensureInteger($index);
		if($this->_items)
		{
			$this->clearSelection();
			if($index>=0 && $index<$this->_items->getCount())
				$this->_items->itemAt($index)->setSelected(true);
		}
	}

	protected function getSelectedIndices()
	{
		$selections=array();
		if($this->_items)
		{
			$n=$this->_items->getCount();
			for($i=0;$i<$n;++$i)
				if($this->_items->itemAt($i)->getSelected())
					$selections[]=$i;
		}
		return $selections;
	}

	protected function setSelectedIndices($indices)
	{
		if($this->_items)
		{
			$this->clearSelection();
			$n=$this->_items->getCount();
			foreach($indices as $index)
			{
				if($index>=0 && $index<$n)
					$this->_items->itemAt($index)->setSelected(true);
			}
		}
	}

	/**
	 * @return TListItem|null the selected item with the lowest cardinal index, null if no selection.
	 */
	public function getSelectedItem()
	{
		if(($index=$this->getSelectedIndex())>=0)
			return $this->_items->itemAt($index);
		else
			return null;
	}

	/**
	 * @return string the value of the selected item with the lowest cardinal index, empty if no selection
	 */
	public function getSelectedValue()
	{
		$index=$this->getSelectedIndex();
		return $index>=0?$this->getItems()->itemAt($index)->getValue():'';
	}

	/**
	 * Sets selection by item value.
	 * @param string the value of the item to be selected.
	 */
	public function setSelectedValue($value)
    {
	    if($this->_items)
	    {
		    if($value===null)
		    	$this->clearSelection();
		    else if(($item=$this->_items->findItemByValue($value))!==null)
	    	{
		    	$this->clearSelection();
		    	$item->setSelected(true);
	    	}
    	}
    }

    public function clearSelection()
    {
	    if($this->_items)
	    {
		    foreach($this->_items as $item)
		    	$item->setSelected(false);
	    }
    }

	/**
	 * @return string the text content of the TTextBox control.
	 */
	public function getText()
	{
		return $this->getSelectedValue();
	}

	/**
	 * Sets the text content of the TTextBox control.
	 * @param string the text content
	 */
	public function setText($value)
	{
		$this->setSelectedValue($value);
	}

	/**
	 * @return string the group of validators which the text box causes validation upon postback
	 */
	public function getValidationGroup()
	{
		return $this->getViewState('ValidationGroup','');
	}

	/**
	 * @param string the group of validators which the text box causes validation upon postback
	 */
	public function setValidationGroup($value)
	{
		$this->setViewState('ValidationGroup',$value,'');
	}

	public function onSelectedIndexChanged($param)
	{
		$this->raiseEvent('SelectedIndexChanged',$this,$param);
	}

	// ????
	public function onTextChanged($param)
	{
		$this->raiseEvent('TextChanged',$this,$param);
	}

	protected function renderContents($writer)
	{
		if($this->_items)
		{
			$writer->writeLine();
			foreach($this->_items as $item)
			{
				if($item->getEnabled())
				{
					if($item->getSelected())
						$writer->addAttribute('selected','selected');
					$writer->addAttribute('value',$item->getValue());
					if($item->getHasAttributes())
					{
						foreach($item->getAttributes() as $name=>$value)
							$writer->addAttribute($name,$value);
					}
					$writer->renderBeginTag('option');
					$writer->write(THttpUtility::htmlEncode($item->getText()));
					$writer->renderEndTag();
					$writer->writeLine();
				}
			}
		}
	}
}

class TListItemCollection extends TList
{
	private $_items=null;

	public function add($item)
	{
		if(is_string($item))
			parent::add(new TListItem($item));
		else if($item instanceof TListItem)
			parent::add($item);
		else
			throw new TInvalidDataTypeException('listitemcollection_item_invalid');
	}

	public function insert($index,$item)
	{
		if(is_string($item))
			parent::insert($index,new TListItem($item));
		else if($item instanceof TListItem)
			parent::insert($index,$item);
		else
			throw new TInvalidDataTypeException('listitemcollection_item_invalid');
	}

	public function findIndexByValue($value,$includeDisabled=true)
	{
		$value=TPropertyValue::ensureString($value);
		$index=0;
		foreach($this as $item)
		{
			if($item->getValue()===$value && ($includeDisabled || $item->getEnabled()))
				return $index;
			$index++;
		}
		return -1;
	}

	public function findIndexByText($text,$includeDisabled=true)
	{
		$text=TPropertyValue::ensureString($text);
		$index=0;
		foreach($this as $item)
		{
			if($item->getText()===$text && ($includeDisabled || $item->getEnabled()))
				return $index;
			$index++;
		}
		return -1;
	}

	public function findItemByValue($value,$includeDisabled=true)
	{
		if(($index=$this->findIndexByValue($value,$includeDisabled))>=0)
			return $this->itemAt($index);
		else
			return null;
	}

	public function findItemByText($text,$includeDisabled=true)
	{
		if(($index=$this->findIndexByText($text,$includeDisabled))>=0)
			return $this->itemAt($index);
		else
			return null;
	}

	public function loadState($state)
	{
		$this->clear();
		if($state!==null)
		{
			foreach($state as $item)
				$this->add(new TListItem($item[0],$item[1],$item[2],$item[3]));
		}
	}

	public function saveState()
	{
		if($this->getCount()>0)
		{
			$state=array();
			foreach($this as $item)
				$state[]=array($item->getText(),$item->getValue(),$item->getEnabled(),$item->getSelected());
			return $state;
		}
		else
			return null;
	}
}

class TListItem extends TComponent
{
	private $_attributes=null;
	private $_text;
	private $_value;
	private $_enabled;
	private $_selected;

	public function __construct($text='',$value='',$enabled=true,$selected=false)
	{
		$this->setText($text);
		$this->setValue($value);
		$this->setEnabled($enabled);
		$this->setSelected($selected);
	}

	public function getEnabled()
	{
		return $this->_enabled;
	}

	public function setEnabled($value)
	{
		$this->_enabled=TPropertyValue::ensureBoolean($value);
	}

	public function getSelected()
	{
		return $this->_selected;
	}

	public function setSelected($value)
	{
		$this->_selected=TPropertyValue::ensureBoolean($value);
	}

	public function getText()
	{
		return $this->_text===''?$this->_value:$this->_text;
	}

	public function setText($value)
	{
		$this->_text=TPropertyValue::ensureString($value);
	}

	public function getValue()
	{
		return $this->_value===''?$this->_text:$this->_value;
	}

	public function setValue($value)
	{
		$this->_value=TPropertyValue::ensureString($value);
	}

	public function getAttributes()
	{
		if(!$this->_attributes)
			$this->_attributes=new TMap;
		return $this->_attributes;
	}

	public function getHasAttributes()
	{
		return $this->_attributes!==null;
	}

	public function hasAttribute($name)
	{
		return $this->_attributes?$this->_attributes->contains($name):false;
	}

	/**
	 * @return string attribute value, '' if attribute does not exist
	 */
	public function getAttribute($name)
	{
		return $this->_attributes?$this->_attributes->itemAt($name):null;
	}

	/**
	 * @param string attribute name
	 * @param string value of the attribute
	 */
	public function setAttribute($name,$value)
	{
		$this->getAttributes()->add($name,$value);
	}

	/**
	 * Removes the named attribute.
	 * @param string the name of the attribute to be removed.
	 * @return string attribute value removed, empty string if attribute does not exist.
	 */
	public function removeAttribute($name)
	{
		return $this->_attributes?$this->_attributes->remove($name):null;
	}
}

?>