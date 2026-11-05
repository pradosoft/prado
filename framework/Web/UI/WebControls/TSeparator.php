<?php

/**
 * TSeparator class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TSeparator class
 *
 * TSeparator represents the HTML `<hr>` element, a thematic break between
 * paragraph-level content: a change of topic or scene. The element is void, so
 * child controls do not render.
 * ```html
 * <com:TSeparator CssClass="chapter-break" />
 * ```
 *
 * The default tag is `hr`. It can be overridden at runtime via the
 * {@see setTagName TagName} property (e.g. from a theme or template), which is
 * provided by the {@see THtmlElement} base class.
 * This is also useful for the {@see \Prado\Web\UI\WebControls\TWebControlDecorator}
 * (used by themes).
 *
 * Accessibility: browsers expose `<hr>` as a `separator`. A TSeparator with
 * another TagName renders `role="separator"`, unless
 * {@see \Prado\Web\UI\WebControls\TWebControl::setRole Role} is set. A line that
 * is decoration only uses CSS, or `Role="presentation"`.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TSeparator extends THtmlElement
{
	/**
	 * @return string the default tag name
	 */
	public function getDefaultTagName()
	{
		return 'hr';
	}

	/**
	 * Adds `role="separator"` when the tag is not `hr` and no Role is set.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 */
	protected function addAttributesToRender($writer)
	{
		if ($this->getRole() === null && strcasecmp($this->getTagName(), $this->getDefaultTagName()) !== 0) {
			$writer->addAttribute('role', 'separator');
		}
		parent::addAttributesToRender($writer);
	}

	/**
	 * Renders no body content; a separator has none.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 */
	public function renderContents($writer)
	{
	}
}
