<?php

/**
 * TDel class file
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TDel class
 * TDel represents the HTML `<del>` element, text removed from the document.
 * {@see TBaseEdit} documents `Cite`, `DateTime`, and accessibility.
 * The default tag is `del`. It can be overridden at runtime via the
 * {@see setTagName TagName} property (e.g. from a theme or template), which is
 * provided by the {@see THtmlElement} base class.
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TDel extends TBaseEdit
{
	/**
	 * @return string the default tag name
	 */
	public function getDefaultTagName()
	{
		return 'del';
	}
}
