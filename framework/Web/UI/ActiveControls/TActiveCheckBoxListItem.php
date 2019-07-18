<?php
/**
 * TActiveCheckBoxList class file.
 *
 * @author Wei Zhuo <weizhuo[at]gamil[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\ActiveControls
 */

namespace Prado\Web\UI\ActiveControls;

use Prado\Web\UI\WebControls\TCheckBox;

/**
 * TActiveCheckBoxListItem class.
 *
 * @author Wei Zhuo <weizhuo[at]gamil[dot]com>
 * @package Prado\Web\UI\ActiveControls
 */

class TActiveCheckBoxListItem extends TActiveCheckBox
{
	/**
	 * Override client implementation to avoid emitting the javascript
	 *
	 * @param THtmlWriter $writer the writer for the rendering purpose
	 * @param string $clientID checkbox id
	 * @param string $onclick onclick js
	 */
	protected function renderInputTag($writer, $clientID, $onclick)
	{
		TCheckBox::renderInputTag($writer, $clientID, $onclick);
	}
}
