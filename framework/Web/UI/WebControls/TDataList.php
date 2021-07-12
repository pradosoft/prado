<?php
/**
 * TDataList class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

use Prado\Exceptions\TInvalidOperationException;
use Prado\Prado;
use Prado\TPropertyValue;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Exceptions\TInvalidDataTypeException;
use Prado\Web\UI\ITemplate;

/**
 * TDataList class
 *
 * TDataList represents a data bound and updatable list control.
 *
 * Like {@link TRepeater}, TDataList displays its content repeatedly based on
 * the data fetched from {@link setDataSource DataSource}.
 * The repeated contents in TDataList are called items, which are controls and
 * can be accessed through {@link getItems Items}. When {@link dataBind()} is
 * invoked, TDataList creates an item for each row of data and binds the data
 * row to the item. Optionally, a TDataList can have a header, a footer and/or
 * separators between items.
 *
 * TDataList differs from {@link TRepeater} in that it supports tiling the items
 * in different manners and it maintains status of items to handle data update.
 *
 * The layout of the repeated contents are specified by inline templates.
 * TDataList items, header, footer, etc. are being instantiated with the corresponding
 * templates when data is being bound to the repeater.
 *
 * Since v3.1.0, the layout can also be by renderers. A renderer is a control class
 * that can be instantiated as datalist items, header, etc. A renderer can thus be viewed
 * as an external template (in fact, it can also be non-templated controls).
 *
 * A renderer can be any control class.
 * - If the class implements {@link \Prado\IDataRenderer}, the <b>Data</b>
 * property will be set as the data row during databinding. Many PRADO controls
 * implement this interface, such as {@link TLabel}, {@link TTextBox}, etc.
 * - If the class implements {@link IItemDataRenderer}, the <b>ItemIndex</b> property will be set
 * as the zero-based index of the item in the datalist item collection, and
 * the <b>ItemType</b> property as the item's type (such as TListItemType::Item).
 * {@link TDataListItemRenderer} may be used as the convenient base class which
 * already implements {@link IDataItemRenderer}.
 *
 * The following properties are used to specify different types of template and renderer
 * for a datalist:
 * - {@link setItemTemplate ItemTemplate}, {@link setItemRenderer ItemRenderer}:
 * for each repeated row of data
 * - {@link setAlternatingItemTemplate AlternatingItemTemplate}, {@link setAlternatingItemRenderer AlternatingItemRenderer}:
 * for each alternating row of data. If not set, {@link setItemTemplate ItemTemplate} or {@link setItemRenderer ItemRenderer}
 * will be used instead.
 * - {@link setHeaderTemplate HeaderTemplate}, {@link setHeaderRenderer HeaderRenderer}:
 * for the datalist header.
 * - {@link setFooterTemplate FooterTemplate}, {@link setFooterRenderer FooterRenderer}:
 * for the datalist footer.
 * - {@link setSeparatorTemplate SeparatorTemplate}, {@link setSeparatorRenderer SeparatorRenderer}:
 * for content to be displayed between items.
 * - {@link setEmptyTemplate EmptyTemplate}, {@link setEmptyRenderer EmptyRenderer}:
 * used when data bound to the datalist is empty.
 * - {@link setEditItemTemplate EditItemTemplate}, {@link setEditItemRenderer EditItemRenderer}:
 * for the row being editted.
 * - {@link setSelectedItemTemplate SelectedItemTemplate}, {@link setSelectedItemRenderer SelectedItemRenderer}:
 * for the row being selected.
 *
 * If a content type is defined with both a template and a renderer, the latter takes precedence.
 *
 * When {@link dataBind()} is being called, TDataList undergoes the following lifecycles for each row of data:
 * - create item based on templates or renderers
 * - set the row of data to the item
 * - raise {@link onItemCreated OnItemCreated}:
 * - add the item as a child control
 * - call dataBind() of the item
 * - raise {@link onItemDataBound OnItemDataBound}:
 *
 * TDataList raises an {@link onItemCommand OnItemCommand} whenever a button control
 * within some datalist item raises a <b>OnCommand</b> event. Therefore,
 * you can handle all sorts of <b>OnCommand</b> event in a central place by
 * writing an event handler for {@link onItemCommand OnItemCommand}.
 *
 * An additional event is raised if the <b>OnCommand</b> event has one of the following
 * command names:
 * - edit: user wants to edit an item. <b>OnEditCommand</b> event will be raised.
 * - update: user wants to save the change to an item. <b>OnUpdateCommand</b> event will be raised.
 * - select: user selects an item. <b>OnSelectedIndexChanged</b> event will be raised.
 * - delete: user deletes an item. <b>OnDeleteCommand</b> event will be raised.
 * - cancel: user cancels previously editting action. <b>OnCancelCommand</b> event will be raised.
 *
 * TDataList provides a few properties to support tiling the items.
 * The number of columns used to display the data items is specified via
 * {@link setRepeatColumns RepeatColumns} property, while the {@link setRepeatDirection RepeatDirection}
 * governs the order of the items being rendered.
 * The layout of the data items in the list is specified via {@link setRepeatLayout RepeatLayout},
 * which can take one of the following values:
 * - Table (default): items are organized using HTML table and cells.
 * When using this layout, one can set {@link setCellPadding CellPadding} and
 * {@link setCellSpacing CellSpacing} to adjust the cellpadding and cellpadding
 * of the table, and {@link setCaption Caption} and {@link setCaptionAlign CaptionAlign}
 * to add a table caption with the specified alignment.
 * - Flow: items are organized using HTML spans and breaks.
 * - Raw: TDataList does not generate any HTML tags to do the tiling.
 *
 * Items in TDataList can be in one of the three status: normal browsing,
 * being editted and being selected. To change the status of a particular
 * item, set {@link setSelectedItemIndex SelectedItemIndex} or
 * {@link setEditItemIndex EditItemIndex}. The former will change
 * the indicated item to selected mode, which will cause the item to
 * use {@link setSelectedItemTemplate SelectedItemTemplate} or
 * {@link setSelectedItemRenderer SelectedItemRenderer} for presentation.
 * The latter will change the indicated item to edit mode and to use corresponding
 * template or renderer.
 * Note, if an item is in edit mode, then selecting this item will have no effect.
 *
 * Different styles may be applied to items in different status. The style
 * application is performed in a hierarchical way: Style in higher hierarchy
 * will inherit from styles in lower hierarchy.
 * Starting from the lowest hierarchy, the item styles include
 * - item's own style
 * - {@link getItemStyle ItemStyle}
 * - {@link getAlternatingItemStyle AlternatingItemStyle}
 * - {@link getSelectedItemStyle SelectedItemStyle}
 * - {@link getEditItemStyle EditItemStyle}.
 * Therefore, if background color is set as red in {@link getItemStyle ItemStyle},
 * {@link getEditItemStyle EditItemStyle} will also have red background color
 * unless it is set to a different value explicitly.
 *
 * When a page containing a datalist is post back, the datalist will restore automatically
 * all its contents, including items, header, footer and separators.
 * However, the data row associated with each item will not be recovered and become null.
 * To access the data, use one of the following ways:
 * - Use {@link getDataKeys DataKeys} to obtain the data key associated with
 * the specified datalist item and use the key to fetch the corresponding data
 * from some persistent storage such as DB.
 * - Save the whole dataset in viewstate, which will restore the dataset automatically upon postback.
 * Be aware though, if the size of your dataset is big, your page size will become big. Some
 * complex data may also have serializing problem if saved in viewstate.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI\WebControls
 * @since 3.0
 */
