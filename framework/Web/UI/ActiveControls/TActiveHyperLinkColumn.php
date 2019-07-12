<?php
/**
 * TActiveDataGrid class file
 *
 * @author LANDWEHR Computer und Software GmbH <programmierung@landwehr-software.de>
 * @link http://www.landwehr-software.de/
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\ActiveControls
 */

namespace Prado\Web\UI\ActiveControls;

use Prado\Prado;
use Prado\Web\UI\WebControls\IItemDataRenderer;
use Prado\Web\UI\WebControls\TDataGrid;
use Prado\Web\UI\WebControls\THyperLinkColumn;

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
 * @package Prado\Web\UI\ActiveControls
 * @since 3.1.9
 */
class TActiveHyperLinkColumn extends THyperLinkColumn
{
	protected function initializeHeaderCell($cell, $columnIndex)
	{
		$text = $this->getHeaderText();

		if (($classPath = $this->getHeaderRenderer()) !== '') {
			$control = Prado::createComponent($classPath);
			if ($control instanceof \Prado\IDataRenderer) {
				if ($control instanceof IItemDataRenderer) {
					$item = $cell->getParent();
					$control->setItemIndex($item->getItemIndex());
					$control->setItemType($item->getItemType());
				}
				$control->setData($text);
			}
			$cell->getControls()->add($control);
		} elseif ($this->getAllowSorting()) {
			$sortExpression = $this->getSortExpression();
			if (($url = $this->getHeaderImageUrl()) !== '') {
				$button = new TActiveImageButton;
				$button->setImageUrl($url);
				$button->setCommandName(TDataGrid::CMD_SORT);
				$button->setCommandParameter($sortExpression);
				if ($text !== '') {
					$button->setAlternateText($text);
				}
				$button->setCausesValidation(false);
				$cell->getControls()->add($button);
			} elseif ($text !== '') {
				$button = new TActiveLinkButton;
				$button->setText($text);
				$button->setCommandName(TDataGrid::CMD_SORT);
				$button->setCommandParameter($sortExpression);
				$button->setCausesValidation(false);
				$cell->getControls()->add($button);
			} else {
				$cell->setText('&nbsp;');
			}
		} else {
			if (($url = $this->getHeaderImageUrl()) !== '') {
				$image = new TActiveImage;
				$image->setImageUrl($url);
				if ($text !== '') {
					$image->setAlternateText($text);
				}
				$cell->getControls()->add($image);
			} elseif ($text !== '') {
				$cell->setText($text);
			} else {
				$cell->setText('&nbsp;');
			}
		}
	}
}
