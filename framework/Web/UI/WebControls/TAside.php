<?php

/**
 * TAside class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TAside class
 *
 * TAside represents the HTML5 `<aside>` element. The `<aside>` element represents a
 * section of a page that consists of content that is tangentially related to the
 * surrounding content, and which could be considered separate from that content.
 *
 * The default tag is `aside`. It can be overridden at runtime via the
 * {@see setTagName TagName} property (e.g. from a theme or template), which is
 * provided by the {@see THtmlElement} base class.
 * This is also useful for the {@see \Prado\Web\UI\WebControls\TWebControlDecorator}
 * (used by themes).
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
class TAside extends THtmlElement
{
	/**
	 * @return string the default tag name
	 */
	public function getDefaultTagName()
	{
		return 'aside';
	}
}