class TDataList extends TBaseDataList implements \Prado\Web\UI\INamingContainer, IRepeatInfoUser
{
	/**
	 * Command name that TDataList understands. They are case-insensitive.
	 */
	public const CMD_SELECT = 'Select';
	public const CMD_EDIT = 'Edit';
	public const CMD_UPDATE = 'Update';
	public const CMD_DELETE = 'Delete';
	public const CMD_CANCEL = 'Cancel';

	/**
	 * @var TDataListItemCollection item list
	 */
	private $_items;
	/**
	 * @var Itemplate various item templates
	 */
	private $_itemTemplate;
	private $_emptyTemplate;
	private $_alternatingItemTemplate;
	private $_selectedItemTemplate;
	private $_editItemTemplate;
	private $_headerTemplate;
	private $_footerTemplate;
	private $_separatorTemplate;
	/**
	 * @var \Prado\Web\UI\TControl header item
	 */
	private $_header;
	/**
	 * @var \Prado\Web\UI\TControl footer item
	 */
	private $_footer;

	/**
	 * @return TDataListItemCollection item list
	 */
	public function getItems()
	{
		if (!$this->_items) {
			$this->_items = new TDataListItemCollection;
		}
		return $this->_items;
	}

	/**
	 * @return int number of items
	 */
	public function getItemCount()
	{
		return $this->_items ? $this->_items->getCount() : 0;
	}

	/**
	 * @return string the class name for datalist items. Defaults to empty, meaning not set.
	 * @since 3.1.0
	 */
	public function getItemRenderer()
	{
		return $this->getViewState('ItemRenderer', '');
	}

	/**
	 * Sets the item renderer class.
	 *
	 * If not empty, the class will be used to instantiate as datalist items.
	 * This property takes precedence over {@link getItemTemplate ItemTemplate}.
	 *
	 * @param string $value the renderer class name in namespace format.
	 * @see setItemTemplate
	 * @since 3.1.0
	 */
	public function setItemRenderer($value)
	{
		$this->setViewState('ItemRenderer', $value, '');
	}

	/**
	 * @return string the class name for alternative datalist items. Defaults to empty, meaning not set.
	 * @since 3.1.0
	 */
	public function getAlternatingItemRenderer()
	{
		return $this->getViewState('AlternatingItemRenderer', '');
	}

	/**
	 * Sets the alternative item renderer class.
	 *
	 * If not empty, the class will be used to instantiate as alternative datalist items.
	 * This property takes precedence over {@link getAlternatingItemTemplate AlternatingItemTemplate}.
	 *
	 * @param string $value the renderer class name in namespace format.
	 * @see setAlternatingItemTemplate
	 * @since 3.1.0
	 */
	public function setAlternatingItemRenderer($value)
	{
		$this->setViewState('AlternatingItemRenderer', $value, '');
	}

	/**
	 * @return string the class name for the datalist item being editted. Defaults to empty, meaning not set.
	 * @since 3.1.0
	 */
	public function getEditItemRenderer()
	{
		return $this->getViewState('EditItemRenderer', '');
	}

	/**
	 * Sets the renderer class for the datalist item being editted.
	 *
	 * If not empty, the class will be used to instantiate as the datalist item.
	 * This property takes precedence over {@link getEditItemTemplate EditItemTemplate}.
	 *
	 * @param string $value the renderer class name in namespace format.
	 * @see setEditItemTemplate
	 * @since 3.1.0
	 */
	public function setEditItemRenderer($value)
	{
		$this->setViewState('EditItemRenderer', $value, '');
	}

	/**
	 * @return string the class name for the datalist item being selected. Defaults to empty, meaning not set.
	 * @since 3.1.0
	 */
	public function getSelectedItemRenderer()
	{
		return $this->getViewState('SelectedItemRenderer', '');
	}

	/**
	 * Sets the renderer class for the datalist item being selected.
	 *
	 * If not empty, the class will be used to instantiate as the datalist item.
	 * This property takes precedence over {@link getSelectedItemTemplate SelectedItemTemplate}.
	 *
	 * @param string $value the renderer class name in namespace format.
	 * @see setSelectedItemTemplate
	 * @since 3.1.0
	 */
	public function setSelectedItemRenderer($value)
	{
		$this->setViewState('SelectedItemRenderer', $value, '');
	}

