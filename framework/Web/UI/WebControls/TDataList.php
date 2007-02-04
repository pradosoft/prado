<?php
/**
 * TDataList class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id$
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
 * displayed between items. Besides the above templates, there are two additional
 * templates, {@link setEditItemTemplate EditItemTemplate} and
 * {@link setSelectedItemTemplate SelectedItemTemplate}, which are used to display
 * items that are in edit and selected mode, respectively.
 *
 * All these templates are associated with styles that may be applied to
 * the corresponding generated items. For example,
 * {@link getAlternatingItemStyle AlternatingItemStyle} will be applied to
 * every alternating item in the data list.
 *
 * Item styles are applied in a hierarchical way. Style in higher hierarchy
 * will inherit from styles in lower hierarchy.
 * Starting from the lowest hierarchy, the item styles include
 * item's own style, {@link getItemStyle ItemStyle}, {@link getAlternatingItemStyle AlternatingItemStyle},
 * {@link getSelectedItemStyle SelectedItemStyle}, and {@link getEditItemStyle EditItemStyle}.
 * Therefore, if background color is set as red in {@link getItemStyle ItemStyle},
 * {@link getEditItemStyle EditItemStyle} will also have red background color
 * unless it is set to a different value explicitly.
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
 * When TDataList creates an item, it will raise an {@link onItemCreated OnItemCreated}
 * so that you may customize the newly created item.
 * When databinding is performed by TDataList, for each item once it has finished
 * databinding, an {@link onItemDataBound OnItemDataBound} event will be raised.
 *
 * When an item is selected by an end-user, a {@link onSelectedIndexChanged OnSelectedIndexChanged}
 * event will be raised. Note, the selected index may not be actually changed.
 * The event mainly informs the server side that the end-user has made a selection.
 *
 * Each datalist item has a {@link TDataListItem::getItemType type}
 * which tells the position and state of the item in the datalist. An item in the header
 * of the repeater is of type Header. A body item may be of either
 * Item, AlternatingItem, SelectedItem or EditItem, depending whether the item
 * index is odd or even, whether it is being selected or edited.
 *
 * TDataList raises an {@link onItemCommand OnItemCommand} whenever a button control
 * within some TDataList item raises a <b>OnCommand</b> event. If the command name
 * is one of the followings: 'edit', 'update', 'select', 'delete', 'cancel' (case-insensitive),
 * another event will also be raised. For example, if the command name is 'edit',
 * then the new event is {@link onEditCommand OnEditCommand}.
 *
 * Note, the data bound to the datalist are reset to null after databinding.
 * There are several ways to access the data associated with a datalist item:
 * - Access the data in {@link onItemDataBound OnItemDataBound} event
 * - Use {@link getDataKeys DataKeys} to obtain the data key associated with
 * the specified datalist item and use the key to fetch the corresponding data
 * from some persistent storage such as DB.
 * - Save the data in viewstate and get it back during postbacks.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id$
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TDataList extends TBaseDataList implements INamingContainer, IRepeatInfoUser
{
	/**
	 * Command name that TDataList understands. They are case-insensitive.
	 */
	const CMD_SELECT='Select';
	const CMD_EDIT='Edit';
	const CMD_UPDATE='Update';
	const CMD_DELETE='Delete';
	const CMD_CANCEL='Cancel';

	/**
	 * @var TDataListItemCollection item list
	 */
	private $_items=null;
	/**
	 * @var Itemplate various item templates
	 */
	private $_itemTemplate=null;
	private $_emptyTemplate=null;
	private $_alternatingItemTemplate=null;
	private $_selectedItemTemplate=null;
	private $_editItemTemplate=null;
	private $_headerTemplate=null;
	private $_footerTemplate=null;
	private $_separatorTemplate=null;
	/**
	 * @var TControl header item
	 */
	private $_header=null;
	/**
	 * @var TControl footer item
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
	 * @return string the class name for datalist items. Defaults to empty, meaning not set.
	 */
	public function getItemRenderer()
	{
		return $this->getViewState('ItemRenderer','');
	}

	/**
	 * Sets the item renderer class.
	 *
	 * If not empty, the class will be used to instantiate as datalist items.
	 * This property takes precedence over {@link getItemTemplate ItemTemplate}.
	 *
	 * @param string the renderer class name in namespace format.
	 * @see setItemTemplate
	 */
	public function setItemRenderer($value)
	{
		$this->setViewState('ItemRenderer',$value,'');
	}

	/**
	 * @return string the class name for alternative datalist items. Defaults to empty, meaning not set.
	 */
	public function getAlternatingItemRenderer()
	{
		return $this->getViewState('AlternatingItemRenderer','');
	}

	/**
	 * Sets the alternative item renderer class.
	 *
	 * If not empty, the class will be used to instantiate as alternative datalist items.
	 * This property takes precedence over {@link getAlternatingItemTemplate AlternatingItemTemplate}.
	 *
	 * @param string the renderer class name in namespace format.
	 * @see setAlternatingItemTemplate
	 */
	public function setAlternatingItemRenderer($value)
	{
		$this->setViewState('AlternatingItemRenderer',$value,'');
	}

	/**
	 * @return string the class name for the datalist item being editted. Defaults to empty, meaning not set.
	 */
	public function getEditItemRenderer()
	{
		return $this->getViewState('EditItemRenderer','');
	}

	/**
	 * Sets the renderer class for the datalist item being editted.
	 *
	 * If not empty, the class will be used to instantiate as the datalist item.
	 * This property takes precedence over {@link getEditItemTemplate EditItemTemplate}.
	 *
	 * @param string the renderer class name in namespace format.
	 * @see setEditItemTemplate
	 */
	public function setEditItemRenderer($value)
	{
		$this->setViewState('EditItemRenderer',$value,'');
	}

	/**
	 * @return string the class name for the datalist item being selected. Defaults to empty, meaning not set.
	 */
	public function getSelectedItemRenderer()
	{
		return $this->getViewState('SelectedItemRenderer','');
	}

	/**
	 * Sets the renderer class for the datalist item being selected.
	 *
	 * If not empty, the class will be used to instantiate as the datalist item.
	 * This property takes precedence over {@link getSelectedItemTemplate SelectedItemTemplate}.
	 *
	 * @param string the renderer class name in namespace format.
	 * @see setSelectedItemTemplate
	 */
	public function setSelectedItemRenderer($value)
	{
		$this->setViewState('SelectedItemRenderer',$value,'');
	}

	/**
	 * @return string the class name for datalist item separators. Defaults to empty, meaning not set.
	 */
	public function getSeparatorRenderer()
	{
		return $this->getViewState('SeparatorRenderer','');
	}

	/**
	 * Sets the datalist item separator renderer class.
	 *
	 * If not empty, the class will be used to instantiate as datalist item separators.
	 * This property takes precedence over {@link getSeparatorTemplate SeparatorTemplate}.
	 *
	 * @param string the renderer class name in namespace format.
	 * @see setSeparatorTemplate
	 */
	public function setSeparatorRenderer($value)
	{
		$this->setViewState('SeparatorRenderer',$value,'');
	}

	/**
	 * @return string the class name for datalist header item. Defaults to empty, meaning not set.
	 */
	public function getHeaderRenderer()
	{
		return $this->getViewState('HeaderRenderer','');
	}

	/**
	 * Sets the datalist header renderer class.
	 *
	 * If not empty, the class will be used to instantiate as datalist header item.
	 * This property takes precedence over {@link getHeaderTemplate HeaderTemplate}.
	 *
	 * @param string the renderer class name in namespace format.
	 * @see setHeaderTemplate
	 */
	public function setHeaderRenderer($value)
	{
		$this->setViewState('HeaderRenderer',$value,'');
	}

	/**
	 * @return string the class name for datalist footer item. Defaults to empty, meaning not set.
	 */
	public function getFooterRenderer()
	{
		return $this->getViewState('FooterRenderer','');
	}

	/**
	 * Sets the datalist footer renderer class.
	 *
	 * If not empty, the class will be used to instantiate as datalist footer item.
	 * This property takes precedence over {@link getFooterTemplate FooterTemplate}.
	 *
	 * @param string the renderer class name in namespace format.
	 * @see setFooterTemplate
	 */
	public function setFooterRenderer($value)
	{
		$this->setViewState('FooterRenderer',$value,'');
	}

	/**
	 * @return string the class name for empty datalist item. Defaults to empty, meaning not set.
	 */
	public function getEmptyRenderer()
	{
		return $this->getViewState('EmptyRenderer','');
	}

	/**
	 * Sets the datalist empty renderer class.
	 *
	 * The empty renderer is created as the child of the datalist
	 * if data bound to the datalist is empty.
	 * This property takes precedence over {@link getEmptyTemplate EmptyTemplate}.
	 *
	 * @param string the renderer class name in namespace format.
	 * @see setEmptyTemplate
	 */
	public function setEmptyRenderer($value)
	{
		$this->setViewState('EmptyRenderer',$value,'');
	}

	/**
	 * @return ITemplate the template for item
	 */
	public function getItemTemplate()
	{
		return $this->_itemTemplate;
	}

	/**
	 * @param ITemplate the template for item
	 * @throws TInvalidDataTypeException if the input is not an {@link ITemplate} or not null.
	 */
	public function setItemTemplate($value)
	{
		if($value instanceof ITemplate || $value===null)
			$this->_itemTemplate=$value;
		else
			throw new TInvalidDataTypeException('datalist_template_required','ItemTemplate');
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
	 * @return ITemplate the template for each alternating item
	 */
	public function getAlternatingItemTemplate()
	{
		return $this->_alternatingItemTemplate;
	}

	/**
	 * @param ITemplate the template for each alternating item
	 * @throws TInvalidDataTypeException if the input is not an {@link ITemplate} or not null.
	 */
	public function setAlternatingItemTemplate($value)
	{
		if($value instanceof ITemplate || $value===null)
			$this->_alternatingItemTemplate=$value;
		else
			throw new TInvalidDataTypeException('datalist_template_required','AlternatingItemType');
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
	 * @return ITemplate the selected item template
	 */
	public function getSelectedItemTemplate()
	{
		return $this->_selectedItemTemplate;
	}

	/**
	 * @param ITemplate the selected item template
	 * @throws TInvalidDataTypeException if the input is not an {@link ITemplate} or not null.
	 */
	public function setSelectedItemTemplate($value)
	{
		if($value instanceof ITemplate || $value===null)
			$this->_selectedItemTemplate=$value;
		else
			throw new TInvalidDataTypeException('datalist_template_required','SelectedItemTemplate');
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
	 * @return ITemplate the edit item template
	 */
	public function getEditItemTemplate()
	{
		return $this->_editItemTemplate;
	}

	/**
	 * @param ITemplate the edit item template
	 * @throws TInvalidDataTypeException if the input is not an {@link ITemplate} or not null.
	 */
	public function setEditItemTemplate($value)
	{
		if($value instanceof ITemplate || $value===null)
			$this->_editItemTemplate=$value;
		else
			throw new TInvalidDataTypeException('datalist_template_required','EditItemTemplate');
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
	 * @return ITemplate the header template
	 */
	public function getHeaderTemplate()
	{
		return $this->_headerTemplate;
	}

	/**
	 * @param ITemplate the header template
	 * @throws TInvalidDataTypeException if the input is not an {@link ITemplate} or not null.
	 */
	public function setHeaderTemplate($value)
	{
		if($value instanceof ITemplate || $value===null)
			$this->_headerTemplate=$value;
		else
			throw new TInvalidDataTypeException('datalist_template_required','HeaderTemplate');
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
	 * @return TControl the header item
	 */
	public function getHeader()
	{
		return $this->_header;
	}

	/**
	 * @return ITemplate the footer template
	 */
	public function getFooterTemplate()
	{
		return $this->_footerTemplate;
	}

	/**
	 * @param ITemplate the footer template
	 * @throws TInvalidDataTypeException if the input is not an {@link ITemplate} or not null.
	 */
	public function setFooterTemplate($value)
	{
		if($value instanceof ITemplate || $value===null)
			$this->_footerTemplate=$value;
		else
			throw new TInvalidDataTypeException('datalist_template_required','FooterTemplate');
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
	 * @return TControl the footer item
	 */
	public function getFooter()
	{
		return $this->_footer;
	}

	/**
	 * @return ITemplate the template applied when no data is bound to the datalist
	 */
	public function getEmptyTemplate()
	{
		return $this->_emptyTemplate;
	}

	/**
	 * @param ITemplate the template applied when no data is bound to the datalist
	 * @throws TInvalidDataTypeException if the input is not an {@link ITemplate} or not null.
	 */
	public function setEmptyTemplate($value)
	{
		if($value instanceof ITemplate || $value===null)
			$this->_emptyTemplate=$value;
		else
			throw new TInvalidDataTypeException('datalist_template_required','EmptyTemplate');
	}

	/**
	 * @return ITemplate the separator template
	 */
	public function getSeparatorTemplate()
	{
		return $this->_separatorTemplate;
	}

	/**
	 * @param ITemplate the separator template
	 * @throws TInvalidDataTypeException if the input is not an {@link ITemplate} or not null.
	 */
	public function setSeparatorTemplate($value)
	{
		if($value instanceof ITemplate || $value===null)
			$this->_separatorTemplate=$value;
		else
			throw new TInvalidDataTypeException('datalist_template_required','SeparatorTemplate');
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
				if($item->getItemType()!==TListItemType::EditItem)
					$item->setItemType($current%2?TListItemType::AlternatingItem : TListItemType::Item);
			}
			if($value>=0 && $value<$itemCount)
			{
				$item=$items->itemAt($value);
				if($item->getItemType()!==TListItemType::EditItem)
					$item->setItemType(TListItemType::SelectedItem);
			}
		}
	}

	/**
	 * @return TControl the selected item, null if no item is selected.
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
	 * @return mixed the key value of the currently selected item
	 * @throws TInvalidOperationException if {@link getDataKeyField DataKeyField} is empty.
	 */
	public function getSelectedDataKey()
	{
		if($this->getDataKeyField()==='')
			throw new TInvalidOperationException('datalist_datakeyfield_required');
		$index=$this->getSelectedItemIndex();
		$dataKeys=$this->getDataKeys();
		if($index>=0 && $index<$dataKeys->getCount())
			return $dataKeys->itemAt($index);
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
				$items->itemAt($current)->setItemType($current%2?TListItemType::AlternatingItem : TListItemType::Item);
			if($value>=0 && $value<$itemCount)
				$items->itemAt($value)->setItemType(TListItemType::EditItem);
		}
	}

	/**
	 * @return TControl the edit item
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
	 * @return TTableCaptionAlign alignment of the caption of the table layout. Defaults to TTableCaptionAlign::NotSet.
	 */
	public function getCaptionAlign()
	{
		return $this->getRepeatInfo()->getCaptionAlign();
	}

	/**
	 * @return TTableCaptionAlign alignment of the caption of the table layout.
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
	 * @return TRepeatDirection the direction of traversing the list, defaults to TRepeatDirection::Vertical
	 */
	public function getRepeatDirection()
	{
		return $this->getRepeatInfo()->getRepeatDirection();
	}

	/**
	 * @param TRepeatDirection the direction of traversing the list
	 */
	public function setRepeatDirection($value)
	{
		$this->getRepeatInfo()->setRepeatDirection($value);
	}

	/**
	 * @return TRepeatLayout how the list should be displayed, using table or using line breaks. Defaults to TRepeatLayout::Table.
	 */
	public function getRepeatLayout()
	{
		return $this->getRepeatInfo()->getRepeatLayout();
	}

	/**
	 * @param TRepeatLayout how the list should be displayed, using table or using line breaks
	 */
	public function setRepeatLayout($value)
	{
		$this->getRepeatInfo()->setRepeatLayout($value);
	}

	/**
	 * This method overrides parent's implementation to handle
	 * {@link onItemCommand OnItemCommand} event which is bubbled from
	 * datalist items and their child controls.
	 * If the event parameter is {@link TDataListCommandEventParameter} and
	 * the command name is a recognized one, which includes 'select', 'edit',
	 * 'delete', 'update', and 'cancel' (case-insensitive), then a
	 * corresponding command event is also raised (such as {@link onEditCommand OnEditCommand}).
	 * This method should only be used by control developers.
	 * @param TControl the sender of the event
	 * @param TEventParameter event parameter
	 * @return boolean whether the event bubbling should stop here.
	 */
	public function bubbleEvent($sender,$param)
	{
		if($param instanceof TDataListCommandEventParameter)
		{
			$this->onItemCommand($param);
			$command=$param->getCommandName();
			if(strcasecmp($command,self::CMD_SELECT)===0)
			{
				$this->setSelectedItemIndex($param->getItem()->getItemIndex());
				$this->onSelectedIndexChanged($param);
				return true;
			}
			else if(strcasecmp($command,self::CMD_EDIT)===0)
			{
				$this->onEditCommand($param);
				return true;
			}
			else if(strcasecmp($command,self::CMD_DELETE)===0)
			{
				$this->onDeleteCommand($param);
				return true;
			}
			else if(strcasecmp($command,self::CMD_UPDATE)===0)
			{
				$this->onUpdateCommand($param);
				return true;
			}
			else if(strcasecmp($command,self::CMD_CANCEL)===0)
			{
				$this->onCancelCommand($param);
				return true;
			}
		}
		return false;
	}


	/**
	 * Raises <b>OnItemCreated</b> event.
	 * This method is invoked after a data list item is created and instantiated with
	 * template, but before added to the page hierarchy.
	 * The datalist item control responsible for the event
	 * can be determined from the event parameter.
	 * If you override this method, be sure to call parent's implementation
	 * so that event handlers have chance to respond to the event.
	 * @param TDataListItemEventParameter event parameter
	 */
	public function onItemCreated($param)
	{
		$this->raiseEvent('OnItemCreated',$this,$param);
	}

	/**
	 * Raises <b>OnItemDataBound</b> event.
	 * This method is invoked right after an item is data bound.
	 * The datalist item control responsible for the event
	 * can be determined from the event parameter.
	 * If you override this method, be sure to call parent's implementation
	 * so that event handlers have chance to respond to the event.
	 * @param TDataListItemEventParameter event parameter
	 */
	public function onItemDataBound($param)
	{
		$this->raiseEvent('OnItemDataBound',$this,$param);
	}

	/**
	 * Raises <b>OnItemCommand</b> event.
	 * This method is invoked when a child control of the data list
	 * raises an <b>OnCommand</b> event.
	 * @param TDataListCommandEventParameter event parameter
	 */
	public function onItemCommand($param)
	{
		$this->raiseEvent('OnItemCommand',$this,$param);
	}

	/**
	 * Raises <b>OnEditCommand</b> event.
	 * This method is invoked when a child control of the data list
	 * raises an <b>OnCommand</b> event and the command name is 'edit' (case-insensitive).
	 * @param TDataListCommandEventParameter event parameter
	 */
	public function onEditCommand($param)
	{
		$this->raiseEvent('OnEditCommand',$this,$param);
	}

	/**
	 * Raises <b>OnDeleteCommand</b> event.
	 * This method is invoked when a child control of the data list
	 * raises an <b>OnCommand</b> event and the command name is 'delete' (case-insensitive).
	 * @param TDataListCommandEventParameter event parameter
	 */
	public function onDeleteCommand($param)
	{
		$this->raiseEvent('OnDeleteCommand',$this,$param);
	}

	/**
	 * Raises <b>OnUpdateCommand</b> event.
	 * This method is invoked when a child control of the data list
	 * raises an <b>OnCommand</b> event and the command name is 'update' (case-insensitive).
	 * @param TDataListCommandEventParameter event parameter
	 */
	public function onUpdateCommand($param)
	{
		$this->raiseEvent('OnUpdateCommand',$this,$param);
	}

	/**
	 * Raises <b>OnCancelCommand</b> event.
	 * This method is invoked when a child control of the data list
	 * raises an <b>OnCommand</b> event and the command name is 'cancel' (case-insensitive).
	 * @param TDataListCommandEventParameter event parameter
	 */
	public function onCancelCommand($param)
	{
		$this->raiseEvent('OnCancelCommand',$this,$param);
	}

	/**
	 * Returns a value indicating whether this control contains header item.
	 * This method is required by {@link IRepeatInfoUser} interface.
	 * @return boolean always false.
	 */
	public function getHasHeader()
	{
		return ($this->getShowHeader() && $this->_headerTemplate!==null);
	}

	/**
	 * Returns a value indicating whether this control contains footer item.
	 * This method is required by {@link IRepeatInfoUser} interface.
	 * @return boolean always false.
	 */
	public function getHasFooter()
	{
		return ($this->getShowFooter() && $this->_footerTemplate!==null);
	}

	/**
	 * Returns a value indicating whether this control contains separator items.
	 * This method is required by {@link IRepeatInfoUser} interface.
	 * @return boolean always false.
	 */
	public function getHasSeparators()
	{
		return $this->_separatorTemplate!==null;
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
		$layout=$repeatInfo->getRepeatLayout();
		if($layout==='Table' || $layout==='Raw')
			$item->renderContents($writer);
		else
			$item->renderControl($writer);
	}

	/**
	 * @param TListItemType item type
	 * @param integer item index
	 * @return TControl data list item with the specified item type and index
	 */
	private function getItem($itemType,$index)
	{
		switch($itemType)
		{
			case TListItemType::Item:
			case TListItemType::AlternatingItem:
			case TListItemType::SelectedItem:
			case TListItemType::EditItem:
				return $this->getItems()->itemAt($index);
			case TListItemType::Header:
				return $this->getControls()->itemAt(0);
			case TListItemType::Footer:
				return $this->getControls()->itemAt($this->getControls()->getCount()-1);
			case TListItemType::Separator:
				$i=$index+$index+1;
				if($this->_headerTemplate!==null)
					$i++;
				return $this->getControls()->itemAt($i);
		}
		return null;
	}

	/**
	 * Creates a datalist item.
	 * This method invokes {@link createItem} to create a new datalist item.
	 * @param integer zero-based item index.
	 * @param TListItemType item type
	 * @return TControl the created item, null if item is not created
	 */
	private function createItemInternal($itemIndex,$itemType)
	{
		if(($item=$this->createItem($itemIndex,$itemType))!==null)
		{
			$param=new TDataListItemEventParameter($item);
			$this->onItemCreated($param);
			$this->getControls()->add($item);
			return $item;
		}
		else
			return null;
	}

	/**
	 * Creates a datalist item and performs databinding.
	 * This method invokes {@link createItem} to create a new datalist item.
	 * @param integer zero-based item index.
	 * @param TListItemType item type
	 * @param mixed data to be associated with the item
	 * @return TControl the created item, null if item is not created
	 */
	private function createItemWithDataInternal($itemIndex,$itemType,$dataItem)
	{
		if(($item=$this->createItem($itemIndex,$itemType))!==null)
		{
			$param=new TDataListItemEventParameter($item);
			if($item instanceof IDataRenderer)
				$item->setData($dataItem);
			$this->onItemCreated($param);
			$this->getControls()->add($item);
			$item->dataBind();
			$this->onItemDataBound($param);
			return $item;
		}
		else
			return null;
	}

	/**
	 * Creates a datalist item instance based on the item type and index.
	 * @param integer zero-based item index
	 * @param TListItemType item type
	 * @return TControl created datalist item
	 */
	protected function createItem($itemIndex,$itemType)
	{
		$template=null;
		$classPath=null;
		switch($itemType)
		{
			case TListItemType::Item :
				$classPath=$this->getItemRenderer();
				$template=$this->_itemTemplate;
				break;
			case TListItemType::AlternatingItem :
				if(($classPath=$this->getAlternatingItemRenderer())==='')
					$classPath=$this->getItemRenderer();
				$template=$this->_alternatingItemTemplate===null ? $this->_itemTemplate : $this->_alternatingItemTemplate;
				break;
			case TListItemType::SelectedItem:
				if(($classPath=$this->getSelectedItemRenderer())==='')
				{
					if(!($itemIndex%2) || ($classPath=$this->getAlternatingItemRenderer())==='')
						$classPath=$this->getItemRenderer();
				}
				if(($template=$this->_selectedItemTemplate)===null)
				{
					if(!($itemIndex%2) || ($template=$this->_alternatingItemTemplate)===null)
						$template=$this->_itemTemplate;
				}
				break;
			case TListItemType::EditItem:
				if(($classPath=$this->getEditItemRenderer())==='')
				{
					if($itemIndex!==$this->getSelectedItemIndex() || ($classPath=$this->getSelectedItemRenderer())==='')
						if(!($itemIndex%2) || ($classPath=$this->getAlternatingItemRenderer())==='')
							$classPath=$this->getItemRenderer();
				}
				if(($template=$this->_editItemTemplate)===null)
				{
					if($itemIndex!==$this->getSelectedItemIndex() || ($template=$this->_selectedItemTemplate)===null)
						if(!($itemIndex%2) || ($template=$this->_alternatingItemTemplate)===null)
							$template=$this->_itemTemplate;
				}
				break;
			case TListItemType::Header :
				$classPath=$this->getHeaderRenderer();
				$template=$this->_headerTemplate;
				break;
			case TListItemType::Footer :
				$classPath=$this->getFooterRenderer();
				$template=$this->_footerTemplate;
				break;
			case TListItemType::Separator :
				$classPath=$this->getSeparatorRenderer();
				$template=$this->_separatorTemplate;
				break;
			default:
				throw new TInvalidDataValueException('datalist_itemtype_unknown',$itemType);
		}
		if($classPath!=='')
		{
			$item=Prado::createComponent($classPath);
			if($item instanceof IItemDataRenderer)
			{
				$item->setItemIndex($itemIndex);
				$item->setItemType($itemType);
			}
		}
		else if($template!==null)
		{
			$item=new TDataListItem;
			$item->setItemIndex($itemIndex);
			$item->setItemType($itemType);
			$template->instantiateIn($item);
		}
		else
			$item=null;

		return $item;
	}

	/**
	 * Creates empty datalist content.
	 */
	protected function createEmptyContent()
	{
		if(($classPath=$this->getEmptyRenderer())!=='')
			$this->getControls()->add(Prado::createComponent($classPath));
		else if($this->_emptyTemplate!==null)
			$this->_emptyTemplate->instantiateIn($this);
	}

	/**
	 * Applies styles to items, header, footer and separators.
	 * Item styles are applied in a hierarchical way. Style in higher hierarchy
	 * will inherit from styles in lower hierarchy.
	 * Starting from the lowest hierarchy, the item styles include
	 * item's own style, {@link getItemStyle ItemStyle}, {@link getAlternatingItemStyle AlternatingItemStyle},
	 * {@link getSelectedItemStyle SelectedItemStyle}, and {@link getEditItemStyle EditItemStyle}.
	 * Therefore, if background color is set as red in {@link getItemStyle ItemStyle},
	 * {@link getEditItemStyle EditItemStyle} will also have red background color
	 * unless it is set to a different value explicitly.
	 */
	protected function applyItemStyles()
	{
		$itemStyle=$this->getViewState('ItemStyle',null);

		$alternatingItemStyle=$this->getViewState('AlternatingItemStyle',null);
		if($itemStyle!==null)
		{
			if($alternatingItemStyle===null)
				$alternatingItemStyle=$itemStyle;
			else
				$alternatingItemStyle->mergeWith($itemStyle);
		}

		$selectedItemStyle=$this->getViewState('SelectedItemStyle',null);

		$editItemStyle=$this->getViewState('EditItemStyle',null);
		if($selectedItemStyle!==null)
		{
			if($editItemStyle===null)
				$editItemStyle=$selectedItemStyle;
			else
				$editItemStyle->mergeWith($selectedItemStyle);
		}

		$headerStyle=$this->getViewState('HeaderStyle',null);
		$footerStyle=$this->getViewState('FooterStyle',null);
		$separatorStyle=$this->getViewState('SeparatorStyle',null);

		foreach($this->getControls() as $index=>$item)
		{
			if(!($item instanceof IItemDataRenderer) || !$item->hasProperty('Style'))
				continue;
			switch($item->getItemType())
			{
				case TListItemType::Header:
					if($headerStyle)
						$item->getStyle()->mergeWith($headerStyle);
					break;
				case TListItemType::Footer:
					if($footerStyle)
						$item->getStyle()->mergeWith($footerStyle);
					break;
				case TListItemType::Separator:
					if($separatorStyle)
						$item->getStyle()->mergeWith($separatorStyle);
					break;
				case TListItemType::Item:
					if($itemStyle)
						$item->getStyle()->mergeWith($itemStyle);
					break;
				case TListItemType::AlternatingItem:
					if($alternatingItemStyle)
						$item->getStyle()->mergeWith($alternatingItemStyle);
					break;
				case TListItemType::SelectedItem:
					if($selectedItemStyle)
						$item->getStyle()->mergeWith($selectedItemStyle);
					if($index % 2==1)
					{
						if($itemStyle)
							$item->getStyle()->mergeWith($itemStyle);
					}
					else
					{
						if($alternatingItemStyle)
							$item->getStyle()->mergeWith($alternatingItemStyle);
					}
					break;
				case TListItemType::EditItem:
					if($editItemStyle)
						$item->getStyle()->mergeWith($editItemStyle);
					if($index % 2==1)
					{
						if($itemStyle)
							$item->getStyle()->mergeWith($itemStyle);
					}
					else
					{
						if($alternatingItemStyle)
							$item->getStyle()->mergeWith($alternatingItemStyle);
					}
					break;
				default:
					break;
			}
		}
	}

	/**
	 * Saves item count in viewstate.
	 * This method is invoked right before control state is to be saved.
	 */
	public function saveState()
	{
		parent::saveState();
		if($this->_items)
			$this->setViewState('ItemCount',$this->_items->getCount(),0);
		else
			$this->clearViewState('ItemCount');
	}

	/**
	 * Loads item count information from viewstate.
	 * This method is invoked right after control state is loaded.
	 */
	public function loadState()
	{
		parent::loadState();
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
			$hasSeparator=$this->_separatorTemplate!==null || $this->getSeparatorRenderer()!=='';
			$this->_header=$this->createItemInternal(-1,TListItemType::Header);
			for($i=0;$i<$itemCount;++$i)
			{
				if($hasSeparator && $i>0)
					$this->createItemInternal($i-1,TListItemType::Separator);
				if($i===$editIndex)
					$itemType=TListItemType::EditItem;
				else if($i===$selectedIndex)
					$itemType=TListItemType::SelectedItem;
				else
					$itemType=$i%2?TListItemType::AlternatingItem : TListItemType::Item;
				$items->add($this->createItemInternal($i,$itemType));
			}
			$this->_footer=$this->createItemInternal(-1,TListItemType::Footer);
		}
		else
			$this->createEmptyContent();
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
		$hasSeparator=$this->_separatorTemplate!==null || $this->getSeparatorRenderer()!=='';
		$selectedIndex=$this->getSelectedItemIndex();
		$editIndex=$this->getEditItemIndex();
		foreach($data as $key=>$dataItem)
		{
			if($keyField!=='')
				$keys->add($this->getDataFieldValue($dataItem,$keyField));
			else
				$keys->add($key);
			if($itemIndex===0)
				$this->_header=$this->createItemWithDataInternal(-1,TListItemType::Header,null);
			if($hasSeparator && $itemIndex>0)
				$this->createItemWithDataInternal($itemIndex-1,TListItemType::Separator,null);
			if($itemIndex===$editIndex)
				$itemType=TListItemType::EditItem;
			else if($itemIndex===$selectedIndex)
				$itemType=TListItemType::SelectedItem;
			else
				$itemType=$itemIndex%2?TListItemType::AlternatingItem : TListItemType::Item;
			$items->add($this->createItemWithDataInternal($itemIndex,$itemType,$dataItem));
			$itemIndex++;
		}
		if($itemIndex>0)
			$this->_footer=$this->createItemWithDataInternal(-1,TListItemType::Footer,null);
		else
		{
			$this->createEmptyContent();
			$this->dataBindChildren();
		}
		$this->setViewState('ItemCount',$itemIndex,0);
	}

	/**
	 * Renders the data list control.
	 * This method overrides the parent implementation.
	 * @param THtmlWriter writer for rendering purpose.
	 */
	public function render($writer)
	{
		if($this->getHasControls())
		{
			if($this->getItemCount()>0)
			{
				$this->applyItemStyles();
				$repeatInfo=$this->getRepeatInfo();
				$repeatInfo->renderRepeater($writer,$this);
			}
			else if($this->_emptyTemplate!==null || $this->getEmptyRenderer()!=='')
				parent::render($writer);
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
 * @version $Id$
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TDataListItemEventParameter extends TEventParameter
{
	/**
	 * The datalist item control responsible for the event.
	 * @var TControl
	 */
	private $_item=null;

	/**
	 * Constructor.
	 * @param TControl DataList item related with the corresponding event
	 */
	public function __construct($item)
	{
		$this->_item=$item;
	}

	/**
	 * @return TControl datalist item related with the corresponding event
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
 * @version $Id$
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TDataListCommandEventParameter extends TCommandEventParameter
{
	/**
	 * @var TControl the datalist item control responsible for the event.
	 */
	private $_item=null;
	/**
	 * @var TControl the control originally raises the <b>OnCommand</b> event.
	 */
	private $_source=null;

	/**
	 * Constructor.
	 * @param TControl datalist item responsible for the event
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
	 * @return TControl the datalist item control responsible for the event.
	 */
	public function getItem()
	{
		return $this->_item;
	}

	/**
	 * @return TControl the control originally raises the <b>OnCommand</b> event.
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
 * @version $Id$
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TDataListItem extends TWebControl implements INamingContainer, IItemDataRenderer
{
	/**
	 * index of the data item in the Items collection of DataList
	 */
	private $_itemIndex;
	/**
	 * type of the TDataListItem
	 * @var TListItemType
	 */
	private $_itemType;
	/**
	 * value of the data associated with this item
	 * @var mixed
	 */
	private $_data;

	/**
	 * Creates a style object for the control.
	 * This method creates a {@link TTableItemStyle} to be used by a datalist item.
	 * @return TStyle control style to be used
	 */
	protected function createStyle()
	{
		return new TTableItemStyle;
	}

	/**
	 * @return TListItemType item type
	 */
	public function getItemType()
	{
		return $this->_itemType;
	}

	/**
	 * @param TListItemType item type.
	 */
	public function setItemType($value)
	{
		$this->_itemType=TPropertyValue::ensureEnum($value,'TListItemType');
	}

	/**
	 * @return integer zero-based index of the item in the item collection of datalist
	 */
	public function getItemIndex()
	{
		return $this->_itemIndex;
	}

	/**
	 * Sets the zero-based index for the item.
	 * If the item is not in the item collection (e.g. it is a header item), -1 should be used.
	 * @param integer zero-based index of the item.
	 */
	public function setItemIndex($value)
	{
		$this->_itemIndex=TPropertyValue::ensureInteger($value);
	}

	/**
	 * @return mixed data associated with the item
	 */
	public function getData()
	{
		return $this->_data;
	}

	/**
	 * @param mixed data to be associated with the item
	 */
	public function setData($value)
	{
		$this->_data=$value;
	}

	/**
	 * This property is deprecated since v3.1.0.
	 * @return mixed data associated with the item
	 * @deprecated deprecated since v3.1.0. Use {@link getData} instead.
	 */
	public function getDataItem()
	{
		return $this->getData();
	}

	/**
	 * This property is deprecated since v3.1.0.
	 * @param mixed data to be associated with the item
	 * @deprecated deprecated since version 3.1.0. Use {@link setData} instead.
	 */
	public function setDataItem($value)
	{
		return $this->setData($value);
	}

	/**
	 * This method overrides parent's implementation by wrapping event parameter
	 * for <b>OnCommand</b> event with item information.
	 * @param TControl the sender of the event
	 * @param TEventParameter event parameter
	 * @return boolean whether the event bubbling should stop here.
	 */
	public function bubbleEvent($sender,$param)
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
 * TDataListItemRenderer class
 *
 * TDataListItemRenderer can be used as a convenient base class to
 * define an item renderer class for {@link TDataList}.
 *
 * Because TDataListItemRenderer extends from {@link TTemplateControl}, derived child classes
 * can have templates to define their presentational layout.
 *
 * TDataListItemRenderer implements {@link IItemDataRenderer} interface,
 * which enables the following properties that are related with data-bound controls:
 * - {@link getItemIndex ItemIndex}: zero-based index of this control in the datalist item collection.
 * - {@link getItemType ItemType}: item type of this control, such as TListItemType::AlternatingItem
 * - {@link getData Data}: data associated with this control

 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id$
 * @package System.Web.UI.WebControls
 * @since 3.1.0
 */
class TDataListItemRenderer extends TTemplateControl implements IItemDataRenderer
{
	/**
	 * index of the data item in the Items collection of TDataList
	 * @var integer
	 */
	private $_itemIndex;
	/**
	 * type of the TDataListItem
	 * @var TListItemType
	 */
	private $_itemType;
	/**
	 * value of the data associated with this item
	 * @var mixed
	 */
	private $_data;

	/**
	 * @return boolean whether the control has defined any style information
	 */
	public function getHasStyle()
	{
		return $this->getViewState('Style',null)!==null;
	}

	/**
	 * Creates a style object to be used by the control.
	 * This method may be overriden by controls to provide customized style.
	 * @return TStyle
	 */
	protected function createStyle()
	{
		return new TStyle;
	}

	/**
	 * @return TStyle the object representing the css style of the control
	 */
	public function getStyle()
	{
		if($style=$this->getViewState('Style',null))
			return $style;
		else
		{
			$style=$this->createStyle();
			$this->setViewState('Style',$style,null);
			return $style;
		}
	}

	/**
	 * @return TListItemType item type
	 */
	public function getItemType()
	{
		return $this->_itemType;
	}

	/**
	 * @param TListItemType item type.
	 */
	public function setItemType($value)
	{
		$this->_itemType=TPropertyValue::ensureEnum($value,'TListItemType');
	}

	/**
	 * @return integer zero-based index of the item in the item collection of datalist
	 */
	public function getItemIndex()
	{
		return $this->_itemIndex;
	}

	/**
	 * Sets the zero-based index for the item.
	 * If the item is not in the item collection (e.g. it is a header item), -1 should be used.
	 * @param integer zero-based index of the item.
	 */
	public function setItemIndex($value)
	{
		$this->_itemIndex=TPropertyValue::ensureInteger($value);
	}

	/**
	 * @return mixed data associated with the item
	 */
	public function getData()
	{
		return $this->_data;
	}

	/**
	 * @param mixed data to be associated with the item
	 */
	public function setData($value)
	{
		$this->_data=$value;
	}

	/**
	 * This method overrides parent's implementation by wrapping event parameter
	 * for <b>OnCommand</b> event with item information.
	 * @param TControl the sender of the event
	 * @param TEventParameter event parameter
	 * @return boolean whether the event bubbling should stop here.
	 */
	public function bubbleEvent($sender,$param)
	{
		if($param instanceof TCommandEventParameter)
		{
			$this->raiseBubbleEvent($this,new TDataListCommandEventParameter($this,$sender,$param));
			return true;
		}
		else
			return false;
	}

	/**
	 * Returns the tag name used for this control.
	 * By default, the tag name is 'span'.
	 * You can override this method to provide customized tag names.
	 * If the tag name is empty, the opening and closing tag will NOT be rendered.
	 * @return string tag name of the control to be rendered
	 */
	protected function getTagName()
	{
		return 'span';
	}

	/**
	 * Adds attribute name-value pairs to renderer.
	 * By default, this method renders the style string.
	 * The method can be overriden to provide customized attribute rendering.
	 * @param THtmlWriter the writer used for the rendering purpose
	 */
	protected function addAttributesToRender($writer)
	{
		if($style=$this->getViewState('Style',null))
			$style->addAttributesToRender($writer);
	}

	/**
	 * Renders the control.
	 * This method overrides the parent implementation by replacing it with
	 * the following sequence:
	 * - {@link renderBeginTag}
	 * - {@link renderContents}
	 * - {@link renderEndTag}
	 * If the {@link getTagName TagName} is empty, only {@link renderContents} is invoked.
	 * @param THtmlWriter the writer used for the rendering purpose
	 */
	public function render($writer)
	{
		if($this->getTagName()!=='')
		{
			$this->renderBeginTag($writer);
			$this->renderContents($writer);
			$this->renderEndTag($writer);
		}
		else
			$this->renderContents();
	}

	/**
	 * Renders the openning tag for the control (including attributes)
	 * This method is invoked when {@link getTagName TagName} is not empty.
	 * @param THtmlWriter the writer used for the rendering purpose
	 */
	public function renderBeginTag($writer)
	{
		$this->addAttributesToRender($writer);
		$writer->renderBeginTag($this->getTagName());
	}

	/**
	 * Renders the body content enclosed between the control tag.
	 * By default, child controls and text strings will be rendered.
	 * You can override this method to provide customized content rendering.
	 * @param THtmlWriter the writer used for the rendering purpose
	 */
	public function renderContents($writer)
	{
		parent::renderChildren($writer);
	}

	/**
	 * Renders the closing tag for the control
	 * This method is invoked when {@link getTagName TagName} is not empty.
	 * @param THtmlWriter the writer used for the rendering purpose
	 */
	public function renderEndTag($writer)
	{
		$writer->renderEndTag();
	}
}

/**
 * TDataListItemCollection class.
 *
 * TDataListItemCollection represents a collection of data list items.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id$
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TDataListItemCollection extends TList
{
	/**
	 * Inserts an item at the specified position.
	 * This overrides the parent implementation by inserting only TControl descendants.
	 * @param integer the speicified position.
	 * @param mixed new item
	 * @throws TInvalidDataTypeException if the item to be inserted is not a TControl descendant.
	 */
	public function insertAt($index,$item)
	{
		if($item instanceof TControl)
			parent::insertAt($index,$item);
		else
			throw new TInvalidDataTypeException('datalistitemcollection_datalistitem_required');
	}
}

?>