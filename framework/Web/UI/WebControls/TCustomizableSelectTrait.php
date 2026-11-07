<?php

/**
 * TCustomizableSelectTrait trait file.
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\TPropertyValue;

/**
 * TCustomizableSelectTrait trait.
 *
 * TCustomizableSelectTrait backs the `Customizable` property of a `<select>` list
 * control: {@see TDropDownList} and {@see TListBox}. A customizable select renders
 * the `customizable-select` class before {@see TWebControl::setCssClass CssClass},
 * and the page registers one stylesheet block that opts the class into
 * `appearance: base-select` on the select and its `::picker(select)`. The picker
 * shows the rich {@see TListItem::setContent Content} of the options.
 *
 * A skin or stylesheet styles the class, the picker, `::picker-icon`, and
 * `::checkmark`. {@see setCustomizableCssUrl CustomizableCssUrl} replaces or
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
trait TCustomizableSelectTrait
{
	/**
	 * The class rendered on a customizable select and opted into `appearance: base-select`.
	 */
	public const CUSTOMIZABLE_CSS_CLASS = 'customizable-select';

	/**
	 * @return bool whether the select uses the customizable `base-select` appearance. Defaults to false.
	 */
	public function getCustomizable()
	{
		return $this->getViewState('Customizable', false);
	}

	/**
	 * @param bool $value whether the select uses the customizable `base-select` appearance.
	 */
	public function setCustomizable($value)
	{
		$this->setViewState('Customizable', TPropertyValue::ensureBoolean($value), false);
	}

	/**
	 * @return string URL of the stylesheet of a customizable select. Defaults to 'default',
	 *   the registered `appearance: base-select` block. An empty string registers no stylesheet.
	 */
	public function getCustomizableCssUrl()
	{
		return $this->getViewState('CustomizableCssUrl', 'default');
	}

	/**
	 * Sets the URL of the stylesheet of a customizable select. A replacement
	 * stylesheet opts the `customizable-select` class into `appearance: base-select` itself.
	 * @param string $value stylesheet URL, 'default' for the registered block,
	 *   or empty to register no stylesheet
	 */
	public function setCustomizableCssUrl($value)
	{
		$this->setViewState('CustomizableCssUrl', TPropertyValue::ensureString($value), 'default');
	}

	/**
	 * Registers the stylesheet of {@see getCustomizableCssUrl CustomizableCssUrl}
	 * when the select is {@see getCustomizable Customizable}. 'default' registers
	 * the `appearance: base-select` block; an empty string registers nothing.
	 */
	protected function registerCustomizableStyleSheet()
	{
		if (!$this->getCustomizable() || ($url = $this->getCustomizableCssUrl()) === '') {
			return;
		}
		$clientScript = $this->getPage()->getClientScript();
		if ($url === 'default') {
			$class = '.' . self::CUSTOMIZABLE_CSS_CLASS;
			$clientScript->registerStyleSheet(self::CUSTOMIZABLE_CSS_CLASS, "select{$class},select{$class}::picker(select){appearance:base-select}");
		} else {
			$clientScript->registerStyleSheetFile($url, $url);
		}
	}

	/**
	 * Adds the `customizable-select` class before {@see getCssClass CssClass}
	 * when the select is {@see getCustomizable Customizable}.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 */
	protected function addCustomizableAttributesToRender($writer)
	{
		if (!$this->getCustomizable()) {
			return;
		}
		$classes = array_filter(
			explode(' ', $this->getCssClass()),
			fn ($class) => $class !== '' && $class !== self::CUSTOMIZABLE_CSS_CLASS
		);
		$writer->addAttribute('class', implode(' ', [self::CUSTOMIZABLE_CSS_CLASS, ...$classes]));
	}
}
