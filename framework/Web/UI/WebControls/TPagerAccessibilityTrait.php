<?php

/**
 * TPagerAccessibilityTrait trait file.
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\Prado;

/**
 * TPagerAccessibilityTrait trait.
 * TPagerAccessibilityTrait gives the pager buttons of {@see TPager} and
 * {@see TDataGrid} their accessible names, and renders a pager as a named
 * navigation landmark ({@see TPager} and {@see TDataGridPager}).
 *
 * | Pager part | Rendering |
 * |---|---|
 * | pager element | `<nav aria-label="Pagination">` unless `aria-label` or `aria-labelledby` is set |
 * | `<<`, `<`, `>`, `>>` button | `aria-label` "First page", "Previous page", "Next page", "Last page" |
 * | numeric pager `<`, `>` button | `aria-label` "Previous pages", "Next pages" |
 * | numeric page button | `aria-label` "Page N" |
 * | current page | `aria-current="page"` |
 * | page dropdown list | `aria-label` "Page" |
 * | list layout | a `<ul>` with each pager button in an `<li>` |
 *
 * The names pass through {@see \Prado\Prado::localize()}. A {@see TLabel}, which
 * renders a `<span>`, takes no `aria-label`; its text is its name.
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
trait TPagerAccessibilityTrait
{
	/**
	 * Names a pager button whose text is a default page text (`<`, `>`, `<<`, `>>`).
	 * @param \Prado\Web\UI\TControl $button the pager button.
	 * @param string $text the button text.
	 * @param string $label the accessible name, such as 'Next page'.
	 */
	protected function labelPagerButton($button, $text, $label)
	{
		if (($text === '<' || $text === '>' || $text === '<<' || $text === '>>') && $this->getIsPagerButtonNameable($button)) {
			$button->setAttribute('aria-label', Prado::localize($label));
		}
	}

	/**
	 * Names a numeric page button "Page N" and marks the current page with `aria-current="page"`.
	 * @param \Prado\Web\UI\TControl $button the page button or label.
	 * @param int $pageNumber the one-based page number.
	 * @param bool $current whether the page is the current page.
	 */
	protected function labelPageNumberButton($button, $pageNumber, $current)
	{
		if (!($button instanceof TWebControl)) {
			return;
		}
		if ($this->getIsPagerButtonNameable($button)) {
			$button->setAttribute('aria-label', Prado::localize('Page {0}', [$pageNumber]));
		}
		if ($current) {
			$button->setAttribute('aria-current', 'page');
		}
	}

	/**
	 * Names the page dropdown list of a dropdown list pager "Page".
	 * @param TDropDownList $list the page list.
	 */
	protected function labelPageList($list)
	{
		$list->setAttribute('aria-label', Prado::localize('Page'));
	}

	/**
	 * @param \Prado\Web\UI\TControl $button the pager button.
	 * @return bool whether the button renders an element that takes an `aria-label`.
	 */
	protected function getIsPagerButtonNameable($button)
	{
		return $button instanceof TWebControl && !($button instanceof TLabel);
	}

	/**
	 * Adds the default `aria-label` "Pagination" when the pager has no accessible name.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer.
	 */
	protected function addPagerLabelToRender($writer)
	{
		if (!$this->getHasAria() || ($this->getAttribute('aria-label') === null && $this->getAttribute('aria-labelledby') === null)) {
			$writer->addAttribute('aria-label', Prado::localize('Pagination'));
		}
	}

	/**
	 * Renders the child controls as a `<ul>` with each control in an `<li>`.
	 * The line breaks between the pager buttons are omitted.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer.
	 */
	protected function renderPagerList($writer)
	{
		$writer->renderBeginTag('ul');
		foreach ($this->getControls() as $control) {
			if (is_string($control)) {
				continue;
			}
			$writer->renderBeginTag('li');
			$control->renderControl($writer);
			$writer->renderEndTag();
		}
		$writer->renderEndTag();
	}
}
