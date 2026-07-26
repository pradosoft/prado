<?php

/**
 * TFigure class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\TPropertyValue;
use Prado\Web\THttpUtility;

/**
 * TFigure class
 *
 * TFigure represents the HTML5 `<figure>` element. The `<figure>` element represents
 * content that is referenced in the main content, but that can be moved to another
 * part of the document without affecting the flow of the main content.
 *
 * A `<figcaption>` is rendered according to the following rules:
 * - If any direct {@see TFigureCaption} child controls are present, they are rendered
 *   in the position specified by their own order in the child collection.
 * - Otherwise, if the {@see getCaption Caption} property is non-empty, a
 *   `<figcaption>` is auto-generated and inserted either before all other content
 *   (`TFigureCaptionOrder::First`) or after all other content (`TFigureCaptionOrder::Last`,
 *   the default) according to {@see getCaptionOrder CaptionOrder}.
 *
 * Properties:
 * - <b>Caption</b>, string — text used to auto-generate a `<figcaption>` child.
 *   Empty string means no auto-generated caption.
 * - <b>CaptionOrder</b>, {@see TFigureCaptionOrder} — position of the auto-generated
 *   caption: `None` (suppressed), `First` (before content), or `Last` (after content,
 *   default). Ignored when `TFigureCaption` child controls are present.
 * - <b>Encode</b>, bool — whether the Caption text is HTML-encoded when rendered.
 *   Defaults to `false`.
 *
 * Template usage:
 * ```html
 * <com:TFigure Caption="Figure 1: Sunrise over the bay">
 *     <com:TImage ImageUrl="sunrise.jpg" AlternateText="Sunrise" />
 * </com:TFigure>
 *
 * <com:TFigure Caption="Listing 1" CaptionOrder="First">
 *     <pre>echo 'hello';</pre>
 * </com:TFigure>
 *
 * <com:TFigure>
 *     <com:TImage ImageUrl="chart.png" />
 *     <com:TFigureCaption>Quarterly results, <b>FY2026</b></com:TFigureCaption>
 * </com:TFigure>
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TFigure extends TWebControl
{
	/**
	 * @return string tag name
	 */
	protected function getTagName()
	{
		return 'figure';
	}

	/**
	 * @return string the text used to auto-generate a `<figcaption>` child element.
	 *   Empty string means no auto-generated caption.
	 */
	public function getCaption()
	{
		return $this->getViewState('Caption', '');
	}

	/**
	 * Sets the caption text. When non-empty and no {@see TFigureCaption} child
	 * controls are present, a `<figcaption>` is rendered automatically in the
	 * position determined by {@see getCaptionOrder CaptionOrder}.
	 * @param string $value the caption text
	 */
	public function setCaption($value)
	{
		$this->setViewState('Caption', $value, '');
	}

	/**
	 * @return null|string the caption position — a {@see TFigureCaptionOrder} value,
	 *   or null to use the default (Last).
	 */
	public function getCaptionOrder()
	{
		return $this->getViewState('CaptionOrder', null);
	}

	/**
	 * Sets the caption order relative to the figure content.
	 * @param null|string $value a {@see TFigureCaptionOrder} value: `None`, `First`,
	 *   or `Last`, or null for the default (Last)
	 */
	public function setCaptionOrder($value)
	{
		if ($value === null || $value === '') {
			$this->setViewState('CaptionOrder', null, null);
		} else {
			$this->setViewState('CaptionOrder', TPropertyValue::ensureEnum($value, TFigureCaptionOrder::class), null);
		}
	}

	/**
	 * @return bool whether the Caption text is HTML-encoded when rendered. Defaults to false.
	 */
	public function getEncode()
	{
		return $this->getViewState('Encode', false);
	}

	/**
	 * @param bool $value whether the Caption text is HTML-encoded when rendered
	 */
	public function setEncode($value)
	{
		$this->setViewState('Encode', TPropertyValue::ensureBoolean($value), false);
	}

	/**
	 * Renders the body content of the figure element.
	 *
	 * If explicit {@see TFigureCaption} child controls are present, they are
	 * rendered in-place with all other children. Otherwise, if {@see getCaption
	 * Caption} is set, an auto-generated `<figcaption>` is inserted either before
	 * or after the remaining children depending on {@see getCaptionOrder}.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 */
	public function renderContents($writer)
	{
		if (!$this->hasFigureCaptionChild()) {
			$caption = $this->getCaption();
			if ($caption !== '' && $this->getCaptionOrder() !== TFigureCaptionOrder::None) {
				if ($this->getCaptionOrder() === TFigureCaptionOrder::First) {
					$this->renderCaption($writer, $caption);
					parent::renderContents($writer);
				} else {
					parent::renderContents($writer);
					$this->renderCaption($writer, $caption);
				}
				return;
			}
		}
		parent::renderContents($writer);
	}

	/**
	 * @return bool whether any direct child is a {@see TFigureCaption} control
	 */
	protected function hasFigureCaptionChild(): bool
	{
		if ($this->getHasControls()) {
			foreach ($this->getControls() as $child) {
				if ($child instanceof TFigureCaption) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Renders the auto-generated `<figcaption>` element from the Caption property.
	 * The text is HTML-encoded when {@see getEncode Encode} is true.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 * @param string $caption the caption text
	 */
	protected function renderCaption($writer, string $caption)
	{
		$writer->renderBeginTag('figcaption');
		$writer->write($this->getEncode() ? THttpUtility::htmlEncode($caption) : $caption);
		$writer->renderEndTag();
	}
}
