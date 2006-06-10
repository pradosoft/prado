<?php
/**
 * TListControl and TListItem class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 */

/**
 * Includes the supporting classes
 */
Prado::using('System.Web.UI.WebControls.TDataBoundControl');
Prado::using('System.Collections.TAttributeCollection');
Prado::using('System.Util.TDataFieldAccessor');


/**
 * TListControl class
 *
 * TListControl is a base class for list controls, such as {@link TListBox},
 * {@link TDropDownList}, {@link TCheckBoxList}, etc.
 * It manages the items and their status in a list control.
 * It also implements how the items can be populated from template and
 * data source.
 *
 * The property {@link getItems} returns a list of the items in the control.
 * To specify or determine which item is selected, use the
 * {@link getSelectedIndex SelectedIndex} property that indicates the zero-based
 * index of the selected item in the item list. You may also use
 * {@link getSelectedItem SelectedItem} and {@link getSelectedValue SelectedValue}
 * to get the selected item and its value. For multiple selection lists
 * (such as {@link TCheckBoxList} and {@link TListBox}), property
 * {@link getSelectedIndices SelectedIndices} is useful.
 *
 * TListControl implements {@link setAutoPostBack AutoPostBack} which allows
 * a list control to postback the page if the selections of the list items are changed.
 * The {@link setCausesValidation CausesValidation} and {@link setValidationGroup ValidationGroup}
 * properties may be used to specify that validation be performed when auto postback occurs.
 *
 * There are three ways to populate the items in a list control: from template,
 * using {@link setDataSource DataSource} and using {@link setDataSourceID DataSourceID}.
 * The latter two are covered in {@link TDataBoundControl}. To specify items via
 * template, using the following template syntax:
 * <code>
 * <com:TListControl>
 *   <com:TListItem Value="xxx" Text="yyy" >
 *   <com:TListItem Value="xxx" Text="yyy" Selected="true" >
 *   <com:TListItem Value="xxx" Text="yyy" >
 * </com:TListControl>
 * </code>
 *
 * When {@link setDataSource DataSource} or {@link setDataSourceID DataSourceID}
 * is used to populate list items, the {@link setDataTextField DataTextField} and
 * {@link setDataValueField DataValueField} properties are used to specify which
 * columns of the data will be used to populate the text and value of the items.
 * For example, if a data source is as follows,
 * <code>
 * $dataSource=array(
 *    array('name'=>'John', 'age'=>31),
 *    array('name'=>'Cary', 'age'=>28),
 *    array('name'=>'Rose', 'age'=>35),
 * );
 * </code>
 * setting {@link setDataTextField DataTextField} and {@link setDataValueField DataValueField}
 * to 'name' and 'age' will make the first item's text be 'John', value be 31,
 * the second item's text be 'Cary', value be 28, and so on.
 * The {@link setDataTextFormatString DataTextFormatString} property may be further
 * used to format how the item should be displayed. See {@link formatDataValue()}
 * for an explanation of the format string.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
abstract class TListControl extends TDataBoundControl
{
	/**
	 * @var TListItemCollection item list
	 */
	private $_items=null;
	/**
	 * @var boolean whether items are restored from viewstate
	 */
	private $_stateLoaded=false;
	/**
	 * @var mixed the following selection variables are used
	 * to keep selections when Items are not available
	 */
	private $_cachedSelectedIndex=-1;
	private $_cachedSelectedValue=null;

	/**
	 * @return string tag name of the list control
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
		if($this->getEnabled(true) && $this->getAutoPostBack() && $page->getClientSupportsJavaScript())
		{
			$writer->addAttribute('id',$this->getClientID());
			$this->getPage()->getClientScript()->registerPostBackControl($this->getClientClassName(),$this->getPostBackOptions());
		}
		if(!$this->getEnabled(true) && $this->getEnabled())
			$writer->addAttribute('disabled','disabled');
		parent::addAttributesToRender($writer);
	}

	/**
	 * Gets the name of the javascript class responsible for performing postback for this control.
	 * This method overrides the parent implementation.
	 * @return string the javascript class name
	 */
	abstract protected function getClientClassName();

	/**
	 * @return array postback options for JS postback code
	 */
	protected function getPostBackOptions()
	{
		$options['ID'] = $this->getClientID();
		$options['CausesValidation'] = $this->getCausesValidation();
		$options['ValidationGroup'] = $this->getValidationGroup();
		$options['EventTarget'] = $this->getUniqueID();
		return $options;
	}

	/**
	 * Adds object parsed from template to the control.
	 * This method adds only {@link TListItem} objects into the {@link getItems Items} collection.
	 * All other objects are ignored.
	 * @param mixed object parsed from template
	 */
	public function addParsedObject($object)
	{
		// Do not add items from template if items are loaded from viewstate
		if(!$this->_stateLoaded && ($object instanceof TListItem))
		{
			$index=$this->getItems()->add($object);
			if(($this->_cachedSelectedValue!==null && $this->_cachedSelectedValue===$object->getValue()) || ($this->_cachedSelectedIndex===$index))
			{
				$object->setSelected(true);
				$this->_cachedSelectedValue=null;
				$this->_cachedSelectedIndex=-1;
			}
		}
	}

	/**
	 * Performs databinding to populate list items from data source.
	 * This method is invoked by dataBind().
	 * You may override this function to provide your own way of data population.
	 * @param Traversable the data
	 */
	protected function performDataBinding($data)
	{
		$items=$this->getItems();
		if(!$this->getAppendDataBoundItems())
			$items->clear();
		$textField=$this->getDataTextField();
		if($textField==='')
			$textField=0;
		$valueField=$this->getDataValueField();
		if($valueField==='')
			$valueField=1;
		$textFormat=$this->getDataTextFormatString();
		foreach($data as $key=>$object)
		{
			$item=$items->createListItem();
			if(is_array($object) || is_object($object))
			{
				$text=TDataFieldAccessor::getDataFieldValue($object,$textField);
				$value=TDataFieldAccessor::getDataFieldValue($object,$valueField);
				$item->setValue($value);
			}
			else
			{
				$text=$object;
				$item->setValue("$key");
			}
			$item->setText($this->formatDataValue($textFormat,$text));
		}
		// SelectedValue or SelectedIndex may be set before databinding
		// so we make them be effective now
		if($this->_cachedSelectedValue!==null)
		{
			$index=$items->findIndexByValue($this->_cachedSelectedValue);
			if($index===-1 || ($this->_cachedSelectedIndex!==-1 && $this->_cachedSelectedIndex!==$index))
				throw new TInvalidDataValueException('listcontrol_selection_invalid',get_class($this));
			$this->setSelectedIndex($index);
			$this->_cachedSelectedValue=null;
			$this->_cachedSelectedIndex=-1;
		}
		else if($this->_cachedSelectedIndex!==-1)
		{
			$this->setSelectedIndex($this->_cachedSelectedIndex);
			$this->_cachedSelectedIndex=-1;
		}
	}

	/**
	 * Creates a collection object to hold list items.
	 * This method may be overriden to create a customized collection.
	 * @return TListItemCollection the collection object
	 */
	protected function createListItemCollection()
	{
		return new TListItemCollection;
	}

	/**
	 * Saves items into viewstate.
	 * This method is invoked right before control state is to be saved.
	 */
	public function saveState()
	{
		parent::saveState();
		if($this->_items)
			$this->setViewState('Items',$this->_items->saveState(),null);
		else
			$this->clearViewState('Items');
	}

	/**
	 * Loads items from viewstate.
	 * This method is invoked right after control state is loaded.
	 */
	public function loadState()
	{
		parent::loadState();
		$this->_stateLoaded=true;
		if(!$this->getIsDataBound())
		{
			$this->_items=$this->createListItemCollection();
			$this->_items->loadState($this->getViewState('Items',null));
		}
		$this->clearViewState('Items');
	}

	/**
	 * @return boolean whether this is a multiselect control. Defaults to false.
	 */
	protected function getIsMultiSelect()
	{
		return false;
	}

	/**
	 * @return boolean whether performing databind should append items or clear the existing ones. Defaults to false.
	 */
	public function getAppendDataBoundItems()
	{
		return $this->getViewState('AppendDataBoundItems',false);
	}

	/**
	 * @param boolean whether performing databind should append items or clear the existing ones.
	 */
	public function setAppendDataBoundItems($value)
	{
		$this->setViewState('AppendDataBoundItems',TPropertyValue::ensureBoolean($value),false);
	}

	/**
	 * @return boolean a value indicating whether an automatic postback to the server
     * will occur whenever the user makes change to the list control and then tabs out of it.
     * Defaults to false.
	 */
	public function getAutoPostBack()
	{
		return $this->getViewState('AutoPostBack',false);
	}

	/**
	 * Sets the value indicating if postback automatically.
	 * An automatic postback to the server will occur whenever the user
	 * makes change to the list control and then tabs out of it.
	 * @param boolean the value indicating if postback automatically
	 */
	public function setAutoPostBack($value)
	{
		$this->setViewState('AutoPostBack',TPropertyValue::ensureBoolean($value),false);
	}

	/**
	 * @return boolean whether postback event trigger by this list control will cause input validation, default is true.
	 */
	public function getCausesValidation()
	{
		return $this->getViewState('CausesValidation',true);
	}

	/**
	 * @param boolean whether postback event trigger by this list control will cause input validation.
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
	 * Sets data text format string.
	 * The format string is used in {@link TDataValueFormatter::format()} to format the Text property value
	 * of each item in the list control.
	 * @param string the formatting string used to control how data bound to the list control is displayed.
	 * @see TDataValueFormatter::format()
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

	/**
	 * @return integer the number of items in the list control
	 */
	public function getItemCount()
	{
		return $this->_items?$this->_items->getCount():0;
	}

	/**
	 * @return boolean whether the list control contains any items.
	 */
	public function getHasItems()
	{
		return ($this->_items && $this->_items->getCount()>0);
	}

	/**
	 * @return TListItemCollection the item collection
	 */
	public function getItems()
	{
		if(!$this->_items)
			$this->_items=$this->createListItemCollection();
		return $this->_items;
	}

	/**
	 * @return integer the index (zero-based) of the item being selected, -1 if no item is selected.
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
	 * @param integer the index (zero-based) of the item to be selected
	 */
	public function setSelectedIndex($index)
	{
		if(($index=TPropertyValue::ensureInteger($index))<0)
			$index=-1;
		if($this->_items)
		{
			$this->clearSelection();
			if($index>=0 && $index<$this->_items->getCount())
				$this->_items->itemAt($index)->setSelected(true);
			else if($index!==-1)
				throw new TInvalidDataValueException('listcontrol_selectedindex_invalid',get_class($this),$index);
		}
		$this->_cachedSelectedIndex=$index;
	}

	/**
	 * @return array list of index of items that are selected
	 */
	public function getSelectedIndices()
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

	/**
	 * @param array list of index of items to be selected
	 */
	public function setSelectedIndices($indices)
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
	 * @return TListItem|null the selected item with the lowest cardinal index, null if no item is selected.
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
	 * Existing selections will be cleared if the item value is found in the item collection.
	 * Note, if the value is null, existing selections will also be cleared.
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
	    	else
	    		throw new TInvalidDataValueException('listcontrol_selectedvalue_invalid',get_class($this),$value);
    	}
    	$this->_cachedSelectedValue=$value;
    }


	/**
	 * @return array list of the selected item values (strings)
	 */
	public function getSelectedValues()
	{
		$values=array();
		if($this->_items)
		{
			foreach($this->_items as $item)
			{
				if($item->getSelected())
					$values[]=$item->getValue();
			}
		}
		return $values;
	}

	/**
	 * @param array list of the selected item values
	 */
	public function setSelectedValues($values)
	{
		if($this->_items)
		{
			$this->clearSelection();
			$lookup=array();
			foreach($this->_items as $item)
				$lookup[$item->getValue()]=$item;
			foreach($values as $value)
			{
				if(isset($lookup["$value"]))
					$lookup["$value"]->setSelected(true);
		    	else
		    		throw new TInvalidDataValueException('listcontrol_selectedvalue_invalid',get_class($this),$value);
			}
		}
	}

    /**
     * @return string selected value
     */
    public function getText()
    {
	    return $this->getSelectedValue();
    }

    /**
     * @param string value to be selected
     */
    public function setText($value)
    {
	    $this->setSelectedValue($value);
    }

    /**
     * Clears all existing selections.
     */
    public function clearSelection()
    {
	    if($this->_items)
	    {
		    foreach($this->_items as $item)
		    	$item->setSelected(false);
	    }
    }

	/**
	 * @return string the group of validators which the list control causes validation upon postback
	 */
	public function getValidationGroup()
	{
		return $this->getViewState('ValidationGroup','');
	}

	/**
	 * @param string the group of validators which the list control causes validation upon postback
	 */
	public function setValidationGroup($value)
	{
		$this->setViewState('ValidationGroup',$value,'');
	}

	/**
	 * Raises OnSelectedIndexChanged event when selection is changed.
	 * This method is invoked when the list control has its selection changed
	 * by end-users.
	 * @param TEventParameter event parameter
	 */
	public function onSelectedIndexChanged($param)
	{
		$this->raiseEvent('OnSelectedIndexChanged',$this,$param);
		$this->onTextChanged($param);
	}

	/**
	 * Raises OnTextChanged event when selection is changed.
	 * This method is invoked when the list control has its selection changed
	 * by end-users.
	 * @param TEventParameter event parameter
	 */
	public function onTextChanged($param)
	{
		$this->raiseEvent('OnTextChanged',$this,$param);
	}

	/**
	 * Renders body content of the list control.
	 * This method renders items contained in the list control as the body content.
	 * @param THtmlWriter writer
	 */
	public function renderContents($writer)
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

	/**
	 * Formats the text value according to a format string.
	 * If the format string is empty, the original value is converted into
	 * a string and returned.
	 * If the format string starts with '#', the string is treated as a PHP expression
	 * within which the token '{0}' is translated with the data value to be formated.
	 * Otherwise, the format string and the data value are passed
	 * as the first and second parameters in {@link sprintf}.
	 * @param string format string
	 * @param mixed the data to be formatted
	 * @return string the formatted result
	 */
	protected function formatDataValue($formatString,$value)
	{
		if($formatString==='')
			return TPropertyValue::ensureString($value);
		else if($formatString[0]==='#')
		{
			$expression=strtr(substr($formatString,1),array('{0}'=>'$value'));
			try
			{
				if(eval("\$result=$expression;")===false)
					throw new Exception('');
				return $result;
			}
			catch(Exception $e)
			{
				throw new TInvalidDataValueException('listcontrol_expression_invalid',get_class($this),$expression,$e->getMessage());
			}
		}
		else
			return sprintf($formatString,$value);
	}
}

