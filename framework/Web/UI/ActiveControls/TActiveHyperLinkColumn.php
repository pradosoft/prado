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
 * TActiveHyperLinkColumn class
 *
 * TActiveHyperLinkColumn contains a hyperlink for each item in the column.
 *
 * This is the active counterpart to the {@link THyperLinkColumn} control. For that purpose,
 * if sorting is allowed, the header links/buttons are replaced by active controls.
 *
 * Please refer to the original documentation of the {@link THyperLinkColumn} for usage.
 *
 * @author LANDWEHR Computer und Software GmbH <programmierung@landwehr-software.de>
 * @package System.Web.UI.ActiveControls
 * @since 3.1.9
 */
class TActiveHyperLinkColumn extends THyperLinkColumn
{

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