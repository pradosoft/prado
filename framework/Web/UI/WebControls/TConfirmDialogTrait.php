<?php

/**
 * TConfirmDialogTrait trait file.
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
 * TConfirmDialogTrait trait.
 *
 * TConfirmDialogTrait backs the confirmation dialog of {@see TConfirmButton} and
 * {@see TConfirmSubmit}: a modal `<dialog role="alertdialog">` with a heading, the
 * message, a Cancel button that takes the focus and closes it, and an OK button that
 * the using class renders through {@see renderConfirmButton}. Inside the page form,
 * the dialog renders after `</form>`, so it does not break the paragraph or table
 * around the control.
 *
 * {@see setEncode Encode} HTML-encodes ConfirmText and ConfirmHeaderText. The button
 * texts are always HTML-encoded.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
trait TConfirmDialogTrait
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
	 * Sets the confirmation message; '' asks for no confirmation.
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
	 * @return string the client ID of the confirmation dialog
	 */
	public function getDialogClientID()
	{
		return $this->getClientID() . '_confirm';
	}

	/**
	 * @return string the client ID of the OK button
	 */
	public function getConfirmButtonClientID()
	{
		return $this->getDialogClientID() . '_ok';
	}

	/**
	 * Registers the stylesheet of {@see getCssUrl CssUrl}. 'default' publishes the
	 * bundled {@see TDialog} stylesheet; an empty string registers nothing.
	 */
	protected function registerDialogStyleSheet()
	{
		if (($url = $this->getCssUrl()) === '') {
			return;
		}
		if ($url === 'default') {
			$url = $this->publishAsset('assets' . DIRECTORY_SEPARATOR . 'dialog.css', TDialog::class);
		}
		$this->getPage()->getClientScript()->registerStyleSheetFile($url, $url);
	}

	/**
	 * Renders the dialog after the page form, or in place outside it and in a callback.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 */
	protected function renderConfirmDialog($writer)
	{
		$page = $this->getPage();
		if (!$page->getIsCallback() && $page->getInFormRender() && ($form = $page->getForm()) !== null) {
			$form->registerTeleportContent($this->getDialogClientID(), $this->renderDialogToString($writer, $form->getClientID()));
		} else {
			$this->renderDialog($writer, '');
		}
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
	 * Renders the OK button of the dialog.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 * @param string $formID the client ID of the page form when the dialog renders
	 *   after it, or ''
	 */
	abstract protected function renderConfirmButton($writer, $formID);
}
