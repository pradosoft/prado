<?php
/**
 * TTemplateColumn class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

use Prado\Web\UI\ITemplate;
use Prado\Exceptions\TInvalidDataTypeException;
use Prado\Prado;

/**
 * TTemplateColumn class
 *
 * TTemplateColumn customizes the layout of controls in the column with templates.
 * In particular, you can specify {@link setItemTemplate ItemTemplate},
 * {@link setEditItemTemplate EditItemTemplate}, {@link setHeaderTemplate HeaderTemplate}
 * and {@link setFooterTemplate FooterTemplate} to customize specific
 * type of cells in the column.
 *
 * Since v3.1.0, TTemplateColumn has introduced two new properties {@link setItemRenderer ItemRenderer}
 * and {@link setEditItemRenderer EditItemRenderer} which can be used to specify
 * the layout of the datagrid cells in browsing and editing mode.
 * A renderer refers to a control class that is to be instantiated as a control.
 * For more details, see {@link TRepeater} and {@link TDataList}.
 *
 * When a renderer and a template are both defined for a type of item, the former
 * takes precedence.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI\WebControls
 * @since 3.0
 */
class TTemplateColumn extends TDataGridColumn
{
	/**
	 * Various item templates
	 * @var ITemplate
	 */
	private $_itemTemplate;
	private $_editItemTemplate;
	private $_headerTemplate;
	private $_footerTemplate;

	/**
	 * @return string the class name for the item cell renderer. Defaults to empty, meaning not set.
	 * @since 3.1.0
	 */
	public function getItemRenderer()
	{
		return $this->getViewState('ItemRenderer', '');
	}

	/**
	 * Sets the item cell renderer class.
	 *
	 * If not empty, the class will be used to instantiate as a child control in the item cells of the column.
	 *
	 * If the class implements {@link \Prado\IDataRenderer}, the <b>Data</b> property
	 * will be set as the row of the data associated with the datagrid item that this cell resides in.
	 *
	 * @param string $value the renderer class name in namespace format.
	 * @since 3.1.0
	 */
	public function setItemRenderer($value)
	{
		$this->setViewState('ItemRenderer', $value, '');
	}

	/**
	 * @return string the class name for the edit item cell renderer. Defaults to empty, meaning not set.
	 * @since 3.1.0
	 */
	public function getEditItemRenderer()
	{
		return $this->getViewState('EditItemRenderer', '');
	}

	/**
	 * Sets the edit item cell renderer class.
	 *
	 * If not empty, the class will be used to instantiate as a child control in the item cell that is in edit mode.
	 *
	 * If the class implements {@link \Prado\IDataRenderer}, the <b>Data</b> property
	 * will be set as the row of the data associated with the datagrid item that this cell resides in.
	 *
	 * @param string $value the renderer class name in namespace format.
	 * @since 3.1.0
	 */
	public function setEditItemRenderer($value)
	{
		$this->setViewState('EditItemRenderer', $value, '');
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
			throw new TInvalidDataTypeException('templatecolumn_template_required', 'EditItemTemplate');
		}
	}

	/**
	 * @return \Prado\Web\UI\ITemplate the item template
	 */
	public function getItemTemplate()
	{
		return $this->_itemTemplate;
	}

	/**
	 * @param \Prado\Web\UI\ITemplate $value the item template
	 * @throws TInvalidDataTypeException if the input is not an {@link ITemplate} or not null.
	 */
	public function setItemTemplate($value)
	{
		if ($value instanceof ITemplate || $value === null) {
			$this->_itemTemplate = $value;
		} else {
			throw new TInvalidDataTypeException('templatecolumn_template_required', 'ItemTemplate');
		}
	}

	/**
	 * @return \Prado\Web\UI\ITemplate the header template
	 */
	public function getHeaderTemplate()
	{
		return $this->_headerTemplate;
	}

	/**
	 * @param \Prado\Web\UI\ITemplate $value the header template.
	 * @throws TInvalidDataTypeException if the input is not an {@link ITemplate} or not null.
	 */
	public function setHeaderTemplate($value)
	{
		if ($value instanceof ITemplate || $value === null) {
			$this->_headerTemplate = $value;
		} else {
			throw new TInvalidDataTypeException('templatecolumn_template_required', 'HeaderTemplate');
		}
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
			throw new TInvalidDataTypeException('templatecolumn_template_required', 'FooterTemplate');
		}
	}

	/**
	 * Initializes the specified cell to its initial values.
	 * This method overrides the parent implementation.
	 * It initializes the cell based on different templates
	 * (ItemTemplate, EditItemTemplate, HeaderTemplate, FooterTemplate).
	 * @param TTableCell $cell the cell to be initialized.
	 * @param int $columnIndex the index to the Columns property that the cell resides in.
	 * @param string $itemType the type of cell (Header,Footer,Item,AlternatingItem,EditItem,SelectedItem)
	 */
	public function initializeCell($cell, $columnIndex, $itemType)
	{
		if ($itemType === TListItemType::Item || $itemType === TListItemType::AlternatingItem || $itemType === TListItemType::SelectedItem || $itemType === TListItemType::EditItem) {
			$template = null;
			$classPath = '';
			if ($itemType === TListItemType::EditItem) {
				if (($classPath = $this->getEditItemRenderer()) === '' && ($template = $this->_editItemTemplate) === null) {
					$classPath = $this->getItemRenderer();
					$template = $this->_itemTemplate;
				}
			} else {
				$template = $this->_itemTemplate;
				$classPath = $this->getItemRenderer();
			}
			if ($classPath !== '') {
				$control = Prado::createComponent($classPath);
				$cell->getControls()->add($control);
				if ($control instanceof IItemDataRenderer) {
					$control->setItemIndex($cell->getParent()->getItemIndex());
					$control->setItemType($itemType);
				}
				if ($control instanceof \Prado\IDataRenderer) {
					$control->attachEventHandler('OnDataBinding', [$this, 'dataBindColumn']);
				}
			} elseif ($template !== null) {
				$template->instantiateIn($cell);
			} elseif ($itemType !== TListItemType::EditItem) {
				$cell->setText('&nbsp;');
			}
		} elseif ($itemType === TListItemType::Header) {
			if (($classPath = $this->getHeaderRenderer()) !== '') {
				$this->initializeHeaderCell($cell, $columnIndex);
			} elseif ($this->_headerTemplate !== null) {
				$this->_headerTemplate->instantiateIn($cell);
			} else {
				$this->initializeHeaderCell($cell, $columnIndex);
			}
		} elseif ($itemType === TListItemType::Footer) {
			if (($classPath = $this->getFooterRenderer()) !== '') {
				$this->initializeFooterCell($cell, $columnIndex);
			} elseif ($this->_footerTemplate !== null) {
				$this->_footerTemplate->instantiateIn($cell);
			} else {
				$this->initializeFooterCell($cell, $columnIndex);
			}
		}
	}

	/**
	 * Databinds a cell in the column.
	 * This method is invoked when datagrid performs databinding.
	 * It populates the content of the cell with the relevant data from data source.
	 * @param mixed $sender
	 * @param mixed $param
	 */
	public function dataBindColumn($sender, $param)
	{
		$item = $sender->getNamingContainer();
		$sender->setData($item->getData());
	}
}