	/**
	 * @return string the class name for datalist item separators. Defaults to empty, meaning not set.
	 * @since 3.1.0
	 */
	public function getSeparatorRenderer()
	{
		return $this->getViewState('SeparatorRenderer', '');
	}

	/**
	 * Sets the datalist item separator renderer class.
	 *
	 * If not empty, the class will be used to instantiate as datalist item separators.
	 * This property takes precedence over {@link getSeparatorTemplate SeparatorTemplate}.
	 *
	 * @param string $value the renderer class name in namespace format.
	 * @see setSeparatorTemplate
	 * @since 3.1.0
	 */
	public function setSeparatorRenderer($value)
	{
		$this->setViewState('SeparatorRenderer', $value, '');
	}

	/**
	 * @return string the class name for datalist header item. Defaults to empty, meaning not set.
	 * @since 3.1.0
	 */
	public function getHeaderRenderer()
	{
		return $this->getViewState('HeaderRenderer', '');
	}

	/**
	 * Sets the datalist header renderer class.
	 *
	 * If not empty, the class will be used to instantiate as datalist header item.
	 * This property takes precedence over {@link getHeaderTemplate HeaderTemplate}.
	 *
	 * @param string $value the renderer class name in namespace format.
	 * @see setHeaderTemplate
	 * @since 3.1.0
	 */
	public function setHeaderRenderer($value)
	{
		$this->setViewState('HeaderRenderer', $value, '');
	}

	/**
	 * @return string the class name for datalist footer item. Defaults to empty, meaning not set.
	 * @since 3.1.0
	 */
	public function getFooterRenderer()
	{
		return $this->getViewState('FooterRenderer', '');
	}

	/**
	 * Sets the datalist footer renderer class.
	 *
	 * If not empty, the class will be used to instantiate as datalist footer item.
	 * This property takes precedence over {@link getFooterTemplate FooterTemplate}.
	 *
	 * @param string $value the renderer class name in namespace format.
	 * @see setFooterTemplate
	 * @since 3.1.0
	 */
	public function setFooterRenderer($value)
	{
		$this->setViewState('FooterRenderer', $value, '');
	}

	/**
	 * @return string the class name for empty datalist item. Defaults to empty, meaning not set.
	 * @since 3.1.0
	 */
	public function getEmptyRenderer()
	{
		return $this->getViewState('EmptyRenderer', '');
	}

	/**
	 * Sets the datalist empty renderer class.
	 *
	 * The empty renderer is created as the child of the datalist
	 * if data bound to the datalist is empty.
	 * This property takes precedence over {@link getEmptyTemplate EmptyTemplate}.
	 *
	 * @param string $value the renderer class name in namespace format.
	 * @see setEmptyTemplate
	 * @since 3.1.0
	 */
	public function setEmptyRenderer($value)
	{
		$this->setViewState('EmptyRenderer', $value, '');
	}

	/**
	 * @return \Prado\Web\UI\ITemplate the template for item
	 */
	public function getItemTemplate()
	{
		return $this->_itemTemplate;
	}

	/**
	 * @param \Prado\Web\UI\ITemplate $value the template for item
	 * @throws TInvalidDataTypeException if the input is not an {@link ITemplate} or not null.
	 */
	public function setItemTemplate($value)
	{
		if ($value instanceof ITemplate || $value === null) {
			$this->_itemTemplate = $value;
		} else {
			throw new TInvalidDataTypeException('datalist_template_required', 'ItemTemplate');
		}
	}

	/**
	 * @return TTableItemStyle the style for item
	 */
	public function getItemStyle()
	{
		if (($style = $this->getViewState('ItemStyle', null)) === null) {
			$style = new TTableItemStyle;
			$this->setViewState('ItemStyle', $style, null);
		}
		return $style;
	}

	/**
	 * @return \Prado\Web\UI\ITemplate the template for each alternating item
	 */
	public function getAlternatingItemTemplate()
	{
		return $this->_alternatingItemTemplate;
	}

	/**
	 * @param \Prado\Web\UI\ITemplate $value the template for each alternating item
	 * @throws TInvalidDataTypeException if the input is not an {@link ITemplate} or not null.
	 */
	public function setAlternatingItemTemplate($value)
	{
		if ($value instanceof ITemplate || $value === null) {
			$this->_alternatingItemTemplate = $value;
		} else {
			throw new TInvalidDataTypeException('datalist_template_required', 'AlternatingItemType');
		}
	}

	/**
	 * @return TTableItemStyle the style for each alternating item
	 */
	public function getAlternatingItemStyle()
	{
		if (($style = $this->getViewState('AlternatingItemStyle', null)) === null) {
			$style = new TTableItemStyle;
			$this->setViewState('AlternatingItemStyle', $style, null);
		}
		return $style;
	}

	/**
	 * @return \Prado\Web\UI\ITemplate the selected item template
	 */
	public function getSelectedItemTemplate()
	{
		return $this->_selectedItemTemplate;
	}

	/**
	 * @param \Prado\Web\UI\ITemplate $value the selected item template
	 * @throws TInvalidDataTypeException if the input is not an {@link ITemplate} or not null.
	 */
	public function setSelectedItemTemplate($value)
	{
		if ($value instanceof ITemplate || $value === null) {
			$this->_selectedItemTemplate = $value;
		} else {
			throw new TInvalidDataTypeException('datalist_template_required', 'SelectedItemTemplate');
		}
	}

	/**
	 * @return TTableItemStyle the style for selected item
	 */
	public function getSelectedItemStyle()
	{
		if (($style = $this->getViewState('SelectedItemStyle', null)) === null) {
			$style = new TTableItemStyle;
			$this->setViewState('SelectedItemStyle', $style, null);
		}
		return $style;
	}

