<?php
/**
 * TCheckBox class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

use Prado\Collections\TAttributeCollection;
use Prado\TPropertyValue;

/**
 * TCheckBox class
 *
 * TCheckBox displays a check box on the page.
 * You can specify the caption to display beside the check box by setting
 * the {@link setText Text} property.  The caption can appear either on the right
 * or left of the check box, which is determined by the {@link setTextAlign TextAlign}
 * property.
 *
 * To determine whether the TCheckBox component is checked, test the {@link getChecked Checked}
 * property. The {@link onCheckedChanged OnCheckedChanged} event is raised when
 * the {@link getChecked Checked} state of the TCheckBox component changes
 * between posts to the server. You can provide an event handler for
 * the {@link onCheckedChanged OnCheckedChanged} event to  to programmatically
 * control the actions performed when the state of the TCheckBox component changes
 * between posts to the server.
 *
 * If {@link setAutoPostBack AutoPostBack} is set true, changing the check box state
 * will cause postback action. And if {@link setCausesValidation CausesValidation}
 * is true, validation will also be processed, which can be further restricted within
 * a {@link setValidationGroup ValidationGroup}.
 *
 * Note, {@link setText Text} is rendered as is. Make sure it does not contain unwanted characters
 * that may bring security vulnerabilities.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI\WebControls
 * @since 3.0
 */
class TCheckBox extends \Prado\Web\UI\WebControls\TWebControl implements \Prado\Web\UI\IPostBackDataHandler, \Prado\Web\UI\IValidatable, \Prado\IDataRenderer, \Prado\Web\UI\ISurroundable
{
	private $_dataChanged = false;
	private $_isValid = true;

	/**
	 * @return string tag name of the button
	 */
	protected function getTagName()
	{
		return 'input';
	}

	/**
	 * Loads user input data.
	 * This method is primarly used by framework developers.
	 * @param string $key the key that can be used to retrieve data from the input data collection
	 * @param array $values the input data collection
	 * @return bool whether the data of the control has been changed
	 */
	public function loadPostData($key, $values)
	{
		$checked = $this->getChecked();
		if ($newChecked = isset($values[$key])) {
			$this->setValue($values[$key]);
		}
		$this->setChecked($newChecked);
		return $this->_dataChanged = ($newChecked !== $checked);
	}

	/**
	 * Raises postdata changed event.
	 * This method raises {@link onCheckedChanged OnCheckedChanged} event.
	 * This method is primarly used by framework developers.
	 */
	public function raisePostDataChangedEvent()
	{
		if ($this->getAutoPostBack() && $this->getCausesValidation()) {
			$this->getPage()->validate($this->getValidationGroup());
		}
		$this->onCheckedChanged(null);
	}

	/**
	 * Raises <b>OnCheckedChanged</b> event when {@link getChecked Checked} changes value during postback.
	 * If you override this method, be sure to call the parent implementation
	 * so that the event delegates can be invoked.
	 * @param TEventParameter $param event parameter to be passed to the event handlers
	 */
	public function onCheckedChanged($param)
	{
		$this->raiseEvent('OnCheckedChanged', $this, $param);
	}

	/**
	 * Registers the checkbox to receive postback data during postback.
	 * This is necessary because a checkbox if unchecked, when postback,
	 * does not have direct mapping between post data and the checkbox name.
	 *
	 * This method overrides the parent implementation and is invoked before render.
	 * @param mixed $param event parameter
	 */
	public function onPreRender($param)
	{
		parent::onPreRender($param);
		if ($this->getEnabled(true)) {
			$this->getPage()->registerRequiresPostData($this);
		}
	}

	/**
	 * Returns a value indicating whether postback has caused the control data change.
	 * This method is required by the \Prado\Web\UI\IPostBackDataHandler interface.
	 * @return bool whether postback has caused the control data change. False if the page is not in postback mode.
	 */
	public function getDataChanged()
	{
		return $this->_dataChanged;
	}

	/**
	 * Returns the value of the property that needs validation.
	 * @return mixed the property value to be validated
	 */
	public function getValidationPropertyValue()
	{
		return $this->getChecked();
	}

	/**
	 * Returns true if this control validated successfully.
	 * Defaults to true.
	 * @return bool wether this control validated successfully.
	 */
	public function getIsValid()
	{
		return $this->_isValid;
	}
	/**
	 * @param bool $value wether this control is valid.
	 */
	public function setIsValid($value)
	{
		$this->_isValid = TPropertyValue::ensureBoolean($value);
	}

	/**
	 * @return string the text caption of the checkbox
	 */
	public function getText()
	{
		return $this->getViewState('Text', '');
	}

	/**
	 * Sets the text caption of the checkbox.
	 * @param string $value the text caption to be set
	 */
	public function setText($value)
	{
		$this->setViewState('Text', $value, '');
	}

	/**
	 * @return string the value of the checkbox. Defaults to empty.
	 */
	public function getValue()
	{
		return $this->getViewState('Value', '');
	}

	/**
	 * @param string $value the value of the checkbox
	 */
	public function setValue($value)
	{
		$this->setViewState('Value', TPropertyValue::ensureString($value), '');
	}

