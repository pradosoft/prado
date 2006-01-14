<?php
/**
 * TDataList class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 */

/**
 * Includes TBaseDataList class
 */
Prado::using('System.Web.UI.WebControls.TBaseDataList');
/**
 * Includes TRepeatInfo class
 */
Prado::using('System.Web.UI.WebControls.TRepeatInfo');

/**
 * TDataList class
 *
 * TDataList represents a data bound and updatable list control.
 *
 * The {@link setHeaderTemplate HeaderTemplate} property specifies the content
 * template that will be displayed at the beginning, while
 * {@link setFooterTemplate FooterTemplate} at the end.
 * If present, these two templates will only be rendered when the data list is
 * given non-empty data. In this case, for each data item the content defined
 * by {@link setItemTemplate ItemTemplate} will be generated and displayed once.
 * If {@link setAlternatingItemTemplate AlternatingItemTemplate} is not empty,
 * then the corresponding content will be displayed alternatively with that
 * in {@link setItemTemplate ItemTemplate}. The content in
 * {@link setSeparatorTemplate SeparatorTemplate}, if not empty, will be
 * displayed between items. All these templates are associated with styles that
 * may be applied to the corresponding generated items. For example,
 * {@link getAlternatingItemStyle AlternatingItemStyle} will be applied to
 * every alternating item in the data list.
 *
 * To change the status of a particular item, set {@link setSelectedItemIndex SelectedItemIndex}
 * or {@link setEditItemIndex EditItemIndex}. The former will change the indicated
 * item to selected mode, which will cause the item to use {@link setSelectedItemTemplate SelectedItemTemplate}
 * for presentation. The latter will change the indicated item to edit mode.
 * Note, if an item is in edit mode, then selecting this item will have no effect.
 *
 * The layout of the data items in the list is specified via
 * {@link setRepeatLayout RepeatLayout}, which can be either 'Table' (default) or 'Flow'.
 * A table layout uses HTML table cells to organize the data items while
 * a flow layout uses line breaks to organize the data items.
 * When the layout is using 'Table', {@link setCellPadding CellPadding} and
 * {@link setCellSpacing CellSpacing} can be used to adjust the cellpadding and
 * cellpadding of the table, and {@link setCaption Caption} and {@link setCaptionAlign CaptionAlign}
 * can be used to add a table caption with the specified alignment.
 *
 * The number of columns used to display the data items is specified via
 * {@link setRepeatColumns RepeatColumns} property, while the {@link setRepeatDirection RepeatDirection}
 * governs the order of the items being rendered.
 *
 * You can retrive the repeated contents by the {@link getItems Items} property.
 * The header and footer items can be accessed by {@link getHeader Header}
 * and {@link getFooter Footer} properties, respectively.
 *
 * When TDataList creates an item, it will raise an {@link onItemCreated ItemCreated}
 * so that you may customize the newly created item.
 * When databinding is performed by TDataList, for each item once it has finished
 * databinding, an {@link onItemDataBound ItemDataBound} event will be raised.
 *
 * When an item is selected by an end-user, a {@link onSelectedIndexChanged SelectedIndexChanged}
 * event will be raised. Note, the selected index may not be actually changed.
 * The event mainly informs the server side that the end-user has made a selection.
 *
 * TDataList raises an {@link onItemCommand ItemCommand} whenever a button control
 * within some TDataList item raises a <b>Command</b> event. If the command name
 * is one of the followings: 'edit', 'update', 'select', 'delete', 'cancel' (case-insensitive),
 * another event will also be raised. For example, if the command name is 'select',
 * then the new event is {@link onSelectCommand SelectCommand}.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TDataList extends TBaseDataList implements INamingContainer, IRepeatInfoUser
{
	/**
	 * Number of seconds that a cached template will expire after
	 */
	const CACHE_EXPIRY=18000;
	/**
	 * @var array in-memory cache of parsed templates
	 */
	private static $_templates=array();
	/**
	 * @var TDataListItemCollection item list
	 */
	private $_items=null;
	/**
	 * @var string Various item templates
	 */
	private $_itemTemplate='';
	private $_alternatingItemTemplate='';
	private $_selectedItemTemplate='';
	private $_editItemTemplate='';
	private $_headerTemplate='';
	private $_footerTemplate='';
	private $_separatorTemplate='';
	/**
	 * @var TDatListItem header item
	 */
	private $_header=null;
	/**
	 * @var TDatListItem footer item
	 */
	private $_footer=null;

	/**
	 * @return TDataListItemCollection item list
	 */
	public function getItems()
	{
		if(!$this->_items)
			$this->_items=new TDataListItemCollection;
		return $this->_items;
	}

	/**
	 * @return integer number of items
	 */
	public function getItemCount()
	{
		return $this->_items?$this->_items->getCount():0;
	}

	/**
	 * @return string the template for item
	 */
	public function getItemTemplate()
	{
		return $this->_itemTemplate;
	}

	/**
	 * @param string the template for item
	 */
	public function setItemTemplate($value)
	{
		$this->_itemTemplate=$value;
	}

	/**
	 * @return TTableItemStyle the style for item
	 */
	public function getItemStyle()
	{
		if(($style=$this->getViewState('ItemStyle',null))===null)
		{
			$style=new TTableItemStyle;
			$this->setViewState('ItemStyle',$style,null);
		}
		return $style;
	}

	/**
	 * @return string the template for each alternating item
	 */
	public function getAlternatingItemTemplate()
	{
		return $this->_alternatingItemTemplate;
	}

	/**
	 * @param string the template for each alternating item
	 */
	public function setAlternatingItemTemplate($value)
	{
		$this->_alternatingItemTemplate=$value;
	}

	/**
	 * @return TTableItemStyle the style for each alternating item
	 */
	public function getAlternatingItemStyle()
	{
		if(($style=$this->getViewState('AlternatingItemStyle',null))===null)
		{
			$style=new TTableItemStyle;
			$this->setViewState('AlternatingItemStyle',$style,null);
		}
		return $style;
	}

	/**
	 * @return TTableItemStyle the selected item template string
	 */
	public function getSelectedItemTemplate()
	{
		return $this->_selectedItemTemplate;
	}

	/**
	 * @param string the selected item template
	 */
	public function setSelectedItemTemplate($value)
	{
		$this->_selectedItemTemplate=$value;
	}

	/**
	 * @return TTableItemStyle the style for selected item
	 */
	public function getSelectedItemStyle()
	{
		if(($style=$this->getViewState('SelectedItemStyle',null))===null)
		{
			$style=new TTableItemStyle;
			$this->setViewState('SelectedItemStyle',$style,null);
		}
		return $style;
	}

	/**
	 * @return string the edit item template string
	 */
	public function getEditItemTemplate()
	{
		return $this->_editItemTemplate;
	}

	/**
	 * @param string the edit item template
	 */
	public function setEditItemTemplate($value)
	{
		$this->_editItemTemplate=$value;
	}

	/**
	 * @return TTableItemStyle the style for edit item
	 */
	public function getEditItemStyle()
	{
		if(($style=$this->getViewState('EditItemStyle',null))===null)
		{
			$style=new TTableItemStyle;
			$this->setViewState('EditItemStyle',$style,null);
		}
		return $style;
	}

	/**
	 * @return string the header template string
	 */
	public function getHeaderTemplate()
	{
		return $this->_headerTemplate;
	}

	/**
	 * @param string the header template
	 */
	public function setHeaderTemplate($value)
	{
		$this->_headerTemplate=$value;
	}

	/**
	 * @return TTableItemStyle the style for header
	 */
	public function getHeaderStyle()
	{
		if(($style=$this->getViewState('HeaderStyle',null))===null)
		{
			$style=new TTableItemStyle;
			$this->setViewState('HeaderStyle',$style,null);
		}
		return $style;
	}

	/**
	 * @return TDataListItem the header item
	 */
	public function getHeader()
	{
		return $this->_header;
	}

	/**
	 * @return string the footer template string
	 */
	public function getFooterTemplate()
	{
		return $this->_footerTemplate;
	}

	/**
	 * @param string the footer template
	 */
	public function setFooterTemplate($value)
	{
		$this->_footerTemplate=$value;
	}

	/**
	 * @return TTableItemStyle the style for footer
	 */
	public function getFooterStyle()
	{
		if(($style=$this->getViewState('FooterStyle',null))===null)
		{
			$style=new TTableItemStyle;
			$this->setViewState('FooterStyle',$style,null);
		}
		return $style;
	}

	/**
	 * @return TDataListItem the footer item
	 */
	public function getFooter()
	{
		return $this->_footer;
	}

	/**
	 * @return TTableItemStyle the separator template string
	 */
	public function getSeparatorTemplate()
	{
		return $this->_separatorTemplate;
	}

	/**
	 * @param string the separator template
	 */
	public function setSeparatorTemplate($value)
	{
		$this->_separatorTemplate=$value;
	}

	/**
	 * @return TTableItemStyle the style for separator
	 */
	public function getSeparatorStyle()
	{
		if(($style=$this->getViewState('SeparatorStyle',null))===null)
		{
			$style=new TTableItemStyle;
			$this->setViewState('SeparatorStyle',$style,null);
		}
		return $style;
	}

	/**
	 * @return integer the zero-based index of the selected item in {@link getItems Items}.
	 * A value -1 means no item selected.
	 */
	public function getSelectedItemIndex()
	{
		return $this->getViewState('SelectedItemIndex',-1);
	}

	/**
	 * Selects an item by its index in {@link getItems Items}.
	 * Previously selected item will be un-selected.
	 * If the item to be selected is already in edit mode, it will remain in edit mode.
	 * If the index is less than 0, any existing selection will be cleared up.
	 * @param integer the selected item index
	 */
	public function setSelectedItemIndex($value)
	{
		if(($value=TPropertyValue::ensureInteger($value))<0)
			$value=-1;
		if(($current=$this->getSelectedItemIndex())!==$value)
		{
			$this->setViewState('SelectedItemIndex',$value,-1);
			$items=$this->getItems();
			$itemCount=$items->getCount();
			if($current>=0 && $current<$itemCount)
			{
				$item=$items->itemAt($current);
				if($item->getItemType()!=='EditItem')
					$item->setItemType($current%2?'AlternatingItem':'Item');
			}
			if($value>=0 && $value<$itemCount)
			{
				$item=$items->itemAt($value);
				if($item->getItemType()!=='EditItem')
					$item->setItemType('SelectedItem');
			}
		}
	}

	/**
	 * @return TDataListItem the selected item, null if no item is selected.
	 */
	public function getSelectedItem()
	{
		$index=$this->getSelectedItemIndex();
		$items=$this->getItems();
		if($index>=0 && $index<$items->getCount())
			return $items->itemAt($index);
		else
			return null;
	}

	/**
	 * @return integer the zero-based index of the edit item in {@link getItems Items}.
	 * A value -1 means no item is in edit mode.
	 */
	public function getEditItemIndex()
	{
		return $this->getViewState('EditItemIndex',-1);
	}

	/**
	 * Edits an item by its index in {@link getItems Items}.
	 * Previously editting item will change to normal item state.
	 * If the index is less than 0, any existing edit item will be cleared up.
	 * @param integer the edit item index
	 */
	public function setEditItemIndex($value)
	{
		if(($value=TPropertyValue::ensureInteger($value))<0)
			$value=-1;
		if(($current=$this->getEditItemIndex())!==$value)
		{
			$this->setViewState('EditItemIndex',$value,-1);
			$items=$this->getItems();
			$itemCount=$items->getCount();
			if($current>=0 && $current<$itemCount)
				$items->itemAt($current)->setItemType($current%2?'AlternatingItem':'Item');
			if($value>=0 && $value<$itemCount)
				$items->itemAt($value)->setItemType('EditItem');
		}
	}

	/**
	 * @return TDataListItem the edit item
	 */
	public function getEditItem()
	{
		$index=$this->getEditItemIndex();
		$items=$this->getItems();
		if($index>=0 && $index<$items->getCount())
			return $items->itemAt($index);
		else
			return null;
	}

	/**
	 * @return boolean whether the header should be shown. Defaults to true.
	 */
	public function getShowHeader()
	{
		return $this->getViewState('ShowHeader',true);
	}

	/**
	 * @param boolean whether to show header
	 */
	public function setShowHeader($value)
	{
		$this->setViewState('ShowHeader',TPropertyValue::ensureBoolean($value),true);
	}

	/**
	 * @return boolean whether the footer should be shown. Defaults to true.
	 */
	public function getShowFooter()
	{
		return $this->getViewState('ShowFooter',true);
	}

	/**
	 * @param boolean whether to show footer
	 */
	public function setShowFooter($value)
	{
		$this->setViewState('ShowFooter',TPropertyValue::ensureBoolean($value),true);
	}

	/**
	 * @return TRepeatInfo repeat information (primarily used by control developers)
	 */
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
	 * @return string caption of the table layout
	 */
	public function getCaption()
	{
		return $this->getRepeatInfo()->getCaption();
	}

	/**
	 * @param string caption of the table layout
	 */
	public function setCaption($value)
	{
		$this->getRepeatInfo()->setCaption($value);
	}

	/**
	 * @return string alignment of the caption of the table layout. Defaults to 'NotSet'.
	 */
	public function getCaptionAlign()
	{
		return $this->getRepeatInfo()->getCaptionAlign();
	}

	/**
	 * @return string alignment of the caption of the table layout.
	 * Valid values include 'NotSet','Top','Bottom','Left','Right'.
	 */
	public function setCaptionAlign($value)
	{
		$this->getRepeatInfo()->setCaptionAlign($value);
	}

	/**
	 * @return integer the number of columns that the list should be displayed with. Defaults to 0 meaning not set.
	 */
	public function getRepeatColumns()
	{
		return $this->getRepeatInfo()->getRepeatColumns();
	}

	/**
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
	 * @param string the direction (Vertical, Horizontal) of traversing the list
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
	 * @param string how the list should be displayed, using table or using line breaks (Table, Flow)
	 */
	public function setRepeatLayout($value)
	{
		$this->getRepeatInfo()->setRepeatLayout($value);
	}

	/**
	 * Handles <b>BubbleEvent</b>.
	 * This method overrides parent's implementation to handle
	 * {@link onItemCommand ItemCommand} event which is bubbled from
	 * {@link TDataListItem} child controls.
	 * If the event parameter is {@link TDataListCommandEventParameter} and
	 * the command name is a recognized one, which includes 'select', 'edit',
	 * 'delete', 'update', and 'cancel' (case-insensitive), then a
	 * corresponding command event is also raised (such as {@link onEditCommand EditCommand}).
	 * This method should only be used by control developers.
	 * @param TControl the sender of the event
	 * @param TEventParameter event parameter
	 * @return boolean whether the event bubbling should stop here.
	 */
	protected function onBubbleEvent($sender,$param)
	{
		if($param instanceof TDataListCommandEventParameter)
		{
			$this->onItemCommand($param);
			$command=$param->getCommandName();
			if(strcasecmp($command,'select')===0)
			{
				$this->setSelectedIndex($param->getItem()->getItemIndex());
				$this->onSelectedIndexChanged(null);
				return true;
			}
			else if(strcasecmp($command,'edit')===0)
			{
				$this->onEditCommand($param);
				return true;
			}
			else if(strcasecmp($command,'delete')===0)
			{
				$this->onDeleteCommand($param);
				return true;
			}
			else if(strcasecmp($command,'update')===0)
			{
				$this->onUpdateCommand($param);
				return true;
			}
			else if(strcasecmp($command,'cancel')===0)
			{
				$this->onCancelCommand($param);
				return true;
			}
		}
		return false;
	}


	/**
	 * Raises <b>ItemCreated</b> event.
	 * This method is invoked after a data list item is created and instantiated with
	 * template, but before added to the page hierarchy.
	 * The {@link TDataListItem} control responsible for the event
	 * can be determined from the event parameter.
	 * If you override this method, be sure to call parent's implementation
	 * so that event handlers have chance to respond to the event.
	 * @param TDataListItemEventParameter event parameter
	 */
	public function onItemCreated($param)
	{
		$this->raiseEvent('ItemCreated',$this,$param);
	}

	/**
	 * Raises <b>ItemDataBound</b> event.
	 * This method is invoked right after an item is data bound.
	 * The {@link TDataListItem} control responsible for the event
	 * can be determined from the event parameter.
	 * If you override this method, be sure to call parent's implementation
	 * so that event handlers have chance to respond to the event.
	 * @param TDataListItemEventParameter event parameter
	 */
	public function onItemDataBound($param)
	{
		$this->raiseEvent('ItemDataBound',$this,$param);
	}

	/**
	 * Raises <b>ItemCommand</b> event.
	 * This method is invoked when a child control of the data list
	 * raises an <b>Command</b> event.
	 * @param TDataListCommandEventParameter event parameter
	 */
	protected function onItemCommand($param)
	{
		$this->raiseEvent('ItemCommand',$this,$param);
	}

	/**
	 * Raises <b>SelectCommand</b> event.
	 * This method is invoked when a child control of the data list
	 * raises an <b>Command</b> event and the command name is 'select' (case-insensitive).
	 * @param TDataListCommandEventParameter event parameter
	 */
	protected function onSelectCommand($param)
	{
		$this->raiseEvent('SelectCommand',$this,$param);
	}

	/**
	 * Raises <b>EditCommand</b> event.
	 * This method is invoked when a child control of the data list
	 * raises an <b>Command</b> event and the command name is 'edit' (case-insensitive).
	 * @param TDataListCommandEventParameter event parameter
	 */
	protected function onEditCommand($param)
	{
		$this->raiseEvent('EditCommand',$this,$param);
	}

	/**
	 * Raises <b>DeleteCommand</b> event.
	 * This method is invoked when a child control of the data list
	 * raises an <b>Command</b> event and the command name is 'delete' (case-insensitive).
	 * @param TDataListCommandEventParameter event parameter
	 */
	protected function onDeleteCommand($param)
	{
		$this->raiseEvent('DeleteCommand',$this,$param);
	}

	/**
	 * Raises <b>UpdateCommand</b> event.
	 * This method is invoked when a child control of the data list
	 * raises an <b>Command</b> event and the command name is 'update' (case-insensitive).
	 * @param TDataListCommandEventParameter event parameter
	 */
	protected function onUpdateCommand($param)
	{
		$this->raiseEvent('UpdateCommand',$this,$param);
	}

	/**
	 * Raises <b>CancelCommand</b> event.
	 * This method is invoked when a child control of the data list
	 * raises an <b>Command</b> event and the command name is 'cancel' (case-insensitive).
	 * @param TDataListCommandEventParameter event parameter
	 */
	protected function onCancelCommand($param)
	{
		$this->raiseEvent('CancelCommand',$this,$param);
	}

	/**
	 * Returns a value indicating whether this control contains header item.
	 * This method is required by {@link IRepeatInfoUser} interface.
	 * @return boolean always false.
	 */
	public function getHasHeader()
	{
		return ($this->getShowHeader() && $this->_headerTemplate!=='');
	}

	/**
	 * Returns a value indicating whether this control contains footer item.
	 * This method is required by {@link IRepeatInfoUser} interface.
	 * @return boolean always false.
	 */
	public function getHasFooter()
	{
		return ($this->getShowFooter() && $this->_footerTemplate!=='');
	}

	/**
	 * Returns a value indicating whether this control contains separator items.
	 * This method is required by {@link IRepeatInfoUser} interface.
	 * @return boolean always false.
	 */
	public function getHasSeparators()
	{
		return $this->_separatorTemplate!=='';
	}

	/**
	 * Returns a style used for rendering items.
	 * This method is required by {@link IRepeatInfoUser} interface.
	 * @param string item type (Header,Footer,Item,AlternatingItem,SelectedItem,EditItem,Separator,Pager)
	 * @param integer index of the item being rendered
	 * @return TStyle item style
	 */
	public function generateItemStyle($itemType,$index)
	{
		if(($item=$this->getItem($itemType,$index))!==null && $item->getHasStyle())
			return $item->getStyle();
		else
			return null;
	}

	/**
	 * Renders an item in the list.
	 * This method is required by {@link IRepeatInfoUser} interface.
	 * @param THtmlWriter writer for rendering purpose
	 * @param TRepeatInfo repeat information
	 * @param string item type (Header,Footer,Item,AlternatingItem,SelectedItem,EditItem,Separator,Pager)
	 * @param integer zero-based index of the item in the item list
	 */
	public function renderItem($writer,$repeatInfo,$itemType,$index)
	{
		$item=$this->getItem($itemType,$index);
		if($repeatInfo->getRepeatLayout()==='Table')
			$item->renderContents($writer);
		else
			$item->renderControl($writer);
	}

	/**
	 * @param string item type
	 * @param integer item index
	 * @return TDataListItem data list item with the specified item type and index
	 */
	private function getItem($itemType,$index)
	{
		switch($itemType)
		{
			case 'Header': return $this->getControls()->itemAt(0);
			case 'Footer': return $this->getControls()->itemAt($this->getControls()->getCount()-1);
			case 'Item':
			case 'AlternatingItem':
			case 'SelectedItem':
			case 'EditItem':
				return $this->getItems()->itemAt($index);
			case 'Separator':
				$i=$index+$index+1;
				if($this->_headerTemplate!=='')
					$i++;
				return $this->getControls->itemAt($i);
		}
		return null;
	}

	/**
	 * Creates a data list item and does databinding if needed.
	 * This method invokes {@link createItem} to create a new data list item.
	 * @param integer zero-based item index.
	 * @param string item type, may be 'Header', 'Footer', 'Item', 'Separator', 'AlternatingItem', 'SelectedItem', 'EditItem'.
	 * @param boolean whether to do databinding for the item
	 * @param mixed data to be associated with the item
	 * @return TDataListItem the created item
	 */
	private function createItemInternal($itemIndex,$itemType,$dataBind,$dataItem)
	{
		$item=$this->createItem($itemIndex,$itemType);
		$this->initializeItem($item);
		$param=new TDataListItemEventParameter($item);
		if($dataBind)
		{
			$item->setDataItem($dataItem);
			$this->onItemCreated($param);
			$this->getControls()->add($item);
			$item->dataBind();
			$this->onItemDataBound($param);
			$item->setDataItem(null);
		}
		else
		{
			$this->onItemCreated($param);
			$this->getControls()->add($item);
		}
		return $item;
	}

	/**
	 * Creates a DataList item instance based on the item type and index.
	 * @param integer zero-based item index
	 * @param string item type, may be 'Header', 'Footer', 'Item', 'Separator', 'AlternatingItem', 'SelectedItem', 'EditItem'.
	 * @return TDataListItem created data list item
	 */
	protected function createItem($itemIndex,$itemType)
	{
		return new TDataListItem($itemIndex,$itemType);
	}

	/**
	 * Initializes a data list item.
	 * The item is added as a child of the data list and the corresponding
	 * template is instantiated within the item.
	 * @param TDataListItem item to be initialized
	 */
	protected function initializeItem($item)
	{
		$tplContent='';
		$style=null;
		switch($item->getItemType())
		{
			case 'Header':
				$tplContent=$this->_headerTemplate;
				$style=$this->getViewState('HeaderStyle',null);
				break;
			case 'Footer':
				$tplContent=$this->_footerTemplate;
				$style=$this->getViewState('FooterStyle',null);
				break;
			case 'Item':
				$tplContent=$this->_itemTemplate;
				$style=$this->getViewState('ItemStyle',null);
				break;
			case 'AlternatingItem':
				if(($tplContent=$this->_alternatingItemTemplate)==='')
					$tplContent=$this->_itemTemplate;
				if(($style=$this->getViewState('AlternatingItemStyle',null))===null)
					$style=$this->getViewState('ItemStyle',null);
				break;
			case 'Separator':
				$tplContent=$this->_separatorTemplate;
				$style=$this->getViewState('SeparatorStyle',null);
				break;
			case 'SelectedItem':
				if(($tplContent=$this->_selectedItemTemplate)==='')
				{
					if(!($item->getItemIndex()%2) || ($tplContent=$this->_alternatingItemTemplate)==='')
						$tplContent=$this->_itemTemplate;
				}
				if(($style=$this->getViewState('SelectedItemStyle',null))===null)
				{
					if(!($item->getItemIndex()%2) || ($style=$this->getViewState('AlternatingItemStyle',null))===null)
						$style=$this->getViewState('ItemStyle',null);
				}
				break;
			case 'EditItem':
				if(($tplContent=$this->_editItemTemplate)==='')
				{
					if($item->getItemIndex()!==$this->getSelectedItemIndex() || ($tplContent=$this->_selectedItemTemplate)==='')
						if(!($item->getItemIndex()%2) || ($tplContent=$this->_alternatingItemTemplate)==='')
							$tplContent=$this->_itemTemplate;
				}
				if(($style=$this->getViewState('EditItemStyle',null))===null)
				{
					if($item->getItemIndex()!==$this->getSelectedItemIndex() || ($style=$this->getViewState('SelectedItemStyle',null))===null)
						if(!($item->getItemIndex()%2) || ($style=$this->getViewState('AlternatingItemStyle',null))===null)
							$style=$this->getViewState('ItemStyle',null);
				}
				break;
			default:
				break;
		}
		if($tplContent!=='')
			$this->createTemplate($tplContent)->instantiateIn($item);
		if($style!==null)
			$item->getStyle()->copyFrom($style);
	}

	/**
	 * Parses item template.
	 * This method uses caching technique to accelerate template parsing.
	 * @param string template string
	 * @return ITemplate parsed template object
	 */
	protected function createTemplate($str)
	{
		$key=md5($str);
		$contextPath=$this->getTemplateControl()->getTemplate()->getContextPath();
		if(($cache=$this->getApplication()->getCache())!==null)
		{
			if(($template=$cache->get($key))===null)
			{
				$template=new TTemplate($str,$contextPath);
				$cache->set($key,$template,self::CACHE_EXPIRY);
			}
		}
		else
		{
			if(isset(self::$_templates[$key]))
				$template=self::$_templates[$key];
			else
			{
				$template=new TTemplate($str,$contextPath);
				self::$_templates[$key]=$template;
			}
		}
		return $template;
	}

	/**
	 * Saves item count in viewstate.
	 * This method is invoked right before control state is to be saved.
	 * @param mixed event parameter
	 */
	protected function onSaveState($param)
	{
		if($this->_items)
			$this->setViewState('ItemCount',$this->_items->getCount(),0);
		else
			$this->clearViewState('ItemCount');
	}

	/**
	 * Loads item count information from viewstate.
	 * This method is invoked right after control state is loaded.
	 * @param mixed event parameter
	 */
	protected function onLoadState($param)
	{
		if(!$this->getIsDataBound())
			$this->restoreItemsFromViewState();
		$this->clearViewState('ItemCount');
	}

	/**
	 * Clears up all items in the data list.
	 */
	public function reset()
	{
		$this->getControls()->clear();
		$this->getItems()->clear();
		$this->_header=null;
		$this->_footer=null;
	}

	/**
	 * Creates data list items based on viewstate information.
	 */
	protected function restoreItemsFromViewState()
	{
		$this->reset();
		if(($itemCount=$this->getViewState('ItemCount',0))>0)
		{
			$items=$this->getItems();
			$selectedIndex=$this->getSelectedItemIndex();
			$editIndex=$this->getEditItemIndex();
			if($this->_headerTemplate!=='')
				$this->_header=$this->createItemInternal(-1,'Header',false,null);
			$hasSeparator=$this->_separatorTemplate!=='';
			for($i=0;$i<$itemCount;++$i)
			{
				if($hasSeparator && $i>0)
					$this->createItemInternal($i-1,'Separator',false,null);
				if($i===$editIndex)
					$itemType='EditItem';
				else if($i===$selectedIndex)
					$itemType='SelectedItem';
				else
					$itemType=$i%2?'AlternatingItem':'Item';
				$items->add($this->createItemInternal($i,$itemType,false,null));
			}
			if($this->_footerTemplate!=='')
				$this->_footer=$this->createItemInternal(-1,'Footer',false,null);
		}
		$this->clearChildState();
	}

	/**
	 * Performs databinding to populate data list items from data source.
	 * This method is invoked by dataBind().
	 * You may override this function to provide your own way of data population.
	 * @param Traversable the data
	 */
	protected function performDataBinding($data)
	{
		$this->reset();
		$keys=$this->getDataKeys();
		$keys->clear();
		$keyField=$this->getDataKeyField();
		$itemIndex=0;
		$items=$this->getItems();
		$hasSeparator=$this->_separatorTemplate!=='';
		$selectedIndex=$this->getSelectedItemIndex();
		$editIndex=$this->getEditItemIndex();
		foreach($data as $dataItem)
		{
			if($keyField!=='')
			{
				if(is_array($dataItem) || ($dataItem instanceof TMap))
					$keys->add($dataItem[$keyField]);
				else if(($dataItem instanceof TComponent) && $dataItem->canGetProperty($keyField))
				{
					$getter='get'.$keyField;
					$keys->add($dataItem->$getter());
				}
				else
					throw new TInvalidDataValueException('datalist_keyfield_invalid',$keyField);
			}
			if($itemIndex===0 && $this->_headerTemplate!=='')
				$this->_header=$this->createItemInternal(-1,'Header',true,null);
			if($hasSeparator && $itemIndex>0)
				$this->createItemInternal($itemIndex-1,'Separator',true,null);
			if($itemIndex===$editIndex)
				$itemType='EditItem';
			else if($itemIndex===$selectedIndex)
				$itemType='SelectedItem';
			else
				$itemType=$itemIndex%2?'AlternatingItem':'Item';
			$items->add($this->createItemInternal($itemIndex,$itemType,true,$dataItem));
			$itemIndex++;
		}
		if($itemIndex>0 && $this->_footerTemplate!=='')
			$this->_footer=$this->createItemInternal(-1,'Footer',true,null);
		$this->setViewState('ItemCount',$itemIndex,0);
	}

	/**
	 * Renders the data list control.
	 * This method overrides the parent implementation.
	 * @param THtmlWriter writer for rendering purpose.
	 */
	protected function render($writer)
	{
		if($this->getHasControls())
		{
			$repeatInfo=$this->getRepeatInfo();
			$repeatInfo->renderRepeater($writer,$this);
		}
	}
}


