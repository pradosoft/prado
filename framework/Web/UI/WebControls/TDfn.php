<?php

/**
 * TDfn class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TDfn class
 *
 * TDfn represents the HTML5 `<dfn>` element. The `<dfn>` element marks the defining
 * instance of a term, inside the paragraph, list item, or section that defines it.
 * A `title` holds only the term being defined, when it differs from the text.
 *
 * TDfn renders its term and marks the first occurrence of each of its
 * {@see setTerms Terms} in its target ({@see TBaseTerm}). The default
 * {@see setMarkLimit MarkLimit} is 1, since a term has one defining instance. A
 * `<dfn>` of a term in the target is its defining instance, so the term of a TDfn
 * inside its target is not marked again.
 * ```html
 * <com:TPanel>
 *   <p>A <com:TDfn Text="render filter" /> changes the output of a control.</p>
 *   <com:TDfn Terms="placeholder" />
 *   <p>A placeholder comment holds exempt markup.</p>
 * </com:TPanel>
 * ```
 *
 * Accessibility: browsers expose `<dfn>` with the `term` role. Set an
 * {@see setID ID} so other text can link to the definition.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TDfn extends TBaseTerm
{
	/**
	 * @return string the default tag name, 'dfn'
	 */
	public function getDefaultTagName()
	{
		return 'dfn';
	}

	/**
	 * @return int the default MarkLimit, 1
	 */
	protected function getDefaultMarkLimit(): int
	{
		return 1;
	}
}
