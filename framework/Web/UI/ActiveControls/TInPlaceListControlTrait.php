<?php

/**
 * TInPlaceListControlTrait file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\ActiveControls;

use Prado\Prado;
use Prado\TPropertyValue;
use Prado\Web\THttpUtility;

/**
 * TInPlaceListControlTrait trait.
 *
 * TInPlaceListControlTrait implements the in-place surface shared by the
 * select-based in-place controls, {@see TInPlaceDropDownList} and
 * {@see TInPlaceListBox}. The control renders a label showing the selected
 * item text over the server-rendered (hidden) select. Changing the selection
 * posts a callback; the label follows the selection, and follows the item
 * list when it is updated during a callback.
 *
 * The using class supplies two seams: {@see renderListControlAttributes} calls
 * the underlying list control's attribute rendering, and
 * {@see getClientClassName} names the client class. Multi-select controls
 * override {@see getSelectedItemText} to join the selected item texts.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
trait TInPlaceListControlTrait
{
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
	 * Updates the client-side label with the selected item text. The callback
	 * request that reaches here posts the whole form, so the server selection
	 * is current; pushing it on each callback keeps the label authoritative for
	 * every selection and item-list change, including changes made by other
	 * controls' handlers.
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
	 * Renders the label followed by the select.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer for rendering
	 */
	public function render($writer)
	{
		$this->renderLabel($writer);
		parent::render($writer);
	}

	/**
	 * Renders the label span holding the selected display text. The label
	 * carries the control's style and tool tip, and a mark when it shows the
	 * empty display text.
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
		$this->renderLabelAccessibilityAttributes($writer, $this->getReadOnly());
		if ($this->getDisplayEditor()) {
			$writer->addStyleAttribute('display', 'none');
		}
		$writer->renderBeginTag('span');
		$writer->write($this->getSelectedDisplayText());
		$writer->renderEndTag();
	}

	/**
	 * Ensures the ID attribute is rendered, hides the select when the label is
	 * displayed, and registers the javascript code for the active control.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer for rendering
	 */
	protected function addAttributesToRender($writer)
	{
		// renderListControlAttributes renders the underlying list control's
		// attributes (size/name/multiple/id and the options) but not the active
		// callback-script registration, which the trait replaces below with the
		// in-place client class. The using class supplies it.
		$this->renderListControlAttributes($writer);
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
		// The server-rendered select has no associated label element; ToolTip
		// names it, with a localized default so the editor is never nameless.
		$options['EditorLabel'] = $this->getToolTip() !== '' ? $this->getToolTip() : Prado::localize('Edit value');

		if ($this->hasEventHandler('OnLoadingItems')) {
			$options['LoadItemsOnEdit'] = true;
		}

		$options['ReadOnly'] = $this->getReadOnly();
		return array_merge($options, $this->getExtraListOptions());
	}

	/**
	 * @return array additional callback options for the using control.
	 */
	protected function getExtraListOptions()
	{
		return [];
	}

	/**
	 * Registers the in-place client script and, on a callback, refreshes the
	 * client-side label from the server's selection. The single authoritative
	 * update covers every selection mutation and item-list change.
	 * @param mixed $param event parameter
	 */
	public function onPreRender($param)
	{
		parent::onPreRender($param);
		$this->registerClientScript();
		if ($this->getActiveControl()->canUpdateClientSide()) {
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
		if (is_array($action) && ($action[0] ?? null) === '__InlineEditor_loadItems__') {
			$parameter = new TCallbackEventParameter($this->getResponse(), $action[1]);
			$this->onLoadingItems($parameter);
		}
		$this->raiseEvent('OnCallback', $this, $param);
	}

	/**
	 * Raised when the item list is requested to be loaded from the server
	 * side. The callback parameter holds the selection at the time editing is
	 * entered: the selected value for a drop down list, and the array of
	 * selected values for a list box (both single and multiple selection).
	 * @param TCallbackEventParameter $param event parameter to be passed to the event handlers
	 */
	public function onLoadingItems($param)
	{
		$this->raiseEvent('OnLoadingItems', $this, $param);
	}
}