/**
 * TDataListItemEventParameter class
 *
 * TDataListItemEventParameter encapsulates the parameter data for
 * {@link TDataList::onItemCreated ItemCreated} event of {@link TDataList} controls.
 * The {@link getItem Item} property indicates the DataList item related with the event.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TDataListItemEventParameter extends TEventParameter
{
	/**
	 * The TDataListItem control responsible for the event.
	 * @var TDataListItem
	 */
	private $_item=null;

	/**
	 * Constructor.
	 * @param TDataListItem DataList item related with the corresponding event
	 */
	public function __construct(TDataListItem $item)
	{
		$this->_item=$item;
	}

	/**
	 * @return TDataListItem DataList item related with the corresponding event
	 */
	public function getItem()
	{
		return $this->_item;
	}
}

/**
 * TDataListCommandEventParameter class
 *
 * TDataListCommandEventParameter encapsulates the parameter data for
 * {@link TDataList::onItemCommand ItemCommand} event of {@link TDataList} controls.
 *
 * The {@link getItem Item} property indicates the DataList item related with the event.
 * The {@link getCommandSource CommandSource} refers to the control that originally
 * raises the Command event.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TDataListCommandEventParameter extends TCommandEventParameter
{
	/**
	 * @var TDataListItem the TDataListItem control responsible for the event.
	 */
	private $_item=null;
	/**
	 * @var TControl the control originally raises the <b>Command</b> event.
	 */
	private $_source=null;

	/**
	 * Constructor.
	 * @param TDataListItem DataList item responsible for the event
	 * @param TControl original event sender
	 * @param TCommandEventParameter original event parameter
	 */
	public function __construct($item,$source,TCommandEventParameter $param)
	{
		$this->_item=$item;
		$this->_source=$source;
		parent::__construct($param->getCommandName(),$param->getCommandParameter());
	}

	/**
	 * @return TDataListItem the TDataListItem control responsible for the event.
	 */
	public function getItem()
	{
		return $this->_item;
	}

	/**
	 * @return TControl the control originally raises the <b>Command</b> event.
	 */
	public function getCommandSource()
	{
		return $this->_source;
	}
}

