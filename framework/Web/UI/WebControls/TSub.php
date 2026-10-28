<?php

/**
 * TSub class file
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TSub class
 * TSub represents the HTML `<sub>` element, a subscript required by a
 * typographical convention: the 2 in H₂O, a variable index. It is not for
 * styling alone.
 * Accessibility: browsers expose `<sub>` with the `subscript` role. Most screen
 * readers do not announce a subscript; text that depends on it says so in words
 * or in a visually hidden span.
 * The default tag is `sub`. It can be overridden at runtime via the
 * {@see setTagName TagName} property (e.g. from a theme or template), which is
 * provided by the {@see THtmlElement} base class.
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TSub extends THtmlElement
{
	/**
	 * @return string the default tag name
	 */
	public function getDefaultTagName()
	{
		return 'sub';
	}
}
