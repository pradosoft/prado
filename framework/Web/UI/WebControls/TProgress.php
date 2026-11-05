<?php

/**
 * TProgress class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\Exceptions\TInvalidDataValueException;
use Prado\TPropertyValue;

/**
 * TProgress class
 *
 * TProgress represents the HTML5 `<progress>` element. The `<progress>` element
 * represents the completion progress of a task.
 *
 * Properties:
 * - <b>Value</b>, ?float — how much of the task has completed, rendered as the
 *   `value` attribute. Must be 0 or greater. `null` (the default) renders no
 *   `value` attribute, which displays the browser's indeterminate progress bar.
 * - <b>Max</b>, float — how much work the task requires in total, rendered as
 *   the `max` attribute when not `1.0`. Must be greater than 0. Defaults to `1.0`.
 *
 * Child content renders inside the element as fallback for user agents without
 * `<progress>` support. Associate a caption with {@see TLabel} and its
 * {@see TLabel::setForControl ForControl} property.
 *
 * The bar's colors are styled with CSS pseudo-elements (`::-webkit-progress-bar`,
 * `::-webkit-progress-value`, `::-moz-progress-bar`) or the `accent-color`
 * property; use {@see setCssClass CssClass} with a stylesheet for theming.
 *
 * Template usage:
 * ```html
 * <com:TProgress Value="0.7" />
 * <com:TProgress ID="Upload" Value="35" Max="100" />
 * <com:TProgress />  <!-- no Value: indeterminate bar -->
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TProgress extends TWebControl
{
	/**
	 * @return string tag name
	 */
	protected function getTagName()
	{
		return 'progress';
	}

	/**
	 * @return ?float how much of the task has completed; null (the default)
	 *   displays an indeterminate progress bar
	 */
	public function getValue()
	{
		return $this->getViewState('Value', null);
	}

	/**
	 * Sets how much of the task has completed.
	 * @param null|float|string $value the completed amount, 0 or greater;
	 *   null or empty string displays an indeterminate progress bar
	 * @throws TInvalidDataValueException if the value is less than 0
	 */
	public function setValue($value)
	{
		if ($value === null || $value === '') {
			$this->setViewState('Value', null, null);
			return;
		}
		$value = TPropertyValue::ensureFloat($value);
		if ($value < 0) {
			throw new TInvalidDataValueException('progress_value_invalid', $value);
		}
		$this->setViewState('Value', $value, null);
	}

	/**
	 * @return float how much work the task requires in total. Defaults to 1.0.
	 */
	public function getMax()
	{
		return $this->getViewState('Max', 1.0);
	}

	/**
	 * Sets how much work the task requires in total.
	 * @param float|string $value the total amount of work, greater than 0
	 * @throws TInvalidDataValueException if the value is not greater than 0
	 */
	public function setMax($value)
	{
		$value = TPropertyValue::ensureFloat($value);
		if ($value <= 0) {
			throw new TInvalidDataValueException('progress_max_invalid', $value);
		}
		$this->setViewState('Max', $value, 1.0);
	}

	/**
	 * Adds attribute name-value pairs to renderer.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 */
	protected function addAttributesToRender($writer)
	{
		parent::addAttributesToRender($writer);
		if (($value = $this->getValue()) !== null) {
			$writer->addAttribute('value', TPropertyValue::ensureString($value));
		}
		if (($max = $this->getMax()) !== 1.0) {
			$writer->addAttribute('max', TPropertyValue::ensureString($max));
		}
	}
}
