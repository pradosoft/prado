<?php
/**
 * TActiveDataGrid class file
 *
 * @author LANDWEHR Computer und Software GmbH <programmierung@landwehr-software.de>
 * @link http://www.landwehr-software.de/
 * @copyright Copyright &copy; 2009 LANDWEHR Computer und Software GmbH
 * @license http://www.pradosoft.com/license/
 * @package System.Web.UI.ActiveControls
 */

/**
 * TActiveCheckBoxColumn class
 *
 * TActiveCheckBoxColumn represents a checkbox column that is bound to a field in a data source.
 *
 * This is the active counterpart to the {@link TCheckBoxColumn} control. For that purpose,
 * if sorting is allowed, the header links/buttons are replaced by active controls.
 *
 * Please refer to the original documentation of the {@link TCheckBoxColumn} for usage.
 *
 * @author LANDWEHR Computer und Software GmbH <programmierung@landwehr-software.de>
 * @package System.Web.UI.ActiveControls
 * @since 3.1.9
 */
class TActiveCheckBoxColumn extends TCheckBoxColumn
{
	/**
	 * Initializes the specified cell to its initial values.
	 * This method overrides the parent implementation.
	 * It creates a checkbox inside the cell.
	 * If the column is read-only or if the item is not in edit mode,
	 * the checkbox will be set disabled.
	 * @param TTableCell the cell to be initialized.
	 * @param integer the index to the Columns property that the cell resides in.
	 * @param string the type of cell (Header,Footer,Item,AlternatingItem,EditItem,SelectedItem)
	 */
	public function initializeCell($cell,$columnIndex,$itemType)
	{
		if($itemType===TListItemType::Item || $itemType===TListItemType::AlternatingItem || $itemType===TListItemType::SelectedItem || $itemType===TListItemType::EditItem)
		{
			$checkBox=new TActiveCheckBox;
			if($this->getReadOnly() || $itemType!==TListItemType::EditItem)
				$checkBox->setEnabled(false);
			$cell->setHorizontalAlign('Center');
			$cell->getControls()->add($checkBox);
			$cell->registerObject('CheckBox',$checkBox);
			if($this->getDataField()!=='')
				$checkBox->attachEventHandler('OnDataBinding',array($this,'dataBindColumn'));
		}
		else
			parent::initializeCell($cell,$columnIndex,$itemType);
	}

	protected function initializeHeaderCell($cell,$columnIndex)
	{
		$text=$this->getHeaderText();

		if(($classPath=$this->getHeaderRenderer())!=='')
		{
			$control=Prado::createComponent($classPath);
			if($control instanceof IDataRenderer)
			{
				if($control instanceof IItemDataRenderer)
				{
					$item=$cell->getParent();
					$control->setItemIndex($item->getItemIndex());
					$control->setItemType($item->getItemType());
				}
				$control->setData($text);
			}
			$cell->getControls()->add($control);
		}
		else if($this->getAllowSorting())
		{
			$sortExpression=$this->getSortExpression();
			if(($url=$this->getHeaderImageUrl())!=='')
			{
				$button=Prado::createComponent('System.Web.UI.WebControls.TActiveImageButton');
				$button->setImageUrl($url);
				$button->setCommandName(TDataGrid::CMD_SORT);
				$button->setCommandParameter($sortExpression);
				if($text!=='')
					$button->setAlternateText($text);
				$button->setCausesValidation(false);
				$cell->getControls()->add($button);
			}
			else if($text!=='')
			{
				$button=Prado::createComponent('System.Web.UI.WebControls.TActiveLinkButton');
				$button->setText($text);
				$button->setCommandName(TDataGrid::CMD_SORT);
				$button->setCommandParameter($sortExpression);
				$button->setCausesValidation(false);
				$cell->getControls()->add($button);
			}
			else
				$cell->setText('&nbsp;');
		}
		else
		{
			if(($url=$this->getHeaderImageUrl())!=='')
			{
				$image=Prado::createComponent('System.Web.UI.WebControls.TActiveImage');
				$image->setImageUrl($url);
				if($text!=='')
					$image->setAlternateText($text);
				$cell->getControls()->add($image);
			}
			else if($text!=='')
				$cell->setText($text);
			else
				$cell->setText('&nbsp;');
		}
	}
}