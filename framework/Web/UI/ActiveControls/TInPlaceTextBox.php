<?php

/**
 * TInPlaceTextBox class file.
 *
 * @author Wei Zhuo <weizhuo[at]gamil[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\ActiveControls;

use Prado\Prado;
use Prado\TPropertyValue;
use Prado\Web\UI\WebControls\TTextBox;
use Prado\Web\UI\WebControls\TWebControl;

/**
 * TInPlaceTextBox Class
 *
 * TInPlaceTextBox is a component rendered as a label and allows its
 * contents to be edited by changing the label to a textbox when
 * the label is clicked or when another control or html element with
 * ID given by {@see setEditTriggerControlID EditTriggerControlID} is clicked.
 *
 * If the {@see OnLoadingText} event is handled, a callback request is
 * made when the label is clicked, while the request is being made the
 * textbox is disabled from editing. The {@see OnLoadingText} event allows
 * you to update the content of the textbox before the client is allowed
 * to edit the content. After the callback request returns successfully,
 * the textbox is enabled and the contents is then allowed to be edited.
 *
 * Once the textbox loses focus, if {@see setAutoPostBack AutoPostBack}
 * is true and the textbox content has changed, a callback request is made and
 * the {@see OnTextChanged} event is raised like that of the TActiveTextBox.
 * During the request, the textbox is disabled.
 *
 * After the callback request returns sucessfully, the textbox is enabled.
 * If the {@see setAutoHideTextBox AutoHideTextBox} property is true, then
 * the textbox will be hidden and the label is then shown.
 *
 * Since 3.1.2, you can set the {@see setReadOnly ReadOnly} property to make
 * the control not editable. This property can be also changed on callback
 *
 * Since 4.4.0, when the text is empty the label shows
 * {@see setEmptyDisplayText EmptyDisplayText}, keeping the label clickable.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @since 3.1
 */
class TInPlaceTextBox extends TActiveTextBox
{
	use TInPlaceControlTrait;

	/**
	 * Sets the auto post back to true by default.
	 */
	public function __construct()
	{
		parent::__construct();
		$this->setAutoPostBack(true);
	}

	/**
	 * Alias of {@see setAutoHideEditor AutoHideEditor}, the name shared by the
	 * in-place control family.
	 * @param bool $value true to hide the textbox after losing focus.
	 * @deprecated 4.4.0 use {@see setAutoHideEditor AutoHideEditor}
	 */
	public function setAutoHideTextBox($value)
	{
		$this->setAutoHideEditor($value);
	}

	/**
	 * Alias of {@see getAutoHideEditor AutoHideEditor}, the name shared by the
	 * in-place control family.
	 * @return bool true will hide the textbox after losing focus.
	 * @deprecated 4.4.0 use {@see getAutoHideEditor AutoHideEditor}
	 */
	public function getAutoHideTextBox()
	{
		return $this->getAutoHideEditor();
	}

	/**
	 * Alias of {@see setDisplayEditor DisplayEditor}, the name shared by the
	 * in-place control family.
	 * @param bool $value true to display the edit textbox
	 * @deprecated 4.4.0 use {@see setDisplayEditor DisplayEditor}
	 */
	public function setDisplayTextBox($value)
	{
		$this->setDisplayEditor($value);
	}

	/**
	 * Alias of {@see getDisplayEditor DisplayEditor}, the name shared by the
	 * in-place control family.
	 * @return bool true to display the edit textbox
	 * @deprecated 4.4.0 use {@see getDisplayEditor DisplayEditor}
	 */
	public function getDisplayTextBox()
	{
		return $this->getDisplayEditor();
	}

	/**
	 * On callback response, the inner HTMl of the label and the
	 * value of the textbox is updated
	 * @param string $value the text value of the label
	 */
	public function setText($value)
	{
		if (TTextBox::getText() === $value) {
			return;
		}

		TTextBox::setText($value);
		if ($this->getActiveControl()->canUpdateClientSide()) {
			$this->callClientFunction('setLabelText', $value);
			$this->getPage()->getCallbackClient()->setValue($this, $value);
		}
	}