	/**
	 * @return \Prado\Web\UI\ITemplate the edit item template
	 */
	public function getEditItemTemplate()
	{
		return $this->_editItemTemplate;
	}

	/**
	 * @param \Prado\Web\UI\ITemplate $value the edit item template
	 * @throws TInvalidDataTypeException if the input is not an {@link ITemplate} or not null.
	 */
	public function setEditItemTemplate($value)
	{
		if ($value instanceof ITemplate || $value === null) {
			$this->_editItemTemplate = $value;
		} else {
			throw new TInvalidDataTypeException('datalist_template_required', 'EditItemTemplate');
		}
	}

	/**
	 * @return TTableItemStyle the style for edit item
	 */
	public function getEditItemStyle()
	{
		if (($style = $this->getViewState('EditItemStyle', null)) === null) {
			$style = new TTableItemStyle;
			$this->setViewState('EditItemStyle', $style, null);
		}
		return $style;
	}

	/**
	 * @return \Prado\Web\UI\ITemplate the header template
	 */
	public function getHeaderTemplate()
	{
		return $this->_headerTemplate;
	}

	/**
	 * @param \Prado\Web\UI\ITemplate $value the header template
	 * @throws TInvalidDataTypeException if the input is not an {@link ITemplate} or not null.
	 */
	public function setHeaderTemplate($value)
	{
		if ($value instanceof ITemplate || $value === null) {
			$this->_headerTemplate = $value;
		} else {
			throw new TInvalidDataTypeException('datalist_template_required', 'HeaderTemplate');
		}
	}

	/**
	 * @return TTableItemStyle the style for header
	 */
	public function getHeaderStyle()
	{
		if (($style = $this->getViewState('HeaderStyle', null)) === null) {
			$style = new TTableItemStyle;
			$this->setViewState('HeaderStyle', $style, null);
		}
		return $style;
	}

	/**
	 * @return \Prado\Web\UI\TControl the header item
	 */
	public function getHeader()
	{
		return $this->_header;
	}

	/**
	 * @return \Prado\Web\UI\ITemplate the footer template
	 */
	public function getFooterTemplate()
	{
		return $this->_footerTemplate;
	}

	/**
	 * @param \Prado\Web\UI\ITemplate $value the footer template
	 * @throws TInvalidDataTypeException if the input is not an {@link ITemplate} or not null.
	 */
	public function setFooterTemplate($value)
	{
		if ($value instanceof ITemplate || $value === null) {
			$this->_footerTemplate = $value;
		} else {
			throw new TInvalidDataTypeException('datalist_template_required', 'FooterTemplate');
		}
	}

	/**
	 * @return TTableItemStyle the style for footer
	 */
	public function getFooterStyle()
	{
		if (($style = $this->getViewState('FooterStyle', null)) === null) {
			$style = new TTableItemStyle;
			$this->setViewState('FooterStyle', $style, null);
		}
		return $style;
	}

	/**
	 * @return \Prado\Web\UI\TControl the footer item
	 */
	public function getFooter()
	{
		return $this->_footer;
	}

	/**
	 * @return \Prado\Web\UI\ITemplate the template applied when no data is bound to the datalist
	 */
	public function getEmptyTemplate()
	{
		return $this->_emptyTemplate;
	}

	/**
	 * @param \Prado\Web\UI\ITemplate $value the template applied when no data is bound to the datalist
	 * @throws TInvalidDataTypeException if the input is not an {@link ITemplate} or not null.
	 */
	public function setEmptyTemplate($value)
	{
		if ($value instanceof ITemplate || $value === null) {
			$this->_emptyTemplate = $value;
		} else {
			throw new TInvalidDataTypeException('datalist_template_required', 'EmptyTemplate');
		}
	}

	/**
	 * @return \Prado\Web\UI\ITemplate the separator template
	 */
	public function getSeparatorTemplate()
	{
		return $this->_separatorTemplate;
	}

	/**
	 * @param \Prado\Web\UI\ITemplate $value the separator template
	 * @throws TInvalidDataTypeException if the input is not an {@link ITemplate} or not null.
	 */
	public function setSeparatorTemplate($value)
	{
		if ($value instanceof ITemplate || $value === null) {
			$this->_separatorTemplate = $value;
		} else {
			throw new TInvalidDataTypeException('datalist_template_required', 'SeparatorTemplate');
		}
	}

	/**
	 * @return TTableItemStyle the style for separator
	 */
	public function getSeparatorStyle()
	{
		if (($style = $this->getViewState('SeparatorStyle', null)) === null) {
			$style = new TTableItemStyle;
			$this->setViewState('SeparatorStyle', $style, null);
		}
		return $style;
	}

	/**
	 * @return int the zero-based index of the selected item in {@link getItems Items}.
	 * A value -1 means no item selected.
	 */
	public function getSelectedItemIndex()
	{
		return $this->getViewState('SelectedItemIndex', -1);
	}

	/**
	 * Selects an item by its index in {@link getItems Items}.
	 * Previously selected item will be un-selected.
	 * If the item to be selected is already in edit mode, it will remain in edit mode.
	 * If the index is less than 0, any existing selection will be cleared up.
	 * @param int $value the selected item index
	 */
	public function setSelectedItemIndex($value)
	{
		if (($value = TPropertyValue::ensureInteger($value)) < 0) {
			$value = -1;
		}
		if (($current = $this->getSelectedItemIndex()) !== $value) {
			$this->setViewState('SelectedItemIndex', $value, -1);
			$items = $this->getItems();
			$itemCount = $items->getCount();
			if ($current >= 0 && $current < $itemCount) {
				$item = $items->itemAt($current);
				if (($item instanceof IItemDataRenderer) && $item->getItemType() !== TListItemType::EditItem) {
					$item->setItemType($current % 2 ? TListItemType::AlternatingItem : TListItemType::Item);
				}
			}
			if ($value >= 0 && $value < $itemCount) {
				$item = $items->itemAt($value);
				if (($item instanceof IItemDataRenderer) && $item->getItemType() !== TListItemType::EditItem) {
					$item->setItemType(TListItemType::SelectedItem);
				}
			}
		}
	}

