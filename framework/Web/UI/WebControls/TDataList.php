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
 * TDataList class
 *
 * TDataList represents a data bound and updatable list control.
 *
 * It can be used to display and maintain a list of data items (rows, records).
 * There are three kinds of layout determined by the <b>RepeatLayout</b>
 * property. The <b>Table</b> layout displays a table and each table cell
 * contains a data item. The <b>Flow</b> layout uses the span tag to organize
 * the presentation of data items. The <b>Raw</b> layout displays all templated
 * content without any additional decorations (therefore, you can use arbitrary
 * complex UI layout). In case when the layout is Table or Flow,
 * the number of table/flow columns is determined by the <b>RepeatColumns</b>
 * property, and the data items are enumerated according to the <b>RepeatDirection</b> property.
 *
 * To use TDataList, sets its <b>DataSource</b> property and invokes dataBind()
 * afterwards. The data will be populated into the TDataList and saved as data items.
 * A data item can be at one of three states: normal, selected and edit.
 * The state determines which template is used to display the item.
 * In particular, data items are displayed using the following templates,
 * <b>ItemTemplate</b>, <b>AlternatingItemTemplate</b>,
 * <b>SelectedItemTemplate</b>, <b>EditItemTemplate</b>. In addition, the
 * <b>HeaderTemplate</b>, <b>FooterTemplate</b>, and <b>SeparatorTemplate</b>
 * can be used to decorate the overall presentation.
 *
 * To change the state of a data item, set either the <b>EditItemIndex</b> property
 * or the <b>SelectedItemIndex</b> property.
 *
 * When an item template contains a button control that raises an <b>OnCommand</b>
 * event, the event will be bubbled up to the data list control.
 * If the event's command name is recognizable by the data list control,
 * a corresponding item event will be raised. The following item events will be
 * raised upon a specific command:
 * - OnEditCommand, edit
 * - OnCancelCommand, cancel
 * - OnSelectCommand, select
 * - OnDeleteCommand, delete
 * - OnUpdateCommand, update
 * The data list will always raise an <b>OnItemCommand</b>
 * upon its receiving a bubbled <b>OnCommand</b> event.
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
		return ($this->getShowHeader() && $this->_headerTemplate!=='')
	}

	/**
	 * Returns a value indicating whether this control contains footer item.
	 * This method is required by {@link IRepeatInfoUser} interface.
	 * @return boolean always false.
	 */
	public function getHasFooter()
	{
		return ($this->getShowFooter() && $this->_footerTemplate!=='')
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
		return null
	}

	/**
	 * Initializes a data list item.
	 * The item is added as a child of the data list and the corresponding
	 * template is instantiated within the item.
	 * @param TRepeaterItem item to be initialized
	 */
	protected function initializeItem($item)
	{
		$tplContent='';
		switch($item->getItemType())
		{
			case 'Header': $tplContent=$this->_headerTemplate; break;
			case 'Footer': $tplContent=$this->_footerTemplate; break;
			case 'Item': $tplContent=$this->_itemTemplate; break;
			case 'AlternatingItem':
				if($this->_alternatingItemTemplate!=='')
					$tplContent=$this->_alternatingItemTemplate;
				else
					$tplContent=$this->_itemTemplate;
				break;
			case 'Separator': $tplContent=$this->_separatorTemplate; break;
			case 'SelectedItem':
				if($this->_selectedItemTemplate==='')
				{
					if($item->getItemIndex()%2 && $this->_alternatingItemTemplate!=='')
						$tplContent=$this->_alternatingItemTemplate;
					else
						$tplContent=$this->_itemTemplate;
				}
				else
					$tplContent=$this->_selectedItemTemplate;
				break;
			case 'EditItem':
				if($this->_editItemTemplate==='')
				{
					if($item->getItemIndex()===$this->getSelectedIndex() && $this->_selectedItemTemplate!=='')
						$tplContent=$this->_selectedItemTemplate;
					else if($item->getItemIndex()%2 && $this->_alternatingItemTemplate!=='')
						$tplContent=$this->_alternatingItemTemplate;
					else
						$tplContent=$this->_itemTemplate;
				}
				else
					$tplContent=$this->_editItemTemplate;
			default: break;
		}
		if($tplContent!=='')
			$this->createTemplate($tplContent)->instantiateIn($item);
	}

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
	 * Renders an item in the list.
	 * This method is required by {@link IRepeatInfoUser} interface.
	 * @param THtmlWriter writer for rendering purpose
	 * @param TRepeatInfo repeat information
	 * @param string item type (Header,Footer,Item,AlternatingItem,SelectedItem,EditItem,Separator,Pager)
	 * @param integer zero-based index of the item in the item list
	 */
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
		$this->_repeatedControl->setAttribute('value',$item->getValue());
		$this->_repeatedControl->setEnabled($this->_isEnabled && $item->getEnabled());
		$this->_repeatedControl->renderControl($writer);
	}

	// how to save item state??? c.f. TRepeater

	/**
	 * Renders the checkbox list control.
	 * This method overrides the parent implementation.
	 * @param THtmlWriter writer for rendering purpose.
	 */
	protected function render($writer)
	{
		if($this->getItemCount()>0)
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


/**
 * TDataListItemEventParameter class
 *
 * TDataListItemEventParameter encapsulates the parameter data for <b>OnItemCreated</b>
 * event of TDataList controls.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version v1.0, last update on 2004/08/13 21:44:52
 * @package System.Web.UI.WebControls
 */
class TDataListItemEventParameter extends TEventParameter
{
	/**
	 * The TDataListItem control responsible for the event.
	 * @var TDataListItem
	 */
	public $item=null;
}

/**
 * TDataListCommandEventParameter class
 *
 * TDataListCommandEventParameter encapsulates the parameter data for <b>OnItemCommand</b>
 * event of TDataList controls.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version v1.0, last update on 2004/08/13 21:44:52
 * @package System.Web.UI.WebControls
 */
class TDataListCommandEventParameter extends TCommandEventParameter
{
	/**
	 * The TDataListItem control responsible for the event.
	 * @var TDataListItem
	 */
	public $item=null;
	/**
	 * The control originally raises the <b>OnCommand</b> event.
	 * @var TControl
	 */
	public $source=null;
}


class TDataListItemCollection extends TCollection
{
	protected $list=null;

	public function __construct($list)
	{
		parent::__construct();
		$this->list=$list;
	}

	protected function onAddItem($item)
	{
		if($item instanceof TDataListItem)
		{
			$this->list->addBody($item);
			return true;
		}
		else
			return false;
	}

	protected function onRemoveItem($item)
	{
		$this->list->getBodies()->remove($item);
	}
}


/**
 * TDataGridItem class
 *
 * A TDataGridItem control represents an item in the TDataGrid control, such
 * as heading section, footer section, data item, or pager section. The
 * item type can be determined by <b>Type</b> property.
 * The data items are stored in the <b>Items</b> property of TDataGrid control.
 * The index and data value of the item can be accessed via <b>Index</b>
 * and <b>Data</b> properties, respectively.
 *
 * Since TDataGridItem inherits from TTableRow, you can also access
 * the <b>Cells</b> property to get the table cells in the item.
 *
 * Namespace: System.Web.UI.WebControls
 *
 * Properties
 * - <b>ItemIndex</b>, mixed
 *   <br>Gets or sets the index of the data item in the Items collection of datagrid.
 * - <b>Data</b>, mixed
 *   <br>Gets or sets the value of the data item.
 * - <b>Type</b>, mixed
 *   <br>Gets or sets the type of the item (Header, Footer, Item, AlternatingItem, EditItem, SelectedItem, Separator)
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version v1.0, last update on 2004/08/13 21:44:52
 * @package System.Web.UI.WebControls
 */
class TDataGridItem extends TTableRow
{
	/**
	 * Header
	 */
	const TYPE_HEADER='Header';
	/**
	 * Footer
	 */
	const TYPE_FOOTER='Footer';
	/**
	 * Data item
	 */
	const TYPE_ITEM='Item';
	/**
	 * Alternating data item
	 */
	const TYPE_ALTERNATING_ITEM='AlternatingItem';
	/**
	 * Selected item
	 */
	const TYPE_SELECTED_ITEM='SelectedItem';
	/**
	 * Edit item
	 */
	const TYPE_EDIT_ITEM='EditItem';
	/**
	 * Pager
	 */
	const TYPE_PAGER='Pager';
	/**
	 * index of the data item
	 * @var mixed
	 */
	private $index='';
	/**
	 * value of the data item
	 * @var mixed
	 */
	private $data=null;
	/**
	 * type of the TDataGridItem
	 * @var string
	 */
	private $type='';

	/**
	 * Constructor.
	 * Initializes the type to 'Item'.
	 */
	public function __construct()
	{
		$this->type=self::TYPE_ITEM;
		parent::__construct();
	}

	/**
	 * @return mixed the index of the data item
	 */
	public function getItemIndex()
	{
		return $this->index;
	}

	/**
	 * Sets the index of the data item
	 * @param mixed the data item index
	 */
	public function setItemIndex($value)
	{
		$this->index=$value;
	}

	/**
	 * @return mixed the value of the data item
	 */
	public function getData()
	{
		return $this->data;
	}

	/**
	 * Sets the value of the data item
	 * @param mixed the value of the data item
	 */
	public function setData($value)
	{
		$this->data=$value;
	}

	/**
	 * @return string the item type
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * Sets the item type
	 * @param string the item type
	 */
	public function setType($value)
	{
		$this->type=$value;
	}

	/**
	 * Handles <b>OnBubbleEvent</b>.
	 * This method overrides parent's implementation to bubble
	 * <b>OnItemCommand</b> event if an <b>OnCommand</b>
	 * event is bubbled from a child control.
	 * This method should only be used by control developers.
	 * @param TControl the sender of the event
	 * @param TEventParameter event parameter
	 * @return boolean whether the event bubbling should stop here.
	 */
	protected function onBubbleEvent($sender,$param)
	{
		if($param instanceof TCommandEventParameter)
		{
			$ce=new TDataGridCommandEventParameter;
			$ce->name=$param->name;
			$ce->parameter=$param->parameter;
			$ce->source=$sender;
			$ce->item=$this;
			$this->raiseBubbleEvent($this,$ce);
			return true;
		}
		else
			return false;
	}

	/**
	 * Renders the body content of this table.
	 * @return string the rendering result
	 */
	protected function renderBody()
	{
		$content="\n";
		$cols=$this->getParent()->getColumns();
		$n=$cols->length();
		foreach($this->getCells() as $index=>$cell)
		{
			if($cell->isVisible())
			{
				if(!isset($cols[$index]) || $cols[$index]->isVisible() || $this->getType()===self::TYPE_PAGER)
					$content.=$cell->render()."\n";
			}
		}
		return $content;
	}
}

?>