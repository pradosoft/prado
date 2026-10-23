<?php

/**
 * TVar class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TVar class
 *
 * TVar represents the HTML5 `<var>` element. The `<var>` element represents a
 * variable in a mathematical expression or a programming context, or a placeholder
 * an author or reader replaces. Browsers render it in italic.
 *
 * TVar renders its variable and marks the other occurrences of it, and of its
 * {@see setTerms Terms}, in its target ({@see TBaseTerm}). Variable names are
 * often single letters, so a small target, such as the TPanel of an expression,
 * confines the marks.
 * ```html
 * <com:TPanel>
 *   <p>The area is <com:TVar Text="w" Terms="h" /> times h.</p>
 * </com:TPanel>
 * ```
 *
 * Accessibility: browsers expose no role for `<var>`, and screen readers read it
 * as plain text. The italic is the only cue, so state the meaning of a variable
 * in the text.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TVar extends TBaseTerm
{
	/**
	 * @return string the default tag name, 'var'
	 */
	public function getDefaultTagName()
	{
		return 'var';
	}
}
