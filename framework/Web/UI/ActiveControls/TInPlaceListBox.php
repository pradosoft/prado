<?php

/**
 * TInPlaceListBox class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\ActiveControls;

use Prado\TPropertyValue;
use Prado\Web\THttpUtility;
use Prado\Web\UI\WebControls\TListBox;

/**
 * TInPlaceListBox class.
 *
 * TInPlaceListBox is a list box rendered as a label showing the selected item
 * texts. Clicking the label, or the control given by
 * {@see setEditTriggerControlID EditTriggerControlID}, swaps the label for the
 * list box. In multiple selection mode the label joins the selected item texts
 * with {@see setSelectionSeparator SelectionSeparator}. When nothing is
 * selected the label shows {@see setEmptyDisplayText EmptyDisplayText}.
 *
 * When {@see \Prado\Web\UI\WebControls\TListControl::setAutoPostBack AutoPostBack}
 * is true (the default), changing the selection makes a callback request that
 * raises {@see \Prado\Web\UI\WebControls\TListControl::onSelectedIndexChanged OnSelectedIndexChanged}
 * and {@see onCallback OnCallback}. After the request returns successfully the
 * label shows the new selection and, when {@see setAutoHideEditor AutoHideEditor}
 * is true, the list box is hidden and the label is shown.
 *
 * If the {@see onLoadingItems OnLoadingItems} event is handled, a callback
 * request is made when the label is clicked, so the item list can be updated
 * before the client selects.
 *
 * The {@see setReadOnly ReadOnly} property prevents entering edit mode. The
 * property can be changed during a callback.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TInPlaceListBox extends TActiveListBox
{
	use TInPlaceControlTrait, TInPlaceListControlTrait {
		TInPlaceListControlTrait::onPreRender insteadof TInPlaceControlTrait;
	}

	/**
	 * @param string $value text placed between the selected item texts in the label.
	 */
	public function setSelectionSeparator($value)
	{
		$this->setViewState('SelectionSeparator', TPropertyValue::ensureString($value), ', ');
	}

	/**
	 * @return string text placed between the selected item texts. Defaults to ", ".
	 */
	public function getSelectionSeparator()
	{
		return $this->getViewState('SelectionSeparator', ', ');
	}

	/**
	 * @return string encoded selected item texts joined by the separator,
	 *   empty when nothing is selected.
	 */
	protected function getSelectedItemText()
	{
		$texts = [];
		foreach ($this->getItems() as $item) {
			if ($item->getSelected() && ($text = $item->getText()) !== '') {
				$texts[] = $text;
			}
		}
		// Encode the joined string, matching the client which encodes the whole
		// join, so an html-special separator renders the same on both sides.
		return THttpUtility::htmlEncode(implode($this->getSelectionSeparator(), $texts));
	}

	/**
	 * @return array additional callback options carrying the selection separator.
	 */
	protected function getExtraListOptions()
	{
		return ['SelectionSeparator' => $this->getSelectionSeparator()];
	}

	/**
	 * Renders the list box attributes, always registering the in-place client
	 * class.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer for rendering
	 */
	protected function renderListControlAttributes($writer)
	{
		TListBox::addAttributesToRender($writer);
	}

	/**
	 * @return string corresponding javascript class name for this TInPlaceListBox
	 */
	protected function getClientClassName()
	{
		return 'Prado.WebUI.TInPlaceListBox';
	}
}
