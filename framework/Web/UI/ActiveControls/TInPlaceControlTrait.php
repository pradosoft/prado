<?php

/**
 * TInPlaceControlTrait file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\ActiveControls;

use Prado\TPropertyValue;

/**
 * TInPlaceControlTrait trait.
 *
 * TInPlaceControlTrait implements the surface shared by in-place editor
 * controls. An in-place control renders a label element that swaps to an
 * edit element when the label, or the control given by
 * {@see setEditTriggerControlID EditTriggerControlID}, is clicked.
 *
 * The trait provides the label client ID, the edit trigger property, calls
 * of client-side static methods, and registration of the in-place editor
 * client script.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
trait TInPlaceControlTrait
{
	/**
	 * @param bool $value true to hide the editor after losing focus.
	 */
	public function setAutoHideEditor($value)
	{
		$this->setViewState('AutoHide', TPropertyValue::ensureBoolean($value), true);
	}

	/**
	 * @return bool true will hide the editor after losing focus.
	 */
	public function getAutoHideEditor()
	{
		return $this->getViewState('AutoHide', true);
	}

	/**
	 * The editor is shown on the client during a callback.
	 * @param bool $value true to display the editor.
	 */
	public function setDisplayEditor($value)
	{
		$value = TPropertyValue::ensureBoolean($value);
		$this->setViewState('DisplayEditor', $value, false);
		if ($this->getActiveControl()->canUpdateClientSide()) {
			$this->callClientFunction('setDisplayEditor', $value);
		}
	}

	/**
	 * @return bool true to display the editor.
	 */
	public function getDisplayEditor()
	{
		return $this->getViewState('DisplayEditor', false);
	}

	/**
	 * @param string $value ID of the control that can trigger editing.
	 */
	public function setEditTriggerControlID($value)
	{
		$this->setViewState('EditTriggerControlID', $value);
	}

	/**
	 * @return string ID of the control that can trigger editing.
	 */
	public function getEditTriggerControlID()
	{
		return $this->getViewState('EditTriggerControlID');
	}

	/**
	 * The client-side placeholder is updated during a callback, so a label
	 * already showing the empty display text follows the change.
	 * @param string $value label html shown when the value is empty.
	 */
	public function setEmptyDisplayText($value)
	{
		$value = TPropertyValue::ensureString($value);
		if ($this->getEmptyDisplayText() === $value) {
			return;
		}

		$this->setViewState('EmptyDisplayText', $value, '');
		if ($this->getActiveControl()->canUpdateClientSide()) {
			$this->callClientFunction('setEmptyDisplayText', $value);
		}
	}

	/**
	 * @return string label html shown when the value is empty.
	 */
	public function getEmptyDisplayText()
	{
		return $this->getViewState('EmptyDisplayText', '');
	}

	/**
	 * @return string edit trigger control client ID.
	 */
	protected function getExternalControlID()
	{
		$extID = $this->getEditTriggerControlID();
		if ($extID === null) {
			return '';
		}
		if (($control = $this->findControl($extID)) !== null) {
			return $control->getClientID();
		}
		return $extID;
	}

	/**
	 * Marks the label element when it shows the empty display text. The
	 * client reads the mark to tell the placeholder apart from a value.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer for rendering
	 * @param bool $isEmpty whether the label shows the empty display text
	 */
	protected function renderEmptyDisplayAttribute($writer, $isEmpty)
	{
		if ($isEmpty && $this->getEmptyDisplayText() !== '') {
			$writer->addAttribute('data-prado-empty', '1');
		}
	}

	/**
	 * Renders the accessibility attributes that make the label operable as an
	 * edit trigger. A polite live region announces value changes; when editing
	 * is allowed the label also carries a button role and a tab stop so it can
	 * be reached and activated from the keyboard. A read only label stays plain
	 * text.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer for rendering
	 * @param bool $readOnly whether the control is read only
	 */
	protected function renderLabelAccessibilityAttributes($writer, $readOnly)
	{
		$writer->addAttribute('aria-live', 'polite');
		if (!$readOnly) {
			$writer->addAttribute('role', 'button');
			$writer->addAttribute('tabindex', '0');
		}
	}

	/**
	 * @return string label client ID
	 */
	protected function getLabelClientID()
	{
		return $this->getClientID() . '__label';
	}

	/**
	 * Calls the client-side static method for this control class.
	 * @param string $func static method name
	 * @param mixed $value method parameter
	 */
	protected function callClientFunction($func, $value)
	{
		$client = $this->getPage()->getCallbackClient();
		$code = $this->getClientClassName() . '.' . $func;
		$client->callClientFunction($code, [$this, $value]);
	}

	/**
	 * Registers CSS and JS.
	 * This method is invoked right before the control rendering, if the control is visible.
	 * @param mixed $param event parameter
	 */
	public function onPreRender($param)
	{
		parent::onPreRender($param);
		$this->registerClientScript();
	}

	/**
	 * Registers the relevant JavaScript.
	 */
	protected function registerClientScript()
	{
		$cs = $this->getPage()->getClientScript();
		$cs->registerPradoScript('inlineeditor');
	}
}
