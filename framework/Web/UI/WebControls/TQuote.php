<?php

/**
 * TQuote class file
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TQuote class
 * TQuote represents the HTML `<q>` element, a short quotation inside a sentence.
 * {@see TCiteTrait::setCite() Cite} is the URL of its source. A quotation of one
 * or more paragraphs is a {@see TBlockQuote}.
 * ```html
 * <com:TQuote Cite="https://example.com/speech">We choose to go</com:TQuote>
 * ```
 * Accessibility: `<q>` has no ARIA role. Browsers add the quotation marks of the
 * element's language, so the text does not include them. Screen readers read the
 * text and do not always read the generated marks.
 * The default tag is `q`. It can be overridden at runtime via the
 * {@see setTagName TagName} property (e.g. from a theme or template), which is
 * provided by the {@see THtmlElement} base class.
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TQuote extends THtmlElement
{
	use TCiteTrait;

	/**
	 * @return string the default tag name
	 */
	public function getDefaultTagName()
	{
		return 'q';
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
