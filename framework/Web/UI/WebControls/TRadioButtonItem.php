<?php
/**
 * TRadioButtonList class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @copyright Copyright &copy; 2005-2016 The PRADO Group
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

class TRadioButtonItem extends TRadioButton
{
	/**
	 * Override client implementation to avoid emitting the javascript
	 * @param mixed $writer
	 */
	protected function renderClientControlScript($writer)
	{
	}
}
