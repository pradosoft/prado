<?php
/**
 * TCheckBoxColumn class file
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
 * TCheckBoxColumn class
 *
 * TCheckBoxColumn represents a column that is bound to a field in a data source.
 * The cells in the column will be displayed using the data indexed by
 * <b>DataField</b>. You can customize the display by setting <b>DataFormatString</b>.
 *
 * If <b>ReadOnly</b> is false, TCheckBoxColumn will display cells in edit mode
 * with textboxes. Otherwise, a static text is displayed.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TCheckBoxColumn extends TDataGridColumn
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
		if($itemType==='EditItem' || $itemType==='Item'
				|| $itemType==='AlternatingItem' || $itemType==='SelectedItem')
		{
			$checkBox=Prado::createComponent('System.Web.UI.WebControls.TCheckBox');
			if($this->getReadOnly() || $itemType!=='EditItem')
				$checkBox->setEnabled(false);
			$cell->setHorizontalAlign('Center');
			$cell->getControls()->add($checkBox);
			if(($dataField=$this->getDataField())!=='')
				$checkBox->attachEventHandler('OnDataBinding',array($this,'dataBindColumn'));
		}
	}

	public function dataBindColumn($sender,$param)
	{
		$item=$sender->getNamingContainer();
		$data=$item->getDataItem();
		if(($field=$this->getDataField())!=='')
			$value=TPropertyValue::ensureBoolean($this->getDataFieldValue($data,$field));
		else
			$value=TPropertyValue::ensureBoolean($data);
		if($sender instanceof TCheckBox)
			$sender->setChecked($value);
	}
}

?>