<?php

/**
 * TDialog class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\TPropertyValue;
use Prado\Web\Javascripts\TJavaScript;

/**
 * TDialog class
 *
 * TDialog represents the HTML5 `<dialog>` element. The `<dialog>` element represents
 * a dialog or other interactive component, such as a dismissible alert, inspector,
 * or subwindow.
 *
 * | Property | Default | Renders |
 * |---|---|---|
 * | {@see setOpen Open} | false | the dialog shown on load |
 * | {@see setModal Modal} | false | an Open dialog shown modally |
 * | {@see setClosedBy ClosedBy} | Auto | `closedby`: the user actions that close the dialog ({@see TDialogClosedBy}) |
 *
 * | Open | Modal | Rendering |
 * |---|---|---|
 * | false | any | no `open` attribute; the dialog is closed |
 * | true | false | the `open` attribute; the dialog shows non-modally |
 * | true | true | the client ID and an end script that calls `showModal()`; without JavaScript the dialog stays closed |
 *
 * Open is kept in view state. Opening or closing the dialog in the browser does
 * not change it, so a postback handler that opened the dialog sets Open to false
 * to close it.
 *
 * A {@see TButton} with {@see TButton::setCommandFor CommandFor} opens and closes
 * the dialog in the browser without script, inside the page form: `show-modal`
 * opens it modally, and `close` closes it with the button's `value` as its
 * `returnValue`. {@see \Prado\Web\UI\ActiveControls\TActiveDialog} raises server
 * events when the dialog opens or closes.
 *
 * Template usage:
 * ```html
 * <com:TButton Text="Edit" CommandFor="EditDialog" Command="show-modal" />
 * <com:TDialog ID="EditDialog" ClosedBy="Any" Attributes.aria-labelledby="EditTitle">
 *     <h2 id="EditTitle">Edit name</h2>
 *     <com:TTextBox ID="Name" />
 *     <com:TButton Text="Save" OnClick="saveClicked" />
 *     <com:TButton Text="Cancel" CommandFor="EditDialog" Command="close" />
 * </com:TDialog>
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TDialog extends TWebControl
{
	/**
	 * @return string tag name
	 */
	protected function getTagName()
	{
		return 'dialog';
	}

	/**
	 * @return bool whether the dialog is open
	 */
	public function getOpen()
	{
		return $this->getViewState('Open', false);
	}

	/**
	 * Sets whether the dialog is open.
	 * @param bool $value whether the dialog is open
	 */
	public function setOpen($value)
	{
		$this->setViewState('Open', TPropertyValue::ensureBoolean($value), false);
	}

	/**
	 * @return bool whether an {@see getOpen Open} dialog shows modally
	 */
	public function getModal()
	{
		return $this->getViewState('Modal', false);
	}

	/**
	 * Sets whether an {@see getOpen Open} dialog shows modally. A modal dialog makes
	 * the rest of the page inert and shows above it, such as a dialog that
	 * reopens after its postback fails validation.
	 * @param bool $value whether an Open dialog shows modally
	 */
	public function setModal($value)
	{
		$this->setViewState('Modal', TPropertyValue::ensureBoolean($value), false);
	}

	/**
	 * @return TDialogClosedBy the user actions that close the dialog. Defaults to TDialogClosedBy::Auto.
	 */
	public function getClosedBy()
	{
		return $this->getViewState('ClosedBy', TDialogClosedBy::Auto);
	}

	/**
	 * Sets the user actions that close the dialog (`closedby`). Any closes it on a
	 * click outside it (light dismiss) or Esc.
	 * @param TDialogClosedBy $value the user actions that close the dialog
	 */
	public function setClosedBy($value)
	{
		$this->setViewState('ClosedBy', TPropertyValue::ensureEnum($value, TDialogClosedBy::class), TDialogClosedBy::Auto);
	}

	/**
	 * Adds attribute name-value pairs to renderer.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 */
	protected function addAttributesToRender($writer)
	{
		parent::addAttributesToRender($writer);
		if (($closedBy = $this->getClosedBy()) !== TDialogClosedBy::Auto) {
			$writer->addAttribute('closedby', strtolower($closedBy));
		}
		if (!$this->getOpen()) {
			return;
		}
		if ($this->getModal()) {
			$writer->addAttribute('id', $this->getClientID());
			$this->registerShowModalScript();
		} else {
			$writer->addAttribute('open', 'open');
		}
	}

	/**
	 * Registers the end script that shows the {@see getOpen Open} dialog modally.
	 * The `open` attribute shows a dialog non-modally, so a Modal dialog renders
	 * without it and the script calls `showModal()`.
	 */
	protected function registerShowModalScript()
	{
		$id = TJavaScript::quoteString($this->getClientID());
		$this->getPage()->getClientScript()->registerEndScript(
			'prado:dialog:' . $this->getClientID(),
			"(function(d) { if (d && !d.open) { d.showModal(); } })(document.getElementById($id));"
		);
	}
}
