<?php

/**
 * TTooltip class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\TPropertyValue;

/**
 * TTooltip class
 *
 * TTooltip renders a tooltip that describes another control or element: a
 * `<div popover="hint" role="tooltip">` that shows next to its target while the
 * pointer is over the target or the target has focus. The client control sets
 * the target's `aria-describedby` to the tooltip, so a screen reader reads the
 * tooltip as the target's description.
 *
 * | Property | Default | Renders |
 * |---|---|---|
 * | {@see setForControl ForControl} | '' | the target: a control ID or an HTML element ID |
 * | {@see setText Text} | '' | the tooltip text, when the body is empty |
 * | {@see setPlacement Placement} | Top | `data-placement`: the side of the target ({@see TPopoverPlacement}) |
 * | {@see setShowDelay ShowDelay} | 300 | the milliseconds the pointer rests on the target before the tooltip shows |
 * | {@see setHideDelay HideDelay} | 150 | the milliseconds before the tooltip hides after the pointer leaves |
 * | {@see setCssUrl CssUrl} | default | the stylesheet of the `prado-tooltip` class |
 *
 * The tooltip meets WCAG 1.4.13 Content on Hover or Focus:
 * - Dismissible: Esc hides it without moving the pointer or the focus, and the
 *   key does not close an enclosing dialog.
 * - Hoverable: the pointer moves from the target onto the tooltip within HideDelay.
 * - Persistent: it shows until the pointer leaves and the focus leaves, or Esc.
 *
 * Focus shows the tooltip at once. The tooltip holds text, not controls; a
 * popover with interactive content is a {@see TPopover}.
 * ```xml
 * <com:TLabel ForControl="Password" Text="Password" />
 * <com:TTextBox ID="Password" TextMode="Password" />
 * <com:TTooltip ForControl="Password" Text="12 or more characters" />
 * ```
 *
 * The `prado-tooltip` class is always rendered before {@see setCssClass CssClass}.
 * {@see setEncode Encode} HTML-encodes Text.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TTooltip extends TWebControl
{
	use TEncodeTrait;

	/**
	 * The class rendered on every tooltip and styled by the bundled stylesheet.
	 */
	public const CSS_CLASS = 'prado-tooltip';

	/**
	 * The client-side class of the control.
	 */
	public const CLIENT_CLASS = 'Prado.WebUI.TTooltip';

	/**
	 * @return string tag name
	 */
	protected function getTagName()
	{
		return 'div';
	}

	/**
	 * @return string the popover state, always 'hint'
	 */
	public function getPopover()
	{
		return 'hint';
	}

	/**
	 * @return string the ID of the target control or HTML element. Defaults to ''.
	 */
	public function getForControl()
	{
		return $this->getViewState('ForControl', '');
	}

	/**
	 * Sets the target the tooltip describes. A control found by
	 * {@see \Prado\Web\UI\TControl::findControl} is named by its ClientID; any
	 * other value is an HTML element ID.
	 * @param string $value the control ID or HTML element ID
	 */
	public function setForControl($value)
	{
		$this->setViewState('ForControl', trim(TPropertyValue::ensureString($value)), '');
	}

	/**
	 * @return string the client ID of the target: the ClientID of the control found
	 *   by {@see getForControl ForControl}, or ForControl itself.
	 */
	public function getTargetClientID()
	{
		if (($id = $this->getForControl()) === '') {
			return '';
		}
		$control = $this->findControl($id);
		return $control ? $control->getClientID() : $id;
	}

	/**
	 * @return string the text of the tooltip. Defaults to ''.
	 */
	public function getText()
	{
		return $this->getViewState('Text', '');
	}

	/**
	 * Sets the text of the tooltip, rendered when the body is empty.
	 * @param string $value the text of the tooltip
	 */
	public function setText($value)
	{
		$this->setViewState('Text', TPropertyValue::ensureString($value), '');
	}

	/**
	 * @return TPopoverPlacement the side of the target where the tooltip shows.
	 *   Defaults to TPopoverPlacement::Top.
	 */
	public function getPlacement()
	{
		return $this->getViewState('Placement', TPopoverPlacement::Top);
	}

	/**
	 * Sets the side of the target where the tooltip shows.
	 * @param TPopoverPlacement $value the side of the target
	 */
	public function setPlacement($value)
	{
		$this->setViewState('Placement', TPropertyValue::ensureEnum($value, TPopoverPlacement::class), TPopoverPlacement::Top);
	}

	/**
	 * @return int the milliseconds the pointer rests on the target before the
	 *   tooltip shows. Defaults to 300.
	 */
	public function getShowDelay()
	{
		return $this->getViewState('ShowDelay', 300);
	}

	/**
	 * @param int $value the milliseconds the pointer rests on the target before
	 *   the tooltip shows; a negative value is 0
	 */
	public function setShowDelay($value)
	{
		$this->setViewState('ShowDelay', max(0, TPropertyValue::ensureInteger($value)), 300);
	}

	/**
	 * @return int the milliseconds before the tooltip hides after the pointer
	 *   leaves the target and the tooltip. Defaults to 150.
	 */
	public function getHideDelay()
	{
		return $this->getViewState('HideDelay', 150);
	}

	/**
	 * Sets the milliseconds before the tooltip hides after the pointer leaves the
	 * target and the tooltip; the pointer crosses to the tooltip in this time.
	 * @param int $value the milliseconds; a negative value is 0
	 */
	public function setHideDelay($value)
	{
		$this->setViewState('HideDelay', max(0, TPropertyValue::ensureInteger($value)), 150);
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
	 * `prado-tooltip` class and its `data-placement` values itself.
	 * @param string $value stylesheet URL, 'default' for the bundled stylesheet,
	 *   or empty to register no stylesheet
	 */
	public function setCssUrl($value)
	{
		$this->setViewState('CssUrl', TPropertyValue::ensureString($value), 'default');
	}

	/**
	 * Registers the stylesheet and, with a target, the client control.
	 * @param mixed $param event parameter
	 */
	public function onPreRender($param)
	{
		parent::onPreRender($param);
		$this->registerStyleSheet();
		if ($this->getTargetClientID() !== '') {
			$this->registerClientScript();
		}
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
			$url = $this->publishAsset('assets' . DIRECTORY_SEPARATOR . 'tooltip.css', self::class);
		}
		$this->getPage()->getClientScript()->registerStyleSheetFile($url, $url);
	}

	/**
	 * Registers the `tooltip` script package and the client control.
	 */
	protected function registerClientScript()
	{
		$cs = $this->getPage()->getClientScript();
		$cs->registerPradoScript('tooltip');
		$cs->registerPostBackControl(static::CLIENT_CLASS, $this->getClientOptions());
	}

	/**
	 * @return array the options of the client control: `ID`, `TargetID`,
	 *   `ShowDelay`, and `HideDelay`
	 */
	protected function getClientOptions()
	{
		return [
			'ID' => $this->getClientID(),
			'TargetID' => $this->getTargetClientID(),
			'ShowDelay' => $this->getShowDelay(),
			'HideDelay' => $this->getHideDelay(),
		];
	}

	/**
	 * Adds the client ID, `role="tooltip"`, the `prado-tooltip` class before
	 * {@see getCssClass CssClass}, and `data-placement` to the renderer.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 */
	protected function addAttributesToRender($writer)
	{
		$this->setEnsureId(true);
		parent::addAttributesToRender($writer);
		$writer->addAttribute('role', 'tooltip');
		$classes = array_filter(
			explode(' ', $this->getCssClass()),
			fn ($class) => $class !== '' && $class !== self::CSS_CLASS
		);
		$writer->addAttribute('class', implode(' ', [self::CSS_CLASS, ...$classes]));
		if (($placement = $this->getPlacement()) !== TPopoverPlacement::None) {
			$writer->addAttribute('data-placement', strtolower($placement));
		}
	}

	/**
	 * Renders the body, or {@see getText Text} when the body is empty.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 */
	public function renderContents($writer)
	{
		if ($this->getHasControls()) {
			parent::renderContents($writer);
		} else {
			$writer->write($this->encodeText($this->getText()));
		}
	}
}
