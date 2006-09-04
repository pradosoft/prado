<?php
/**
 * TTemplateColumn class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 */

/**
 * TDataGridColumn class file
 */
Prado::using('System.Web.UI.WebControls.TDataGridColumn');

/**
 * TTemplateColumn class
 *
 * TTemplateColumn customizes the layout of controls in the column with templates.
 * In particular, you can specify {@link setItemTemplate ItemTemplate},
 * {@link setEditItemTemplate EditItemTemplate}, {@link setHeaderTemplate HeaderTemplate}
 * and {@link setFooterTemplate FooterTemplate} to customize specific
 * type of cells in the column.
 *
 * Note, if {@link setHeaderTemplate HeaderTemplate} is not set, the column
 * header will be displayed with {@link setHeaderText HeaderText}.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TTemplateColumn extends TDataGridColumn
{
	/**
	 * Various item templates
	 * @var string
	 */
	private $_itemTemplate=null;
	private $_editItemTemplate=null;
	private $_headerTemplate=null;
	private $_footerTemplate=null;

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
			throw new TInvalidDataTypeException('templatecolumn_template_required','EditItemTemplate');
	}

	/**
	 * @return ITemplate the item template
	 */
	public function getItemTemplate()
	{
		return $this->_itemTemplate;
	}

	/**
	 * @param ITemplate the item template
	 * @throws TInvalidDataTypeException if the input is not an {@link ITemplate} or not null.
	 */
	public function setItemTemplate($value)
	{
		if($value instanceof ITemplate || $value===null)
			$this->_itemTemplate=$value;
		else
			throw new TInvalidDataTypeException('templatecolumn_template_required','ItemTemplate');
	}

	/**
	 * @return ITemplate the header template
	 */
	public function getHeaderTemplate()
	{
		return $this->_headerTemplate;
	}

	/**
	 * @param ITemplate the header template.
	 * @throws TInvalidDataTypeException if the input is not an {@link ITemplate} or not null.
	 */
	public function setHeaderTemplate($value)
	{
		if($value instanceof ITemplate || $value===null)
			$this->_headerTemplate=$value;
		else
			throw new TInvalidDataTypeException('templatecolumn_template_required','HeaderTemplate');
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
			throw new TInvalidDataTypeException('templatecolumn_template_required','FooterTemplate');
	}

	/**
	 * Initializes the specified cell to its initial values.
	 * This method overrides the parent implementation.
	 * It initializes the cell based on different templates
	 * (ItemTemplate, EditItemTemplate, HeaderTemplate, FooterTemplate).
	 * @param TTableCell the cell to be initialized.
	 * @param integer the index to the Columns property that the cell resides in.
	 * @param string the type of cell (Header,Footer,Item,AlternatingItem,EditItem,SelectedItem)
	 */
	public function initializeCell($cell,$columnIndex,$itemType)
	{
		parent::initializeCell($cell,$columnIndex,$itemType);
		$template=null;
		switch($itemType)
		{
			case TListItemType::Header:
				$template=$this->_headerTemplate;
				break;
			case TListItemType::Footer:
				$template=$this->_footerTemplate;
				break;
			case TListItemType::Item:
			case TListItemType::AlternatingItem:
			case TListItemType::SelectedItem:
				$template=$this->_itemTemplate;
				break;
			case TListItemType::EditItem:
				$template=$this->_editItemTemplate===null?$this->_itemTemplate:$this->_editItemTemplate;
				break;
		}
		if($template!==null)
		{
			$cell->setText('');
			$cell->getControls()->clear();
			$template->instantiateIn($cell);
		}
		else if($itemType===TListItemType::Item || $itemType===TListItemType::AlternatingItem || $itemType===TListItemType::SelectedItem || $itemType===TListItemType::EditItem)
			$cell->setText('&nbsp;');
	}
}

?>