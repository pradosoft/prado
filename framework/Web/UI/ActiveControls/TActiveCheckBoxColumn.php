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
use Prado\Web\UI\WebControls\TCheckBoxColumn;
use Prado\Web\UI\WebControls\TDataGrid;
use Prado\Web\UI\WebControls\TListItemType;

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
 * @package Prado\Web\UI\ActiveControls
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
	 * @param TTableCell $cell the cell to be initialized.
	 * @param int $columnIndex the index to the Columns property that the cell resides in.
	 * @param string $itemType the type of cell (Header,Footer,Item,AlternatingItem,EditItem,SelectedItem)
	 */
	public function initializeCell($cell, $columnIndex, $itemType)
	{
		if ($itemType === TListItemType::Item || $itemType === TListItemType::AlternatingItem || $itemType === TListItemType::SelectedItem || $itemType === TListItemType::EditItem) {
			$checkBox = new TActiveCheckBox;
			if ($this->getReadOnly() || $itemType !== TListItemType::EditItem) {
				$checkBox->setEnabled(false);
			}
			$cell->setHorizontalAlign('Center');
			$cell->getControls()->add($checkBox);
			$cell->registerObject('CheckBox', $checkBox);
			if ($this->getDataField() !== '') {
				$checkBox->attachEventHandler('OnDataBinding', [$this, 'dataBindColumn']);
			}
		} else {
			parent::initializeCell($cell, $columnIndex, $itemType);
		}
	}

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
