<?php

/**
 * TCanvas class file
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\TPropertyValue;
use Prado\Web\Javascripts\TJavaScript;

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
 * ## Client-side drawing
 * {@see getClientSide() ClientSide}.OnDraw ({@see TCanvasClientScript}) is the
 * JavaScript that draws the canvas. With it, the canvas registers the
 * `Prado.WebUI.TCanvas` client class, which calls OnDraw:
 * - when the page loads;
 * - when a callback renders the canvas again, since the new element is blank;
 * - when the display size changes, while {@see setMatchDisplaySize() MatchDisplaySize}
 *   is true.
 * MatchDisplaySize sizes the bitmap to the display size times the device pixel
 * ratio, so the drawing is sharp on high-density screens, and scales a `2d`
 * context so OnDraw draws in CSS pixels. A display size that follows the bitmap
 * size, with no CSS width or height, is fixed at its first size.
 * {@see setContextType() ContextType} selects the drawing context. Without
 * OnDraw, TCanvas registers no script.
 * Moving content that lasts more than five seconds needs a way to pause it
 * (WCAG 2.2.2); OnDraw receives `reducedMotion` for a user who prefers reduced
 * motion. A drawing that changes in a callback updates AlternateText or the
 * fallback content in the same callback.
 * The default tag is `canvas`. It can be overridden at runtime via the
 * {@see setTagName TagName} property (e.g. from a theme or template), which is
 * provided by the {@see THtmlElement} base class.
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TCanvas extends THtmlElement
{
	/**
	 * @var ?TCanvasClientScript the client-side event options.
	 */
	private $_clientScript;

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

	/**
	 * @return TCanvasClientScript the client-side event options of the canvas.
	 */
	public function getClientSide()
	{
		if ($this->_clientScript === null) {
			$this->_clientScript = $this->createClientScript();
		}
		return $this->_clientScript;
	}

	/**
	 * @return TCanvasClientScript a new set of client-side event options.
	 */
	protected function createClientScript()
	{
		return new TCanvasClientScript();
	}

	/**
	 * @return bool whether the bitmap follows the display size times the device pixel
	 *   ratio. Defaults to true.
	 */
	public function getMatchDisplaySize()
	{
		return $this->getViewState('MatchDisplaySize', true);
	}

	/**
	 * @param bool $value whether the bitmap follows the display size times the device
	 *   pixel ratio.
	 */
	public function setMatchDisplaySize($value)
	{
		$this->setViewState('MatchDisplaySize', TPropertyValue::ensureBoolean($value), true);
	}

	/**
	 * @return string the drawing context passed to OnDraw, a {@see TCanvasContextType}.
	 *   Defaults to Context2D.
	 */
	public function getContextType()
	{
		return $this->getViewState('ContextType', TCanvasContextType::Context2D);
	}

	/**
	 * @param string $value the drawing context passed to OnDraw, a {@see TCanvasContextType}.
	 */
	public function setContextType($value)
	{
		$this->setViewState('ContextType', TPropertyValue::ensureEnum($value, TCanvasContextType::class), TCanvasContextType::Context2D);
	}

	/**
	 * @return bool whether the canvas registers its client script: OnDraw is set, the
	 *   client supports JavaScript, and the control is enabled.
	 */
	protected function getClientEnhanced(): bool
	{
		$page = $this->getPage();
		return $this->_clientScript !== null && $this->_clientScript->getOnDraw() !== null
			&& $page !== null && $page->getClientSupportsJavaScript() && $this->getEnabled(true);
	}

	/**
	 * Registers the client script when the canvas is client enhanced.
	 * @param mixed $param event parameter
	 */
	public function onPreRender($param)
	{
		parent::onPreRender($param);
		if ($this->getClientEnhanced()) {
			$this->registerClientScript();
		}
	}

	/**
	 * Registers the canvas client script and its instantiation, and renders the `id`
	 * the script finds the element by.
	 */
	protected function registerClientScript()
	{
		$this->setEnsureId(true);
		$options = TJavaScript::encode($this->getClientOptions());
		$className = $this->getClientClassName();
		$cs = $this->getPage()->getClientScript();
		$cs->registerPradoScript('canvas');
		$cs->registerEndScript('prado:' . $this->getClientID(), "new $className($options);");
	}

	/**
	 * @return string the name of the client-side JavaScript class.
	 */
	protected function getClientClassName()
	{
		return 'Prado.WebUI.TCanvas';
	}

	/**
	 * @return array the options passed to the client script: the element ID, the
	 *   context type as `getContext()` names it, MatchDisplaySize, and the ClientSide
	 *   options.
	 */
	protected function getClientOptions(): array
	{
		$options = [];
		$options['ID'] = $this->getClientID();
		$options['ContextType'] = $this->getContextType() === TCanvasContextType::Context2D ? '2d' : strtolower($this->getContextType());
		$options['MatchDisplaySize'] = $this->getMatchDisplaySize();
		return array_merge($options, $this->getClientSide()->getOptions()->toArray());
	}
}
