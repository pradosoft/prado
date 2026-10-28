<?php

/**
 * TRubyParenthesis class file
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TRubyParenthesis class
 * TRubyParenthesis represents the HTML `<rp>` element, a parenthesis around a
 * {@see TRubyText}. A browser that renders ruby hides it; otherwise the annotation
 * shows in parentheses after the base text, and copied text keeps them.
 * Accessibility: a browser that renders ruby hides the parentheses from screen
 * readers as well.
 * The default tag is `rp`. It can be overridden at runtime via the
 * {@see setTagName TagName} property (e.g. from a theme or template), which is
 * provided by the {@see THtmlElement} base class.
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TRubyParenthesis extends THtmlElement
{
	/**
	 * @return string the default tag name
	 */
	public function getDefaultTagName()
	{
		return 'rp';
	}
}
