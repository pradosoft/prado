<?php

/**
 * TSearch class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TSearch class
 *
 * TSearch represents the HTML5 `<search>` element. The `<search>` element groups
 * the controls of a search or filtering operation: the query input, its submit
 * button, and any filter options. It searches the site, the page, or a data set
 * on the page.
 * ```html
 * <com:TSearch Aria.label="Site">
 *     <com:TLabel ForControl="Query" Text="Search" />
 *     <com:TTextBox ID="Query" TextMode="Search" />
 *     <com:TButton Text="Go" OnClick="search" />
 * </com:TSearch>
 * ```
 *
 * The default tag is `search`. It can be overridden at runtime via the
 * {@see setTagName TagName} property (e.g. from a theme or template), which is
 * provided by the {@see THtmlElement} base class.
 * This is also useful for the {@see \Prado\Web\UI\WebControls\TWebControlDecorator}
 * (used by themes).
 *
 * Accessibility: browsers expose `<search>` as a `search` landmark. A TSearch with
 * another TagName renders `role="search"` to keep the landmark, unless
 * {@see \Prado\Web\UI\WebControls\TWebControl::setRole Role} is set. A browser
 * that predates `<search>` exposes no landmark and renders the element inline;
 * TagName `div` supports those browsers. A page with more than one search
 * landmark names each of them with `Aria.label` or `Aria.labelledby`. The
 * landmark does not label the query input; the input keeps its own label.
 *
 * Do not add `role="search"` to a `<search>`; the element already has the role.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TSearch extends THtmlElement
{
	/**
	 * @return string the default tag name
	 */
	public function getDefaultTagName()
	{
		return 'search';
	}

	/**
	 * Adds `role="search"` when the tag is not `search` and no Role is set.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 */
	protected function addAttributesToRender($writer)
	{
		if ($this->getRole() === null && strcasecmp($this->getTagName(), $this->getDefaultTagName()) !== 0) {
			$writer->addAttribute('role', 'search');
		}
		parent::addAttributesToRender($writer);
	}
}