/**
 * TListItemCollection class.
 *
 * TListItemCollection maintains a list of {@link TListItem} for {@link TListControl}.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TListItemCollection extends TList
{
	/**
	 * Creates a list item object.
	 * This method may be overriden to provide a customized list item object.
	 * @param integer index where the newly created item is to be inserted at.
	 * If -1, the item will be appended to the end.
	 * @return TListItem list item object
	 */
	public function createListItem($index=-1)
	{
		$item=new TListItem;
		if($index<0)
			$this->add($item);
		else
			$this->insertAt($index,$item);
		return $item;
	}

	/**
	 * Inserts an item into the collection.
	 * @param integer the location where the item will be inserted.
	 * The current item at the place and the following ones will be moved backward.
	 * @param TListItem the item to be inserted.
	 * @throws TInvalidDataTypeException if the item being inserted is neither a string nor TListItem
	 */
	public function insertAt($index,$item)
	{
		if($item instanceof TListItem)
			parent::insertAt($index,$item);
		else if(is_string($item))
		{
			$item=$this->createListItem($index);
			$item->setText($item);
		}
		else
			throw new TInvalidDataTypeException('listitemcollection_item_invalid',get_class($this));
	}

	/**
	 * Finds the lowest cardinal index of the item whose value is the one being looked for.
	 * @param string the value to be looked for
	 * @param boolean whether to look for disabled items also
	 * @return integer the index of the item found, -1 if not found.
	 */
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

	/**
	 * Finds the lowest cardinal index of the item whose text is the one being looked for.
	 * @param string the text to be looked for
	 * @param boolean whether to look for disabled items also
	 * @return integer the index of the item found, -1 if not found.
	 */
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

	/**
	 * Finds the item whose value is the one being looked for.
	 * @param string the value to be looked for
	 * @param boolean whether to look for disabled items also
	 * @return TListItem the item found, null if not found.
	 */
	public function findItemByValue($value,$includeDisabled=true)
	{
		if(($index=$this->findIndexByValue($value,$includeDisabled))>=0)
			return $this->itemAt($index);
		else
			return null;
	}

	/**
	 * Finds the item whose text is the one being looked for.
	 * @param string the text to be looked for
	 * @param boolean whether to look for disabled items also
	 * @return TListItem the item found, null if not found.
	 */
	public function findItemByText($text,$includeDisabled=true)
	{
		if(($index=$this->findIndexByText($text,$includeDisabled))>=0)
			return $this->itemAt($index);
		else
			return null;
	}

	/**
	 * Loads state into every item in the collection.
	 * This method should only be used by framework and control developers.
	 * @param array|null state to be loaded.
	 */
	public function loadState($state)
	{
		$this->clear();
		if($state!==null)
			$this->copyFrom($state);
	}

	/**
	 * Saves state of items.
	 * This method should only be used by framework and control developers.
	 * @return array|null the saved state
	 */
	public function saveState()
	{
		return ($this->getCount()>0) ? $this->toArray() : null;
	}
}

