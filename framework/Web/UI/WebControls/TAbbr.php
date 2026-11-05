<?php

/**
 * TAbbr class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TAbbr class
 *
 * TAbbr represents the HTML5 `<abbr>` element. The `<abbr>` element represents an
 * abbreviation or acronym; its `title` attribute holds the expansion.
 *
 * TAbbr renders its abbreviation and marks the other occurrences of it, and of its
 * {@see setTerms Terms}, in its target ({@see TBaseTerm}). Each mark has the `title`
 * of its abbreviation.
 * ```html
 * <com:TPanel>
 *   <p><com:TAbbr Text="HTML" Title="HyperText Markup Language" /> pages use HTML and CSS.</p>
 *   <com:TAbbr Terms="CSS => Cascading Style Sheets" MarkLimit="1" />
 * </com:TPanel>
 * ```
 *
 * Accessibility: browsers expose the `title` of an `<abbr>` as its name, and most
 * screen readers do not announce it by default. A pointer shows it only on hover,
 * so keyboard and touch users do not see it. Write the expansion in the text at
 * the first use of an abbreviation; the `title` is a supplement.
 * {@see setMarkLimit MarkLimit} `1` marks the first occurrence only.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TAbbr extends TBaseTerm
{
	/**
	 * @return string the default tag name, 'abbr'
	 */
	public function getDefaultTagName()
	{
		return 'abbr';
	}
}
