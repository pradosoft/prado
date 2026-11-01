<?php

/**
 * TTeleport class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\IO\TTextWriter;
use Prado\Web\UI\ActiveControls\TCallbackResponseWriter;

/**
 * TTeleport class
 *
 * TTeleport renders its content after the closing tag of the page
 * {@see \Prado\Web\UI\TForm}, outside the form. The content is a `<div>` with the
 * `id` of the control and a `data-prado-teleport` attribute. In the control tree,
 * and so for postback data, events, and callbacks, the children stay where the
 * template puts them.
 *
 * Content outside the page form has these uses:
 * - A native `<form method="dialog">` in a {@see TDialog} works. The HTML parser
 *   ignores a `<form>` start tag inside an open form, so inside the page form the
 *   dialog form does not exist and its buttons submit the page form.
 * - An overlay or a toast is positioned and stacked free of the ancestors at the
 *   position of the TTeleport, such as one with a `transform` or `overflow: hidden`.
 *
 * The output goes where the control renders:
 *
 * | Render | Output |
 * |---|---|
 * | inside the page form | after `</form>`; the form renders its end scripts after the content |
 * | inside another TTeleport | in place, in the outer content |
 * | outside the page form | in place |
 * | in a callback, by an ancestor's update | replaces the `<div>` after the form, through `Prado.WebUI.TTeleport.replace()` |
 *
 * An active control inside the content updates by its ID in a callback, as anywhere
 * on the page, and its `ActiveControl.UpdateMode` Morph applies.
 *
 * The browser submits a field with its form owner only. The client control
 * `Prado.WebUI.TTeleport` sets the HTML `form` attribute of each field and button in
 * the content to the page form ID, so a postback submits them. A field inside a
 * nested form, such as `<form method="dialog">`, belongs to that form, and a field
 * with its own `form` attribute keeps it. A `MutationObserver` applies this to content
 * that a callback or a script adds later. A callback posts every field on the page,
 * the fields of a dialog form included.
 *
 * ```html
 * <com:TTeleport>
 *     <com:TActiveDialog ID="RenameDialog" Attributes.aria-labelledby="RenameTitle" OnClose="renameClosed">
 *         <h2 id="RenameTitle">Rename</h2>
 *         <com:TLabel ForControl="NewName" Text="Name" />
 *         <com:TTextBox ID="NewName" />
 *         <form method="dialog">
 *             <button value="save">Save</button>
 *             <button value="cancel">Cancel</button>
 *         </form>
 *     </com:TActiveDialog>
 * </com:TTeleport>
 * ```
 *
 * A dialog button closes the dialog without script and sets its `returnValue`. The
 * close callback of the TActiveDialog posts the `NewName` field.
 *
 * The content moves to the end of the reading and Tab order of the page. It suits
 * a modal dialog, which takes the focus, and a toast in a live region. Content that
 * belongs to the reading order at its position stays in place.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TTeleport extends TWebControl
{
	/**
	 * The client-side class of the control.
	 */
	public const CLIENT_CLASS = 'Prado.WebUI.TTeleport';

	/**
	 * @return string tag name of the content element
	 */
	protected function getTagName()
	{
		return 'div';
	}

	/**
	 * Renders the content after the page form, in place, or as a callback update.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 */
	public function render($writer)
	{
		if ($this->getIsInsideTeleport()) {
			parent::render($writer);
			return;
		}
		$page = $this->getPage();
		if ($page->getIsCallback()) {
			$this->renderCallbackUpdate();
			return;
		}
		$this->registerClientScript();
		if ($page->getInFormRender() && ($form = $page->getForm()) !== null) {
			$form->registerTeleportContent($this->getUniqueID(), $this->renderContent($writer));
			return;
		}
		parent::render($writer);
	}

	/**
	 * Adds the `id` and the `data-prado-teleport` attribute to the renderer.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 */
	protected function addAttributesToRender($writer)
	{
		parent::addAttributesToRender($writer);
		$writer->addAttribute('id', $this->getClientID());
		$writer->addAttribute('data-prado-teleport', '');
	}

	/**
	 * @return bool whether an ancestor is a TTeleport, whose content carries this one
	 */
	public function getIsInsideTeleport(): bool
	{
		for ($control = $this->getParent(); $control !== null; $control = $control->getParent()) {
			if ($control instanceof TTeleport) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Renders the content element through the writer into a string. The writer keeps
	 * its render filter context, and its output is restored afterward.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 * @return string the HTML of the content element
	 */
	protected function renderContent($writer): string
	{
		$output = $writer->getWriter();
		$writer->setWriter(new TTextWriter());
		try {
			parent::render($writer);
			return $writer->flush();
		} finally {
			$writer->setWriter($output);
		}
	}

	/**
	 * Sends the content element to the client in a callback that updates an ancestor.
	 * The client replaces the element after the page form, or adds it there. The
	 * content goes in a response boundary when the response has the callback adapter,
	 * and in the call otherwise.
	 */
	protected function renderCallbackUpdate(): void
	{
		$writer = $this->getResponse()->createHtmlWriter();
		$output = $writer->getWriter();
		if ($output instanceof TCallbackResponseWriter) {
			parent::render($writer);
			$content = null;
			$boundary = $output->getBoundary();
		} else {
			$content = $this->renderContent($writer);
			$boundary = null;
		}
		$this->registerClientScript();
		$this->getPage()->getCallbackClient()->callClientFunction(
			self::CLIENT_CLASS . '.replace',
			[$this, $this->getFormClientID(), $content, $boundary]
		);
	}

	/**
	 * Registers the `teleport` script package and the client control.
	 */
	protected function registerClientScript(): void
	{
		$cs = $this->getPage()->getClientScript();
		$cs->registerPradoScript('teleport');
		$cs->registerPostBackControl(self::CLIENT_CLASS, $this->getClientOptions());
	}

	/**
	 * @return array the options of the client control: `ID` and `FormID`
	 */
	protected function getClientOptions(): array
	{
		return ['ID' => $this->getClientID(), 'FormID' => $this->getFormClientID()];
	}

	/**
	 * @return string the client ID of the page form, or '' without one
	 */
	protected function getFormClientID(): string
	{
		$form = $this->getPage()->getForm();
		return $form !== null ? $form->getClientID() : '';
	}
}
