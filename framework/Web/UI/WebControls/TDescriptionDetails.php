<?php

/**
 * TDescriptionDetails class file
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\TPropertyValue;

/**
 * TDescriptionDetails class
 * TDescriptionDetails represents the HTML `<dd>` element, the value of a group in
 * a {@see TDescriptionList}. When {@see setTerm() Term} is set, a `<dt>` holding
 * it renders before the `<dd>`, so one control renders a one-term group.
 * ```html
 * <com:TDescriptionDetails Term="Author">Brad Anderson</com:TDescriptionDetails>
 * ```
 * renders `<dt>Author</dt><dd>Brad Anderson</dd>`. The attributes of the control
 * render on the `<dd>`. {@see setEncode() Encode} HTML-encodes `Term`.
 * Accessibility: browsers expose `<dd>` with the `definition` role and the `<dt>`
 * with the `term` role.
 * The default tag is `dd`. It can be overridden at runtime via the
 * {@see setTagName TagName} property (e.g. from a theme or template), which is
 * provided by the {@see THtmlElement} base class.
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TDescriptionDetails extends THtmlElement
{
	use TEncodeTrait;

	/**
	 * @return string the default tag name
	 */
	public function getDefaultTagName()
	{
		return 'dd';
	}

	/**
	 * @return string the term rendered in a `<dt>` before the details. Defaults to ''.
	 */
	public function getTerm()
	{
		return $this->getViewState('Term', '');
	}

	/**
	 * @param string $value the term rendered in a `<dt>` before the details.
	 */
	public function setTerm($value)
	{
		$this->setViewState('Term', TPropertyValue::ensureString($value), '');
	}

	/**
	 * Renders the `<dt>` of {@see getTerm() Term} when it is set, then the details.
	 * @param \Prado\Web\UI\THtmlWriter $writer the writer used for the rendering purpose
	 */
	public function render($writer)
	{
		if (($term = $this->getTerm()) !== '') {
			$writer->renderBeginTag('dt');
			$writer->write($this->encodeText($term));
			$writer->renderEndTag();
		}
		parent::render($writer);
	}
}
