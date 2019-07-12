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
use Prado\Web\UI\WebControls\TButtonColumnType;
use Prado\Web\UI\WebControls\TEditCommandColumn;

/**
 * TActiveEditCommandColumn class
 *
 * TActiveEditCommandColumn contains the Edit command buttons for editing data items in each row.
 *
 * TActiveEditCommandColumn will create an edit button if a cell is not in edit mode.
 * Otherwise an update button and a cancel button will be created within the cell.
 * The button captions are specified using {@link setEditText EditText},
 * {@link setUpdateText UpdateText}, and {@link setCancelText CancelText}.
 *
 * This is the active counterpart to the {@link TEditCommandColumn} control. The buttons for
 * interaction are replaced by active buttons.
 *
 * Please refer to the original documentation of the {@link TEditCommandColumn} for usage.
 *
 * @author LANDWEHR Computer und Software GmbH <programmierung@landwehr-software.de>
 * @package Prado\Web\UI\ActiveControls
 * @since 3.1.9
 */
class TActiveEditCommandColumn extends TEditCommandColumn
{
	protected function createButton($commandName, $text, $causesValidation, $validationGroup)
	{
		if ($this->getButtonType() === TButtonColumnType::LinkButton) {
			$button = new TActiveLinkButton;
		} elseif ($this->getButtonType() === TButtonColumnType::PushButton) {
			$button = new TActiveButton;
		} else {  // image buttons
			$button = new TActiveImageButton;
			$button->setToolTip($text);
			if (strcasecmp($commandName, 'Update') === 0) {
				$url = $this->getUpdateImageUrl();
			} elseif (strcasecmp($commandName, 'Cancel') === 0) {
				$url = $this->getCancelImageUrl();
			} else {
				$url = $this->getEditImageUrl();
			}
			$button->setImageUrl($url);
		}
		$button->setText($text);
		$button->setCommandName($commandName);
		$button->setCausesValidation($causesValidation);
		$button->setValidationGroup($validationGroup);
		return $button;
	}
}