	/**
	 * @return \Prado\Web\UI\TControl the selected item, null if no item is selected.
	 */
	public function getSelectedItem()
	{
		$index = $this->getSelectedItemIndex();
		$items = $this->getItems();
		if ($index >= 0 && $index < $items->getCount()) {
			return $items->itemAt($index);
		} else {
			return null;
		}
	}

	/**
	 * @throws TInvalidOperationException if {@link getDataKeyField DataKeyField} is empty.
	 * @return mixed the key value of the currently selected item
	 */
	public function getSelectedDataKey()
	{
		if ($this->getDataKeyField() === '') {
			throw new TInvalidOperationException('datalist_datakeyfield_required');
		}
		$index = $this->getSelectedItemIndex();
		$dataKeys = $this->getDataKeys();
		if ($index >= 0 && $index < $dataKeys->getCount()) {
			return $dataKeys->itemAt($index);
		} else {
			return null;
		}
	}

	/**
	 * @return int the zero-based index of the edit item in {@link getItems Items}.
	 * A value -1 means no item is in edit mode.
	 */
	public function getEditItemIndex()
	{
		return $this->getViewState('EditItemIndex', -1);
	}

	/**
	 * Edits an item by its index in {@link getItems Items}.
	 * Previously editting item will change to normal item state.
	 * If the index is less than 0, any existing edit item will be cleared up.
	 * @param int $value the edit item index
	 */
	public function setEditItemIndex($value)
	{
		if (($value = TPropertyValue::ensureInteger($value)) < 0) {
			$value = -1;
		}
		if (($current = $this->getEditItemIndex()) !== $value) {
			$this->setViewState('EditItemIndex', $value, -1);
			$items = $this->getItems();
			$itemCount = $items->getCount();
			if ($current >= 0 && $current < $itemCount) {
				$items->itemAt($current)->setItemType($current % 2 ? TListItemType::AlternatingItem : TListItemType::Item);
			}
			if ($value >= 0 && $value < $itemCount) {
				$items->itemAt($value)->setItemType(TListItemType::EditItem);
			}
		}
	}

	/**
	 * @return \Prado\Web\UI\TControl the edit item
	 */
	public function getEditItem()
	{
		$index = $this->getEditItemIndex();
		$items = $this->getItems();
		if ($index >= 0 && $index < $items->getCount()) {
			return $items->itemAt($index);
		} else {
			return null;
		}
	}

	/**
	 * @return bool whether the header should be shown. Defaults to true.
	 */
	public function getShowHeader()
	{
		return $this->getViewState('ShowHeader', true);
	}

	/**
	 * @param bool $value whether to show header
	 */
	public function setShowHeader($value)
	{
		$this->setViewState('ShowHeader', TPropertyValue::ensureBoolean($value), true);
	}

	/**
	 * @return bool whether the footer should be shown. Defaults to true.
	 */
	public function getShowFooter()
	{
		return $this->getViewState('ShowFooter', true);
	}

	/**
	 * @param bool $value whether to show footer
	 */
	public function setShowFooter($value)
	{
		$this->setViewState('ShowFooter', TPropertyValue::ensureBoolean($value), true);
	}

