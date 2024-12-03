<?php

/**
 * TCheckBoxList class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

/**
 * TCheckBoxItem class
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 */
class TCheckBoxItem extends TCheckBox
{
	/**
	 * Override client implementation to avoid emitting the javascript
	 * @param mixed $writer
	 */
	protected function renderClientControlScript($writer)
	{
	}
}