	/**
	 * @return TTextAlign the alignment (Left or Right) of the text caption, defaults to TTextAlign::Right.
	 */
	public function getTextAlign()
	{
		return $this->getViewState('TextAlign', TTextAlign::Right);
	}

	/**
	 * @param TTextAlign $value the alignment of the text caption. Valid values include Left and Right.
	 */
	public function setTextAlign($value)
	{
		$this->setViewState('TextAlign', TPropertyValue::ensureEnum($value, 'Prado\\Web\\UI\\WebControls\\TTextAlign'), TTextAlign::Right);
	}

	/**
	 * @return bool whether the checkbox is checked
	 */
	public function getChecked()
	{
		return $this->getViewState('Checked', false);
	}

	/**
	 * Sets a value indicating whether the checkbox is to be checked or not.
	 * @param bool $value whether the checkbox is to be checked or not.
	 */
	public function setChecked($value)
	{
		$this->setViewState('Checked', TPropertyValue::ensureBoolean($value), false);
	}

	/**
	 * Returns the value indicating whether the checkbox is checked.
	 * This method is required by {@link \Prado\IDataRenderer}.
	 * It is the same as {@link getChecked()}.
	 * @return bool whether the checkbox is checked.
	 * @see getChecked
	 * @since 3.1.0
	 */
	public function getData()
	{
		return $this->getChecked();
	}

	/**
	 * Sets the value indicating whether the checkbox is to be checked or not.
	 * This method is required by {@link \Prado\IDataRenderer}.
	 * It is the same as {@link setChecked()}.
	 * @param bool $value whether the checkbox is to be checked
	 * @see setChecked
	 * @since 3.1.0
	 */
	public function setData($value)
	{
		$this->setChecked($value);
	}

	/**
	 * @return bool whether clicking on the checkbox will post the page.
	 */
	public function getAutoPostBack()
	{
		return $this->getViewState('AutoPostBack', false);
	}

	/**
	 * Sets a value indicating whether clicking on the checkbox will post the page.
	 * @param bool $value whether clicking on the checkbox will post the page.
	 */
	public function setAutoPostBack($value)
	{
		$this->setViewState('AutoPostBack', TPropertyValue::ensureBoolean($value), false);
	}

	/**
	 * @return bool whether postback event triggered by this checkbox will cause input validation, default is true.
	 */
	public function getCausesValidation()
	{
		return $this->getViewState('CausesValidation', true);
	}

	/**
	 * Sets the value indicating whether postback event trigger by this checkbox will cause input validation.
	 * @param bool $value whether postback event trigger by this checkbox will cause input validation.
	 */
	public function setCausesValidation($value)
	{
		$this->setViewState('CausesValidation', TPropertyValue::ensureBoolean($value), true);
	}

	/**
	 * @return string the group of validators which the checkbox causes validation upon postback
	 */
	public function getValidationGroup()
	{
		return $this->getViewState('ValidationGroup', '');
	}

	/**
	 * @param string $value the group of validators which the checkbox causes validation upon postback
	 */
	public function setValidationGroup($value)
	{
		$this->setViewState('ValidationGroup', $value, '');
	}

	/**
	 * @return string the tag used to wrap the control in.
	 */
	public function getSurroundingTag()
	{
		return 'span';
	}

	/**
	 * @return string the id of the surrounding tag or this clientID if no such tag needed.
	 */
	public function getSurroundingTagID()
	{
		return $this->getSpanNeeded() ? $this->getClientID() . '_parent' : $this->getClientID();
	}

	/**
	 * Renders the checkbox control.
	 * This method overrides the parent implementation by rendering a checkbox input element
	 * and a span element if needed.
	 * @param THtmlWriter $writer the writer used for the rendering purpose
	 */
	public function render($writer)
	{
		$this->getPage()->ensureRenderInForm($this);
		if ($this->getHasStyle()) {
			$this->getStyle()->addAttributesToRender($writer);
		}
		if (($tooltip = $this->getToolTip()) !== '') {
			$writer->addAttribute('title', $tooltip);
		}
		if ($this->getHasAttributes()) {
			$attributes = $this->getAttributes();
			$value = $attributes->remove('value');
			// onclick js should only be added to input tag
			if (($onclick = $attributes->remove('onclick')) === null) {
				$onclick = '';
			}
			if ($attributes->getCount()) {
				$writer->addAttributes($attributes);
			}
			if ($value !== null) {
				$attributes->add('value', $value);
			}
		} else {
			$onclick = '';
		}
		if ($needspan = $this->getSpanNeeded()) {
			$writer->addAttribute('id', $this->getSurroundingTagID());
			$writer->renderBeginTag($this->getSurroundingTag());
		}
		$clientID = $this->getClientID();
		if (($text = $this->getText()) !== '') {
			if ($this->getTextAlign() === TTextAlign::Left) {
				$this->renderLabel($writer, $clientID, $text);
				$this->renderInputTag($writer, $clientID, $onclick);
			} else {
				$this->renderInputTag($writer, $clientID, $onclick);
				$this->renderLabel($writer, $clientID, $text);
			}
		} else {
			$this->renderInputTag($writer, $clientID, $onclick);
		}
		if ($needspan) {
			$writer->renderEndTag();
		}
	}

