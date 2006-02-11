<?php
/**
 * TButtonColumn class file
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
 * TButtonColumn class
 *
 * TButtonColumn contains a user-defined command button, such as Add or Remove,
 * that corresponds with each row in the column.
 *
 * The caption of the buttons in the column is determined by {@link setText Text}
 * and {@link setDataTextField DataTextField} properties. If both are present,
 * the latter takes precedence. The {@link setDataTextField DataTextField} property
 * refers to the name of the field in datasource whose value will be used as the button caption.
 * If {@link setDataTextFormatString DataTextFormatString} is not empty,
 * the value will be formatted before rendering.
 *
 * The buttons in the column can be set to display as hyperlinks or push buttons
 * by setting the {@link setButtonType ButtonType} property.
 * The {@link setCommandName CommandName} will assign its value to
 * all button's <b>CommandName</b> property. The datagrid will capture
 * the command event where you can write event handlers based on different command names.
 * The buttons' <b>CausesValidation</b> and <b>ValidationGroup</b> property values
 * are determined by the column's corresponding properties.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TButtonColumn extends TDataGridColumn
{
	/**
	 * @return string the text caption of the button
	 */
	public function getText()
	{
		return $this->getViewState('Text','');
	}

	/**
	 * Sets the text caption of the button.
	 * @param string the text caption to be set
	 */
	public function setText($value)
	{
		$this->setViewState('Text',$value,'');
	}

	/**
	 * @return string the field name from the data source to bind to the button caption
	 */
	public function getDataTextField()
	{
		return $this->getViewState('DataTextField','');
	}

	/**
	 * @param string the field name from the data source to bind to the button caption
	 */
	public function setDataTextField($value)
	{
		$this->setViewState('DataTextField',$value,'');
	}

	/**
	 * @return string the formatting string used to control how the button caption will be displayed.
	 */
	public function getDataTextFormatString()
	{
		return $this->getViewState('DataTextFormatString','');
	}

	/**
	 * @param string the formatting string used to control how the button caption will be displayed.
	 */
	public function setDataTextFormatString($value)
	{
		$this->setViewState('DataTextFormatString',$value,'');
	}

	/**
	 * @return string the type of command button. Defaults to LinkButton.
	 */
	public function getButtonType()
	{
		return $this->getViewState('ButtonType','LinkButton');
	}

	/**
	 * @param string the type of command button, LinkButton or PushButton
	 */
	public function setButtonType($value)
	{
		$this->setViewState('ButtonType',TPropertyValue::ensureEnum($value,'LinkButton','PushButton'),'LinkButton');
	}

	/**
	 * @return string the command name associated with the <b>OnCommand</b> event.
	 */
	public function getCommandName()
	{
		return $this->getViewState('CommandName','');
	}

	/**
	 * Sets the command name associated with the <b>Command</b> event.
	 * @param string the text caption to be set
	 */
	public function setCommandName($value)
	{
		$this->setViewState('CommandName',$value,'');
	}

	/**
	 * @return boolean whether postback event trigger by this button will cause input validation, default is true
	 */
	public function getCausesValidation()
	{
		return $this->getViewState('CausesValidation',true);
	}

	/**
	 * @param boolean whether postback event trigger by this button will cause input validation
	 */
	public function setCausesValidation($value)
	{
		$this->setViewState('CausesValidation',TPropertyValue::ensureBoolean($value),true);
	}

	/**
	 * @return string the group of validators which the button causes validation upon postback
	 */
	public function getValidationGroup()
	{
		return $this->getViewState('ValidationGroup','');
	}

	/**
	 * @param string the group of validators which the button causes validation upon postback
	 */
	public function setValidationGroup($value)
	{
		$this->setViewState('ValidationGroup',$value,'');
	}

	/**
	 * Initializes the specified cell to its initial values.
	 * This method overrides the parent implementation.
	 * It creates a command button within the cell.
	 * @param TTableCell the cell to be initialized.
	 * @param integer the index to the Columns property that the cell resides in.
	 * @param string the type of cell (Header,Footer,Item,AlternatingItem,EditItem,SelectedItem)
	 */
	public function initializeCell($cell,$columnIndex,$itemType)
	{
		parent::initializeCell($cell,$columnIndex,$itemType);
		if($itemType==='Item' || $itemType==='AlternatingItem' || $itemType==='SelectedItem' || $itemType==='EditItem')
		{
			if($this->getButtonType()==='LinkButton')
				$button=Prado::createComponent('System.Web.UI.WebControls.TLinkButton');
			else
				$button=Prado::createComponent('System.Web.UI.WebControls.TButton');
			$button->setText($this->getText());
			$button->setCommandName($this->getCommandName());
			$button->setCausesValidation($this->getCausesValidation());
			$button->setValidationGroup($this->getValidationGroup());
			if($this->getDataTextField()!=='')
				$button->attachEventHandler('OnDataBinding',array($this,'dataBindColumn'));
			$cell->getControls()->add($button);
		}
	}

	/**
	 * Databinds a cell in the column.
	 * This method is invoked when datagrid performs databinding.
	 * It populates the content of the cell with the relevant data from data source.
	 */
	public function dataBindColumn($sender,$param)
	{
		if(($field=$this->getDataTextField())!=='')
		{
			$item=$sender->getNamingContainer();
			$data=$item->getDataItem();
			$value=$this->getDataFieldValue($data,$field);
			$text=$this->formatDataValue($this->getDataTextFormatString(),$value);
			if(($sender instanceof TLinkButton) || ($sender instanceof TButton))
				$sender->setText($text);
		}
	}
}

?>