<?php

/**
 * TActiveSvg class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\ActiveControls;

use Prado\Web\UI\WebControls\TSvg;

/**
 * TActiveSvg class.
 *
 * TActiveSvg is the active control counterpart of {@see TSvg}. During a
 * callback, a change to the content or to an SVG attribute property re-renders
 * the graphic once, at the end of the callback, and the client morphs it into
 * the existing `<svg>`. Unchanged nodes keep their identity and the listeners
 * a page script attached to them.
 * ```html
 * <com:TActiveSvg ID="Chart" ViewBox="0 0 100 100" AlternateText="Sales" />
 * <com:TActiveButton Text="Refresh" OnCallback="refresh" />
 * ```
 * ```php
 * public function refresh($sender, $param)
 * {
 *     $this->Chart->setSvg($this->renderChart());
 * }
 * ```
 *
 * | Change in a callback | Client update |
 * |---|---|
 * | {@see setSvg() Svg}, {@see setSvgFile() SvgFile} | the graphic re-renders |
 * | {@see setViewBox() ViewBox}, {@see setPreserveAspectRatio() PreserveAspectRatio}, {@see setPixelWidth() PixelWidth}, {@see setPixelHeight() PixelHeight} | the graphic re-renders |
 * | {@see setAlternateText() AlternateText}, {@see setDescription() Description}, {@see setDecorative() Decorative} | the graphic re-renders |
 * | the same value | none |
 * | `render($param->getNewWriter())` | the graphic re-renders, for template content |
 *
 * Several changes in one callback send one update. Other properties take effect
 * when the graphic re-renders. While the graphic re-renders, the active controls
 * inside it send no updates of their own. The update requires
 * {@see TBaseActiveControl::setEnableUpdate ActiveControl.EnableUpdate}.
 *
 * The {@see TBaseActiveControl::setUpdateMode ActiveControl.UpdateMode} of a
 * TActiveSvg is Morph, so the update keeps the nodes whatever the page's
 * {@see \Prado\Web\UI\TPage::setCallbackUpdateMode CallbackUpdateMode}.
 * `Replace` replaces the element, and `Default` follows the page.
 *
 * Accessibility: the update keeps the `role`, `aria-labelledby`, and `<title>`
 * of {@see TSvg}, so a new AlternateText renames the graphic. The graphic is not
 * a live region and a screen reader does not announce the change; a page that
 * reports it pairs the graphic with a {@see TActiveOutput}.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 * @method TActiveControlAdapter getAdapter()
 */
class TActiveSvg extends TSvg implements IActiveControl
{
	/**
	 * Creates a new active control, sets the adapter to TActiveControlAdapter,
	 * and morphs its updates.
	 */
	public function __construct()
	{
		parent::__construct();
		$this->setAdapter(new TActiveControlAdapter($this));
		$this->getActiveControl()->setUpdateMode(TActiveUpdateMode::Morph);
	}

	/**
	 * @return TBaseActiveControl basic active control options.
	 */
	public function getActiveControl()
	{
		return $this->getAdapter()->getBaseActiveControl();
	}

	/**
	 * @return bool true; the client locates the element by its ID
	 */
	public function getEnsureId()
	{
		return true;
	}

	/**
	 * Sets the `.svg` file of the content. A change in a callback re-renders the graphic.
	 * @param string $value the namespace, such as `Application.icons.logo`, or file path of the `.svg` file.
	 */
	public function setSvgFile($value)
	{
		$old = $this->getSvgFile();
		parent::setSvgFile($value);
		$this->updateIfChanged($old, $this->getSvgFile());
	}

	/**
	 * Sets the SVG markup of the content. A change in a callback re-renders the graphic.
	 * @param string $value the SVG markup, with a root `<svg>` element.
	 */
	public function setSvg($value)
	{
		$old = $this->getSvg();
		parent::setSvg($value);
		$this->updateIfChanged($old, $this->getSvg());
	}

	/**
	 * Sets the `viewBox`. A change in a callback re-renders the graphic.
	 * @param string $value the `viewBox`, such as `0 0 24 24`.
	 */
	public function setViewBox($value)
	{
		$old = $this->getViewBox();
		parent::setViewBox($value);
		$this->updateIfChanged($old, $this->getViewBox());
	}

	/**
	 * Sets the `preserveAspectRatio`. A change in a callback re-renders the graphic.
	 * @param string $value the `preserveAspectRatio`, such as `xMidYMid meet`.
	 */
	public function setPreserveAspectRatio($value)
	{
		$old = $this->getPreserveAspectRatio();
		parent::setPreserveAspectRatio($value);
		$this->updateIfChanged($old, $this->getPreserveAspectRatio());
	}

	/**
	 * Sets the width. A change in a callback re-renders the graphic.
	 * @param int|string $value the width in pixels; a negative value unsets it.
	 */
	public function setPixelWidth($value)
	{
		$old = $this->getPixelWidth();
		parent::setPixelWidth($value);
		$this->updateIfChanged($old, $this->getPixelWidth());
	}

	/**
	 * Sets the height. A change in a callback re-renders the graphic.
	 * @param int|string $value the height in pixels; a negative value unsets it.
	 */
	public function setPixelHeight($value)
	{
		$old = $this->getPixelHeight();
		parent::setPixelHeight($value);
		$this->updateIfChanged($old, $this->getPixelHeight());
	}

	/**
	 * Sets the text alternative. A change in a callback re-renders the graphic.
	 * @param string $value the text alternative of the graphic, rendered in a `<title>`.
	 */
	public function setAlternateText($value)
	{
		$old = $this->getAlternateText();
		parent::setAlternateText($value);
		$this->updateIfChanged($old, $this->getAlternateText());
	}

	/**
	 * Sets the longer description. A change in a callback re-renders the graphic.
	 * @param string $value the longer description of the graphic, rendered in a `<desc>`.
	 */
	public function setDescription($value)
	{
		$old = $this->getDescription();
		parent::setDescription($value);
		$this->updateIfChanged($old, $this->getDescription());
	}

	/**
	 * Sets whether the graphic is decorative. A change in a callback re-renders the graphic.
	 * @param bool $value whether the graphic is decorative and hidden from assistive technology.
	 */
	public function setDecorative($value)
	{
		$old = $this->getDecorative();
		parent::setDecorative($value);
		$this->updateIfChanged($old, $this->getDecorative());
	}

	/**
	 * Renders the graphic. After pre-render in a callback, the rendering updates the
	 * client element; before it, the render is deferred to the callback response.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 */
	public function render($writer)
	{
		if ($this->getHasPreRendered()) {
			parent::render($writer);
			if ($this->getActiveControl()->canUpdateClientSide()) {
				$this->getActiveControl()->replaceClientContent($this, $writer);
			}
		} else {
			$this->registerUpdate($writer);
		}
	}

	/**
	 * Registers the re-render of a changed value in a callback.
	 * @param mixed $old the value before the change
	 * @param mixed $new the value after the change
	 */
	protected function updateIfChanged($old, $new): void
	{
		if ($old !== $new && $this->getActiveControl()->canUpdateClientSide()) {
			$this->registerUpdate($this->getResponse()->createHtmlWriter());
		}
	}

	/**
	 * Defers the render to the callback response, once per callback, and stops the
	 * updates of the active controls inside the graphic.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer of the re-render
	 */
	protected function registerUpdate($writer): void
	{
		$this->getPage()->getAdapter()->registerControlToRender($this, $writer);
		if ($this->getHasControls()) {
			foreach ($this->findControlsByType(IActiveControl::class, false) as $control) {
				$control->getActiveControl()->setEnableUpdate(false);
			}
		}
	}
}
