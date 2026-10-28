<?php

/**
 * TBdi class file
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TBdi class
 * TBdi represents the HTML `<bdi>` element, text isolated from the direction of
 * the text around it. It holds text of unknown direction, such as a user name
 * inserted into a sentence, so that right-to-left text does not reorder the
 * punctuation and numbers beside it. A TBdi without `Dir` takes its direction
 * from its own text.
 * ```html
 * <li><com:TBdi><%# $this->Data['user'] %></com:TBdi>: 3 posts</li>
 * ```
 * Accessibility: `<bdi>` has no ARIA role. Screen readers read the text in its
 * logical order; the isolation changes only the display. A TBdi holding text in
 * another language also sets `Lang`.
 * The default tag is `bdi`. It can be overridden at runtime via the
 * {@see setTagName TagName} property (e.g. from a theme or template), which is
 * provided by the {@see THtmlElement} base class.
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TBdi extends THtmlElement
{
	/**
	 * @return string the default tag name
	 */
	public function getDefaultTagName()
	{
		return 'bdi';
	}
}
