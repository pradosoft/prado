<?php

/**
 * TPopover class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\TPropertyValue;

/**
 * TPopover class
 *
 * TPopover renders a `<div>` popover (the HTML Popover API) next to the button that
 * opens it. The browser shows the popover in the top layer, closes it on Esc and,
 * for an `auto` popover, on a click outside it (light dismiss), and returns the
 * focus to the invoker. No script is needed.
 *
 * | Property | Default | Renders |
 * |---|---|---|
 * | {@see setPopover Popover} | auto | `popover`: `auto`, `manual`, or `hint` |
 * | {@see setPlacement Placement} | Bottom | `data-placement`: the side of the invoker ({@see TPopoverPlacement}) |
 * | {@see setButtonText ButtonText} | '' | an invoker button before the popover that toggles it |
 * | {@see setButtonCssClass ButtonCssClass} | '' | the class of the invoker button |
 * | {@see setCssUrl CssUrl} | default | the stylesheet of the `prado-popover` class |
 *
 * ButtonText pairs the popover with its own invoker, a `<button type="button">`
 * with `commandfor` and `command="toggle-popover"`. Another invoker is a
 * {@see TButton} with {@see TButton::setCommandFor CommandFor} naming the popover
 * and a `show-popover`, `hide-popover`, or `toggle-popover`
 * {@see TButton::setCommand Command}. The browser exposes the expanded state of an
 * invoker and makes the opened popover its anchor.
 * ```xml
 * <com:TPopover ID="Help" ButtonText="Help" Placement="End">
 *     <p>Passwords take 12 or more characters.</p>
 * </com:TPopover>
 *
 * <com:TButton Text="Filters" CommandFor="Filters" Command="toggle-popover" />
 * <com:TPopover ID="Filters">...</com:TPopover>
 * ```
 *
 * The `prado-popover` class is always rendered before {@see setCssClass CssClass}.
 * {@see setEncode Encode} HTML-encodes ButtonText.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TPopover extends TWebControl
{
	use TEncodeTrait;

	/**
	 * The class rendered on every popover and styled by the bundled stylesheet.
	 */
	public const CSS_CLASS = 'prado-popover';

	/**
	 * @return string tag name
	 */
	protected function getTagName()
	{
		return 'div';
	}

	/**
	 * @return string the popover state: 'auto', 'manual', or 'hint'. Defaults to 'auto'.
	 */
	public function getPopover()
	{
		return $this->getViewState('Popover', 'auto');
	}

	/**
	 * Sets the popover state. '' or false restores the default 'auto'.
	 * @param bool|string $value the popover state
	 * @throws \Prado\Exceptions\TInvalidDataValueException if the value is not a popover state
	 */
	public function setPopover($value)
	{
		parent::setPopover($value);
		if ($this->getViewState('Popover', 'auto') === '') {
			$this->clearViewState('Popover');
		}
	}

	/**
	 * @return TPopoverPlacement the side of the anchor where the popover shows.
	 *   Defaults to TPopoverPlacement::Bottom.
	 */
	public function getPlacement()
	{
		return $this->getViewState('Placement', TPopoverPlacement::Bottom);
	}

	/**
	 * Sets the side of the anchor where the popover shows. None leaves the popover
	 * at the browser default position.
	 * @param TPopoverPlacement $value the side of the anchor
	 */
	public function setPlacement($value)
	{
		$this->setViewState('Placement', TPropertyValue::ensureEnum($value, TPopoverPlacement::class), TPopoverPlacement::Bottom);
	}

	/**
	 * @return string the text of the invoker button. Defaults to '', no button.
	 */
	public function getButtonText()
	{
		return $this->getViewState('ButtonText', '');
	}

	/**
	 * Sets the text of the invoker button rendered before the popover; '' renders
	 * no button.
	 * @param string $value the text of the invoker button
	 */
	public function setButtonText($value)
	{
		$this->setViewState('ButtonText', TPropertyValue::ensureString($value), '');
	}

	/**
	 * @return string the CSS class of the invoker button. Defaults to ''.
	 */
	public function getButtonCssClass()
	{
		return $this->getViewState('ButtonCssClass', '');
	}

	/**
	 * @param string $value the CSS class of the invoker button
	 */
	public function setButtonCssClass($value)
	{
		$this->setViewState('ButtonCssClass', TPropertyValue::ensureString($value), '');
	}

	/**
	 * @return string the client ID of the invoker button
	 */
	public function getButtonClientID()
	{
		return $this->getClientID() . '_button';
	}

	/**
	 * @return string URL of the stylesheet. Defaults to 'default', the bundled
	 *   stylesheet. An empty string registers no stylesheet.
	 */
	public function getCssUrl()
	{
		return $this->getViewState('CssUrl', 'default');
	}

	/**
	 * Sets the URL of the stylesheet. A replacement stylesheet styles the
	 * `prado-popover` class and its `data-placement` values itself.
	 * @param string $value stylesheet URL, 'default' for the bundled stylesheet,
	 *   or empty to register no stylesheet
	 */
	public function setCssUrl($value)
	{
		$this->setViewState('CssUrl', TPropertyValue::ensureString($value), 'default');
	}

	/**
	 * Registers the stylesheet.
	 * @param mixed $param event parameter
	 */
	public function onPreRender($param)
	{
		parent::onPreRender($param);
		$this->registerStyleSheet();
	}

	/**
	 * Registers the stylesheet of {@see getCssUrl CssUrl}. 'default' publishes the
	 * bundled stylesheet; an empty string registers nothing.
	 */
	protected function registerStyleSheet()
	{
		if (($url = $this->getCssUrl()) === '') {
			return;
		}
		if ($url === 'default') {
			$url = $this->publishAsset('assets' . DIRECTORY_SEPARATOR . 'popover.css', self::class);
		}
		$this->getPage()->getClientScript()->registerStyleSheetFile($url, $url);
	}

	/**
	 * Renders the invoker button of {@see getButtonText ButtonText}, then the popover.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 */
	public function render($writer)
	{
		if ($this->getButtonText() !== '') {
			$this->renderButton($writer);
		}
		parent::render($writer);
	}

	/**
	 * Renders the invoker button that toggles the popover.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 */
	protected function renderButton($writer)
	{
		$writer->addAttribute('type', 'button');
		$writer->addAttribute('id', $this->getButtonClientID());
		if (($cssClass = $this->getButtonCssClass()) !== '') {
			$writer->addAttribute('class', $cssClass);
		}
		$writer->addAttribute('commandfor', $this->getClientID());
		$writer->addAttribute('command', 'toggle-popover');
		$writer->renderBeginTag('button');
		$writer->write($this->encodeText($this->getButtonText()));
		$writer->renderEndTag();
	}

	/**
	 * Adds the client ID, the `prado-popover` class before {@see getCssClass CssClass},
	 * and `data-placement` to the renderer.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 */
	protected function addAttributesToRender($writer)
	{
		$this->setEnsureId(true);
		parent::addAttributesToRender($writer);
		$classes = array_filter(
			explode(' ', $this->getCssClass()),
			fn ($class) => $class !== '' && $class !== self::CSS_CLASS
		);
		$writer->addAttribute('class', implode(' ', [self::CSS_CLASS, ...$classes]));
		if (($placement = $this->getPlacement()) !== TPopoverPlacement::None) {
			$writer->addAttribute('data-placement', strtolower($placement));
		}
	}
}
