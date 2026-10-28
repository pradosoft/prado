<?php

/**
 * TDescriptionList class file
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TDescriptionList class
 * TDescriptionList represents the HTML `<dl>` element, a list of name-value
 * groups: terms and their definitions, metadata keys and values, questions and
 * answers. Each group is one or more {@see TDescriptionTerm} followed by one or
 * more {@see TDescriptionDetails}; a TDescriptionDetails with a `Term` renders its
 * own term.
 * ```html
 * <com:TDescriptionList>
 *     <com:TDescriptionDetails Term="Author">Brad Anderson</com:TDescriptionDetails>
 *     <com:TDescriptionTerm>Editor</com:TDescriptionTerm>
 *     <com:TDescriptionTerm>Reviewer</com:TDescriptionTerm>
 *     <com:TDescriptionDetails>Qiang Xue</com:TDescriptionDetails>
 * </com:TDescriptionList>
 * ```
 * A `<div>` may wrap each group, as for styling.
 * Accessibility: browsers expose the terms with the `term` role and the details
 * with the `definition` role. Screen readers announce a list and read each term
 * before its details. It is not for indenting text or for dialogue.
 * The default tag is `dl`. It can be overridden at runtime via the
 * {@see setTagName TagName} property (e.g. from a theme or template), which is
 * provided by the {@see THtmlElement} base class.
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TDescriptionList extends THtmlElement
{
	/**
	 * @return string the default tag name
	 */
	public function getDefaultTagName()
	{
		return 'dl';
	}
}
