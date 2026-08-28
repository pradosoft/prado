<?php

/**
 * TInPlaceDropDownList class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\ActiveControls;

use Prado\TPropertyValue;
use Prado\Web\THttpUtility;
use Prado\Web\UI\WebControls\TDropDownList;

/**
 * TInPlaceDropDownList class.
 *
 * TInPlaceDropDownList is a drop down list rendered as a label showing the
 * selected item text. Clicking the label, or the control given by
 * {@see setEditTriggerControlID EditTriggerControlID}, swaps the label for
 * the drop down list. When no item is selected or the selected item text is
 * empty, the label shows {@see setEmptyDisplayText EmptyDisplayText}.
 *
 * When {@see \Prado\Web\UI\WebControls\TListControl::setAutoPostBack AutoPostBack}
 * is true (the default), changing the selection makes a callback request that
 * raises {@see \Prado\Web\UI\WebControls\TListControl::onSelectedIndexChanged OnSelectedIndexChanged}
 * and {@see onCallback OnCallback}. During the request the drop down list is
 * disabled. After the request returns successfully, the label shows the new
 * selection and, when {@see setAutoHideEditor AutoHideEditor} is true, the
 * drop down list is hidden and the label is shown.
 *
 * If the {@see onLoadingItems OnLoadingItems} event is handled, a callback
 * request is made when the label is clicked. The event allows the item list
 * to be updated, through the active list adapter, before the client selects
 * a value.
 *
 * The {@see setReadOnly ReadOnly} property prevents entering edit mode. The
 * property can be changed during a callback.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TInPlaceDropDownList extends TActiveDropDownList
{
	use TInPlaceControlTrait {
		onPreRender as protected inPlaceOnPreRender;
	}

	/**
	 * @param bool $value true to prevent entering edit mode.
	 */
	public function setReadOnly($value)
	{
		$value = TPropertyValue::ensureBoolean($value);
		if ($this->getReadOnly() === $value) {
			return;
		}

		$this->setViewState('ReadOnly', $value, false);
		if ($this->getActiveControl()->canUpdateClientSide()) {
			$this->callClientFunction('setReadOnly', $value);
		}
	}

	/**
	 * @return bool true to prevent entering edit mode. Defaults to false.
	 */
	public function getReadOnly()
	{
		return $this->getViewState('ReadOnly', false);
	}

	/**
	 * On callback response, the label is updated with the new selection.
	 * @param string $value value of the item to be selected
	 */
	public function setSelectedValue($value)
	{
		$prior = $this->getSelectedValue();
		parent::setSelectedValue($value);
		if ($prior !== $this->getSelectedValue() && $this->getActiveControl()->canUpdateClientSide()) {
			$this->updateLabel();
		}
	}

	/**
	 * On callback response, the label is updated with the new selection.
	 * @param int $index index of the item to be selected
	 */
	public function setSelectedIndex($index)
	{
		$prior = $this->getSelectedIndex();
		parent::setSelectedIndex($index);
		if ($prior !== $this->getSelectedIndex() && $this->getActiveControl()->canUpdateClientSide()) {
			$this->updateLabel();
		}
	}

	/**
	 * Updates the client-side label with the selected display text.
	 */
	protected function updateLabel()
	{
		$this->callClientFunction('setLabelText', $this->getSelectedItemText());
	}

	/**
	 * @return string encoded selected item text, empty when nothing is selected.
	 */
	protected function getSelectedItemText()
	{
		$item = $this->getSelectedItem();
		if ($item === null || ($text = $item->getText()) === '') {
			return '';
		}
		return THttpUtility::htmlEncode($text);
	}

	/**
	 * @return string encoded selected item text, or {@see getEmptyDisplayText} when empty.
	 */
	protected function getSelectedDisplayText()
	{
		if (($text = $this->getSelectedItemText()) === '') {
			return $this->getEmptyDisplayText();
		}
		return $text;
	}

	/**
	 * Renders the label followed by the drop down list.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer for rendering
	 */
	public function render($writer)
	{
		$this->renderLabel($writer);
		parent::render($writer);
	}

	/**
	 * Renders the label span holding the selected display text.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer for rendering
	 */
	protected function renderLabel($writer)
	{
		$writer->addAttribute('id', $this->getLabelClientID());
		if ($this->getHasStyle()) {
			$this->getStyle()->addAttributesToRender($writer);
		}
		if (($toolTip = $this->getToolTip()) !== '') {
			$writer->addAttribute('title', $toolTip);
		}
		$this->renderEmptyDisplayAttribute($writer, $this->getSelectedItemText() === '');
		if ($this->getDisplayEditor()) {
			$writer->addStyleAttribute('display', 'none');
		}
		$writer->renderBeginTag('span');
		$writer->write($this->getSelectedDisplayText());
		$writer->renderEndTag();
	}

	/**
	 * Ensures the ID attribute is rendered, hides the drop down list when the
	 * label is displayed, and registers the javascript code for initializing
	 * the active control.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer for rendering
	 */
	protected function addAttributesToRender($writer)
	{
		//calls the TDropDownList to always register the in-place client class.
		TDropDownList::addAttributesToRender($writer);
		$writer->addAttribute('id', $this->getClientID());
		if (!$this->getDisplayEditor()) {
			$writer->addStyleAttribute('display', 'none');
		}
		$this->getActiveControl()->registerCallbackClientScript(
			$this->getClientClassName(),
			$this->getPostBackOptions()
		);
	}

	/**
	 * @return array callback options.
	 */
	protected function getPostBackOptions()
	{
		$options = parent::getPostBackOptions();
		$options['ID'] = $this->getLabelClientID();
		$options['EditorID'] = $this->getClientID();
		$options['ExternalControl'] = $this->getExternalControlID();
		$options['AutoHide'] = $this->getAutoHideEditor();
		$options['AutoPostBack'] = $this->getAutoPostBack();
		$options['EmptyDisplayText'] = $this->getEmptyDisplayText();
		$options['DisplayEditor'] = $this->getDisplayEditor();

		if ($this->hasEventHandler('OnLoadingItems')) {
			$options['LoadItemsOnEdit'] = true;
		}

		$options['ReadOnly'] = $this->getReadOnly();
		return $options;
	}

	/**
	 * Refreshes the client-side label when the item list changed during a
	 * callback, so a new text for the selected value reaches the label.
	 * @param mixed $param event parameter
	 */
	public function onPreRender($param)
	{
		$items = $this->getItems();
		$listChanged = ($items instanceof TActiveListItemCollection) && $items->getListHasChanged();

		$this->inPlaceOnPreRender($param);

		if ($listChanged && $this->getActiveControl()->canUpdateClientSide()) {
			$this->updateLabel();
		}
	}

	/**
	 * This method is invoked when a callback is requested. The method raises
	 * 'OnCallback' event to fire up the event handlers. If you override this
	 * method, be sure to call the parent implementation so that the event
	 * handler can be invoked.
	 * @param TCallbackEventParameter $param event parameter to be passed to the event handlers
	 */
	public function onCallback($param)
	{
		$action = $param->getCallbackParameter();
		if (is_array($action) && $action[0] === '__InlineEditor_loadItems__') {
			$parameter = new TCallbackEventParameter($this->getResponse(), $action[1]);
			$this->onLoadingItems($parameter);
		}
		$this->raiseEvent('OnCallback', $this, $param);
	}

	/**
	 * Raised when the item list is requested to be loaded from the server
	 * side. The callback parameter holds the drop down list value at the time
	 * editing is entered.
	 * @param TCallbackEventParameter $param event parameter to be passed to the event handlers
	 */
	public function onLoadingItems($param)
	{
		$this->raiseEvent('OnLoadingItems', $this, $param);
	}

	/**
	 * @return string corresponding javascript class name for this TInPlaceDropDownList
	 */
	protected function getClientClassName()
	{
		return 'Prado.WebUI.TInPlaceDropDownList';
	}
}
