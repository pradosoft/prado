<?php

/**
 * TConfirmButton class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\IO\TTextWriter;
use Prado\Prado;
use Prado\TPropertyValue;
use Prado\Web\THttpUtility;

/**
 * TConfirmButton class
 *
 * TConfirmButton is a {@see TButton} that asks for confirmation in a modal dialog
 * before it posts back. It replaces `onclick="return confirm('...')"` with an
 * accessible `<dialog role="alertdialog">` that works without script.
 *
 * With {@see setConfirmText ConfirmText} set, the button renders as an invoker
 * (`commandfor`, `command="show-modal"`) of its confirmation dialog. The dialog
 * holds a heading, the message, a Cancel button that closes it, and an OK button
 * that submits the page form as the TConfirmButton: OnClick and OnCommand are
 * raised, and validation runs, as for a TButton. Cancel takes the focus when the
 * dialog opens. The button keeps CssClass, Style, and the other attributes of the
 * control; ButtonTag and ButtonType do not apply.
 *
 * | Property | Default | Renders |
 * |---|---|---|
 * | {@see setConfirmText ConfirmText} | '' | the message; '' renders a plain TButton |
 * | {@see setConfirmHeaderText ConfirmHeaderText} | localized "Confirm" | the heading that names the dialog |
 * | {@see setConfirmButtonText ConfirmButtonText} | localized "OK" | the text of the button that posts back |
 * | {@see setCancelButtonText CancelButtonText} | localized "Cancel" | the text of the button that closes the dialog |
 * | {@see setCssUrl CssUrl} | default | the stylesheet of the dialog, the {@see TDialog} stylesheet |
 *
 * Inside the page form, the dialog renders after `</form>`, as a {@see TTeleport}
 * does, so it does not break the paragraph or table around the button. Its OK
 * button names the page form with the `form` attribute. When client validation
 * fails, the dialog closes so the error messages show.
 * ```xml
 * <com:TConfirmButton Text="Delete" ConfirmText="Delete this record?"
 *     ConfirmButtonText="Delete" OnClick="deleteRecord" />
 * ```
 *
 * {@see setEncode Encode} HTML-encodes ConfirmText and ConfirmHeaderText. The
 * button and dialog button texts are always HTML-encoded.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TConfirmButton extends TButton
{
	use TEncodeTrait;

	/**
	 * @return string the confirmation message. Defaults to '', no confirmation.
	 */
	public function getConfirmText()
	{
		return $this->getViewState('ConfirmText', '');
	}

	/**
	 * Sets the confirmation message; '' renders a plain TButton that posts back at once.
	 * @param string $value the confirmation message
	 */
	public function setConfirmText($value)
	{
		$this->setViewState('ConfirmText', TPropertyValue::ensureString($value), '');
	}

	/**
	 * @return string the heading of the dialog. Defaults to the localized "Confirm".
	 */
	public function getConfirmHeaderText()
	{
		return $this->getViewState('ConfirmHeaderText', '') ?: Prado::localize('Confirm');
	}

	/**
	 * Sets the heading of the dialog; '' restores the localized "Confirm".
	 * @param string $value the heading of the dialog
	 */
	public function setConfirmHeaderText($value)
	{
		$this->setViewState('ConfirmHeaderText', TPropertyValue::ensureString($value), '');
	}

	/**
	 * @return string the text of the button that posts back. Defaults to the localized "OK".
	 */
	public function getConfirmButtonText()
	{
		return $this->getViewState('ConfirmButtonText', '') ?: Prado::localize('OK');
	}

	/**
	 * Sets the text of the button that posts back; '' restores the localized "OK".
	 * @param string $value the text of the button that posts back
	 */
	public function setConfirmButtonText($value)
	{
		$this->setViewState('ConfirmButtonText', TPropertyValue::ensureString($value), '');
	}

	/**
	 * @return string the text of the button that closes the dialog. Defaults to the
	 *   localized "Cancel".
	 */
	public function getCancelButtonText()
	{
		return $this->getViewState('CancelButtonText', '') ?: Prado::localize('Cancel');
	}

	/**
	 * Sets the text of the button that closes the dialog; '' restores the localized "Cancel".
	 * @param string $value the text of the button that closes the dialog
	 */
	public function setCancelButtonText($value)
	{
		$this->setViewState('CancelButtonText', TPropertyValue::ensureString($value), '');
	}

	/**
	 * @return string URL of the stylesheet of the dialog. Defaults to 'default', the
	 *   bundled {@see TDialog} stylesheet. An empty string registers no stylesheet.
	 */
	public function getCssUrl()
	{
		return $this->getViewState('CssUrl', 'default');
	}

	/**
	 * Sets the URL of the stylesheet of the dialog.
	 * @param string $value stylesheet URL, 'default' for the bundled stylesheet,
	 *   or empty to register no stylesheet
	 */
	public function setCssUrl($value)
	{
		$this->setViewState('CssUrl', TPropertyValue::ensureString($value), 'default');
	}

	/**
	 * @return bool whether the button asks for confirmation: ConfirmText is set and
	 *   the button is enabled
	 */
	public function getHasConfirmation()
	{
		return $this->getConfirmText() !== '' && $this->getEnabled(true);
	}

	/**
	 * @return string the client ID of the confirmation dialog
	 */
	public function getDialogClientID()
	{
		return $this->getClientID() . '_confirm';
	}

	/**
	 * Registers the stylesheet of the dialog.
	 * @param mixed $param event parameter
	 */
	public function onPreRender($param)
	{
		parent::onPreRender($param);
		if ($this->getHasConfirmation() && ($url = $this->getCssUrl()) !== '') {
			if ($url === 'default') {
				$url = $this->publishAsset('assets' . DIRECTORY_SEPARATOR . 'dialog.css', TDialog::class);
			}
			$this->getPage()->getClientScript()->registerStyleSheetFile($url, $url);
		}
	}

	/**
	 * Renders the button. A button that asks for confirmation renders as the invoker
	 * of its dialog, and the dialog after the page form, or in place outside it and
	 * in a callback.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 */
	public function render($writer)
	{
		if (!$this->getHasConfirmation()) {
			parent::render($writer);
			return;
		}
		$this->renderInvoker($writer);
		$page = $this->getPage();
		if (!$page->getIsCallback() && $page->getInFormRender() && ($form = $page->getForm()) !== null) {
			$form->registerTeleportContent($this->getDialogClientID(), $this->renderDialogToString($writer, $form->getClientID()));
		} else {
			$this->renderDialog($writer, '');
		}
	}

	/**
	 * Renders the visible button: an invoker that opens the dialog modally, with
	 * the attributes of the control.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 */
	protected function renderInvoker($writer)
	{
		$this->getPage()->ensureRenderInForm($this);
		$this->setEnsureId(true);
		TWebControl::addAttributesToRender($writer);
		$writer->addAttribute('type', 'button');
		$writer->addAttribute('aria-haspopup', 'dialog');
		$writer->addAttribute('commandfor', $this->getDialogClientID());
		$writer->addAttribute('command', 'show-modal');
		$writer->renderBeginTag('button');
		$writer->write(THttpUtility::htmlEncode($this->getText()));
		$writer->renderEndTag();
	}

	/**
	 * Renders the dialog into a string through the writer, whose output is restored.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 * @param string $formID the client ID of the form the OK button submits
	 * @return string the HTML of the dialog
	 */
	protected function renderDialogToString($writer, $formID)
	{
		$output = $writer->getWriter();
		$writer->setWriter(new TTextWriter());
		try {
			$this->renderDialog($writer, $formID);
			return $writer->flush();
		} finally {
			$writer->setWriter($output);
		}
	}

	/**
	 * Renders the confirmation dialog: its heading, the message, and the Cancel
	 * and OK buttons.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 * @param string $formID the client ID of the form the OK button submits, or ''
	 *   when the dialog renders inside it
	 */
	protected function renderDialog($writer, $formID)
	{
		$id = $this->getDialogClientID();
		$writer->addAttribute('id', $id);
		$writer->addAttribute('role', 'alertdialog');
		$writer->addAttribute('class', 'prado-confirm');
		$writer->addAttribute('aria-labelledby', $id . '_header');
		$writer->addAttribute('aria-describedby', $id . '_message');
		$writer->renderBeginTag('dialog');

		$writer->addAttribute('class', 'dialog-header');
		$writer->renderBeginTag('div');
		$writer->addAttribute('id', $id . '_header');
		$writer->addAttribute('class', 'dialog-title');
		$writer->renderBeginTag('h2');
		$writer->write($this->encodeText($this->getConfirmHeaderText()));
		$writer->renderEndTag();
		$writer->renderEndTag();

		$writer->addAttribute('id', $id . '_message');
		$writer->renderBeginTag('p');
		$writer->write($this->encodeText($this->getConfirmText()));
		$writer->renderEndTag();

		$writer->addAttribute('class', 'dialog-buttons');
		$writer->renderBeginTag('div');
		$writer->addAttribute('type', 'button');
		$writer->addAttribute('commandfor', $id);
		$writer->addAttribute('command', 'close');
		$writer->addAttribute('autofocus', 'autofocus');
		$writer->renderBeginTag('button');
		$writer->write(THttpUtility::htmlEncode($this->getCancelButtonText()));
		$writer->renderEndTag();
		$this->renderConfirmButton($writer, $formID);
		$writer->renderEndTag();

		$writer->renderEndTag();
	}

	/**
	 * Renders the OK button, which submits the form as this control and runs its
	 * client validation.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 * @param string $formID the client ID of the form the button submits, or ''
	 */
	protected function renderConfirmButton($writer, $formID)
	{
		$writer->addAttribute('type', 'submit');
		$writer->addAttribute('id', $this->getConfirmButtonClientID());
		$writer->addAttribute('name', $this->getUniqueID());
		$writer->addAttribute('value', $this->getText());
		if ($formID !== '') {
			$writer->addAttribute('form', $formID);
		}
		$this->addFormSubmitterAttributesToRender($writer);
		if ($this->getEnableClientScript() && $this->needPostBackScript()) {
			$this->getPage()->getClientScript()->registerPostBackControl($this->getClientClassName(), $this->getPostBackOptions());
		}
		$writer->renderBeginTag('button');
		$writer->write(THttpUtility::htmlEncode($this->getConfirmButtonText()));
		$writer->renderEndTag();
	}

	/**
	 * @return string the client ID of the OK button
	 */
	public function getConfirmButtonClientID()
	{
		return $this->getDialogClientID() . '_ok';
	}

	/**
	 * @return array the postback options of the OK button, which the client control handles
	 */
	protected function getPostBackOptions()
	{
		$options = parent::getPostBackOptions();
		if ($this->getHasConfirmation()) {
			$options['ID'] = $this->getConfirmButtonClientID();
		}
		return $options;
	}

	/**
	 * @return string the client class, which closes the dialog when validation fails
	 */
	protected function getClientClassName()
	{
		return $this->getHasConfirmation() ? 'Prado.WebUI.TConfirmButton' : parent::getClientClassName();
	}
}
