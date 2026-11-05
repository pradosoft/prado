<?php

/**
 * TDescriptionTerm class file
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TDescriptionTerm class
 * TDescriptionTerm represents the HTML `<dt>` element, the name of a group in a
 * {@see TDescriptionList}. A {@see TDescriptionDetails} with a `Term` renders
 * a one-term group without a TDescriptionTerm.
 * Accessibility: browsers expose `<dt>` with the `term` role.
 * The default tag is `dt`. It can be overridden at runtime via the
 * {@see setTagName TagName} property (e.g. from a theme or template), which is
 * provided by the {@see THtmlElement} base class.
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TDescriptionTerm extends THtmlElement
{
	/**
	 * @return string the default tag name
	 */
	public function getDefaultTagName()
	{
		return 'dt';
	}
}
