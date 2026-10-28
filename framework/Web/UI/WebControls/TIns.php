<?php

/**
 * TIns class file
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TIns class
 * TIns represents the HTML `<ins>` element, text added to the document.
 * {@see TBaseEdit} documents `Cite`, `DateTime`, and accessibility.
 * The default tag is `ins`. It can be overridden at runtime via the
 * {@see setTagName TagName} property (e.g. from a theme or template), which is
 * provided by the {@see THtmlElement} base class.
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TIns extends TBaseEdit
{
	/**
	 * @return string the default tag name
	 */
	public function getDefaultTagName()
	{
		return 'ins';
	}
}