	/**
	 * Update ClientSide Readonly property
	 * @param bool $value value
	 * @since 3.1.2
	 */
	public function setReadOnly($value)
	{
		$value = TPropertyValue::ensureBoolean($value);
		if (TTextBox::getReadOnly() === $value) {
			return;
		}

		TTextBox::setReadOnly($value);
		if ($this->getActiveControl()->canUpdateClientSide()) {
			$this->callClientFunction('setReadOnly', $value);
		}
	}

	/**
	 * @return string tag name of the label.
	 */
	protected function getTagName()
	{
		return 'span';
	}

	/**
	 * Renders the body content of the label. An empty text renders
	 * {@see getEmptyDisplayText EmptyDisplayText} when set.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer for rendering
	 */
	public function renderContents($writer)
	{
		if (($text = $this->getText()) !== '') {
			$writer->write($text);
		} elseif (($emptyText = $this->getEmptyDisplayText()) !== '') {
			$writer->write($emptyText);
		} else {
			parent::renderContents($writer);
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
		if (is_array($action) && $action[0] === '__InlineEditor_loadExternalText__') {
			$parameter = new TCallbackEventParameter($this->getResponse(), $action[1]);
			$this->onLoadingText($parameter);
		}
		$this->raiseEvent('OnCallback', $this, $param);
	}

	/**
	 * @return array callback options.
	 */
	protected function getPostBackOptions()
	{
		$options = parent::getPostBackOptions();
		$options['ID'] = $this->getLabelClientID();
		$options['TextBoxID'] = $this->getClientID();
		$options['EditorID'] = $this->getClientID();
		$options['ExternalControl'] = $this->getExternalControlID();
		$options['AutoHide'] = $this->getAutoHideEditor() == false ? '' : true;
		$options['AutoPostBack'] = $this->getAutoPostBack() == false ? '' : true;
		$options['EmptyDisplayText'] = $this->getEmptyDisplayText();
		$options['DisplayEditor'] = $this->getDisplayEditor();
		// The created text input has no associated label element; ToolTip names
		// it, with a localized default so the editor is never nameless.
		$options['EditorLabel'] = $this->getToolTip() !== '' ? $this->getToolTip() : Prado::localize('Edit value');
		$options['Columns'] = $this->getColumns();
		if ($this->getTextMode() === 'MultiLine') {
			$options['Rows'] = $this->getRows();
			$options['Wrap'] = $this->getWrap() == false ? '' : true;
		} else {
			$length = $this->getMaxLength();
			$options['MaxLength'] = $length > 0 ? $length : '';
		}

		if ($this->hasEventHandler('OnLoadingText')) {
			$options['LoadTextOnEdit'] = true;
		}

		$options['ReadOnly'] = $this->getReadOnly();
		return $options;
	}

	/**
	 * Raised when editing the content is requsted to be loaded from the
	 * server side.
	 * @param TCallbackEventParameter $param event parameter to be passed to the event handlers
	 */
	public function onLoadingText($param)
	{
		$this->raiseEvent('OnLoadingText', $this, $param);
	}

	/**
	 * @return string corresponding javascript class name for this TInPlaceTextBox
	 */
	protected function getClientClassName()
	{
		return 'Prado.WebUI.TInPlaceTextBox';
	}

	/**
	 * Ensure that the ID attribute is rendered and registers the javascript code
	 * for initializing the active control.
	 * @param mixed $writer
	 */
	protected function addAttributesToRender($writer)
	{
		//calls the TWebControl to avoid rendering other attribute normally render for a textbox.
		TWebControl::addAttributesToRender($writer);
		$writer->addAttribute('id', $this->getLabelClientID());
		$this->renderEmptyDisplayAttribute($writer, $this->getText() === '');
		$this->renderLabelAccessibilityAttributes($writer, $this->getReadOnly());
		$this->getActiveControl()->registerCallbackClientScript(
			$this->getClientClassName(),
			$this->getPostBackOptions()
		);
	}
}
