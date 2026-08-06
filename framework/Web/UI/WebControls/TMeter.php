<?php

/**
 * TMeter class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\TPropertyValue;

/**
 * TMeter class
 *
 * TMeter represents the HTML5 `<meter>` element. The `<meter>` element
 * represents a scalar measurement within a known range, such as disk usage,
 * a relevance score, or a fraction of a voting population.
 *
 * Properties:
 * - <b>Value</b>, float — the measured value, rendered as the `value` attribute.
 *   Defaults to `0.0`. The attribute is always rendered; the HTML specification
 *   requires it.
 * - <b>Min</b>, float — the lower bound of the range, rendered as the `min`
 *   attribute when not `0.0`. Defaults to `0.0`.
 * - <b>Max</b>, float — the upper bound of the range, rendered as the `max`
 *   attribute when not `1.0`. Defaults to `1.0`.
 * - <b>Low</b>, ?float — the upper bound of the "low" segment of the range,
 *   rendered as the `low` attribute. `null` (the default) renders no attribute.
 * - <b>High</b>, ?float — the lower bound of the "high" segment of the range,
 *   rendered as the `high` attribute. `null` (the default) renders no attribute.
 * - <b>Optimum</b>, ?float — the optimum point in the range, rendered as the
 *   `optimum` attribute. `null` (the default) renders no attribute.
 *
 * The browser clamps out-of-range combinations per the HTML specification:
 * `value`, `low`, `high`, and `optimum` are constrained into `[min, max]`.
 * The properties accept any float so they can be assigned in any order.
 *
 * Child content renders inside the element as fallback for user agents without
 * `<meter>` support. Associate a caption with {@see TLabel} and its
 * {@see TLabel::setForControl ForControl} property.
 *
 * The gauge's colors are styled with CSS pseudo-elements
 * (`::-webkit-meter-bar`, `::-webkit-meter-optimum-value`,
 * `::-webkit-meter-suboptimum-value`, `::-moz-meter-bar`); use
 * {@see setCssClass CssClass} with a stylesheet for theming.
 *
 * Template usage:
 * ```html
 * <com:TMeter Value="0.6" />
 * <com:TMeter ID="Disk" Value="70" Max="100" Low="25" High="85" Optimum="10" />
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TMeter extends TWebControl
{
	/**
	 * @return string tag name
	 */
	protected function getTagName()
	{
		return 'meter';
	}

	/**
	 * @return float the measured value. Defaults to 0.0.
	 */
	public function getValue()
	{
		return $this->getViewState('Value', 0.0);
	}

	/**
	 * @param float|string $value the measured value
	 */
	public function setValue($value)
	{
		$this->setViewState('Value', TPropertyValue::ensureFloat($value), 0.0);
	}

	/**
	 * @return float the lower bound of the range. Defaults to 0.0.
	 */
	public function getMin()
	{
		return $this->getViewState('Min', 0.0);
	}

	/**
	 * @param float|string $value the lower bound of the range
	 */
	public function setMin($value)
	{
		$this->setViewState('Min', TPropertyValue::ensureFloat($value), 0.0);
	}

	/**
	 * @return float the upper bound of the range. Defaults to 1.0.
	 */
	public function getMax()
	{
		return $this->getViewState('Max', 1.0);
	}

	/**
	 * @param float|string $value the upper bound of the range
	 */
	public function setMax($value)
	{
		$this->setViewState('Max', TPropertyValue::ensureFloat($value), 1.0);
	}

	/**
	 * @return ?float the upper bound of the "low" segment of the range;
	 *   null (the default) renders no attribute
	 */
	public function getLow()
	{
		return $this->getViewState('Low', null);
	}

	/**
	 * @param null|float|string $value the upper bound of the "low" segment;
	 *   null or empty string renders no attribute
	 */
	public function setLow($value)
	{
		$this->setViewState('Low', ($value === null || $value === '') ? null : TPropertyValue::ensureFloat($value), null);
	}

	/**
	 * @return ?float the lower bound of the "high" segment of the range;
	 *   null (the default) renders no attribute
	 */
	public function getHigh()
	{
		return $this->getViewState('High', null);
	}

	/**
	 * @param null|float|string $value the lower bound of the "high" segment;
	 *   null or empty string renders no attribute
	 */
	public function setHigh($value)
	{
		$this->setViewState('High', ($value === null || $value === '') ? null : TPropertyValue::ensureFloat($value), null);
	}

	/**
	 * @return ?float the optimum point in the range; null (the default) renders
	 *   no attribute
	 */
	public function getOptimum()
	{
		return $this->getViewState('Optimum', null);
	}

	/**
	 * @param null|float|string $value the optimum point in the range;
	 *   null or empty string renders no attribute
	 */
	public function setOptimum($value)
	{
		$this->setViewState('Optimum', ($value === null || $value === '') ? null : TPropertyValue::ensureFloat($value), null);
	}

	/**
	 * Adds attribute name-value pairs to renderer.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 */
	protected function addAttributesToRender($writer)
	{
		parent::addAttributesToRender($writer);
		if (($min = $this->getMin()) !== 0.0) {
			$writer->addAttribute('min', TPropertyValue::ensureString($min));
		}
		if (($max = $this->getMax()) !== 1.0) {
			$writer->addAttribute('max', TPropertyValue::ensureString($max));
		}
		if (($low = $this->getLow()) !== null) {
			$writer->addAttribute('low', TPropertyValue::ensureString($low));
		}
		if (($high = $this->getHigh()) !== null) {
			$writer->addAttribute('high', TPropertyValue::ensureString($high));
		}
		if (($optimum = $this->getOptimum()) !== null) {
			$writer->addAttribute('optimum', TPropertyValue::ensureString($optimum));
		}
		$writer->addAttribute('value', TPropertyValue::ensureString($this->getValue()));
	}
}
