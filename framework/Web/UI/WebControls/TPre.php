<?php

/**
 * TPre class file
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TPre class
 * TPre represents the HTML `<pre>` element, preformatted text whose whitespace
 * and line breaks render as written. The browser drops one line break directly
 * after the opening tag.
 * Accessibility: `<pre>` has no ARIA role. Screen readers read its text line by
 * line. Text art is not text: a TPre holding a picture made of characters sets
 * `Role="img"` and an `Aria.label` describing it. Code inside a TPre is a
 * {@see TCode}.
 * The default tag is `pre`. It can be overridden at runtime via the
 * {@see setTagName TagName} property (e.g. from a theme or template), which is
 * provided by the {@see THtmlElement} base class.
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TPre extends THtmlElement
{
	/**
	 * @return string the default tag name
	 */
	public function getDefaultTagName()
	{
		return 'pre';
	}
}
