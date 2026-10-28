<?php

/**
 * TBlockQuote class file
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TBlockQuote class
 * TBlockQuote represents the HTML `<blockquote>` element, a quotation of one
 * or more paragraphs from another source. {@see TCiteTrait::setCite() Cite} is
 * the URL of its source. The attribution, such as a {@see TCite}, goes outside
 * the TBlockQuote or in a {@see TFigureCaption} of a {@see TFigure} holding it.
 * Accessibility: browsers expose `<blockquote>` with the `blockquote` role, and
 * screen readers announce its start and end. It is not for indenting text.
 * The default tag is `blockquote`. It can be overridden at runtime via the
 * {@see setTagName TagName} property (e.g. from a theme or template), which is
 * provided by the {@see THtmlElement} base class.
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TBlockQuote extends THtmlElement
{
	use TCiteTrait;

	/**
	 * @return string the default tag name
	 */
	public function getDefaultTagName()
	{
		return 'blockquote';
	}

	/**
	 * Adds the `cite` attribute when {@see getCite() Cite} is set.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 */
	protected function addAttributesToRender($writer)
	{
		parent::addAttributesToRender($writer);
		$this->addCiteAttributeToRender($writer);
	}
}
