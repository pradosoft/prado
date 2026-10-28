<?php

/**
 * TAddress class file
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TAddress class
 * TAddress represents the HTML `<address>` element, the contact information
 * for its nearest {@see TArticle} or, outside an article, for the page: a name,
 * an email link, a postal address. A postal address that is not contact
 * information is ordinary text.
 * Accessibility: browsers expose `<address>` with the `group` role. A group has
 * no name unless `Aria.label` or `Aria.labelledby` gives one. Browsers render it
 * as a block in italics.
 * The default tag is `address`. It can be overridden at runtime via the
 * {@see setTagName TagName} property (e.g. from a theme or template), which is
 * provided by the {@see THtmlElement} base class.
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TAddress extends THtmlElement
{
	/**
	 * @return string the default tag name
	 */
	public function getDefaultTagName()
	{
		return 'address';
	}
}