/**
 * TDataListItem class
 *
 * A TDataListItem control represents an item in the {@link TDataList} control,
 * such as heading section, footer section, or a data item.
 * The index and data value of the item can be accessed via {@link getItemIndex ItemIndex}>
 * and {@link getDataItem DataItem} properties, respectively. The type of the item
 * is given by {@link getItemType ItemType} property.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TDataListItem extends TWebControl implements INamingContainer
{
	/**
	 * index of the data item in the Items collection of DataList
	 */
	private $_itemIndex='';
	/**
	 * type of the TDataListItem
	 * @var string
	 */
	private $_itemType='';
	/**
	 * value of the data item
	 * @var mixed
	 */
	private $_dataItem=null;

	/**
	 * Constructor.
	 * @param integer zero-based index of the item in the item collection of DataList
	 * @param string item type, can be 'Header','Footer','Item','AlternatingItem','SelectedItem','EditItem','Separator','Pager'.
	 */
	public function __construct($itemIndex,$itemType)
	{
		$this->_itemIndex=$itemIndex;
		$this->setItemType($itemType);
	}

	/**
	 * Creates a style object for the control.
	 * This method creates a {@link TTableStyle} to be used by checkbox list.
	 * @return TStyle control style to be used
	 */
	protected function createStyle()
	{
		return new TTableItemStyle;
	}

	/**
	 * @return string item type, can be 'Header','Footer','Item','AlternatingItem','SelectedItem','EditItem','Separator','Pager'
	 */
	public function getItemType()
	{
		return $this->_itemType;
	}

	/**
	 * @param mixed data to be associated with the item
	 */
	public function setItemType($value)
	{
		$this->_itemType=TPropertyValue::ensureEnum($value,'Header','Footer','Item','AlternatingItem','SelectedItem','EditItem','Separator','Pager');
	}

	/**
	 * @return integer zero-based index of the item in the item collection of DataList
	 */
	public function getItemIndex()
	{
		return $this->_itemIndex;
	}

	/**
	 * @return mixed data associated with the item
	 */
	public function getDataItem()
	{
		return $this->_dataItem;
	}

	/**
	 * @param mixed data to be associated with the item
	 */
	public function setDataItem($value)
	{
		$this->_dataItem=$value;
	}

	/**
	 * Handles <b>BubbleEvent</b>.
	 * This method overrides parent's implementation by wrapping event parameter
	 * for <b>Command</b> event with item information.
	 * @param TControl the sender of the event
	 * @param TEventParameter event parameter
	 * @return boolean whether the event bubbling should stop here.
	 */
	protected function onBubbleEvent($sender,$param)
	{
		if($param instanceof TCommandEventParameter)
		{
			$this->raiseBubbleEvent($this,new TDataListCommandEventParameter($this,$sender,$param));
			return true;
		}
		else
			return false;
	}
}


/**
 * TDataListItemCollection class.
 *
 * TDataListItemCollection represents a collection of data list items.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TDataListItemCollection extends TList
{
	/**
	 * Returns true only when the item to be added is a {@link TDataListItem}.
	 * This method is invoked before adding an item to the list.
	 * If it returns true, the item will be added to the list, otherwise not.
	 * @param mixed item to be added
	 * @return boolean whether the item can be added to the list
	 */
	protected function canAddItem($item)
	{
		return ($item instanceof TDataListItem);
	}
}

?>