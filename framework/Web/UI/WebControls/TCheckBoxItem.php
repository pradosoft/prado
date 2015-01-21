<?php
/**
 * TCheckBoxList class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @copyright Copyright &copy; 2005-2016 The PRADO Group
 * @license https://github.com/pradosoft/prado/blob/master/COPYRIGHT
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

class TCheckBoxItem extends TCheckBox {
	/**
	 * Override client implementation to avoid emitting the javascript
	 */
	protected function renderClientControlScript($writer)
	{
	}
}