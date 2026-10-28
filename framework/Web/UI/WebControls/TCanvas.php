<?php

/**
 * TCanvas class file
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\TPropertyValue;

/**
 * TCanvas class
 * TCanvas represents the HTML `<canvas>` element, a bitmap that client script
 * draws on. {@see setWidth() Width} and {@see setHeight() Height} are the size
 * of the bitmap in pixels, rendered as the `width` and `height` attributes; the
 * browser default is 300 by 150. The display size is CSS, set through `Style` or
 * `CssClass`.
 * The child controls are the fallback content: the browser shows them when it
 * does not render the canvas, and assistive technology reads them in its place.
 * Accessibility: a canvas is a bitmap that screen readers cannot read.
 * - A static drawing, such as a chart or a logo, sets
 *   {@see setAlternateText() AlternateText}. It renders `role="img"` and an
 *   `aria-label` of the text, unless {@see TWebControl::setRole() Role} is set.
 * - A drawing that carries data or controls puts that data and those controls
 *   in the fallback content, such as a table of the chart's values or a button
 *   for each region. Focusable fallback elements are in the tab order. A TCanvas
 *   with such content does not set AlternateText, because the children of an
 *   `img` are hidden from assistive technology.
 * ```html
 * <com:TCanvas Width="400" Height="200" AlternateText="Sales rose 20% in 2026" />
 * ```
 * The default tag is `canvas`. It can be overridden at runtime via the
 * {@see setTagName TagName} property (e.g. from a theme or template), which is
 * provided by the {@see THtmlElement} base class.
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TCanvas extends THtmlElement
{
	/**
	 * @return string the default tag name
	 */
	public function getDefaultTagName()
	{
		return 'canvas';
	}

	/**
	 * @return int|string the width of the bitmap in pixels, or -1 for the browser default.
	 *   Defaults to -1.
	 */
	public function getWidth()
	{
		return $this->getViewState('Width', -1);
	}

	/**
	 * @param int|string $value the width of the bitmap in pixels; a negative value is the
	 *   browser default.
	 */
	public function setWidth($value)
	{
		if (($value = TPropertyValue::ensureInteger($value)) < 0) {
			$value = -1;
		}
		$this->setViewState('Width', $value, -1);
	}

	/**
	 * @return int|string the height of the bitmap in pixels, or -1 for the browser default.
	 *   Defaults to -1.
	 */
	public function getHeight()
	{
		return $this->getViewState('Height', -1);
	}

	/**
	 * @param int|string $value the height of the bitmap in pixels; a negative value is the
	 *   browser default.
	 */
	public function setHeight($value)
	{
		if (($value = TPropertyValue::ensureInteger($value)) < 0) {
			$value = -1;
		}
		$this->setViewState('Height', $value, -1);
	}

	/**
	 * @return string the text alternative of a static drawing. Defaults to ''.
	 */
	public function getAlternateText()
	{
		return $this->getViewState('AlternateText', '');
	}

	/**
	 * @param string $value the text alternative of a static drawing.
	 */
	public function setAlternateText($value)
	{
		$this->setViewState('AlternateText', TPropertyValue::ensureString($value), '');
	}

	/**
	 * Adds the `width` and `height` attributes when they are set, and `role="img"`
	 * with an `aria-label` when {@see getAlternateText() AlternateText} is set.
	 * An `Aria.label` replaces the AlternateText label.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 */
	protected function addAttributesToRender($writer)
	{
		if (($text = $this->getAlternateText()) !== '') {
			if ($this->getRole() === null) {
				$writer->addAttribute('role', 'img');
			}
			$writer->addAttribute('aria-label', $text);
		}
		parent::addAttributesToRender($writer);
		if (($width = $this->getWidth()) !== -1) {
			$writer->addAttribute('width', (string) $width);
		}
		if (($height = $this->getHeight()) !== -1) {
			$writer->addAttribute('height', (string) $height);
		}
	}
}
