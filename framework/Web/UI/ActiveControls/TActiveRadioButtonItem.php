<?php
/**
 * TActiveRadioButtonList class file.
 *
 * @author Wei Zhuo <weizhuo[at]gamil[dot]com>
 * @link https://github.com/pradosoft/prado
 * @copyright Copyright &copy; 2005-2016 The PRADO Group
 * @license https://github.com/pradosoft/prado/blob/master/COPYRIGHT
 * @package Prado\Web\UI\ActiveControls
 */

namespace Prado\Web\UI\ActiveControls;

use Prado\Web\UI\WebControls\TRadioButton;

class TActiveRadioButtonItem extends TActiveRadioButton
{
	/**
	 * Override client implementation to avoid emitting the javascript
	 *
	 * @param THtmlWriter the writer for the rendering purpose
	 * @param string checkbox id
	 * @param string onclick js
	 */
	protected function renderInputTag($writer,$clientID,$onclick)
	{
		TRadioButton::renderInputTag($writer,$clientID,$onclick);
	}
}