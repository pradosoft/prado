<?php

/**
 * TOutput class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\Exceptions\TInvalidDataValueException;
use Prado\TPropertyValue;
use Prado\Web\THttpUtility;

/**
 * TOutput class
 *
 * TOutput represents the HTML5 `<output>` element. The `<output>` element holds
 * the result of a calculation or a user action, such as a total computed from
 * form fields.
 *
 * Properties:
 * - <b>Text</b>, string: the result, HTML-encoded for rendering. When it is
 *   empty, the body content of the component tag renders instead.
 * - <b>ForControl</b>, string: the IDs of the controls whose values the result
 *   derives from, separated by spaces or commas. Each control must be locatable
 *   via {@see \Prado\Web\UI\TControl::findControl}. It renders as the `for`
 *   attribute of their client IDs. An invisible control is left out.
 *
 * The `name` attribute renders the {@see \Prado\Web\UI\TControl::getUniqueID UniqueID},
 * so the element is in the form's `elements` collection. The browser does not
 * post an `<output>` with the form.
 * ```html
 * <com:TTextBox ID="Price" TextMode="Number" />
 * <com:TTextBox ID="Quantity" TextMode="Number" />
 * <com:TLabel ForControl="Total" Text="Total" />
 * <com:TOutput ID="Total" ForControl="Price Quantity" Text="0.00" />
 * ```
 *
 * Accessibility: browsers expose `<output>` with the `status` role. It is a
 * polite live region, and screen readers announce its whole content when it
 * changes. The element is labelable; a {@see TLabel} with
 * {@see TLabel::setForControl ForControl} names it. ForControl relates the
 * output to its source controls and does not name it. A live region announces
 * changes only when it is in the page before its content changes; a TOutput
 * that becomes visible with its result is not announced.
 *
 * Do not add the live region attributes by hand:
 * - `role="status"`, `aria-live="polite"`, and `aria-atomic="true"` duplicate
 *   the implicit semantics.
 * - `aria-live="off"` stops the announcements.
 * - `role="alert"` or `aria-live="assertive"` interrupts the user; it suits
 *   urgent messages only.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TOutput extends TWebControl implements \Prado\IDataRenderer
{
	/**
	 * @return string the tag name of the control, 'output'
	 */
	protected function getTagName()
	{
		return 'output';
	}

	/**
	 * Adds the `name` and `for` attributes to the renderer.
	 * @param \Prado\Web\UI\THtmlWriter $writer the renderer
	 * @throws TInvalidDataValueException if a ForControl control cannot be found
	 */
	protected function addAttributesToRender($writer)
	{
		$writer->addAttribute('name', $this->getUniqueID());
		if (($for = $this->getForAttributeValue()) !== '') {
			$writer->addAttribute('for', $for);
		}
		parent::addAttributesToRender($writer);
	}

	/**
	 * Renders the HTML-encoded Text, or the body content when Text is empty.
	 * @param \Prado\Web\UI\THtmlWriter $writer the renderer
	 */
	public function renderContents($writer)
	{
		if (($text = $this->getText()) === '') {
			parent::renderContents($writer);
		} else {
			$writer->write(THttpUtility::htmlEncode($text));
		}
	}

	/**
	 * Resolves {@see getForControl ForControl} to the client IDs of its visible controls.
	 * @throws TInvalidDataValueException if a ForControl control cannot be found
	 * @return string the space-separated client IDs, '' when there are none
	 */
	protected function getForAttributeValue(): string
	{
		$ids = [];
		foreach ($this->getForControlIDs() as $id) {
			if (!($control = $this->findControl($id))) {
				throw new TInvalidDataValueException('output_forcontrol_invalid', $id);
			}
			if ($control->getVisible(true)) {
				$ids[] = $control->getClientID();
			}
		}
		return implode(' ', $ids);
	}

	/**
	 * @return string[] the control IDs of {@see getForControl ForControl}
	 */
	public function getForControlIDs(): array
	{
		return preg_split('/[\s,]+/', $this->getForControl(), -1, PREG_SPLIT_NO_EMPTY);
	}

	/**
	 * @return string the result text
	 */
	public function getText()
	{
		return $this->getViewState('Text', '');
	}

	/**
	 * @param string $value the result text
	 */
	public function setText($value)
	{
		$this->setViewState('Text', TPropertyValue::ensureString($value), '');
	}

	/**
	 * Returns the result text. This method is required by {@see \Prado\IDataRenderer}.
	 * @return string the result text
	 */
	public function getData()
	{
		return $this->getText();
	}

	/**
	 * Sets the result text. This method is required by {@see \Prado\IDataRenderer}.
	 * @param string $value the result text
	 */
	public function setData($value)
	{
		$this->setText($value);
	}

	/**
	 * @return string the IDs of the controls the result derives from
	 */
	public function getForControl()
	{
		return $this->getViewState('ForControl', '');
	}

	/**
	 * @param string $value the IDs of the controls the result derives from,
	 *   separated by spaces or commas
	 */
	public function setForControl($value)
	{
		$this->setViewState('ForControl', trim(TPropertyValue::ensureString($value)), '');
	}
}
