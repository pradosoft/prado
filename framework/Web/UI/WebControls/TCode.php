<?php

/**
 * TCode class file
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TCode class
 * TCode represents the HTML `<code>` element, a fragment of computer code:
 * an element name, a file name, a program statement. A block of code is a
 * TCode inside a {@see TPre}.
 * Accessibility: browsers expose `<code>` with the `code` role. Screen readers
 * read it as ordinary text.
 * The default tag is `code`. It can be overridden at runtime via the
 * {@see setTagName TagName} property (e.g. from a theme or template), which is
 * provided by the {@see THtmlElement} base class.
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TCode extends THtmlElement
{
	/**
	 * @return string the default tag name
	 */
	public function getDefaultTagName()
	{
		return 'code';
	}
}
