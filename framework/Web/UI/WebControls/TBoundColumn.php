<?php
/**
 * TBoundColumn class file
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
 * TBoundColumn class
 *
 * TBoundColumn represents a column that is bound to a field in a data source.
 * The cells in the column will be displayed using the data indexed by
 * <b>DataField</b>. You can customize the display by setting <b>DataFormatString</b>.
 *
 * If <b>ReadOnly</b> is false, TBoundColumn will display cells in edit mode
 * with textboxes. Otherwise, a static text is displayed.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TBoundColumn extends TDataGridColumn
{
	/**
	 * @return string the field name from the data source to bind to the column
	 */
	public function getDataField()
	{
		return $this->getViewState('DataField','');
	}

	/**
	 * @param string the field name from the data source to bind to the column
	 */
	public function setDataField($value)
	{
		$this->setViewState('DataField',$value,'');
		$this->onColumnChanged();
	}

	/**
	 * @return string the formatting string used to control how the bound data will be displayed.
	 */
	public function getDataFormatString()
	{
		return $this->getViewState('DataFormatString','');
	}

	/**
	 * @param string the formatting string used to control how the bound data will be displayed.
	 */
	public function setDataFormatString($value)
	{
		$this->setViewState('DataFormatString',$value,'');
		$this->onColumnChanged();
	}

	/**
	 * @return boolean whether the items in the column can be edited. Defaults to false.
	 */
	public function getReadOnly()
	{
		return $this->getViewState('ReadOnly',false);
	}

	/**
	 * @param boolean whether the items in the column can be edited
	 */
	public function setReadOnly($value)
	{
		$this->setViewState('ReadOnly',TPropertyValue::ensureBoolean($value),false);
		$this->onColumnChanged();
	}

	/**
	 * Initializes the specified cell to its initial values.
	 * This method overrides the parent implementation.
	 * It creates a textbox for item in edit mode and the column is not read-only.
	 * Otherwise it displays a static text.
	 * The caption of the button and the static text are retrieved
	 * from the datasource.
	 * @param TTableCell the cell to be initialized.
	 * @param integer the index to the Columns property that the cell resides in.
	 * @param string the type of cell (Header,Footer,Item,AlternatingItem,EditItem,SelectedItem)
	 */
	public function initializeCell($cell,$columnIndex,$itemType)
	{
		parent::initializeCell($cell,$columnIndex,$itemType);
		switch($itemType)
		{
			case 'EditItem':
				$control=$cell;
				if(!$this->getReadOnly())
				{
					$textBox=Prado::createComponent('System.Web.UI.WebControls.TTextBox');
					$cell->getControls()->add($textBox);
					$control=$textBox;
				}
			case 'Item':
			case 'AlternatingItem':
			case 'SelectedItem':
				if(($dataField=$this->getDataField())!=='')
					$control->attachEventHandler('DataBinding',array($this,'dataBindColumn'));
				break;
		}
	}

	public function dataBindColumn($sender,$param)
	{
		$item=$sender->getNamingContainer();
		$data=$item->getDataItem();
		if(($field=$this->getDataField())!=='')
			$value=$this->formatDataValue($this->getDataFieldValue($data,$field));
		else
			$value=$this->formatDataValue($data);
		if(($sender instanceof TTableCell) || ($sender instanceof TTextBox))
			$sender->setText($value);
	}

	/**
	 * Formats the text value according to format string.
	 * This method is invoked when setting the text to a cell.
	 * This method can be overriden.
	 * @param mixed the data associated with the cell
	 * @return string the formatted result
	 */
	protected function formatDataValue($formatString,$value)
	{
		return $formatString===''?TPropertyValue::ensureString($value):sprintf($formatString,$value);
	}
}

?>