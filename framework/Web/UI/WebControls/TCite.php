<?php

/**
 * TCite class file
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TCite class
 * TCite represents the HTML `<cite>` element, the title of a creative work: a
 * book, a paper, a song, a film, a web page. It is not for the name of a person.
 * A {@see TQuote} or {@see TBlockQuote} gives the address of its source in `Cite`.
 * Accessibility: `<cite>` has no ARIA role. Browsers render it in italics, and
 * screen readers read it as ordinary text.
 * The default tag is `cite`. It can be overridden at runtime via the
 * {@see setTagName TagName} property (e.g. from a theme or template), which is
 * provided by the {@see THtmlElement} base class.
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TCite extends THtmlElement
{
	/**
	 * @return string the default tag name
	 */
	public function getDefaultTagName()
	{
		return 'cite';
	}
}
