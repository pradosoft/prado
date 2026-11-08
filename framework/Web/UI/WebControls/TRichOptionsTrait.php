<?php

/**
 * TRichOptionsTrait trait file.
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\TPropertyValue;

/**
 * TRichOptionsTrait trait.
 *
 * TRichOptionsTrait backs the `RichOptions` property of a `<select>` list
 * control: {@see TDropDownList} and {@see TListBox}. With RichOptions, the select
 * uses the browser's customizable select and shows the HTML
 * {@see TListItem::setContent Content} of its options. It renders the
 * `rich-options` class before {@see TWebControl::setCssClass CssClass},
 * and the page registers one stylesheet block that opts the class into
 * `appearance: base-select` on the select and its `::picker(select)`.
 *
 * A skin or stylesheet styles the class, the picker, `::picker-icon`, and
 * `::checkmark`. {@see setRichOptionsCssUrl RichOptionsCssUrl} replaces or
 * removes the registered block.
 *
 * Fallback: a browser without customizable select support ignores the
 * `appearance: base-select` declaration, the `<button>` and `<selectedcontent>`
 * markup, and the elements in option content. It renders a native select with
 * the text of each option.
 *
 * The using class has `getViewState()`, `setViewState()`, `getCssClass()`, and
 * `getPage()`.
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
trait TRichOptionsTrait
{
	/**
	 * The class rendered on a RichOptions select and opted into `appearance: base-select`.
	 */
	public const RICH_OPTIONS_CSS_CLASS = 'rich-options';

	/**
	 * @return bool whether the select shows rich option content with `appearance: base-select`. Defaults to false.
	 */
	public function getRichOptions()
	{
		return $this->getViewState('RichOptions', false);
	}

	/**
	 * @param bool $value whether the select shows rich option content with `appearance: base-select`.
	 */
	public function setRichOptions($value)
	{
		$this->setViewState('RichOptions', TPropertyValue::ensureBoolean($value), false);
	}

	/**
	 * @return string URL of the stylesheet of a RichOptions select. Defaults to 'default',
	 *   the registered `appearance: base-select` block. An empty string registers no stylesheet.
	 */
	public function getRichOptionsCssUrl()
	{
		return $this->getViewState('RichOptionsCssUrl', 'default');
	}

	/**
	 * Sets the URL of the stylesheet of a RichOptions select. A replacement
	 * stylesheet opts the `rich-options` class into `appearance: base-select` itself.
	 * @param string $value stylesheet URL, 'default' for the registered block,
	 *   or empty to register no stylesheet
	 */
	public function setRichOptionsCssUrl($value)
	{
		$this->setViewState('RichOptionsCssUrl', TPropertyValue::ensureString($value), 'default');
	}

	/**
	 * Registers the stylesheet of {@see getRichOptionsCssUrl RichOptionsCssUrl}
	 * when the select is {@see getRichOptions RichOptions}. 'default' registers
	 * the `appearance: base-select` block; an empty string registers nothing.
	 */
	protected function registerRichOptionsStyleSheet()
	{
		if (!$this->getRichOptions() || ($url = $this->getRichOptionsCssUrl()) === '') {
			return;
		}
		$clientScript = $this->getPage()->getClientScript();
		if ($url === 'default') {
			$class = '.' . self::RICH_OPTIONS_CSS_CLASS;
			$clientScript->registerStyleSheet(self::RICH_OPTIONS_CSS_CLASS, "select{$class},select{$class}::picker(select){appearance:base-select}");
		} else {
			$clientScript->registerStyleSheetFile($url, $url);
		}
	}

	/**
	 * Adds the `rich-options` class before {@see getCssClass CssClass}
	 * when the select is {@see getRichOptions RichOptions}.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 */
	protected function addRichOptionsAttributesToRender($writer)
	{
		if (!$this->getRichOptions()) {
			return;
		}
		$classes = array_filter(
			explode(' ', $this->getCssClass()),
			fn ($class) => $class !== '' && $class !== self::RICH_OPTIONS_CSS_CLASS
		);
		$writer->addAttribute('class', implode(' ', [self::RICH_OPTIONS_CSS_CLASS, ...$classes]));
	}
}
