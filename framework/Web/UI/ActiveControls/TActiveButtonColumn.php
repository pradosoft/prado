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

use Prado\Web\UI\WebControls\TButtonColumn;
use Prado\Web\UI\WebControls\TButtonColumnType;
use Prado\Web\UI\WebControls\TListItemType;

/**
 * TActiveButtonColumn class
 *
 * TActiveButtonColumn contains a user-defined command button, such as Add or Remove,
 * that corresponds with each row in the column.
 *
 * This is the active counterpart to the {@link TButtonColumn} control where the
 * button is replaced by the appropriate active button control.
 *
 * Please refer to the original documentation of the {@link TButtonColumn} for usage.
 *
 * @author LANDWEHR Computer und Software GmbH <programmierung@landwehr-software.de>
 * @package Prado\Web\UI\ActiveControls
 * @since 3.1.9
 */
class TActiveButtonColumn extends TButtonColumn
{
	public function initializeCell($cell, $columnIndex, $itemType)
	{
		if ($itemType === TListItemType::Item || $itemType === TListItemType::AlternatingItem || $itemType === TListItemType::SelectedItem || $itemType === TListItemType::EditItem) {
			$buttonType = $this->getButtonType();
			if ($buttonType === TButtonColumnType::LinkButton) {
				$button = new TActiveLinkButton;
			} elseif ($buttonType === TButtonColumnType::PushButton) {
				$button = new TActiveButton;
			} else { // image button
				$button = new TActiveImageButton;
				$button->setImageUrl($this->getImageUrl());
				$button->setToolTip($this->getText());
			}
			$button->setText($this->getText());
			$button->setCommandName($this->getCommandName());
			$button->setCausesValidation($this->getCausesValidation());
			$button->setValidationGroup($this->getValidationGroup());
			if ($this->getDataTextField() !== '' || ($buttonType === TButtonColumnType::ImageButton && $this->getDataImageUrlField() !== '')) {
				$button->attachEventHandler('OnDataBinding', [$this, 'dataBindColumn']);
			}
			$cell->getControls()->add($button);
			$cell->registerObject('Button', $button);
		} else {
			parent::initializeCell($cell, $columnIndex, $itemType);
		}
	}
}