	/**
	 * @return TMap list of attributes to be rendered for label beside the checkbox
	 */
	public function getLabelAttributes()
	{
		if ($attributes = $this->getViewState('LabelAttributes', null)) {
			return $attributes;
		} else {
			$attributes = new TAttributeCollection;
			$this->setViewState('LabelAttributes', $attributes, null);
			return $attributes;
		}
	}

	/**
	 * @return TMap list of attributes to be rendered for the checkbox
	 */
	public function getInputAttributes()
	{
		if ($attributes = $this->getViewState('InputAttributes', null)) {
			return $attributes;
		} else {
			$attributes = new TAttributeCollection;
			$this->setViewState('InputAttributes', $attributes, null);
			return $attributes;
		}
	}

	/**
	 * @return string the value attribute to be rendered
	 */
	protected function getValueAttribute()
	{
		if (($value = $this->getValue()) !== '') {
			return $value;
		} else {
			$attributes = $this->getViewState('InputAttributes', null);
			if ($attributes && $attributes->contains('value')) {
				return $attributes->itemAt('value');
			} elseif ($this->hasAttribute('value')) {
				return $this->getAttribute('value');
			} else {
				return '';
			}
		}
	}

	/**
	 * @return bool whether to render javascript.
	 */
	public function getEnableClientScript()
	{
		return $this->getViewState('EnableClientScript', true);
	}

	/**
	 * @param bool $value whether to render javascript.
	 */
	public function setEnableClientScript($value)
	{
		$this->setViewState('EnableClientScript', TPropertyValue::ensureBoolean($value), true);
	}

	/**
	 * Check if we need a span tag to surround this control. The span tag will be created if
	 * the Text property is set for this control.
	 *
	 * @return bool wether this control needs a surrounding span tag
	 */
	protected function getSpanNeeded()
	{
		return $this->getText() !== '';
	}

	/**
	 * Renders a label beside the checkbox.
	 * @param THtmlWriter $writer the writer for the rendering purpose
	 * @param string $clientID checkbox id
	 * @param string $text label text
	 */
	protected function renderLabel($writer, $clientID, $text)
	{
		$writer->addAttribute('for', $clientID);
		if ($attributes = $this->getViewState('LabelAttributes', null)) {
			$writer->addAttributes($attributes);
		}
		$writer->renderBeginTag('label');
		$writer->write($text);
		$writer->renderEndTag();
	}

	/**
	 * Renders a checkbox input element.
	 * @param THtmlWriter $writer the writer for the rendering purpose
	 * @param string $clientID checkbox id
	 * @param string $onclick onclick js
	 */
	protected function renderInputTag($writer, $clientID, $onclick)
	{
		if ($clientID !== '') {
			$writer->addAttribute('id', $clientID);
		}
		$writer->addAttribute('type', 'checkbox');
		if (($value = $this->getValueAttribute()) !== '') {
			$writer->addAttribute('value', $value);
		}
		if (!empty($onclick)) {
			$writer->addAttribute('onclick', $onclick);
		}
		if (($uniqueID = $this->getUniqueID()) !== '') {
			$writer->addAttribute('name', $uniqueID);
		}
		if ($this->getChecked()) {
			$writer->addAttribute('checked', 'checked');
		}
		if (!$this->getEnabled(true)) {
			$writer->addAttribute('disabled', 'disabled');
		}

		$page = $this->getPage();
		if ($this->getEnabled(true)
			&& $this->getEnableClientScript()
			&& $this->getAutoPostBack()
			&& $page->getClientSupportsJavaScript()) {
			$this->renderClientControlScript($writer);
		}

		if (($accesskey = $this->getAccessKey()) !== '') {
			$writer->addAttribute('accesskey', $accesskey);
		}
		if (($tabindex = $this->getTabIndex()) > 0) {
			$writer->addAttribute('tabindex', "$tabindex");
		}
		if ($attributes = $this->getViewState('InputAttributes', null)) {
			$writer->addAttributes($attributes);
		}
		$writer->renderBeginTag('input');
		$writer->renderEndTag();
	}

	/**
	 * Renders the client-script code.
	 * @param mixed $writer
	 */
	protected function renderClientControlScript($writer)
	{
		$cs = $this->getPage()->getClientScript();
		$cs->registerPostBackControl($this->getClientClassName(), $this->getPostBackOptions());
	}

	/**
	 * Gets the name of the javascript class responsible for performing postback for this control.
	 * This method overrides the parent implementation.
	 * @return string the javascript class name
	 */
	protected function getClientClassName()
	{
		return 'Prado.WebUI.TCheckBox';
	}

	/**
	 * Gets the post back options for this checkbox.
	 * @return array
	 */
	protected function getPostBackOptions()
	{
		$options['ID'] = $this->getClientID();
		$options['ValidationGroup'] = $this->getValidationGroup();
		$options['CausesValidation'] = $this->getCausesValidation();
		$options['EventTarget'] = $this->getUniqueID();
		return $options;
	}
}
