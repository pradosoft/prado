<?php
/**
 * TEditCommandColumn class file
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
 * TEditCommandColumn class
 *
 * TEditCommandColumn contains the Edit command buttons for editing data items in each row.
 *
 * TEditCommandColumn will create an edit button if a cell is not in edit mode.
 * Otherwise an update button and a cancel button will be created within the cell.
 * The button captions are specified using <b>EditText</b>, <b>UpdateText</b>
 * and <b>CancelText</b>.
 *
 * The buttons in the column can be set to display as hyperlinks or push buttons
 * by setting the <b>ButtonType</b> property.
 *
 * When an edit button is clicked, the datagrid will generate an <b>OnEditCommand</b>
 * event. When an update/cancel button is clicked, the datagrid will generate an
 * <b>OnUpdateCommand</b> or an <b>OnCancelCommand</b>. You can write these event handlers
 * to change the state of specific datagrid item.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TEditCommandColumn extends TDataGridColumn
{
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
		$this->onColumnChanged();
	}

	/**
	 * @return string the caption of the edit button
	 */
	public function getEditText()
	{
		return $this->getViewState('EditText','');
	}

	/**
	 * @param string the caption of the edit button
	 */
	public function setEditText($value)
	{
		$this->setViewState('EditText',$value,'');
		$this->onColumnChanged();
	}

	/**
	 * @return string the caption of the update button
	 */
	public function getUpdateText()
	{
		return $this->getViewState('UpdateText','');
	}

	/**
	 * @param string the caption of the update button
	 */
	public function setUpdateText($value)
	{
		$this->setViewState('UpdateText',$value,'');
		$this->onColumnChanged();
	}

	/**
	 * @return string the caption of the cancel button
	 */
	public function getCancelText()
	{
		return $this->getViewState('CancelText','');
	}

	/**
	 * @param string the caption of the cancel button
	 */
	public function setCancelText($value)
	{
		$this->setViewState('CancelText',$value,'');
		$this->onColumnChanged();
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
		$this->onColumnChanged();
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
		$this->onColumnChanged();
	}

	/**
	 * Initializes the specified cell to its initial values.
	 * This method overrides the parent implementation.
	 * It creates an update and a cancel button for cell in edit mode.
	 * Otherwise it creates an edit button.
	 * @param TTableCell the cell to be initialized.
	 * @param integer the index to the Columns property that the cell resides in.
	 * @param string the type of cell (Header,Footer,Item,AlternatingItem,EditItem,SelectedItem)
	 */
	public function initializeCell($cell,$columnIndex,$itemType)
	{
		parent::initializeCell($cell,$columnIndex,$itemType);
		$buttonType=$this->getButtonType()=='LinkButton'?'TLinkButton':'TButton';
		if($itemType==='Item' || $itemType==='AlternatingItem' || $itemType==='SelectedItem')
			$this->addButtonToCell($cell,'Edit',$this->getUpdateText(),false,'');
		else if($itemType==='EditItem')
		{
			$this->addButtonToCell($cell,'Update',$this->getUpdateText(),$this->getCausesValidation(),$this->getValidationGroup());
			$cell->getControls()->add('&nbsp;');
			$this->addButtonToCell($cell,'Cancel',$this->getUpdateText(),false,'');
		}
	}

	private function addButtonToCell($cell,$commandName,$text,$causesValidation,$validationGroup)
	{
		if($this->getButtonType()==='LinkButton')
			$button=Prado::createComponent('System.Web.UI.WebControls.TLinkButton');
		else
			$button=Prado::createComponent('System.Web.UI.WebControls.TButton');
		$button->setText($text);
		$button->setCommandName($commandName);
		$button->setCausesValidation($causesValidation);
		$button->setValidationGroup($validationGroup);
		$cell->getControls()->add($button);
	}
}

?>