	/**
	 * @return TRepeatInfo repeat information (primarily used by control developers)
	 */
	protected function getRepeatInfo()
	{
		if (($repeatInfo = $this->getViewState('RepeatInfo', null)) === null) {
			$repeatInfo = new TRepeatInfo;
			$this->setViewState('RepeatInfo', $repeatInfo, null);
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
	 * @param string $value caption of the table layout
	 */
	public function setCaption($value)
	{
		$this->getRepeatInfo()->setCaption($value);
	}

	/**
	 * @return TTableCaptionAlign alignment of the caption of the table layout. Defaults to TTableCaptionAlign::NotSet.
	 * @deprecated use the CSS properties caption-side and text-align instead.
	 */
	public function getCaptionAlign()
	{
		return $this->getRepeatInfo()->getCaptionAlign();
	}

	/**
	 * @param TTableCaptionAlign $value alignment of the caption of the table layout.
	 * @deprecated use the CSS properties caption-side and text-align instead.
	 */
	public function setCaptionAlign($value)
	{
		$this->getRepeatInfo()->setCaptionAlign($value);
	}

	/**
	 * @return int the number of columns that the list should be displayed with. Defaults to 0 meaning not set.
	 */
	public function getRepeatColumns()
	{
		return $this->getRepeatInfo()->getRepeatColumns();
	}

	/**
	 * @param int $value the number of columns that the list should be displayed with.
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
	 * @param TRepeatDirection $value the direction of traversing the list
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
	 * @param TRepeatLayout $value how the list should be displayed, using table or using line breaks
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
	 * @param \Prado\Web\UI\TControl $sender the sender of the event
	 * @param \Prado\TEventParameter $param event parameter
	 * @return bool whether the event bubbling should stop here.
	 */
	public function bubbleEvent($sender, $param)
	{
		if ($param instanceof TDataListCommandEventParameter) {
			$this->onItemCommand($param);
			$command = $param->getCommandName();
			if (strcasecmp($command, self::CMD_SELECT) === 0) {
				if (($item = $param->getItem()) instanceof IItemDataRenderer) {
					$this->setSelectedItemIndex($item->getItemIndex());
				}
				$this->onSelectedIndexChanged($param);
				return true;
			} elseif (strcasecmp($command, self::CMD_EDIT) === 0) {
				$this->onEditCommand($param);
				return true;
			} elseif (strcasecmp($command, self::CMD_DELETE) === 0) {
				$this->onDeleteCommand($param);
				return true;
			} elseif (strcasecmp($command, self::CMD_UPDATE) === 0) {
				$this->onUpdateCommand($param);
				return true;
			} elseif (strcasecmp($command, self::CMD_CANCEL) === 0) {
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
	 * @param TDataListItemEventParameter $param event parameter
	 */
	public function onItemCreated($param)
	{
		$this->raiseEvent('OnItemCreated', $this, $param);
	}

	/**
	 * Raises <b>OnItemDataBound</b> event.
	 * This method is invoked right after an item is data bound.
	 * The datalist item control responsible for the event
	 * can be determined from the event parameter.
	 * If you override this method, be sure to call parent's implementation
	 * so that event handlers have chance to respond to the event.
	 * @param TDataListItemEventParameter $param event parameter
	 */
	public function onItemDataBound($param)
	{
		$this->raiseEvent('OnItemDataBound', $this, $param);
	}

	/**
	 * Raises <b>OnItemCommand</b> event.
	 * This method is invoked when a child control of the data list
	 * raises an <b>OnCommand</b> event.
	 * @param TDataListCommandEventParameter $param event parameter
	 */
	public function onItemCommand($param)
	{
		$this->raiseEvent('OnItemCommand', $this, $param);
	}

	/**
	 * Raises <b>OnEditCommand</b> event.
	 * This method is invoked when a child control of the data list
	 * raises an <b>OnCommand</b> event and the command name is 'edit' (case-insensitive).
	 * @param TDataListCommandEventParameter $param event parameter
	 */
	public function onEditCommand($param)
	{
		$this->raiseEvent('OnEditCommand', $this, $param);
	}

	/**
	 * Raises <b>OnDeleteCommand</b> event.
	 * This method is invoked when a child control of the data list
	 * raises an <b>OnCommand</b> event and the command name is 'delete' (case-insensitive).
	 * @param TDataListCommandEventParameter $param event parameter
	 */
	public function onDeleteCommand($param)
	{
		$this->raiseEvent('OnDeleteCommand', $this, $param);
	}

	/**
	 * Raises <b>OnUpdateCommand</b> event.
	 * This method is invoked when a child control of the data list
	 * raises an <b>OnCommand</b> event and the command name is 'update' (case-insensitive).
	 * @param TDataListCommandEventParameter $param event parameter
	 */
	public function onUpdateCommand($param)
	{
		$this->raiseEvent('OnUpdateCommand', $this, $param);
	}

	/**
	 * Raises <b>OnCancelCommand</b> event.
	 * This method is invoked when a child control of the data list
	 * raises an <b>OnCommand</b> event and the command name is 'cancel' (case-insensitive).
	 * @param TDataListCommandEventParameter $param event parameter
	 */
	public function onCancelCommand($param)
	{
		$this->raiseEvent('OnCancelCommand', $this, $param);
	}

	/**
	 * Returns a value indicating whether this control contains header item.
	 * This method is required by {@link IRepeatInfoUser} interface.
	 * @return bool whether the datalist has header
	 */
	public function getHasHeader()
	{
		return ($this->getShowHeader() && ($this->_headerTemplate !== null || $this->getHeaderRenderer() !== ''));
	}

	/**
	 * Returns a value indicating whether this control contains footer item.
	 * This method is required by {@link IRepeatInfoUser} interface.
	 * @return bool whether the datalist has footer
	 */
	public function getHasFooter()
	{
		return ($this->getShowFooter() && ($this->_footerTemplate !== null || $this->getFooterRenderer() !== ''));
	}

	/**
	 * Returns a value indicating whether this control contains separator items.
	 * This method is required by {@link IRepeatInfoUser} interface.
	 * @return bool always false.
	 */
	public function getHasSeparators()
	{
		return $this->_separatorTemplate !== null || $this->getSeparatorRenderer() !== '';
	}

	/**
	 * Returns a style used for rendering items.
	 * This method is required by {@link IRepeatInfoUser} interface.
	 * @param string $itemType item type (Header,Footer,Item,AlternatingItem,SelectedItem,EditItem,Separator,Pager)
	 * @param int $index index of the item being rendered
	 * @return TStyle item style
	 */
	public function generateItemStyle($itemType, $index)
	{
		if (($item = $this->getItem($itemType, $index)) !== null && ($item instanceof IStyleable) && $item->getHasStyle()) {
			$style = $item->getStyle();
			$item->clearStyle();
			return $style;
		} else {
			return null;
		}
	}

	/**
	 * Renders an item in the list.
	 * This method is required by {@link IRepeatInfoUser} interface.
	 * @param \Prado\Web\UI\THtmlWriter $writer writer for rendering purpose
	 * @param TRepeatInfo $repeatInfo repeat information
	 * @param string $itemType item type (Header,Footer,Item,AlternatingItem,SelectedItem,EditItem,Separator,Pager)
	 * @param int $index zero-based index of the item in the item list
	 */
	public function renderItem($writer, $repeatInfo, $itemType, $index)
	{
		$item = $this->getItem($itemType, $index);
		if ($repeatInfo->getRepeatLayout() === TRepeatLayout::Raw && get_class($item) === 'TDataListItem') {
			$item->setTagName('div');
		}
		$item->renderControl($writer);
	}

	/**
	 * @param TListItemType $itemType item type
	 * @param int $index item index
	 * @return TDataListItem data list item with the specified item type and index
	 */
	private function getItem($itemType, $index)
	{
		switch ($itemType) {
			case TListItemType::Item:
			case TListItemType::AlternatingItem:
			case TListItemType::SelectedItem:
			case TListItemType::EditItem:
				return $this->getItems()->itemAt($index);
			case TListItemType::Header:
				return $this->getControls()->itemAt(0);
			case TListItemType::Footer:
				return $this->getControls()->itemAt($this->getControls()->getCount() - 1);
			case TListItemType::Separator:
				$i = $index + $index + 1;
				if ($this->_headerTemplate !== null || $this->getHeaderRenderer() !== '') {
					$i++;
				}
				return $this->getControls()->itemAt($i);
		}
		return null;
	}

	/**
	 * Creates a datalist item.
	 * This method invokes {@link createItem} to create a new datalist item.
	 * @param int $itemIndex zero-based item index.
	 * @param TListItemType $itemType item type
	 * @return \Prado\Web\UI\TControl the created item, null if item is not created
	 */
	private function createItemInternal($itemIndex, $itemType)
	{
		if (($item = $this->createItem($itemIndex, $itemType)) !== null) {
			$param = new TDataListItemEventParameter($item);
			$this->onItemCreated($param);
			$this->getControls()->add($item);
			return $item;
		} else {
			return null;
		}
	}

	/**
	 * Creates a datalist item and performs databinding.
	 * This method invokes {@link createItem} to create a new datalist item.
	 * @param int $itemIndex zero-based item index.
	 * @param TListItemType $itemType item type
	 * @param mixed $dataItem data to be associated with the item
	 * @return \Prado\Web\UI\TControl the created item, null if item is not created
	 */
	private function createItemWithDataInternal($itemIndex, $itemType, $dataItem)
	{
		if (($item = $this->createItem($itemIndex, $itemType)) !== null) {
			$param = new TDataListItemEventParameter($item);
			if ($item instanceof \Prado\IDataRenderer) {
				$item->setData($dataItem);
			}
			$this->onItemCreated($param);
			$this->getControls()->add($item);
			$item->dataBind();
			$this->onItemDataBound($param);
			return $item;
		} else {
			return null;
		}
	}

	private function getAlternatingItemDisplay()
	{
		if (($classPath = $this->getAlternatingItemRenderer()) === '' && $this->_alternatingItemTemplate === null) {
			return [$this->getItemRenderer(), $this->_itemTemplate];
		} else {
			return [$classPath, $this->_alternatingItemTemplate];
		}
	}

	private function getSelectedItemDisplay($itemIndex)
	{
		if (($classPath = $this->getSelectedItemRenderer()) === '' && $this->_selectedItemTemplate === null) {
			if ($itemIndex % 2 === 0) {
				return [$this->getItemRenderer(), $this->_itemTemplate];
			} else {
				return $this->getAlternatingItemDisplay();
			}
		} else {
			return [$classPath, $this->_selectedItemTemplate];
		}
	}

	private function getEditItemDisplay($itemIndex)
	{
		if (($classPath = $this->getEditItemRenderer()) === '' && $this->_editItemTemplate === null) {
			return $this->getSelectedItemDisplay($itemIndex);
		} else {
			return [$classPath, $this->_editItemTemplate];
		}
	}

	/**
	 * Creates a datalist item instance based on the item type and index.
	 * @param int $itemIndex zero-based item index
	 * @param TListItemType $itemType item type
	 * @return \Prado\Web\UI\TControl created datalist item
	 */
	protected function createItem($itemIndex, $itemType)
	{
		$template = null;
		$classPath = null;
		switch ($itemType) {
			case TListItemType::Item:
				$classPath = $this->getItemRenderer();
				$template = $this->_itemTemplate;
				break;
			case TListItemType::AlternatingItem:
				[$classPath, $template] = $this->getAlternatingItemDisplay();
				break;
			case TListItemType::SelectedItem:
				[$classPath, $template] = $this->getSelectedItemDisplay($itemIndex);
				break;
			case TListItemType::EditItem:
				[$classPath, $template] = $this->getEditItemDisplay($itemIndex);
				break;
			case TListItemType::Header:
				$classPath = $this->getHeaderRenderer();
				$template = $this->_headerTemplate;
				break;
			case TListItemType::Footer:
				$classPath = $this->getFooterRenderer();
				$template = $this->_footerTemplate;
				break;
			case TListItemType::Separator:
				$classPath = $this->getSeparatorRenderer();
				$template = $this->_separatorTemplate;
				break;
			default:
				throw new TInvalidDataValueException('datalist_itemtype_unknown', $itemType);
		}
		if ($classPath !== '') {
			$item = Prado::createComponent($classPath);
			if ($item instanceof IItemDataRenderer) {
				$item->setItemIndex($itemIndex);
				$item->setItemType($itemType);
			}
		} elseif ($template !== null) {
			$item = new TDataListItem;
			$item->setItemIndex($itemIndex);
			$item->setItemType($itemType);
			$template->instantiateIn($item);
		} else {
			$item = null;
		}

		return $item;
	}

	/**
	 * Creates empty datalist content.
	 */
	protected function createEmptyContent()
	{
		if (($classPath = $this->getEmptyRenderer()) !== '') {
			$this->getControls()->add(Prado::createComponent($classPath));
		} elseif ($this->_emptyTemplate !== null) {
			$this->_emptyTemplate->instantiateIn($this);
		}
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
		$itemStyle = $this->getViewState('ItemStyle', null);

		$alternatingItemStyle = $this->getViewState('AlternatingItemStyle', null);
		if ($itemStyle !== null) {
			if ($alternatingItemStyle === null) {
				$alternatingItemStyle = $itemStyle;
			} else {
				$alternatingItemStyle->mergeWith($itemStyle);
			}
		}

		$selectedItemStyle = $this->getViewState('SelectedItemStyle', null);

		$editItemStyle = $this->getViewState('EditItemStyle', null);
		if ($selectedItemStyle !== null) {
			if ($editItemStyle === null) {
				$editItemStyle = $selectedItemStyle;
			} else {
				$editItemStyle->mergeWith($selectedItemStyle);
			}
		}

		// apply header style if any
		if ($this->_header !== null && $this->_header instanceof IStyleable) {
			if ($headerStyle = $this->getViewState('HeaderStyle', null)) {
				$this->_header->getStyle()->mergeWith($headerStyle);
			}
		}

		// apply footer style if any
		if ($this->_footer !== null && $this->_footer instanceof IStyleable) {
			if ($footerStyle = $this->getViewState('FooterStyle', null)) {
				$this->_footer->getStyle()->mergeWith($footerStyle);
			}
		}

		$selectedIndex = $this->getSelectedItemIndex();
		$editIndex = $this->getEditItemIndex();

		// apply item styles if any
		foreach ($this->getItems() as $index => $item) {
			if ($index === $editIndex) {
				$style = $editItemStyle;
			} elseif ($index === $selectedIndex) {
				$style = $selectedItemStyle;
			} elseif ($index % 2 === 0) {
				$style = $itemStyle;
			} else {
				$style = $alternatingItemStyle;
			}
			if ($style && $item instanceof IStyleable) {
				$item->getStyle()->mergeWith($style);
			}
		}

		// apply separator style if any
		if (($separatorStyle = $this->getViewState('SeparatorStyle', null)) !== null && $this->getHasSeparators()) {
			$controls = $this->getControls();
			$count = $controls->getCount();
			for ($i = $this->_header ? 2 : 1; $i < $count; $i += 2) {
				if (($separator = $controls->itemAt($i)) instanceof IStyleable) {
					$separator->getStyle()->mergeWith($separatorStyle);
				}
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
		if ($this->_items) {
			$this->setViewState('ItemCount', $this->_items->getCount(), 0);
		} else {
			$this->clearViewState('ItemCount');
		}
	}

	/**
	 * Loads item count information from viewstate.
	 * This method is invoked right after control state is loaded.
	 */
	public function loadState()
	{
		parent::loadState();
		if (!$this->getIsDataBound()) {
			$this->restoreItemsFromViewState();
		}
		$this->clearViewState('ItemCount');
	}

	/**
	 * Clears up all items in the data list.
	 */
	public function reset()
	{
		$this->getControls()->clear();
		$this->getItems()->clear();
		$this->_header = null;
		$this->_footer = null;
	}

	/**
	 * Creates data list items based on viewstate information.
	 */
	protected function restoreItemsFromViewState()
	{
		$this->reset();
		if (($itemCount = $this->getViewState('ItemCount', 0)) > 0) {
			$items = $this->getItems();
			$selectedIndex = $this->getSelectedItemIndex();
			$editIndex = $this->getEditItemIndex();
			$hasSeparator = $this->_separatorTemplate !== null || $this->getSeparatorRenderer() !== '';
			$this->_header = $this->createItemInternal(-1, TListItemType::Header);
			for ($i = 0; $i < $itemCount; ++$i) {
				if ($hasSeparator && $i > 0) {
					$this->createItemInternal($i - 1, TListItemType::Separator);
				}
				if ($i === $editIndex) {
					$itemType = TListItemType::EditItem;
				} elseif ($i === $selectedIndex) {
					$itemType = TListItemType::SelectedItem;
				} else {
					$itemType = $i % 2 ? TListItemType::AlternatingItem : TListItemType::Item;
				}
				$items->add($this->createItemInternal($i, $itemType));
			}
			$this->_footer = $this->createItemInternal(-1, TListItemType::Footer);
		} else {
			$this->createEmptyContent();
		}
		$this->clearChildState();
	}

	/**
	 * Performs databinding to populate data list items from data source.
	 * This method is invoked by dataBind().
	 * You may override this function to provide your own way of data population.
	 * @param \Traversable $data the data
	 */
	protected function performDataBinding($data)
	{
		$this->reset();
		$keys = $this->getDataKeys();
		$keys->clear();
		$keyField = $this->getDataKeyField();
		$itemIndex = 0;
		$items = $this->getItems();
		$hasSeparator = $this->_separatorTemplate !== null || $this->getSeparatorRenderer() !== '';
		$selectedIndex = $this->getSelectedItemIndex();
		$editIndex = $this->getEditItemIndex();
		foreach ($data as $key => $dataItem) {
			if ($keyField !== '') {
				$keys->add($this->getDataFieldValue($dataItem, $keyField));
			} else {
				$keys->add($key);
			}
			if ($itemIndex === 0) {
				$this->_header = $this->createItemWithDataInternal(-1, TListItemType::Header, null);
			}
			if ($hasSeparator && $itemIndex > 0) {
				$this->createItemWithDataInternal($itemIndex - 1, TListItemType::Separator, null);
			}
			if ($itemIndex === $editIndex) {
				$itemType = TListItemType::EditItem;
			} elseif ($itemIndex === $selectedIndex) {
				$itemType = TListItemType::SelectedItem;
			} else {
				$itemType = $itemIndex % 2 ? TListItemType::AlternatingItem : TListItemType::Item;
			}
			$items->add($this->createItemWithDataInternal($itemIndex, $itemType, $dataItem));
			$itemIndex++;
		}
		if ($itemIndex > 0) {
			$this->_footer = $this->createItemWithDataInternal(-1, TListItemType::Footer, null);
		} else {
			$this->createEmptyContent();
			$this->dataBindChildren();
		}
		$this->setViewState('ItemCount', $itemIndex, 0);
	}

	/**
	 * Renders the data list control.
	 * This method overrides the parent implementation.
	 * @param \Prado\Web\UI\THtmlWriter $writer writer for rendering purpose.
	 */
	public function render($writer)
	{
		if ($this->getHasControls()) {
			if ($this->getItemCount() > 0) {
				$this->applyItemStyles();
				$repeatInfo = $this->getRepeatInfo();
				$repeatInfo->renderRepeater($writer, $this);
			} elseif ($this->_emptyTemplate !== null || $this->getEmptyRenderer() !== '') {
				parent::render($writer);
			}
		}
	}
}
