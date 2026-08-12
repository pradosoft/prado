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

/**
 * TDialog class
 *
 * TDialog represents the HTML5 `<dialog>` element. The `<dialog>` element represents
 * a dialog or other interactive component, such as a dismissible alert, inspector,
 * or subwindow.
 *
 * Properties:
 * - <b>Open</b>, bool — whether the dialog is shown. When `true`, the `open`
 *   attribute is rendered and the dialog displays non-modally. Defaults to `false`.
 *
 * A dialog rendered with the `open` attribute is non-modal. Modal display requires
 * the client-side `showModal()` method; see {@see \Prado\Web\UI\ActiveControls\TActiveDialog}
 * for server-driven open and close with callback events.
 *
 * Template usage:
 * ```html
 * <com:TDialog ID="Notice" Open="true">
 *     <p>Settings saved.</p>
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
	 * Adds attribute name-value pairs to renderer.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 */
	protected function addAttributesToRender($writer)
	{
		parent::addAttributesToRender($writer);
		if ($this->getOpen()) {
			$writer->addAttribute('open', 'open');
		}
	}
}
