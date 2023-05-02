<?php
/**
 * TEditCommandColumn class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\Prado;
use Prado\TPropertyValue;

/**
 * TEditCommandColumn class
 *
 * TEditCommandColumn contains the Edit command buttons for editing data items in each row.
 *
 * TEditCommandColumn will create an edit button if a cell is not in edit mode.
 * Otherwise an update button and a cancel button will be created within the cell.
 * The button captions are specified using {@link setEditText EditText},
 * {@link setUpdateText UpdateText}, and {@link setCancelText CancelText}.
 *
 * The buttons in the column can be set to display as hyperlinks, push or image buttons
 * by setting the {@link setButtonType ButtonType} property.
 *
 * When an edit button is clicked, the datagrid will generate an
 * {@link onEditCommand OnEditCommand} event. When an update/cancel button
 * is clicked, the datagrid will generate an
 * {@link onUpdateCommand OnUpdateCommand} or an {@link onCancelCommand OnCancelCommand}
 * You can write these event handlers to change the state of specific datagrid item.
 *
 * The {@link setCausesValidation CausesValidation} and {@link setValidationGroup ValidationGroup}
 * properties affect the corresponding properties of the edit and update buttons.
 * The cancel button does not cause validation by default.
 *
 * The command buttons in the column can be accessed by one of the following methods:
 * <code>
 * $datagridItem->ButtonColumnID->EditButton (or UpdateButton, CancelButton)
 * $datagridItem->ButtonColumnID->Controls[0]
 * </code>
 * The second method is possible because the button control created within the
 * datagrid cell is the first child.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 */
class TEditCommandColumn extends TDataGridColumn
{
	/**
	 * @return TButtonColumnType the type of command button. Defaults to TButtonColumnType::LinkButton.
	 */
	public function getButtonType()
	{
		return $this->getViewState('ButtonType', TButtonColumnType::LinkButton);
	}

	/**
	 * @param TButtonColumnType $value the type of command button.
	 */
	public function setButtonType($value)
	{
		$this->setViewState('ButtonType', TPropertyValue::ensureEnum($value, TButtonColumnType::class), TButtonColumnType::LinkButton);
	}

	/**
	 * @return string the caption of the edit button. Defaults to 'Edit'.
	 */
	public function getEditText()
	{
		return $this->getViewState('EditText', 'Edit');
	}

	/**
	 * @param string $value the caption of the edit button
	 */
	public function setEditText($value)
	{
		$this->setViewState('EditText', $value, 'Edit');
	}

	/**
	 * @return string the URL of the image file for edit image buttons
	 */
	public function getEditImageUrl()
	{
		return $this->getViewState('EditImageUrl', '');
	}

	/**
	 * @param string $value the URL of the image file for edit image buttons
	 */
	public function setEditImageUrl($value)
	{
		$this->setViewState('EditImageUrl', $value, '');
	}

	/**
	 * @return string the caption of the update button. Defaults to 'Update'.
	 */
	public function getUpdateText()
	{
		return $this->getViewState('UpdateText', 'Update');
	}

	/**
	 * @param string $value the caption of the update button
	 */
	public function setUpdateText($value)
	{
		$this->setViewState('UpdateText', $value, 'Update');
	}

	/**
	 * @return string the URL of the image file for update image buttons
	 */
	public function getUpdateImageUrl()
	{
		return $this->getViewState('UpdateImageUrl', '');
	}

	/**
	 * @param string $value the URL of the image file for update image buttons
	 */
	public function setUpdateImageUrl($value)
	{
		$this->setViewState('UpdateImageUrl', $value, '');
	}

	/**
	 * @return string the caption of the cancel button. Defaults to 'Cancel'.
	 */
	public function getCancelText()
	{
		return $this->getViewState('CancelText', 'Cancel');
	}

	/**
	 * @param string $value the caption of the cancel button
	 */
	public function setCancelText($value)
	{
		$this->setViewState('CancelText', $value, 'Cancel');
	}

	/**
	 * @return string the URL of the image file for cancel image buttons
	 */
	public function getCancelImageUrl()
	{
		return $this->getViewState('CancelImageUrl', '');
	}

	/**
	 * @param string $value the URL of the image file for cancel image buttons
	 */
	public function setCancelImageUrl($value)
	{
		$this->setViewState('CancelImageUrl', $value, '');
	}

	/**
	 * @return bool whether postback event trigger by edit or update button will cause input validation, default is true
	 */
	public function getCausesValidation()
	{
		return $this->getViewState('CausesValidation', true);
	}

	/**
	 * @param bool $value whether postback event trigger by edit or update button will cause input validation
	 */
	public function setCausesValidation($value)
	{
		$this->setViewState('CausesValidation', TPropertyValue::ensureBoolean($value), true);
	}

	/**
	 * @return string the group of validators which the edit or update button causes validation upon postback
	 */
	public function getValidationGroup()
	{
		return $this->getViewState('ValidationGroup', '');
	}

	/**
	 * @param string $value the group of validators which the edit or update button causes validation upon postback
	 */
	public function setValidationGroup($value)
	{
		$this->setViewState('ValidationGroup', $value, '');
	}

	/**
	 * Initializes the specified cell to its initial values.
	 * This method overrides the parent implementation.
	 * It creates an update and a cancel button for cell in edit mode.
	 * Otherwise it creates an edit button.
	 * @param TTableCell $cell the cell to be initialized.
	 * @param int $columnIndex the index to the Columns property that the cell resides in.
	 * @param string $itemType the type of cell (Header,Footer,Item,AlternatingItem,EditItem,SelectedItem)
	 */
	public function initializeCell($cell, $columnIndex, $itemType)
	{
		if ($itemType === TListItemType::Item || $itemType === TListItemType::AlternatingItem || $itemType === TListItemType::SelectedItem) {
			$button = $this->createButton('Edit', $this->getEditText(), false, '');
			$cell->getControls()->add($button);
			$cell->registerObject('EditButton', $button);
		} elseif ($itemType === TListItemType::EditItem) {
			$controls = $cell->getControls();
			$button = $this->createButton('Update', $this->getUpdateText(), $this->getCausesValidation(), $this->getValidationGroup());
			$controls->add($button);
			$cell->registerObject('UpdateButton', $button);
			$controls->add('&nbsp;');
			$button = $this->createButton('Cancel', $this->getCancelText(), false, '');
			$controls->add($button);
			$cell->registerObject('CancelButton', $button);
		} else {
			parent::initializeCell($cell, $columnIndex, $itemType);
		}
	}

	/**
	 * Creates a button and initializes its properties.
	 * The button type is determined by {@link getButtonType ButtonType}.
	 * @param string $commandName command name associated with the button
	 * @param string $text button caption
	 * @param bool $causesValidation whether the button should cause validation
	 * @param string $validationGroup the validation group that the button belongs to
	 * @return mixed the newly created button.
	 */
	protected function createButton($commandName, $text, $causesValidation, $validationGroup)
	{
		if ($this->getButtonType() === TButtonColumnType::LinkButton) {
			$button = new TLinkButton();
		} elseif ($this->getButtonType() === TButtonColumnType::PushButton) {
			$button = new TButton();
		} else {	// image buttons
			$button = new TImageButton();
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
