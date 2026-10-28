<?php

/**
 * TSamp class file
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TSamp class
 * TSamp represents the HTML `<samp>` element, sample or quoted output from a
 * program or computing system.
 * Accessibility: `<samp>` has no ARIA role. Screen readers read it as ordinary
 * text.
 * The default tag is `samp`. It can be overridden at runtime via the
 * {@see setTagName TagName} property (e.g. from a theme or template), which is
 * provided by the {@see THtmlElement} base class.
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TSamp extends THtmlElement
{
	/**
	 * @return string the default tag name
	 */
	public function getDefaultTagName()
	{
		return 'samp';
	}
}