/**
 * TListItem class.
 *
 * TListItem represents an item in a list control. Each item has a {@link setText Text}
 * property and a {@link setValue Value} property. If either one of them is not set,
 * it will take the value of the other property.
 * An item can be {@link setSelected Selected} or {@link setEnabled Enabled},
 * and it can have additional {@link getAttributes Attributes} which may be rendered
 * if the list control supports so.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TListItem extends TComponent
{
	/**
	 * @var TMap list of custom attributes
	 */
	private $_attributes=null;
	/**
	 * @var string text of the item
	 */
	private $_text;
	/**
	 * @var string value of the item
	 */
	private $_value;
	/**
	 * @var boolean whether the item is enabled
	 */
	private $_enabled;
	/**
	 * @var boolean whether the item is selected
	 */
	private $_selected;

	/**
	 * Constructor.
	 * @param string text of the item
	 * @param string value of the item
	 * @param boolean whether the item is enabled
	 * @param boolean whether the item is selected
	 */
	public function __construct($text='',$value='',$enabled=true,$selected=false)
	{
		$this->setText($text);
		$this->setValue($value);
		$this->setEnabled($enabled);
		$this->setSelected($selected);
	}

	/**
	 * @return boolean whether the item is enabled
	 */
	public function getEnabled()
	{
		return $this->_enabled;
	}

	/**
	 * @param boolean whether the item is enabled
	 */
	public function setEnabled($value)
	{
		$this->_enabled=TPropertyValue::ensureBoolean($value);
	}

	/**
	 * @return boolean whether the item is selected
	 */
	public function getSelected()
	{
		return $this->_selected;
	}

	/**
	 * @param boolean whether the item is selected
	 */
	public function setSelected($value)
	{
		$this->_selected=TPropertyValue::ensureBoolean($value);
	}

	/**
	 * @return string text of the item
	 */
	public function getText()
	{
		return $this->_text===''?$this->_value:$this->_text;
	}

	/**
	 * @param string text of the item
	 */
	public function setText($value)
	{
		$this->_text=TPropertyValue::ensureString($value);
	}

	/**
	 * @return string value of the item
	 */
	public function getValue()
	{
		return $this->_value===''?$this->_text:$this->_value;
	}

	/**
	 * @param string value of the item
	 */
	public function setValue($value)
	{
		$this->_value=TPropertyValue::ensureString($value);
	}

	/**
	 * @return TAttributeCollection custom attributes
	 */
	public function getAttributes()
	{
		if(!$this->_attributes)
			$this->_attributes=new TAttributeCollection;
		return $this->_attributes;
	}

	/**
	 * @return boolean whether the item has any custom attribute
	 */
	public function getHasAttributes()
	{
		return $this->_attributes && $this->_attributes->getCount()>0;
	}

	/**
	 * @param string name of the attribute
	 * @return boolean whether the named attribute exists
	 */
	public function hasAttribute($name)
	{
		return $this->_attributes?$this->_attributes->contains($name):false;
	}

	/**
	 * @return string the named attribute value, null if attribute does not exist
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